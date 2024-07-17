<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Receiver')) {
    class Ship_Depot_Receiver extends Ship_Depot_Customer
    {
        public string $Type = "";

        function __construct($object = null)
        {
            if ($object != null) {
                if (isset($object->first_name)) {
                    $this->FirstName = $object->first_name;
                }

                if (isset($object->first_name)) {
                    $this->LastName = $object->last_name;
                }
            }
            parent::MapData($object, $this);
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Receiver] this: ' . print_r($this, true));
        }
    }
}
