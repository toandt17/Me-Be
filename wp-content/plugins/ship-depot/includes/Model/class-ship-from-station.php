<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Ship_From_Station')) {
    class Ship_Depot_Ship_From_Station extends Ship_Depot_Base_Model
    {
        public bool $IsActive = false;
        public Ship_Depot_Province $Province;
        public Ship_Depot_District $District;
        public Ship_Depot_Station $Station;
        //

        function __construct($object = null)
        {
            $this->Province = new Ship_Depot_Province();
            $this->District = new Ship_Depot_District();
            $this->Station = new Ship_Depot_Station();
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Ship_From_Station] this: ' . print_r($this, true));
        }
    }
}
