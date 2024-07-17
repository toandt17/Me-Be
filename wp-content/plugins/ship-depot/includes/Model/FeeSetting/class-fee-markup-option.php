<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Fee_Markup_Option')) {
    class Ship_Depot_Fee_Markup_Option extends Ship_Depot_Base_Model
    {
        public string $Type = "";
        public string $SubType = "";
        public int $Value = 0;
        function __construct($object = null)
        {
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Fee_Markup_Option] this: ' . print_r($this, true));
        }
    }
}
