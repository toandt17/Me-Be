<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Shop_Info')) {
    class Ship_Depot_Shop_Info extends Ship_Depot_Base_Model
    {
        public int $MemberISN = 0;
        public string $CompanyName = "";
        public string $UserName = "";
        public string $FirstName = "";
        public string $LastName = "";
        public string $Phone = "";
        public string $Address = "";
        public string $Ward = "";
        public string $District = "";
        public string $City = "";
        public int $CityISN = 0;
        public int $DistrictISN = 0;
        public int $WardISN = 0;
        function __construct($object = null)
        {
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Shop_Info] this: ' . print_r($this, true));
        }
    }
}
