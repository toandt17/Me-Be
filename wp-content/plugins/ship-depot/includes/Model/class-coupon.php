<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Coupon')) {
    class Ship_Depot_Coupon extends Ship_Depot_Base_Model
    {
        public string $Code = '';
        public float $Value = 0;
        function __construct($object = null)
        {
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Coupon] this: ' . print_r($this, true));
        }
    }
}
