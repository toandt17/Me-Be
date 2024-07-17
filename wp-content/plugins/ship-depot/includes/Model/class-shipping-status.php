<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Shipping_Status')) {
    class Ship_Depot_Shipping_Status extends Ship_Depot_Base_Model
    {
        public string $ID = "";
        public string $Name = "";
        public string $Description = "";
        public bool $IsUsed = false;
        public string $WooOrderStatusID = "";

        function __construct($object = null)
        {
            parent::MapData($object, $this);
            Ship_Depot_Logger::wrlog('[Ship_Depot_Shipping_Status] this: ' . print_r($this, true));
        }

        public static function GetListStatusDefault(): array
        {
            $list_status = [];
            //
            $delivery_status = new Ship_Depot_Shipping_Status();
            $delivery_status->ID = esc_html(SD_DELIVERING_STATUS);
            $delivery_status->Name = esc_html__('Đang giao hàng', 'ship-depot-translate');
            $delivery_status->Description = esc_html__('Tuỳ chọn bên dưới sẽ giúp cập nhật trạng thái đơn hàng tự động khi vận đơn đang trên đường giao đến khách hàng.', 'ship-depot-translate');
            $delivery_status->IsUsed = false;
            array_push($list_status, $delivery_status);
            //
            $delivery_success_status = new Ship_Depot_Shipping_Status();
            $delivery_success_status->ID = esc_html(SD_DELI_SUCCESS_STATUS);
            $delivery_success_status->Name = esc_html__('Giao hàng thành công', 'ship-depot-translate');
            $delivery_success_status->Description = esc_html__('Trường hợp vận đơn đã hoàn thành, tuỳ chọn bên dưới sẽ giúp cập nhật trạng thái đơn hàng tự động.', 'ship-depot-translate');
            $delivery_success_status->IsUsed = false;
            array_push($list_status, $delivery_success_status);
            //
            $delivery_failed_status = new Ship_Depot_Shipping_Status();
            $delivery_failed_status->ID = esc_html(SD_DELI_FAIL_STATUS);
            $delivery_failed_status->Name = esc_html__('Giao hàng không thành công', 'ship-depot-translate');
            $delivery_failed_status->Description = esc_html__('Nếu vận đơn không thể hoàn thành, tuỳ chọn bên dưới sẽ giúp cập nhật trạng thái đơn hàng tự động.', 'ship-depot-translate');
            $delivery_failed_status->IsUsed = false;
            array_push($list_status, $delivery_failed_status);
            //
            $for_control_status = new Ship_Depot_Shipping_Status();
            $for_control_status->ID = esc_html(SD_FOR_CONTROL_STATUS);
            $for_control_status->Name = esc_html__('Đã đối soát', 'ship-depot-translate');
            $for_control_status->Description = esc_html__('Nếu vận đơn đã đối soát, tuỳ chọn bên dưới sẽ giúp cập nhật trạng thái đơn hàng tự động.', 'ship-depot-translate');
            $for_control_status->IsUsed = false;
            array_push($list_status, $for_control_status);
            //
            return $list_status;
        }
    }
}
