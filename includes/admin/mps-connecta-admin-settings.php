<?php

defined('ABSPATH') or exit();

// Check that the user has the required permissions
if (!current_user_can('administrator')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

?>

<div id="mps-ebay-admin-main-container">
  <div class="container">
    <div class='container-fluid d-flex justify-content-center'>
      <img src="<?php echo  plugin_dir_url( __FILE__ ) . '../../assets/img/primary.svg'?>" width="64px" height="64px" class="text-center mb-3">
    </div>
    <h1 class="mb-5 text-center">Settings</h1>
    <main id='mps-ebay-main'>


			<div class="alert alert-primary">
				<div class="alert-body p-4">
				Create your free account at <a href="https://connecta.mipromotionalsourcing.com" target="_blank">connecta.mipromotionalsourcing.com</a> to connect your accounts and generate your keys.
				</div>
      </div>
      
			<div class="mps-item mt-5">
          <div class="mps-row pad py-4">
              <h5 class="mps-item-title">Enter Verification Code</h5>
          </div>
          <div class="mps-row px-4">
            <div class="form-group" style="width: 100%">
              <input type='hidden' id='mps_ebay_verif_code_nonce' value="<?php echo wp_create_nonce('mps_connecta_save_verif_code');?>">
              <input type="text" id="mps_ebay_verif_code" class="form-control" placeholder="48 Digit Code" aria-describedby="helpId" value="<?php echo sanitize_text_field(get_option('mps_ebay_verification_code') ?? "") ?>">
            </div>
          </div>
          <div class="mps-row px-4 mb-3 text-center justify-content-center">
              <button id="mps_ebay_save_verif_code_btn" class="btn btn-primary btn-lg text-light">Save</button>
          </div>
      </div>

      <div class="mps-item mt-5">
          <div class="mps-row pad py-4">
              <h5 class="mps-item-title">Enter Key</h5>
          </div>
          <div class="mps-row px-4">
            <div class="form-group" style="width: 100%">
              <textarea id="mps_ebay_key" class="form-control" placeholder="512 Digit Key" aria-describedby="helpId"><?php echo sanitize_text_field(get_option('mps_ebay_api_key') ?? "") ?></textarea>
              <input type='hidden' id='mps_ebay_key_nonce' value="<?php echo wp_create_nonce('mps_connecta_save_key'); ?>">
              <small id="helpId" class="text-muted">Login to Connecta to access your key.</small>
            </div>
          </div>
          <div class="mps-row px-4 mb-3 text-center justify-content-center">
              <button id="mps_ebay_save_key_btn" class="btn btn-primary btn-lg text-light">Save</button>
          </div>
      </div>

    </main>
  </div>
</div>
