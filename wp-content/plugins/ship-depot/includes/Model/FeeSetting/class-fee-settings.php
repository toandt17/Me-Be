<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Fee_Setting')) {
    class Ship_Depot_Fee_Setting extends Ship_Depot_Base_Model
    {
        public bool $IsActive = false;
        public array $ListFeeMarkups = [];

        function __construct($object = null)
        {
            $this->ListFeeMarkups = [];
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Fee_Setting] this: ' . print_r($this, true));
        }
    }
}
