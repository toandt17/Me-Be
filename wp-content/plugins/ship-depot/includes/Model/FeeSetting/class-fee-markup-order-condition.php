<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Fee_Markup_Order_Condition')) {
    class Ship_Depot_Fee_Markup_Order_Condition extends Ship_Depot_Base_Model
    {
        public string $ID = "";
        public string $Type = "";
        public int $FromValue = 0;
        public int $ToValue = 0;
        public string $FixedValue = "";
        function __construct($object = null)
        {
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Fee_Markup_Order_Condition] this: ' . print_r($this, true));
        }
    }
}
