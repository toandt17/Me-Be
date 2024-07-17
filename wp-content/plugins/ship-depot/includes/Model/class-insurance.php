<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Insurance')) {
    class Ship_Depot_Insurance extends Ship_Depot_Base_Model
    {
        public bool $IsActive = false;
        public int $Value = 0;
        function __construct($object = null)
        {
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Insurance] this: ' . print_r($this, true));
        }
    }
}
