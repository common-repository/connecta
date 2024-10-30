<?php

defined('ABSPATH') or exit();

/*
|--------------------------------------------------------------------------
| MPS Ebay Ajax
|--------------------------------------------------------------------------
|
| This class registers ajax handlers
|
 */
class MPS_Ebay_AJAX
{
    public function middleware( $func, $needsNonce = false )
    {
        if (current_user_can('administrator') === false) {
            wp_send_json(array(
                'success' => false,
                'error' => 'You are not allowed to call this endpoint.',
            ));
            exit();
        }

        if( $needsNonce ){
            if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], $func ) ) {
                wp_send_json(array(
                    'success' => false,
                    'error' => 'You are not allowed to call this endpoint.',
                ));
                exit();
            } 
        }
    }

    /**
     * Updates the frontend with the most recent sync
     */
    public function mps_connecta_update_frontend()
    {
        $this->middleware('mps_connecta_update_frontend', $needsNonce = false);

        // get last sync
        $last_sync_status = get_option('mps_ebay_last_sync_status');
        $last_sync        = json_decode(get_option('mps_ebay_last_sync'), false);

        // no sync yet recorded
        if (!$last_sync_status || !$last_sync) {
            wp_send_json(array(
                "last_sync" => "none",
            ));
            exit();
        }

        $last_sync_status_fmt = $last_sync_status == 'success' ? 'Success' : 'Failed';
        $last_sync_colour     = $last_sync_status == 'success' ? 'success' : 'danger';
        $last_sync_icon       = $last_sync_status == 'success' ? '' : '';

        $last_sync_time     = time_elapsed_string('@' . $last_sync->timestamp);
        $last_sync_time_fmt = sprintf('%s %s', ($last_sync_status == 'success') ? '' : 'Failed ', $last_sync_time);

        wp_send_json(array(
            "last_sync_status" => __( get_option('mps_ebay_last_sync_status'), 'connecta' ),
            "last_sync" => json_decode(get_option('mps_ebay_last_sync'), false),
            "last_sync_time" => $last_sync_time_fmt,
        ));
        exit();
    }

    /**
     * Tutorial Step 0
     * Updates the verification code entered by the user
     */
    public function mps_connecta_save_verif_code()
    {
        $this->middleware('mps_connecta_save_verif_code');

        $code = sanitize_text_field($_POST['verification_code']);
        update_option('mps_ebay_verification_code', $code);
        
        if( isset($_POST['istut']) && $_POST['istut'] == true ){
            update_option('mps_connecta_tutorial_stage', 1); // move to next tut step
        }

        wp_send_json(array(
            'success' => true,
        ));
        exit();
    }

    /**
     * Tutorial Step 1
     * Updates the api key entered by the user
     */
    public function mps_connecta_save_key()
    {
        $this->middleware('mps_connecta_save_key');

        $key = sanitize_text_field($_POST['api_key']);
        update_option('mps_ebay_api_key', $key);

        if( isset($_POST['istut']) && $_POST['istut'] == true ){
            update_option('mps_connecta_tutorial_stage', 'complete'); // tut complete
        }

        wp_send_json(array(
            'success' => true,
        ));
        exit();
    }
}
