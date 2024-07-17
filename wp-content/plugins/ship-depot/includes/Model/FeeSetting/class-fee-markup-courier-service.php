<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Fee_Markup_Courier_Service')) {
    class Ship_Depot_Fee_Markup_Courier_Service extends Ship_Depot_Base_Model
    {
        public string $CourierID = "";
        public bool $IsActive = false;
        public array $ListServicesSelected = [];
        function __construct($object = null)
        {
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Fee_Markup_Courier_Service] this: ' . print_r($this, true));
        }
    }
}
