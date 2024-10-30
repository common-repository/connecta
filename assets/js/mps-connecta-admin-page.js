(function iifeJquery($) {
  "use strict";

  $(document).ready(function domReady() {
    function noOrders() {
      return `
                <div class="alert alert-info">
                    <div class="alert-heading p-3">
                    <b>No orders synced yet</b>
                    </div>
                    <div class="px-3 pb-3">
                    Orders will appear here, and on your WooCommerce Orders page, as they are received.
                    </div>
                </div>
            `;
    }

    function updating() {
      return `
            <div class="alert alert-primary">
                <div class="alert-heading p-2 d-flex justify-content-between align-items-center">
                    <div class="spinner-border text-primary" role="status">
                    </div>
                </div>
            </div>
            `;
    }

    function successStatus(status) {
      let lastSyncColour = status == "success" ? "success" : "danger";
      let lastSyncFmt = status == "success" ? "Success" : "Failed";
      return `
                <div class="mps-item mt-5">
                    <div class="mps-row pad">
                        <h5 class="mps-item-title">Sync Status</h5>
                        <div class='d-flex justify-content-center'>
                            <div class="mps-item-badge ${lastSyncColour}"><b>${lastSyncFmt}</b></div> 
                        </div>  
                    </div>
                </div>
            `;
    }

    function lastSyncDetails(lastSync, lastSyncTime, lastSyncStatus) {
      let lastSyncColour = lastSyncStatus == "success" ? "success" : "danger";
      let html = `
                <div class="mps-item">
                    <div class="mps-row pad">
                        <h5 class="mps-item-title">Last Sync</h5>
                        <div class='d-flex justify-content-center'>
                            <div class="mps-item-badge ${lastSyncColour}"><b>${lastSyncTime}</b></div> 
                        </div>  
                    </div>
            `;

      if (lastSyncStatus == "success") {
        html += `
                    <div class="mps-row">
                        <table class='table m-3 text-center'>
                            <thead class='table-light'>
                                <tr>
                                    <th>New</th>
                                    <th>Updated</th>
                                    <th>Ignored</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>${lastSync.orders_created}</td>
                                    <td>${lastSync.orders_updated}</td>
                                    <td>${lastSync.orders_skipped}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                `;

        if (lastSync.orders_created) {
          html +=
            `
                        <div class="mps-row">
                            <table class='table m-3 text-left'>
                            <thead class=''>
                                <tr>
                                    <th>New Orders (eBay ID)</th>
                                </tr>
                            </thead>
                            <tbody> ` +
            lastSync.orders_created_details
              .map(order => {
                return `
                                        <tr>
                                            <td><a href='${order.url}'>${order.orderId}</a></td>
                                        </tr>
                                    `;
              })
              .join("");
          +`</tbody>
                            </table>
                        </div>
                    `;
        }

        if (lastSync.orders_updated) {
          html +=
            `
                        <div class="mps-row">
                            <table class='table m-3 text-left'>
                            <thead class=''>
                                <tr>
                                    <th>Orders Updated (eBay ID)</th>
                                </tr>
                            </thead>
                            <tbody> ` +
            Object.keys(lastSync.orders_updated_details)
              .map(orderId => {
                let order = lastSync.orders_updated_details[orderId];

                let ret = `
                                        <tr>
                                            <td class='table-info'><a href='${order.url}'>${orderId}</a></td>
                                        </tr>
                                    `;

                ret += order.updates
                  .map(update => {
                    return `
                                            <tr>
                                                <td class='pl-3'>
                                                    ${
                                                      update ==
                                                      "fulfillment_status"
                                                        ? "Fulfillment status changed."
                                                        : "Payment status changed."
                                                    } 
                                                </td>
                                            </tr>
                                        `;
                  })
                  .join("");

                return ret;
              })
              .join("") +
            `</tbody>
                            </table>
                        </div>
                    `;
        }
      }

      html += "</div>";
      return html;
    }

    window.updateFrontend = function() {
      // submit form data
      $.ajax({
        url: document.location.origin + "/wp-admin/admin-ajax.php",
        type: "post",
        data: {
          action: "mps_connecta_update_frontend"
        }
      }).done(function(response) {
        if (response) {
          $("main#mps-ebay-main").empty();
          if (response.last_sync == "none") {
            $("main#mps-ebay-main").append(noOrders());
          } else {
            $("main#mps-ebay-main").empty();
            $("main#mps-ebay-main").append(updating());
            setTimeout(function() {
              $("main#mps-ebay-main").empty();
              $("main#mps-ebay-main").append(
                successStatus(response.last_sync_status)
              );
              $("main#mps-ebay-main").append(
                lastSyncDetails(
                  response.last_sync,
                  response.last_sync_time,
                  response.last_sync_status
                )
              );
            }, 1000);
          }
        }
      });
    };

    window.isTutorial = () => {
      return $("input[name=istut]") != undefined;
    };

    if (window.connectaInit) {
      setInterval(window.updateFrontend, 30 * 1000); // every 30 seconds
      window.updateFrontend();
    }

    $("#mps_ebay_save_verif_code_btn").click(function saveVerificationCode() {
      let code = $("#mps_ebay_verif_code").val();
      let nonce = $("#mps_ebay_verif_code_nonce").val();
      if (!code || !nonce) {
        swal(
          "Oh No!",
          "Something went wrong! Please refresh the page and try again.",
          "error"
        );
        return;
      }
      $("#mps_ebay_save_verif_code_btn").empty();
      $("#mps_ebay_save_verif_code_btn").html(`
                Save
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <span class="sr-only">Saving...</span>
            `);
      $.ajax({
        url: document.location.origin + "/wp-admin/admin-ajax.php",
        type: "post",
        data: {
          action: "mps_connecta_save_verif_code",
          verification_code: code,
          _wpnonce: nonce,
          istut: window.isTutorial
        }
      })
        .done(function(response) {
          if (response.success === false) {
            swal(
              "Oops!",
              "There was an error with your request. Please try again.",
              "error"
            );
          } else {
            swal("Saved", "Verification code saved.", "success");
          }
        })
        .fail(function() {
          swal(
            "Oops!",
            "There was an error with your request. Please try again.",
            "error"
          );
        })
        .always(function() {
          $("#mps_ebay_save_verif_code_btn").empty();
          $("#mps_ebay_save_verif_code_btn").html("Save");
        });
    });

    $("#mps_ebay_save_key_btn").click(function saveKey() {
      let key = $("#mps_ebay_key").val();
      let nonce = $("#mps_ebay_key_nonce").val();
      if (!key || !nonce) {
        swal(
          "Oh No!",
          "Something went wrong! Please refresh the page and try again.",
          "error"
        );
        return;
      }
      $("#mps_ebay_save_key_btn").empty();
      $("#mps_ebay_save_key_btn").html(`
                Save
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <span class="sr-only">Saving...</span>
            `);
      $.ajax({
        url: document.location.origin + "/wp-admin/admin-ajax.php",
        type: "post",
        data: {
          action: "mps_connecta_save_key",
          api_key: key,
          _wpnonce: nonce,
          istut: true
        }
      })
        .done(function(response) {
          if (response.success === false) {
            swal(
              "Oops!",
              "There was an error with your request. Please try again.",
              "error"
            );
          } else {
            swal("Saved", "Key saved.", "success");
          }
        })
        .fail(function() {
          swal(
            "Oops!",
            "There was an error with your request. Please try again.",
            "error"
          );
        })
        .always(function() {
          $("#mps_ebay_save_key_btn").empty();
          $("#mps_ebay_save_key_btn").html("Save");
        });
    });
  });
})(jQuery);
