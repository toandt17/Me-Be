<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Fee_Markup')) {
    class Ship_Depot_Fee_Markup extends Ship_Depot_Base_Model
    {
        public string $ID = "";
        public bool $IsActive = false;
        public array $ListOrderConditions = [];
        public Ship_Depot_Fee_Markup_Option $Option;
        public Ship_Depot_Fee_Markup_Time_Condition $TimeApply;
        public Ship_Depot_Fee_Markup_Courier_Condition $CourierApply;
        public string $Notes = "";

        function __construct($object = null)
        {
            $this->Option = new Ship_Depot_Fee_Markup_Option();
            $this->TimeApply = new Ship_Depot_Fee_Markup_Time_Condition();
            $this->CourierApply = new Ship_Depot_Fee_Markup_Courier_Condition();
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Fee_Markup] this aft: ' . print_r($this, true));
        }
    }
}
