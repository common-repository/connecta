<?php
defined('ABSPATH') or exit();

/*
|--------------------------------------------------------------------------
| MPS Ebay Order Stub
|--------------------------------------------------------------------------
|
| This class is responsible for taking the raw order
| returned from eBay, and turning it into a WooCommerce order.
|
 */
class MPS_Ebay_Order_Stub
{
    protected $order;

    /**
     * @param StdClass $order - the raw object returned from the ebay api
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Create a new WC Order using the raw ebay order
     *
     * @param StdClass $rawOrder
     * @param Integer  $user_id
     * @return void
     */
    public static function createWooCommerceOrder($rawOrder, $user_id)
    {

        $stub = new MPS_Ebay_Order_Stub($rawOrder);

        // create new wc order
        $wc_order = wc_create_order(array(
            'customer_id' => $user_id,
            'status' => MPS_Ebay_Order::calculate_order_status($stub->order->orderPaymentStatus, $stub->order->orderFulfillmentStatus),
            'customer_note' => $order->buyerCheckoutNotes ?? null,
        ));

        if (is_wp_error($wc_order)) {
            throw new ConnectaFailedToCreateOrder(json_encode($wc_order->get_error_messages()));
        }

        // ebay order id
        MPS_Ebay_Order::make_ebay_order($wc_order->get_id(), $stub->order->orderId);

        if (!MPS_Ebay_Order::is_ebay_order($wc_order->get_id())) {
            throw new ConnectaFailedToCreateOrder();
        }

        // set currency
        $wc_order->set_currency($stub->order->pricingSummary->total->currency);

        // ebay data dump
        update_post_meta($wc_order->get_id(), 'mps_ebay_data', json_encode($stub->order));

        // date created
        $date = new DateTime($stub->order->creationDate ?? "now");
        update_option("mps_ebay_created", $date->getTimestamp());
        $wc_order->set_date_created($date->getTimestamp());

        // shipping address (only for orders that are not digital/pickup )
        if (!in_array($stub->order->fulfillmentStartInstructions[0]->fulfillmentInstructionsType, ['DIGITAL', 'PREPARE_FOR_PICKUP'])) {
            $wc_order->set_address($stub->getShippingAddress(), 'shipping');
        }

        // add products to order
        $line_items = $stub->getLineItems();

        // some line items may not be defined in the woocommerce store,
        // so we make a note of them, to display to the user
        if (!empty($line_items['filteredLineItems'])) {
            update_post_meta($wc_order->get_id(), 'mt_invalid_skus', json_encode($line_items['filteredLineItems']));
        }

        // lets now add all of the valid order items
        foreach ($line_items['validLineItems'] as $item) {

            // add the item
            $product_id = wc_get_product_id_by_sku($item->sku);
            $item_id = $wc_order->add_product(
                new WC_Product_Variation(intval($product_id)),
                $item->quantity,
                array(
                    'subtotal' => $item->lineItemCost->value,
                    'total' => $item->total->value,
                )
            );

            // set the item totals
            $wc_item = new WC_Order_Item_Product($item_id);
            $wc_item->set_subtotal($item->lineItemCost->value);

            // add shipping costs for this item, if shipping costs
            // are specified
            if ($item->deliveryCost->shippingCost) {
                $wc_shipping = new WC_Order_Item_Shipping();
                $wc_shipping->set_props(array(
                    'method_title' => sprintf('Shipping for %s x %s', $item->sku, $item->quantity),
                    'method_id' => 'ebay_shipping',
                    'total' => wc_format_decimal($item->deliveryCost->shippingCost->value),
                    'taxes' => 0, // todo
                ));
                $wc_order->add_item($wc_shipping);
            }
        }

        // set overall totals

        // calculate totals to calculate shipping totals, because that isn't given to us by ebay :(
        $wc_order->calculate_totals();

        // override the overall total, using the ebay provided order total
        $wc_order->set_total(wc_format_decimal($stub->order->pricingSummary->total->value));

        // process any refunds (important to do this AFTER setting the order totals)
        $stub->processRefunds($wc_order);

        $wc_order->save();

        return new MPS_Ebay_Order($wc_order->get_id());
    }

