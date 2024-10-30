<?php

defined('ABSPATH') or exit();

/**
 * Add custom ebay meta box to the woocommerce order details
 * page, if the order is an ebay order.
 */
class MPS_Connecta_Add_Meta_Box
{
    /**
     * Register the meta box
     */
    public function __construct()
    {   
        add_action( 'add_meta_boxes', function(){
            if( isset($_GET['post']) && MPS_Ebay_Order::is_ebay_order(sanitize_text_field($_GET['post'])) ){
                $order = new MPS_Ebay_Order(sanitize_text_field($_GET['post']));
                add_meta_box( 'mps_connecta_ebay_order_meta_box', 
                             '<img width="20px" height="20px" style="float:left; margin-right:5px;" src="' . plugin_dir_url( __FILE__ ) . '../../assets/img/primary.svg'.'"> eBay Order ', // title
                             array($this, 'output'), // render function
                             'shop_order', 
                             'normal', 
                             'high' );
            }
        });
        
    }

    /**
     * Output content to display in the meta-box
     *
     * @param WP_Post $post
     * @return void
     */
    public function output( $post ){
        if(!MPS_Ebay_Order::is_ebay_order($post->ID)){
            return;
        }
        
        // get the ebay order
        $order = new MPS_Ebay_Order($post->ID);
        ?>
        <div id="mt-ebay-meta-box-content">
            <?php
            $this->styles();
            $this->invalid_sku_error();
            $this->order_details($order);
            $this->fulfillment_instructions($order);
            ?> 
        </div>
        <?php
        $this->scripts();
    }

