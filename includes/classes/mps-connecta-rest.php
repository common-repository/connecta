<?php

defined('ABSPATH') or exit();
/*
|--------------------------------------------------------------------------
| MPS Ebay REST
|--------------------------------------------------------------------------
|
| This class defines webhooks for the Connecta application to send
| date to. 
|
| The Connecta server will sign every request with a private key. 
| Auth middleware is defined for every route which checks for and 
| verifies this signature ('MPS-SIGNATURE' header) against the public key. 
|
| Defined route: /wp-json/mpsebay/v1
|
 */
class MPS_Ebay_REST
{
    /**
     * Register endpoints
     */
    public function __construct()
    {
        add_action('rest_api_init', function () {

            // validate plugin is active
            register_rest_route('connecta/v1', '/validate', array(
                'methods' => 'GET',
                'callback' => array('MPS_Ebay_REST', 'validate'),
            ));

            // syncOrders
            register_rest_route('connecta/v1', '/syncOrders', array(
                'methods' => 'POST',
                'callback' => array('MPS_Ebay_REST', 'syncOrders'),
            ));
        });

        // add auth middleware
        add_filter( 'rest_pre_dispatch', array('MPS_Ebay_REST', 'authenticate'), 0, 3 );
    }

    /*
    |--------------------------------------------------------------------------
    | Auth Middleware
    |--------------------------------------------------------------------------
    */

    /**
     * Verify the signature
     *
     * @param String $signature
     * @return Boolean
     */
    private static function verify($signature, $payload)
    {
        $file = 'file://' . plugin_dir_path(__FILE__) . '../../mps/mps-ebay-public.pem';
        $pub = openssl_get_publickey($file);
        $result = openssl_verify($payload, base64_decode($signature), $pub, "sha256WithRSAEncryption");
        openssl_free_key($pub);
        return $result;
    }

    /**
     * Authenticate the inbound request, by ensuring
     * it was signed by the MPS eBay server
     * @param mixed $result
     * @param WP_REST_Server $server
     * @param WP_REST_Request $request - the inbound request
     */
    public function authenticate($result, WP_REST_Server $server, WP_REST_Request $request)
    {
				if( !in_array($request->get_route(), ['/connecta/v1/validate', '/connecta/v1/syncOrders'])){
					return;
				}

				$error = new WP_Error(
						'Connecta.notAuthorized',
						'Connecta: You are not authorized to perform this action.',
						array('status' => 401)
				);

				if (!$request->get_header('mps-signature')) {
						return $error;
				}

				$signature = $request->get_header('mps-signature');
				$payload = $request->get_body();

				if (!self::verify($signature, $payload)) {
						return $error;
				}
    }

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */

    /**
     * GET mpsebay/v1/validate
     * Validates that the provided woocommerce shop
     * has this plugin enabled, and the user has entered
     * the verification code
     *
     * @return void
     */
    public function validate()
    {
        wp_send_json(array(
            "active" => true,
            "code" => get_option("mps_ebay_verification_code"),
        ));
        exit();
    }

    /**
     * GET mpsebay/v1/syncOrders
     * This endpoint receives orders sent via our
     * server, and keeps our database up to date
     *
     * @return void
     */
    public function syncOrders(WP_REST_Request $request)
    {

        // decode as an array of objects
        $orders = json_decode($request->get_body(), false);

        if (!is_array($orders)) {
            // sync failed.
            update_option("mps_ebay_last_sync", "failed");
            update_option("mps_ebay_last_sync_status", $request->get_body());
            update_option('mps_ebay_is_syncing', "update_ready");
            return false;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $orders_created_details = array();
        $orders_updated_details = array();
        $orders_skipped_details = array();

        foreach ($orders as $order) {
            try {
                MPS_Ebay_Order::create_order_from_ebay_order($order);
                $wc_id = MPS_Ebay_Order::get_wc_order_id_from_ebay_id($order->orderId);
                $orders_created_details[] = array(
                    'orderId' => $order->orderId,
                    'wcId' => $wc_id,
                    'url' => add_query_arg(array(
                        'post' => $wc_id,
                        'action' => 'edit',
                    ), site_url('/wp-admin/post.php')),
                );
                $created += 1;
            } catch (ConnectaOrderAlreadyExists $e) {
                $update_result = MPS_Ebay_Order::update_order($order);
                if ($update_result->success) {
                    $orders_updated_details[$order->orderId] = $update_result;
                    $updated += 1;
                } else {
                    $orders_skipped_details[$order->orderId] = [
                        "reason" => "AttemptedUpdate",
                        "description" => "No changes to update",
                    ];
                    $skipped += 1;
                }
            } catch (Exception $e) {
                $skipped += 1;
                $orders_skipped_details[$order->orderId] = [
                    "reason" => get_class($e),
                    "description" => $e->getMessage(),
                ];
            }
        }

        update_option("mps_ebay_last_sync_status", "success");

        $timestamp = time();
        $sync_result = array(
            'orders_created' => $created,
            'orders_updated' => $updated,
            'orders_skipped' => $skipped,
            'orders_created_details' => $orders_created_details,
            'orders_updated_details' => $orders_updated_details,
            'orders_skipped_details' => $orders_skipped_details,
            'timestamp' => $timestamp,
            'time' => date('Y-m-d H:i:s', $timestamp),
        );

        update_option("mps_ebay_last_sync", json_encode($sync_result));
        update_option('mps_ebay_is_syncing', ($orders_created > 0 || $orders_updated > 0) ? "update_ready" : "false");

        wp_send_json($sync_result);
        exit();
    }
}

return new MPS_Ebay_REST();
