<?php

defined('ABSPATH') or exit();


/****************************************/
//              Front end               //
/****************************************/


/**
 * Error message to display (in the order-items section
 * of the order details page) if the ebay order
 * has invalid or missing SKUs
 *
 * @param Integer $order_id : wc order id
 * @return void
 */
function mps_ebay_invalid_order_items($order_id){
    if(!MPS_Ebay_Order::has_sku_errors($order_id)){
        return;
    }

    $order  = new MPS_Ebay_Order($order_id);
    $errors = json_decode($order->get_sku_errors(),false);
    ?>
    <tr class="item" style="background: #eba3a3; color:#7b201f;">
        <td colspan='6'>
            <div class="dashicons dashicons-warning"></div> <h4>Missing SKUs!</h4>
            <i>The following items have either missing SKUs, or SKU doesn't match.</i>
        </td>
    </tr>
    <?php
    foreach( $errors as $error ): 
    ?>

        <tr class="item" style="background: #eba3a3; color:#7b201f;">
            <td class="thumb">
                <div class="dashicons dashicons-warning"></div>	
            </td>
            <td class="name">
                <?php echo $error->title; ?>
            </td>
            <td class="item_cost" width="1%" data-sort-value="35.00">
                
            </td>
            <td class="quantity" width="1%">
                <div class="view">
                    <small class="times" style="color:#7b201f;">×</small>  <?php echo $error->quantity; ?>	
                </div>
            </td>
            <td class="line_cost" width="1%" data-sort-value="35.00">
                
            </td>
            <td></td>
        </tr>
    <?php
    endforeach;
}
add_action('woocommerce_admin_order_items_after_line_items', 'mps_ebay_invalid_order_items', 10, 1);

/**
 * Add 'eBay' badge on the orders list page
 *
 * @param String $column
 * @param Integer $post_id
 * @return void
 */
function mps_ebay_modify_woocommerce_order_status_column( $column, $post_id ) {
    if(!MPS_Ebay_Order::is_ebay_order($post_id)){
        return;
    }
    if( $column != 'order_status' ){
        return;
    }
    printf("
        <style>
            mark.mt-ebay-orders-list-badge::after{ 
                font-family: 'dashicons';
                content: '\\f174';
                font-size: 1rem;
                margin: 0px;
                padding: 0px 10px 0px 0px;
            }
            mark.mt-ebay-orders-list-badge span{ 
                margin-right: 5px; 
            }
        </style>
        <mark class='order-status status-completed mt-ebay-orders-list-badge'>
            <span>eBay</span>
        </mark>
    ");
}
add_action( 'manage_shop_order_posts_custom_column', 'mps_ebay_modify_woocommerce_order_status_column', 10, 2 );

/**
 * Helper function for admin-main
 *
 * @param DateTime $datetime
 * @param boolean $full
 * @return String
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
  }