<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Package')) {
    class Ship_Depot_Package extends Ship_Depot_Base_Model
    {
        public float $Length = 0;
        public float $Width = 0;
        public float $Height = 0;
        public float $Weight = 0;
        function __construct($object = null)
        {
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Package] this: ' . print_r($this, true));
        }
    }
}
