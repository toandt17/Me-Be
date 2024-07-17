<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Courier_Response')) {
    class Ship_Depot_Courier_Response extends Ship_Depot_Base_Model
    {
        public string $CourierID = "";
        public int $CourierISN = 0;
        public string $CourierName = "";
        public string $LogoURL = "";
        public array $ListServices = array();
        //For cod failed amount
        public Ship_Depot_COD_Failed $CODFailed;
        //For pickup at shop (PAS)
        public string $PASAddress = "";
        public string $PASPhone = "";

        function __construct($object = null)
        {
            $this->CODFailed = new Ship_Depot_COD_Failed();
            $this->ListServices = [];
            parent::MapData($object, $this);
            if ($this->CourierID == PAS_COURIER_CODE) {
                if (Ship_Depot_Helper::check_null_or_empty($this->PASAddress) || Ship_Depot_Helper::check_null_or_empty($this->PASPhone)) {
                    if (!isset($sender_info)) {
                        $str_sender_info = get_option('sd_sender_info');
                        if (!is_null($str_sender_info) && !empty($str_sender_info)) {
                            $sender_info_obj = json_decode($str_sender_info);
                            $sender_info = new Ship_Depot_Shop_Info($sender_info_obj);
                        }
                    }

                    if (Ship_Depot_Helper::check_null_or_empty($this->PASAddress)) {
                        $this->PASAddress = esc_html($sender_info->Address . ', ' . $sender_info->Ward . ', ' . $sender_info->District . ', ' . $sender_info->City);
                    }

                    if (Ship_Depot_Helper::check_null_or_empty($this->PASPhone)) {
                        $this->PASPhone = $sender_info->Phone;
                    }
                }
            }
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Courier_Response] this: ' . print_r($this, true));
        }
    }
}
