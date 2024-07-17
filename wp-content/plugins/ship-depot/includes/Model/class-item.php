<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Item')) {
    class Ship_Depot_Item extends Ship_Depot_Package
    {
        public int $ID = 0;
        public string $Sku = "";
        public string $Name = "";
        public string $ImageURL = "";
        public int $Quantity = 0;
        public int $Price = 0;
        public int $TotalPrice = 0;
        public int $RegularPrice = 0;

        function __construct($object = null)
        {
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Item] this: ' . print_r($this, true));
        }
    }
}
