<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Shipping_Helper')) {
    class Ship_Depot_Shipping_Helper
    {
        public static function update_cancel_shipping_info(WC_Order $order, string $tracking_number, string $cancel_reason, bool $update_status = true)
        {
            Ship_Depot_Logger::wrlog('[Ship_Depot_Shipping_Helper][update_cancel_shipping_info]');
            Ship_Depot_Logger::wrlog('[Ship_Depot_Shipping_Helper][update_cancel_shipping_info] order id: ' . $order->get_id());
            Ship_Depot_Logger::wrlog('[Ship_Depot_Shipping_Helper][update_cancel_shipping_info] tracking_number: ' . $tracking_number);
            Ship_Depot_Logger::wrlog('[Ship_Depot_Shipping_Helper][update_cancel_shipping_info] cancel_reason: ' . $cancel_reason);
            Ship_Depot_Helper::UpdateOrderMetadata($order->get_id(), 'sd_ship_info', '');
            $order->add_order_note($cancel_reason);
            if ($update_status) {
                $order->update_status('on-hold', __('Cập nhật trạng thái đơn hàng sang "Tạm giữ" sau khi hủy vận đơn.', 'ship-depot-translate'));
            }
        }
    }
}
