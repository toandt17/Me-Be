<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_COD_Failed_Notice')) {
    class Ship_Depot_COD_Failed_Notice extends Ship_Depot_Base_Model
    {
        public bool $IsShow = false;
        public string $Content = '';

        function __construct($object = null)
        {
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_COD_Failed_Notice] this: ' . print_r($this, true));
        }
    }
}
