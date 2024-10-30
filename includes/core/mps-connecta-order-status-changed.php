<?php

defined('ABSPATH') or exit();

/**
 * Actions to perform when an order status changes
 */
class MPS_Connecta_Order_Status_Changed
{
    public function __construct()
    {   
        // attatch to order complete hook 
        add_action('woocommerce_order_status_changed', array($this, 'mps_connecta_order_status_changed'), 10, 3);
    }

    /**
     * Actions to perform when an order status changes
     * 
     * - when the order status changes to completed, notify ebay by setting
     *   the ebay fulfillment status to fulfilled
     * @param Integer $order_id
     * @param String $old_status
     * @param String $new_status
     * @return void
     */
    public function mps_connecta_order_status_changed( $order_id, $old_status, $new_status ){
        // ignore non ebay orders
        if( !MPS_Ebay_Order::is_ebay_order($order_id) ){
            return;
        }
        $order = new MPS_Ebay_Order($order_id);
        if( $new_status == 'completed' ){
            $createdFulfillment = $order->ebay_set_fulfilled();
            $order->add_order_note( $createdFulfillment ? 
                'Connecta: Fulfilled the order!' : 
                'Connecta: Something went wrong when trying to fulfill the order.' 
            );
        }
    }
}

return new MPS_Connecta_Order_Status_Changed();