<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Shipping_Fee_Response')) {
    class Ship_Depot_Shipping_Fee_Response extends Ship_Depot_Base_Model
    {
        public int $CourierISN = 0;
        public int $CourierServiceISN = 0;
        public string $CourierID = "";
        public string $ServiceCode = "";
        public string $CourierServiceCode = "";
        public string $ServiceName = "";
        public string $TimeExpected = "";
        public Ship_Depot_Shipping_Fee_Info $CourierShippingFee;
        public Ship_Depot_Shipping_Fee_Info $ShipDepotMarkupShippingFee;
        public Ship_Depot_Shipping_Fee_Info $ShopMarkupShippingFee;
        //For cod failed
        public Ship_Depot_No_Markup_Shipping_Fee $NoMarkupShippingFee;
        function __construct($object = null)
        {
            $this->CourierShippingFee = new Ship_Depot_Shipping_Fee_Info();
            $this->ShipDepotMarkupShippingFee = new Ship_Depot_Shipping_Fee_Info();
            $this->ShopMarkupShippingFee = new Ship_Depot_Shipping_Fee_Info();
            $this->NoMarkupShippingFee = new Ship_Depot_No_Markup_Shipping_Fee();
            if ($object != null) {
                if (isset($object->ShippingFeeTotal)) {
                    $courSF = new Ship_Depot_Shipping_Fee_Info();
                    $courSF->IsActive = true;
                    $courSF->ShippingFeeTotal = $object->ShippingFeeTotal ?? 0;
                    $courSF->ShippingFeeNet = $object->ShippingFeeNet ?? 0;
                    $courSF->InsuranceFee = $object->InsuranceFee ?? 0;
                    $courSF->CODFee = $object->CODFee ?? 0;
                    $courSF->OtherFees = $object->OtherFees ?? 0;
                    //
                    $object->CourierShippingFee = $courSF;
                    $object->ShipDepotMarkupShippingFee = $courSF;
                    $shopSF = new Ship_Depot_Shipping_Fee_Info();
                    if (isset($object->ShippingFeeOverride) && $object->ShippingFeeOverride != -9999) {
                        $shopSF->IsActive = true;
                        $shopSF->ShippingFeeNet = $object->ShippingFeeOverride ?? 0;
                        $shopSF->InsuranceFee = $object->InsuranceFee ?? 0;
                        $shopSF->CODFee = $object->CODFee ?? 0;
                        $shopSF->OtherFees = $object->OtherFees ?? 0;
                        $shopSF->ShippingFeeTotal = $courSF->ShippingFeeTotal - $courSF->ShippingFeeNet + $shopSF->ShippingFeeNet;
                    }
                }
            }
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Shipping_Fee_Response] this: ' . print_r($this, true));
        }
    }
}