    /**
     * Retuns an object with the extracted
     * shipping details, ready to insert into
     * a woocommerce order
     *
     * @return void
     */
    public function getShippingAddress()
    {
        $full = $this->order->fulfillmentStartInstructions[0];
        return json_decode(json_encode(array(
            'first_name' => explode(' ', $this->order->fullName, 1)[0] ?? null,
            'last_name' => explode(' ', $this->order->fullName, 1)[1] ?? null,
            'company' => $full->shippingStep->shipTo->companyName ?? null,
            'email' => $full->shippingStep->shipTo->email ?? null,
            'phone' => $full->shippingStep->shipTo->primaryPhone->phoneNumber ?? null,
            'address_1' => $full->shippingStep->shipTo->contactAddress->addressLine1 ?? null,
            'address_2' => $full->shippingStep->shipTo->contactAddress->addressLine2 ?? null,
            'city' => $full->shippingStep->shipTo->contactAddress->city ?? null,
            'state' => $full->shippingStep->shipTo->contactAddress->stateOrProvince ?? null,
            'postcode' => $full->shippingStep->shipTo->contactAddress->postalCode ?? null,
            'country' => $full->shippingStep->shipTo->contactAddress->countryCode ?? null,
        )), false);
    }

    /**
     * Extract line items from the ebay order. If a line item does not
     * have an sku assigned, or the sku is not found in this WC shop,
     * then remove it, and set some metadata to display error messages
     * to the user.
     *
     * @return void
     */
    public function getLineItems()
    {
        // filter out invalid SKUs
				$filtered_items = array();
				
				$order_items = array_filter($this->order->lineItems, function ($line_item) use (&$filtered_items) {

					// For now, just ignore this line item (dont add it to the order),
					// and attatch some meta data to the order which will flag it to the admin
					$valid_sku = isset($line_item->sku) && in_array($line_item->sku, MPS_Ebay_Order::get_all_skus());

					if (!$valid_sku) {
							if (!isset($line_item->sku) || empty($line_item->sku)) {
									// sku is missing
									$filtered_items[] = [
											"title" => $line_item->title,
											"quantity" => $line_item->quantity,
											"sku" => "MISSING",
									];
							} else {
									// sku is invalid
									$filtered_items[] = [
											"title" => $line_item->title,
											"quantity" => $line_item->quantity,
											"sku" => $line_item->sku,
									];
							}
					}

					return $valid_sku;
				});
			
        return array(
            "validLineItems" => $order_items,
            "filteredLineItems" => $filtered_items,
        );
    }

    /**
     * TODO
     * Add Refunds to an order
     *
     * @param WC_Order $order
     * @return void
     */
    public function processRefunds($wc_order)
    {
        update_post_meta($wc_order->get_id(), 'mps_ebay', "0");
        foreach ($this->order->paymentSummary->refunds as $refund) {

            update_post_meta($wc_order->get_id(), 'mps_ebay', "1");

            // refund date
            $date = new DateTime($refund->refundDate ?? "now");

            $wc_refund = wc_create_refund(array(
                'amount' => floatval($refund->amount->value),
                'reason' => 'Refunded via eBay',
                'order_id' => $wc_order->get_id(),
                'line_items' => array(
                    'qty' => $item->quantity,
                    'refund_total' => floatval($refund->amount->value),
                    'refund_tax' => 0,
                ),
                'date_created' => $date->getTimestamp(),
                'refund_payment' => false, // super important! we are processing already refunded items
                'restock_items' => false,
            ));

            update_option("mps_ebay_created", $date->getTimestamp());
            $wc_order->set_date_created($date->getTimestamp());

            // if refund was a success, add some meta so that we
            // know this originated from ebay
            if (!is_wp_error($wc_refund)) {
                update_post_meta($wc_order->get_id(), 'mps_ebay_refund', "yay");
                $wc_refund->update_meta_data('mps_ebay_refund', true);
            } else {
                update_post_meta($wc_order->get_id(), 'mps_ebay_refund',
                    json_encode(array(
                        $wc_refund,
                        wc_format_decimal($refund->amount->value),
                        $wc_order->get_total(),
                        $wc_order->get_remaining_refund_amount(),
                        WC_Order_Data_Store_CPT::get_total_refunded($wc_order),
                    )
                    )
                );
            }
        }
    }
}
