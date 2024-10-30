<?php

defined('ABSPATH') or exit();

// Check that the user has the required permissions
if ( !current_user_can( 'administrator' ) )  {
    wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
}

$tutorial = get_option('mps_connecta_tutorial_stage');
?>

<div id="mps-ebay-admin-main-container">
  <div class="container">
    <div class='container-fluid d-flex justify-content-center'>
      <img src="<?php echo plugin_dir_url( __FILE__ ) . '../../assets/img/primary.svg'?>" width="64px" height="64px" class="text-center mb-3">
    </div>
    <h1 class="mb-5 text-center">Connecta</h1>
    <main id='mps-ebay-main'>

<?php if( $tutorial === 'complete' ): ?>

      <div class="alert alert-primary">
          <div class="alert-heading p-2 d-flex justify-content-between align-items-center">
              <div class="spinner-border text-primary" role="status">
              </div>
          </div>
      </div>

      <script>
      window.connectaInit = true;
      </script>

<?php elseif( $tutorial === '0' ): ?>

      <input type='hidden' name='istut' value='true'>

      <div class="alert alert-primary">
        <div class="alert-heading p-3">
          <b>Hello! Thank you for choosing Connecta! 🎉</b>
        </div>
        <div class="px-3 pb-3">
        To get started, click the login button below to setup Connecta. Once you have signed up or logged in, please enter the 48 digit verification code below.
        </div>
      </div>

      <div class="mps-item mt-5">
          <div class="mps-row pad">
              <h5 class="mps-item-title">Login to Connecta</h5>
              <div class='d-flex justify-content-center p-3'>
                  <a href='https://connecta.mipromotionalsourcing.com/register' class="btn btn-primary text-light" target="_blank"><b>Login</b></a> 
              </div>  
          </div>
      </div>

      <div class="mps-item mt-5">
          <div class="mps-row pad py-4">
              <h5 class="mps-item-title">Enter Verification Code</h5>
          </div>
          <div class="mps-row px-4">
            <div class="form-group" style="width: 100%">
              <input type='hidden' id='mps_ebay_verif_code_nonce' value="<?php echo wp_create_nonce( 'mps_connecta_save_verif_code' ); ?>" >
              <input type="text" id="mps_ebay_verif_code" class="form-control" placeholder="48 Digit Code" aria-describedby="helpId">
              <div class="alert alert-info mt-2">
                <small id="helpId">
                  <i class="fas fa-question-circle mr-2"></i>
                  Login to Connecta to access your 48 digit verification code.</small>
              </div>
            </div>
          </div>
          <div class="mps-row px-4 mb-3 text-center justify-content-center">
              <button id="mps_ebay_save_verif_code_btn" class="btn btn-primary btn-lg text-light">Save</button>
          </div>
      </div>

      <div class="mt-5">
          <a href="javascript:window.location.reload(true)" class="btn btn-dark btn-lg btn-block">
            Next <i class="fas fa-arrow-circle-right ml-1"></i>
          </a>
      </div>

<?php elseif( $tutorial === '1' ): ?>

      <input type='hidden' name='istut' value='true'>

      <div class="alert alert-primary">
        <div class="alert-heading p-3">
          <b>Your store has been successfully connected to Connecta! 🎉</b>
        </div>
        <div class="px-3 pb-3">
        Please now enter the 512 digit key into the box below.
        </div>
      </div>

      <div class="mps-item mt-5">
          <div class="mps-row pad py-4">
              <h5 class="mps-item-title">Enter Key</h5>
          </div>
          <div class="mps-row px-4">
            <div class="form-group" style="width: 100%">
              <textarea id="mps_ebay_key" class="form-control" placeholder="512 Digit Key" aria-describedby="helpId"></textarea>
              <input type='hidden' id='mps_ebay_key_nonce' value="<?php echo wp_create_nonce( 'mps_connecta_save_key' ); ?>"" >
              <small id="helpId" class="text-muted">Login to Connecta to access your key.</small>
            </div>
          </div>
          <div class="mps-row px-4 mb-3 text-center justify-content-center">
              <button id="mps_ebay_save_key_btn" class="btn btn-primary btn-lg text-light">Save</button>
          </div>
      </div>

      <div class="mt-5">
          <a href="javascript:window.location.reload(true)" class="btn btn-dark btn-lg btn-block">
            Next <i class="fas fa-arrow-circle-right ml-1"></i>
          </a>
      </div>

<?php endif; ?>

    </main>
  </div>
</div>