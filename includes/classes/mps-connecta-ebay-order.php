<?php

defined('ABSPATH') or exit();

use GuzzleHttp\Client;

/*
|--------------------------------------------------------------------------
| MPS Ebay Order
|--------------------------------------------------------------------------
|
| This class represents a WooCommerce order which has been
| placed on eBay and synced using Connecta.
|
 */
class MPS_Ebay_Order extends WC_Order
{
    /**
     * @var MPS_Ebay_User
     */
    private $__mps_ebay_user;

    /**
     * eBay Order ID
     * @var String
     */
    private $__order_ebay_id;

    /**
     * Raw ebay order received from eBay API
     * @var Object
     */
    private $__order;

    /**
     * @param Integer $order_id
     */
    public function __construct($order_id)
    {
        if (!MPS_Ebay_Order::is_ebay_order($order_id)) {
            throw new ConnectaNotAnEbayOrder();
        }

        parent::__construct($order_id);

        // fetch user
        $this->__mps_ebay_user = new MPS_Ebay_User($this->get_user_id());

        // fetch order meta data
        $this->__order_ebay_id = get_post_meta($order_id, 'mps_ebay_ebay_order_id', true);
        $this->__order = json_decode(get_post_meta($order_id, 'mps_ebay_data', true), false);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
     */

    /**
     * Find the user that placed the ebay order, and if not found,
     * create a new one.
     *
     * @param Object $order the raw ebay order
     * @return MPS_Ebay_User
     */
    public static function create_user($order)
    {
        $username = $order->buyer->username;
        $email = $order->fulfillmentStartInstructions[0]->shippingStep->shipTo->email;
        $phone = $order->fulfillmentStartInstructions[0]->shippingStep->shipTo->primaryPhone->phone;

        if (!is_email($email)) {
            // ignore orders which have their email masked
            // > ebay does this to orders which were placed
            //   more than 14 days ago
            throw new ConnectaUserEmailMissing();
        }

        // create user (or fetch existing user)
        return MPS_Ebay_User::find_or_create_user($username, $email, $phone);
    }

    /**
     * Given an array with the required fields below, this method
     * attempts to create a WC Order, and also attempts to create
     * a new user, if the user doesn't already exist
     * @param Array data
     */
    public static function create_order_from_ebay_order($order)
    {

        // Order already exists?
        if (MPS_Ebay_Order::ebay_id_exists($order->orderId)) {
            throw new ConnectaOrderAlreadyExists();
        }

        // Create user
        $user = MPS_Ebay_Order::create_user($order);
        if (!$user) {
            throw new ConnectaFailedToCreateUser();
        }

        // Create new WC Order
        $ebay_order = MPS_Ebay_Order_Stub::createWooCommerceOrder($order, $user->get_id());

        return $ebay_order;
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
     */

    /**
     * Given an array with the required fields below, this method
     * attempts to take an existing ebay order, and update it with
     * the recently fetched order details from ebay
     * @param Array data
     * @return Boolean updated or not updated
     */
    public static function update_order($order)
    {

        // Order doesn't exist or isn't an ebay order?
        if (!MPS_Ebay_Order::ebay_id_exists($order->orderId)) {
            return false;
        }

        // get the old order
        $wc_order_id = self::get_wc_order_id_from_ebay_id($order->orderId);
        $ebay_order = new MPS_Ebay_Order($wc_order_id);

        $old_order = $ebay_order->get_order_data();
        $new_order = $order;

        $updates_made = false;
        $change_order_status = false;

        $changes_made = array();

        // make a copy to make changes to
        $updated_order = $old_order;

        // update fulfillment status
        if ($old_order->orderFulfillmentStatus != $new_order->orderFulfillmentStatus) {
            $updated_order->orderFulfillmentStatus = $new_order->orderFulfillmentStatus;
            $updates_made = true;
            $change_order_status = true;

            $changes_made[] = "fulfillment_status";
        }

        // update payment status
        if ($old_order->orderPaymentStatus != $new_order->orderPaymentStatus) {
            $updated_order->orderPaymentStatus = $new_order->orderPaymentStatus;
            $updates_made = true;
            $change_order_status = true;
            $ebay_order->add_order_note(sprintf('eBay payment status changed from %s to %s', $old_order->orderPaymentStatus, $new_order->orderPaymentStatus));

            $changes_made[] = "payment_status";
        }

        if ($change_order_status) {
            $ebay_order->update_status(self::calculate_order_status($updated_order->orderPaymentStatus, $updated_order->orderFulfillmentStatus));
            $ebay_order->add_order_note(sprintf('eBay fulfillment status changed to %s', $new_order->orderFulfillmentStatus));
        }

        // save the new data
        if ($updates_made) {
            $updated = $ebay_order->set_order_data($updated_order);
            return json_decode(json_encode(array(
                'success' => $updated,
                'updates' => $changes_made,
                'url' => add_query_arg(array(
                    'post' => $wc_order_id,
                    'action' => 'edit',
                ), site_url('/wp-admin/post.php')),
            )), false);
        }
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Setters
    |--------------------------------------------------------------------------
     */

    /**
     * Make an ordinary order, into an ebay order
     *
     * @param Integer $order_id
     * @param Integer $ebay_order_id
     * @return Boolean
     */
    public static function make_ebay_order($order_id, $ebay_order_id)
    {
        return update_post_meta($order_id, 'mps_ebay_ebay_order_id', $ebay_order_id);
    }

    /**
     * Undocumented function
     *
     * @param [type] $new_data
     * @return void
     */
    private function set_order_data($new_data)
    {
        $updated = update_post_meta($this->get_id(), 'mps_ebay_data', json_encode($new_data));
        if ($updated) {
            $this->__order = $new_data;
        }
        return $updated;
    }

    /**
     * Completes the order (marks every item as fulfilled)
     * If the order is already fulfilled, this method has no effect
     */
    public function ebay_set_fulfilled()
    {
        try {
            $client = new Client(['base_uri' => 'http://mipromotionalsourcing.com']);

            // hit validate endpoint, to check whether the plugin is installed
            $response = $client->request('POST', '/api/createFulfillment', [
                'headers' => [
                    'accept' => 'application/json',
                ],
                'query' => [
                    'api_token' => get_option('mps_ebay_api_key') ?? 'none',
                    'ebay_order_id' => $this->get_ebay_order_id() ?? '0',
                ],
                'http_errors' => false,
            ]);

            // response failed
            if ($response->getStatusCode() !== 201) {
                return false;
            }

        } catch (\Exception $e) {
            // something went wrong: probably the shop url
            // isnt valid
            return false;
        }
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
     */

    /**
     * Checks whether the provided ebay order id already exists
     * @param String $ebay_order_id
     * @return Boolean
     */
    public static function ebay_id_exists($ebay_order_id)
    {
        $results = self::get_wc_order_from_ebay_id($ebay_order_id);
        return count($results) > 0;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function get_order_data()
    {
        $order = $this->__order;
        return $order;
    }

    /**
     * Undocumented function
     *
     * @param [type] $ebay_order_id
     * @return void
     */
    public static function get_wc_order_from_ebay_id($ebay_order_id)
    {
        global $wpdb;
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT post_id
                            FROM wp_postmeta
                            WHERE meta_key = 'mps_ebay_ebay_order_id'
                            AND meta_value = '%s'",
                sanitize_text_field($ebay_order_id))
        );
        return $results;
    }

    /**
     * Undocumented function
     *
     * @param [type] $ebay_id
     * @return void
     */
    public static function get_wc_order_id_from_ebay_id($ebay_order_id)
    {
        $results = self::get_wc_order_from_ebay_id($ebay_order_id);
        if (count($results) == 0) {
            return false;
        }
        return $results[0]->post_id;
    }

    /**
     * Check whether the order import failed due to missing
     * or invalid SKUs
     *
     * @param Integer $order_id
     * @return Boolean
     */
    public static function has_sku_errors($order_id)
    {
        return metadata_exists('post', $order_id, 'mt_invalid_skus');
    }

    /**
     * Gets the array of line items (and skus) which
     * caused the order import to fail
     *
     * Returns empty array if none found
     *
     * @param Integer $order_id
     * @return Array
     */
    public function get_sku_errors()
    {
        return get_post_meta($this->get_id(), 'mt_invalid_skus', true);
    }

    /**
     * Gets the Ebay user attatched to this order
     * @return String
     */
    public function get_ebay_user()
    {
        return $this->__mps_ebay_user;
    }

    /**
     * Gets the ebay order id
     * @return String
     */
    public function get_ebay_order_id()
    {
        return $this->__order_ebay_id;
    }

    /**
     * Gets the order fulfillment status
     *
     * @return String {NOT_STARTED, IN_PROGRESS, FULFILLED}
     */
    public function get_fulfillment_status()
    {
        return $this->__order->orderFulfillmentStatus;
    }

    /**
     * Gets the formatted order fulfillment status
     *
     * @return String
     */
    public function get_fulfillment_status_formatted()
    {
        switch ($this->get_fulfillment_status()) {
            case "FULFILLED":return "Fulfilled";
            case "IN_PROGRESS":return "In Progress";
            case "NOT_STARTED":return "Not Started";
        }
    }

    /**
     * Gets the payment status
     *
     * @return String {FAILED, FULLY_REFUNDED, PAID, PARTIALLY_REFUNDED, PENDING}
     * @see https://developer.ebay.com/api-docs/sell/fulfillment/types/sel:OrderPaymentStatusEnum
     *
     */
    public function get_payment_status()
    {
        return $this->__order->orderPaymentStatus;
    }

    /**
     * Gets the formatted order payment status
     *
     * @return String
     */
    public function get_payment_status_formatted()
    {
        switch ($this->get_payment_status()) {
            case "PARTIALLY_REFUNDED":return "Partially Refunded";
            case "FULLY_REFUNDED":return "Fully Refunded";
            case "PENDING":return "Pending";
            case "FAILED":return "Failed";
            case "PAID":return "Paid";
        }
    }

    /**
     * Gets the array of line items
     *
     * @return Array of eBay LineItem
     * @see https://developer.ebay.com/api-docs/sell/fulfillment/types/sel:LineItem
     *
     */
    public function get_ebay_line_items()
    {
        return $this->__order->lineItems;
    }

    /**
     * Gets the raw status of the order
     * @return void
     */
    public function get_order_status()
    {
        return $this->get_status();
    }

    /**
     * Gets the status of the order
     * @return void
     */
    public function get_order_status_name()
    {
        return wc_get_order_status_name($this->get_status());
    }

    /**
     * Gets the order total returned by ebay
     * @return String
     */
    public function get_order_total()
    {
        return $this->__order->pricingSummary->total;
    }

    /**
     * Gets created at
     * @return String
     */
    public function get_order_created_at()
    {
        return $this->__order->creationDate;
    }

    /**
     * Check the eBay meta fields are set, to determine
     * if provided order id originated from ebay
     *
     * Just checks mps_ebay_ebay_order_id exists for now
     *
     * @param Integer $order_id
     * @return Boolean
     */
    public static function is_ebay_order($order_id)
    {
        return metadata_exists('post', $order_id, 'mps_ebay_ebay_order_id');
    }

    /**
     * Given the ebay payment and fulfillment statuses, calculate
     * what the relevant woocommerce status should be.
     *
     * @param String $ebayStatus
     * @param String $fulfillmentStatus
     * @return String wc order status
     */
    public static function calculate_order_status($ebayStatus, $fulfillmentStatus)
    {
        if ($ebayStatus == 'PAID' && $fulfillmentStatus == 'FULFILLED') {
            return 'completed';
        }

        switch ($ebayStatus) {
            case 'PAID':return 'processing';
            case 'FAILED':return 'failed';
            case 'PENDING':return 'pending';
            case 'FULLY_REFUNDED':return 'refunded';
            case 'PARTIALLY_REFUNDED':return 'refunded';
            default:return 'pending';
        }
    }

    /**
     * Gets all SKUs
     * @return Array string
     */
    public static function get_all_skus()
    {
				global $wpdb;
				return array_map(function ($sku) {
					return $sku->meta_value;
				}, $wpdb->get_results(
            "SELECT DISTINCT(meta_value) FROM wp_postmeta WHERE meta_key = '_sku'"
				));
    }
}
