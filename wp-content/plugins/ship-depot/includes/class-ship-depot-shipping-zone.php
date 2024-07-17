<?php
defined('ABSPATH') || exit;
if (!class_exists('Ship_Depot_Shipping_Zone')) {
    class Ship_Depot_Shipping_Zone
    {
        public const sd_zone_name = 'Ship Depot Zone';
        public static function get_sd_zone()
        {
            if (class_exists('WC_Shipping_Zones')) {
                $available_zones = WC_Shipping_Zones::get_zones();
                //Array to store available names
                $available_zones_names = array();
                // Add each existing zone name into our array
                foreach ($available_zones as $zone) {
                    if (!in_array($zone['zone_name'], $available_zones_names)) {
                        $available_zones_names[] = $zone['zone_name'];
                    }
                }

                ////Ship_Depot_Logger::wrlog('[Ship_Depot_Shipping_Zone][get_sd_zone] available_zones_names: ' . print_r($available_zones_names, true));
                // Check if our zone 'Ship Depot Zone' is not existed => create new
                if (!in_array('Ship Depot Zone', $available_zones_names)) {
                    //Ship_Depot_Logger::wrlog('[Ship_Depot_Shipping_Zone][get_sd_zone] create sd zone');
                    // Instantiate a new shipping zone with our object
                    $new_zone = new WC_Shipping_Zone();
                    $new_zone->set_zone_name(self::sd_zone_name);
                    // Save the zone, if non existent it will create a new zone
                    $new_zone->save();
                    (new self)->checkAddShippingMethod();
                    return $new_zone;
                }

                foreach ($available_zones as $the_zone) {
                    if ($the_zone['zone_name'] == self::sd_zone_name) {
                        return WC_Shipping_Zones::get_zone($the_zone['zone_id']);
                    }
                }
            }
            return false;
        }

        public static function get_shipping_method($method_id = SHIP_DEPOT_SHIPPING_METHOD)
        {
            //Ship_Depot_Logger::wrlog('[Ship_Depot_Shipping_Zone][get_shipping_method] method_id: ' . json_encode($method_id));
            $sd_zone = Ship_Depot_Shipping_Zone::get_sd_zone();
            if (!$sd_zone) {
                return false;
            }

            //Ship_Depot_Logger::wrlog('[Ship_Depot_Shipping_Zone][get_shipping_method] sd_zone: ' . print_r($sd_zone, true));
            (new self)->checkAddShippingMethod();
            foreach ($sd_zone->get_shipping_methods() as $method) {
                if ($method->id == $method_id) {
                    return $method;
                }
            }

            return false;
        }

        public function checkAddShippingMethod()
        {
            $sd_zone = Ship_Depot_Shipping_Zone::get_sd_zone();
            if (!$sd_zone) {
                return false;
            }
            Ship_Depot_Logger::wrlog('[Ship_Depot_Shipping_Zone][checkAddShippingMethod] list shipping methods: ' . print_r($sd_zone->get_shipping_methods(), true));
            foreach ($sd_zone->get_shipping_methods() as $method) {
                if ($method->id == SHIP_DEPOT_SHIPPING_METHOD) {
                    $isExisted = true;
                }
            }
            Ship_Depot_Logger::wrlog('[Ship_Depot_Shipping_Zone][checkAddShippingMethod] isExisted: ' . json_encode($isExisted));
            if (!$isExisted) {
                $rs = $sd_zone->add_shipping_method(SHIP_DEPOT_SHIPPING_METHOD);
                Ship_Depot_Logger::wrlog('[Ship_Depot_Shipping_Zone][checkAddShippingMethod] rs: ' . json_encode($rs));
            }
        }
    }
}
