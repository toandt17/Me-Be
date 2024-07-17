<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Fee_Markup_Courier_Condition')) {
    class Ship_Depot_Fee_Markup_Courier_Condition extends Ship_Depot_Base_Model
    {
        public string $Type = "";
        public array $ListCouriers = [];
        function __construct($object = null)
        {
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Fee_Markup_Courier_Condition] this: ' . print_r($this, true));
        }
    }
}
