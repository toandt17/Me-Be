<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Station')) {
    class Ship_Depot_Station extends Ship_Depot_Base_Model
    {
        public int $Id = 0;
        public string $Code = '';
        public string $Name = '';
        public string $Address = '';
        //

        function __construct($object = null)
        {
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Station] this: ' . print_r($this, true));
        }
    }
}
