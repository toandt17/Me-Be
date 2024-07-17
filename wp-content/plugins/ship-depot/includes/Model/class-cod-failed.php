<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_COD_Failed')) {
    class Ship_Depot_COD_Failed extends Ship_Depot_Base_Model
    {
        public bool $IsUsed = false;
        //For cod failed amount
        public int $CODFailedAmount = 0;
        public Ship_Depot_COD_Failed_Notice $ContentCheckout;
        public Ship_Depot_COD_Failed_Notice $ContentShippingLabel;

        function __construct($object = null)
        {
            $this->ContentCheckout = new Ship_Depot_COD_Failed_Notice();
            $this->ContentShippingLabel = new Ship_Depot_COD_Failed_Notice();
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_COD_Failed] this: ' . print_r($this, true));
        }
    }
}
