<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Custom_Order_Fields')) {
    class Ship_Depot_Custom_Order_Fields
    {
        function __construct()
        {
            // Gõ bỏ các fields không sử dụng.
            add_filter('woocommerce_order_formatted_billing_address', array($this, 'sd_woocommerce_order_formatted_billing_address'), 999, 2);
            add_filter('woocommerce_order_formatted_shipping_address', array($this, 'sd_woocommerce_order_formatted_shipping_address'), 999, 2);
            //Thêm custom fields data dùng để hiển thị ở order detail shipping/billing address
            add_filter('woocommerce_formatted_address_replacements', array($this, 'sd_woocommerce_formatted_address_replacements'), 999, 2);
            //Chỉnh format đối với quốc gia muốn custom để thêm custom data và dùng data cung cấp ở filter woocommerce_formatted_address_replacements để hiển thị ở order detail shipping/billing address
            add_filter('woocommerce_localisation_address_formats', array($this, 'sd_woocommerce_localisation_address_formats'));

            // Sửa link google maps
            //add_filter('woocommerce_shipping_address_map_url_parts', array($this, 'fsw_woocommerce_shipping_address_map_url_parts'), 10, 1);
        }

        function sd_woocommerce_order_formatted_billing_address($raw_address, $order)
        {
            $order_id = $order->get_id();
            //Ship_Depot_Logger::wrlog('[sd_woocommerce_order_formatted_billing_address] raw_address: ' . print_r($raw_address, true));
            ////Ship_Depot_Logger::wrlog('[sd_woocommerce_order_formatted_billing_address] order: ' . print_r($order, true));
            $option_ct =  Ship_Depot_Address_Helper::get_all_province_key_value();
            if (isset($raw_address['city']) && !Ship_Depot_Helper::check_null_or_empty($raw_address['city']) && array_key_exists($raw_address['city'], $option_ct)) {
                $raw_address['city'] = $option_ct[$raw_address['city']];
            }

            $option_dt =  Ship_Depot_Address_Helper::get_all_district_key_value($order->get_billing_city());
            $raw_address['district'] = '';
            if (array_key_exists(get_post_meta($order_id, '_billing_district', true), $option_dt)) {
                $raw_address['district'] = $option_dt[get_post_meta($order_id, '_billing_district', true)];
            }


            $option_wd = Ship_Depot_Address_Helper::get_all_wards_key_value($order->get_billing_city(), get_post_meta($order_id, '_billing_district', true));
            $raw_address['ward'] = '';
            if (array_key_exists(get_post_meta($order_id, '_billing_ward', true), $option_wd)) {
                $raw_address['ward'] = $option_wd[get_post_meta($order_id, '_billing_ward', true)];
            }
            //Ship_Depot_Logger::wrlog('[sd_woocommerce_order_formatted_billing_address] raw_address aft: ' . print_r($raw_address, true));
            return $raw_address;
        }

        function sd_woocommerce_order_formatted_shipping_address($raw_address, $order)
        {
            $order_id = $order->get_id();
            Ship_Depot_Logger::wrlog('[sd_woocommerce_order_formatted_shipping_address] raw_address: ' . print_r($raw_address, true));
            $option_ct =  Ship_Depot_Address_Helper::get_all_province_key_value();
            if (isset($raw_address['city']) && !Ship_Depot_Helper::check_null_or_empty($raw_address['city']) && array_key_exists($raw_address['city'], $option_ct)) {
                $raw_address['city'] = $option_ct[$raw_address['city']];
            }
            Ship_Depot_Logger::wrlog('[sd_woocommerce_order_formatted_shipping_address] order: ' . print_r($order, true));
            $option_dt =  Ship_Depot_Address_Helper::get_all_district_key_value($order->get_shipping_city());
            $raw_address['district'] = '';
            if (array_key_exists(get_post_meta($order_id, '_shipping_district', true), $option_dt)) {
                $raw_address['district'] = $option_dt[get_post_meta($order_id, '_shipping_district', true)];
            }

            $option_wd = Ship_Depot_Address_Helper::get_all_wards_key_value($order->get_shipping_city(), get_post_meta($order_id, '_shipping_district', true));
            $raw_address['ward'] = '';
            if (array_key_exists(get_post_meta($order_id, '_shipping_ward', true), $option_wd)) {
                $raw_address['ward'] = $option_wd[get_post_meta($order_id, '_shipping_ward', true)];
            }
            Ship_Depot_Logger::wrlog('[sd_woocommerce_order_formatted_shipping_address] raw_address aft: ' . print_r($raw_address, true));
            return $raw_address;
        }

        function sd_woocommerce_formatted_address_replacements($list_repl, $raw_address)
        {
            //Ship_Depot_Logger::wrlog('[sd_woocommerce_formatted_address_replacements] raw_address: ' . print_r($raw_address, true));
            //Ship_Depot_Logger::wrlog('[sd_woocommerce_formatted_address_replacements] list_repl: ' . print_r($list_repl, true));
            $list_repl['{district}'] = isset($raw_address['district']) ? $raw_address['district'] : '';
            $list_repl['{ward}'] = isset($raw_address['ward']) ? $raw_address['ward'] : '';
            //Ship_Depot_Logger::wrlog('[sd_woocommerce_formatted_address_replacements] list_repl aft: ' . print_r($list_repl, true));
            return $list_repl;
        }


        function sd_woocommerce_localisation_address_formats($list_address_format)
        {
            $list_address_format['VN'] = "{name}\n{address_1}\n{ward},{district}\n{city}";
            return $list_address_format;
        }
    }

    new Ship_Depot_Custom_Order_Fields();
}
