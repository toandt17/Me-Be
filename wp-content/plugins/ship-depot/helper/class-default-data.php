<?php
defined('ABSPATH') || exit;

use Automattic\WooCommerce\Utilities\OrderUtil;

if (!class_exists('Ship_Depot_Default_Data')) {
    class Ship_Depot_Default_Data
    {
        public static function DefaultAutoCreateShip()
        {
            $is_auto = get_option('sd_auto_create_shipping');
            if (!$is_auto || $is_auto == '') {
                update_option('sd_auto_create_shipping', 'yes');
            }

            $status_auto = get_option('sd_status_auto_create_shipping');
            if (!$status_auto || $status_auto == '') {
                update_option('sd_status_auto_create_shipping', 'wc-processing');
            }
        }
    }
}
