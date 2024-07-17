<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Courier_Service')) {
    class Ship_Depot_Courier_Service extends Ship_Depot_Base_Model
    {
        public int $CourierServiceISN = 0;
        public int $CourierISN = 0;
        public string $CourierID = "";
        public string $CourierName = "";

        public string $ServiceName = "";
        public string $ServiceCode = "";
        public string $ServiceDesc = "";
        public bool $IsUsed = false;
        //For GHN Only
        public int $GHNServiceID = 0;
        //For Aha Only
        public string $AhaServiceID = "";

        function __construct($object = null)
        {
            parent::MapData($object, $this);
            //Ship_Depot_Logger::wrlog('[Ship_Depot_Courier_Service] this: ' . print_r($this, true));
        }
    }
}
