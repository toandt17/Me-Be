<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Shipping_Fee_Info')) {
    class Ship_Depot_Shipping_Fee_Info extends Ship_Depot_Base_Model
    {
        public bool $IsActive = false;
        public int $ShippingFeeTotal = 0;
        public int $ShippingFeeNet = 0;
        public int $InsuranceFee = 0;
        public int $CODFee = 0;
        public int $OtherFees = 0;
        public Ship_Depot_Fee_Markup_Option $MarkupOption;

        function __construct($object = null)
        {
            $this->MarkupOption = new Ship_Depot_Fee_Markup_Option();
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Shipping_Fee_Info] this: ' . print_r($this, true));
        }
    }
}
