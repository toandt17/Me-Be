<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_District')) {
    class Ship_Depot_District extends Ship_Depot_Base_Model
    {
        public int $CityISN = -1;
        public int $ParentISN = -1;
        public int $LocationISN = -1;
        public int $DistrictISN = -1;
        public string $Code = '';
        public string $Name = '';
        //

        function __construct($object = null)
        {
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_District] this: ' . print_r($this, true));
        }
    }
}
