<?php

defined('ABSPATH') or exit();

/**
 * Register core actions
 */
class MPS_Ebay_Core
{
    public function __construct()
    {   
        // things to do when an order status changed in woocommerce
        require_once 'mps-connecta-order-status-changed.php';  
    }
}

return new MPS_Ebay_Core();