    /**
     * Outputs eBay order details to display in the meta-box
     *
     * @param MPS_Ebay_Order $order
     * @return void
     */
    private function order_details( $order ){
        ?>
        <div class='table-responsive'>
            <table class='table widefat striped'>
                <tbody>
                    <tr>
                        <td><b>Order ID</b></td>
                        <td><?php echo $order->get_ebay_order_id(); ?></td>
                    </tr>
                    <tr>
                        <td><b>Buyer</b></td>
                        <td><?php echo $order->get_order_data()->buyer->username; ?></td>
                    </tr>
                    <tr>
                        <td><b>Seller</b></td>
                        <td><?php echo $order->get_order_data()->sellerId; ?></td>
                    </tr>
                    <tr>
                        <?php 
                        $badge = 'status-pending';
                        $account_status = $order->get_ebay_user()->get_ebay_account_status();

                        switch( $account_status ){
                            case 'NEW':      $badge = 'status-processing'; break;
                            case 'EXISTING': $badge = 'status-completed';  break;
                        } 
                        ?>
                        <td><b>Customer Status</b></td>
                        <td>
                            <mark class="mt-ebay-user-status-badge order-status <?php echo $badge; ?> <?php echo $account_status; ?> tips">
                                <span><?php echo $order->get_ebay_user()->get_ebay_account_formatted_status(); ?></span>
                            </mark>
                        </td>
                    </tr>
                    <tr>
                        <td><b>Fulfillment</b></td>
                        <td>
                            <?php 
                            $badge = 'status-pending';
                            $fulfillment_status = $order->get_fulfillment_status();

                            switch( $fulfillment_status ){
                                case 'FULFILLED':   $badge = 'status-completed'; break;
                                case 'IN_PROGRESS': $badge = 'status-on-hold';   break;
                                case 'NOT_STARTED': $badge = 'status-pending';   break;
                            } 
                            ?>
                            <mark class="mt-ebay-fulfillment-badge order-status <?php echo $badge; ?> <?php echo $fulfillment_status; ?> tips">
                                <span><?php echo $order->get_fulfillment_status_formatted(); ?></span>
                            </mark>
                        </td>
                    </tr>
                    <tr>
                        <td><b>Payment</b></td>
                        <td>
                            <?php 
                            $badge = 'status-pending';
                            $icon = '';
                            $payment_status = $order->get_payment_status();

                            switch( $payment_status ){
                                case "PAID":               $badge = 'status-completed'; $icon = 'dashicons dashicons-yes-alt'; break; 
                                case "FAILED":             $badge = 'status-failed';    break;
                                case "PENDING":            $badge = 'status-pending';   break;
                                case "FULLY_REFUNDED":     $badge = 'status-on-hold';   break;
                                case "PARTIALLY_REFUNDED": $badge = 'status-on-hold';   $icon = 'dashicons dashicons-yes-alt'; break;
                            } 
                            ?>
                            <mark class="mt-ebay-payment-badge order-status <?php echo $badge; ?> <?php echo $payment_status; ?> tips">
                                <span><?php echo $order->get_payment_status_formatted(); ?></span>
                            </mark>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Outputs all fulfillment instructions attatched to 
     * the eBay order
     *
     * @param MPS_Ebay_Order $order
     * @return void
     */
    private function fulfillment_instructions( $order ){
        ?>
        <div class='table-responsive'>
            <table class='table widefat striped'>
                <thead>
                    <tr>
                        <td colspan='3' style="margin:5px 0px;">
                            Delivery Instructions
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach($order->get_order_data()->fulfillmentStartInstructions as $i => $instruction): 

                        /**
                         * Shipping details
                         */
                        if( $instruction->fulfillmentInstructionsType == "SHIP_TO" ):
                            $name  = $instruction->shippingStep->shipTo->fullName ?? null;
                            $phone = $instruction->shippingStep->shipTo->primaryPhone->phoneNumber ?? null;
                            $addr  = $instruction->shippingStep->shipTo->contactAddress ?? null;
                            $email = $instruction->shippingStep->shipTo->email ?? null;
                            $company = $instruction->shippingStep->shipTo->companyName ?? null;
                            ?>
                            <tr>
                                <td><b>Ship To</b></td>
                                <td>
                                    <?php
                                    echo $name     ? $name    . '<br>' : '';
                                    echo $phone    ? $phone   . '<br>' : '';
                                    echo $email    ? $email   . '<br>' : '';
                                    echo "<br>";
                                    echo $company  ? $company . '<br>' : '';
                                    echo isset($addr->addressLine1)    ? $addr->addressLine1    . "<br>" : '';
                                    echo isset($addr->addressLine2)    ? $addr->addressLine2    . "<br>" : '';
                                    echo isset($addr->city)            ? $addr->city            . "<br>" : '';
                                    echo isset($addr->stateOrProvince) ? $addr->stateOrProvince . "<br>" : '';
                                    echo isset($addr->postalCode)      ? $addr->postalCode      . "<br>" : '';
                                    echo isset($addr->countryCode)     ? $addr->countryCode     . "<br>" : '';
                                    ?>
                                </td>
                            </tr>
                            <?php
                        /**
                         * Digital delivery details
                         */
                        elseif( $instruction->fulfillmentInstructionsType == "DIGITAL" ):
                            $name  = $instruction->shippingStep->shipTo->fullName ?? null;
                            $phone = $instruction->shippingStep->shipTo->primaryPhone->phoneNumber ?? null;
                            $addr  = $instruction->shippingStep->shipTo->contactAddress ?? null;
                        ?>
                            <tr>
                                <td><b>Digital Delivery</b></td>
                                <td>
                                    <?php
                                    echo $name     ? $name       . '<br>' : '';
                                    echo $phone    ? $phone      . '<br>' : '';
                                    echo $email    ? $email      . '<br>' : '';
                                    ?>
                                </td>
                            </tr>
                        <?php 
                        endif; 
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Error message to output if the ebay order
     * has invalid or missing SKUs
     *
     * @return void
     */
    private function invalid_sku_error(){
        if( !MPS_Ebay_Order::has_sku_errors( $post->ID ) ){
            return;
        }
        $errors = json_decode( $order->get_sku_errors(), false );
        ?>
        <div class='table-responsive'>
            <table class='table widefat striped mt-ebay-order-error'>
                <thead>
                    <tr>
                        <td colspan='2'>
                            <span class="dashicons dashicons-warning"></span> Order Error!
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <colgroup>
                        <col width="100%" />
                        <col width="0%" />
                        <col width="0%" />
                    </colgroup>
                    <tr>
                        <td colspan='3'>
                        This order contained either missing, or invalid SKUs. In order to 
                        prevent this from re-occurring, make sure that all SKUs in your eBay 
                        shop match with a SKU in this WooCommerce shop.
                        </td>
                    </tr>
                    <tr >
                        <td colspan='3'>
                            <b>Affected Line Items:</b>
                        </td>
                    </tr>
                    <tr>
                    <td>Item Title</td>
                    <td>SKU</td>
                    <td>x</td>
                    </tr>
                    <?php foreach( $errors as $error ): ?>
                    <tr>
                        <td style="white-space: nowrap; text-overflow:ellipsis; overflow: hidden; max-width:1px;" title="<?php echo $error->title ?>">
                            <?php echo $error->title ?>
                        </td>
                        <td style="white-space: nowrap;" title=" <?php echo $error->sku ?>"><?php echo $error->sku ?></td>
                        <td style="white-space: nowrap;"><?php echo $error->quantity ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Scripts to output
     *
     * @return void
     */
    private function scripts(){
        ?>
        <script>
        $('.mt-ebay-fulfillment-badge.NOT_STARTED').tipTip({"content":"Packaging line items from the order has not yet begun."})
        $('.mt-ebay-fulfillment-badge.IN_PROGRESS').tipTip({"content":"Packaging and shipping line items from the order has begun, but not all line items have been shipped."})
        $('.mt-ebay-fulfillment-badge.FULFILLED').tipTip({"content":"The entire order has been shipped. 🎉"})
        $('.mt-ebay-payment-badge.PARTIALLY_REFUNDED').tipTip({"content":"A partial amount of the order has been refunded to the buyer."})
        $('.mt-ebay-payment-badge.FULLY_REFUNDED').tipTip({"content":"The full amount of the order has been refunded to the buyer."})
        $('.mt-ebay-payment-badge.PENDING').tipTip({"content":"Either pending buyer payment, or pending refund from the seller."})
        $('.mt-ebay-payment-badge.FAILED').tipTip({"content":"Buyer payment or refund has failed."})
        $('.mt-ebay-payment-badge.PAID').tipTip({"content":"This order is fully paid. 🎉"})
        $('.mt-ebay-user-status-badge.NEW').tipTip({"content":"This is a new customer! A new WooCoommerce customer has been created for this eBay buyer."})
        $('.mt-ebay-user-status-badge.EXISTING').tipTip({"content":"This buyer has placed orders previously."})
        </script>
        <?php
    }

    /**
     * Styles to output
     *
     * @return void
     */
    private function styles(){
        ?>
        <style>
        #mt-ebay-meta-box-content{
            display: flex;
            flex-wrap: wrap;
        }
        #mt-ebay-meta-box-content div.table-responsive{
            flex-grow: 1;
            margin: 6px 0px;
        }
        #mt-ebay-meta-box-content .order-status{
            cursor: pointer !important;
        }
        #mt-ebay-meta-box-content table.mt-ebay-order-error{
            background: #eba3a3;
            border: 2px solid #eba3a3;
            border-bottom: 3px solid #e69696;
            border-radius: 5px;
        }
        #mt-ebay-meta-box-content table.mt-ebay-order-error td, 
        #mt-ebay-meta-box-content table.mt-ebay-order-error li{
            background: #eba3a3;
            color: #761919 !important;
            border-bottom: 0px !important;
        }
        #mt-ebay-meta-box-content mark.mt-ebay-payment-badge::after, 
        #mt-ebay-meta-box-content mark.mt-ebay-fulfillment-badge::after{
            font-size: 1rem;
            margin: 0px;
            padding: 0px 10px 0px 0px;
            font-family: 'dashicons';
        }
        #mt-ebay-meta-box-content mark.mt-ebay-payment-badge.PAID span,
        #mt-ebay-meta-box-content mark.mt-ebay-payment-badge.FAILED span,
        #mt-ebay-meta-box-content mark.mt-ebay-payment-badge.PENDING span,
        #mt-ebay-meta-box-content mark.mt-ebay-fulfillment-badge.FULFILLED span,
        #mt-ebay-meta-box-content mark.mt-ebay-fulfillment-badge.IN_PROGRESS span,
        #mt-ebay-meta-box-content mark.mt-ebay-fulfillment-badge.NOT_STARTED span{ 
            margin-right: 5px; 
        }
        #mt-ebay-meta-box-content mark.mt-ebay-payment-badge.PAID::after{ content: "\f147"; }
        #mt-ebay-meta-box-content mark.mt-ebay-payment-badge.FAILED::after{ content: "\f335"; }
        #mt-ebay-meta-box-content mark.mt-ebay-payment-badge.PENDING::after{ content: "\f321"; }
        #mt-ebay-meta-box-content mark.mt-ebay-fulfillment-badge.FULFILLED::after{ content: "\f147"; }
        #mt-ebay-meta-box-content mark.mt-ebay-fulfillment-badge.IN_PROGRESS::after{ content: "\f113"; }
        #mt-ebay-meta-box-content mark.mt-ebay-fulfillment-badge.NOT_STARTED::after{ content: "\f534"; }
        </style>
        <?php
    }
}

return new MPS_Connecta_Add_Meta_Box();
