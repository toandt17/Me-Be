<?php
// ADDING 2 NEW COLUMNS WITH THEIR TITLES (keeping "Total" and "Actions" columns at the end)
if (Ship_Depot_Helper::IsHPOS()) {
    add_filter('manage_woocommerce_page_wc-orders_columns', 'custom_shop_order_column', 20);
} else {
    add_filter('manage_edit-shop_order_columns', 'custom_shop_order_column', 20);
}


function custom_shop_order_column($columns)
{
    $reordered_columns = array();
    // Inserting columns to a specific location
    foreach ($columns as $key => $column) {
        $reordered_columns[$key] = $column;
        if ($key ==  'order_status') {
            // Inserting after "Status" column
            $reordered_columns['sd-shipping-info'] = __('Thông tin vận đơn', 'ship-depot-translate');
            $reordered_columns['sd-shipping-fee'] = __('Phí vận đơn', 'ship-depot-translate');
            $reordered_columns['sd-shipping-status'] = __('Trạng thái vận đơn', 'ship-depot-translate');
        }
    }
    return $reordered_columns;
}

// Adding custom fields meta data for each new column (example)
if (Ship_Depot_Helper::IsHPOS()) {
    add_action('manage_woocommerce_page_wc-orders_custom_column', 'custom_orders_list_column_content', 10, 2);
} else {
    add_action('manage_shop_order_posts_custom_column', 'custom_orders_list_column_content', 20, 2);
}

use Automattic\WooCommerce\Utilities\OrderUtil;

function custom_orders_list_column_content($column, $post_id_or_order)
{
    Ship_Depot_Logger::wrlog('[custom_orders_list_column_content] column: ' . print_r($column, true));
    $order = is_numeric($post_id_or_order) ? wc_get_order($post_id_or_order) : $post_id_or_order;
    switch ($column) {
        case 'sd-shipping-info':
            // Get custom post meta data
            $str_ship_info = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_ship_info', true);
            if (!Ship_Depot_Helper::check_null_or_empty($str_ship_info)) {
                $ship_info = json_decode($str_ship_info);
                $tracking_number = isset($ship_info->TrackingNumber) ? $ship_info->TrackingNumber : '';
                //
                $str_list_couriers = get_option('sd_list_couriers');
                if (!Ship_Depot_Helper::check_null_or_empty($str_list_couriers)) {
                    $listCouriers = json_decode($str_list_couriers);
                    foreach ($listCouriers as $courier_obj) {
                        $courier = new Ship_Depot_Courier($courier_obj);
                        if ($courier->CourierID == Ship_Depot_Helper::GetOrderMetadata($order, 'sd_selected_courier', true)) {
                            $selected_courier = $courier;
                            Ship_Depot_Logger::wrlog('[custom_orders_list_column_content] selected_courier: ' . print_r($selected_courier, true));
                        }
                    }
                }
?>
                <div>
                    <p><?php echo isset($selected_courier) ? esc_html($selected_courier->CourierName) : ''; ?></p>
                    <b><?php echo esc_html($tracking_number) ?></b>
                </div>

<?php
            } else {
            }
            break;

        case 'sd-shipping-fee':
            // Get custom post meta data
            // Ship_Depot_Logger::wrlog('[custom_orders_list_column_content] post_id_or_order: ' . print_r($post_id_or_order, true));
            // Ship_Depot_Logger::wrlog('[custom_orders_list_column_content] order: ' . print_r($order, true));
            $json_shipping = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_selected_shipping', true);
            Ship_Depot_Logger::wrlog('[custom_orders_list_column_content] json_shipping: ' . $json_shipping);
            if (!Ship_Depot_Helper::check_null_or_empty($json_shipping)) {
                $selected_shipping = new Ship_Depot_Shipping_Fee_Response(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($json_shipping));
                echo '<p>' . esc_html(Ship_Depot_Helper::currency_format($selected_shipping->ShopMarkupShippingFee->IsActive ? $selected_shipping->ShopMarkupShippingFee->ShippingFeeTotal : $selected_shipping->ShipDepotMarkupShippingFee->ShippingFeeTotal)) . '</p>';
            }
            break;
        case 'sd-shipping-status':
            // Get custom post meta data
            $str_ship_info = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_ship_info', true);
            if (!Ship_Depot_Helper::check_null_or_empty($str_ship_info)) {
                $ship_info = json_decode($str_ship_info);
                $shipping_status = isset($ship_info->ShipStatus) ? $ship_info->ShipStatus : '';
                echo '<mark class="order-status"><span>' . esc_html($shipping_status) . '</span></mark>';
            }
            break;
    }
}

//Modify width of sd-shipping-status column
add_action('admin_enqueue_scripts', 'wc_product_list_css_overrides', 998);

function wc_product_list_css_overrides()
{
    wp_add_inline_style('woocommerce_admin_styles', "table.wp-list-table .column-sd-shipping-status{ width: 16%; }");
}
