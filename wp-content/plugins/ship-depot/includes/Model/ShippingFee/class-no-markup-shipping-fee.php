<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_No_Markup_Shipping_Fee')) {
    class Ship_Depot_No_Markup_Shipping_Fee extends Ship_Depot_Base_Model
    {
        public int $CODFailedFee = 0;
        public int $NoMarkupShippingFeeTotal = 0;

        function __construct($object = null)
        {
            parent::MapData($object, $this);
            $this->GetTotal();
        }

        function GetTotal(): int
        {
            $this->NoMarkupShippingFeeTotal = $this->CODFailedFee;
            return $this->NoMarkupShippingFeeTotal;
        }
    }
}
