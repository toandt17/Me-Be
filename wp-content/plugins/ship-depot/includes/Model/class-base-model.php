<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Base_Model')) {
    class Ship_Depot_Base_Model
    {
        function MapData($from, $to)
        {
            if ($from != null && $to != null) {
                foreach ($to as $key => $value) {
                    if (isset($from->$key)) {
                        // Ship_Depot_Logger::wrlog('[Ship_Depot_Base_Model][MapData] key: ' . print_r($key, true));
                        // Ship_Depot_Logger::wrlog('[Ship_Depot_Base_Model][MapData] obj value: ' . print_r($from->$key, true));
                        // Ship_Depot_Logger::wrlog('[Ship_Depot_Base_Model][MapData] this value bf: ' . print_r($to->$key, true));
                        if (is_object($to->$key)) {
                            // Ship_Depot_Logger::wrlog('[Ship_Depot_Base_Model][MapData][Class] this class: ' . print_r(get_class($to->$key), true));
                            $clss = get_class($to->$key);
                            $to->$key = new $clss($from->$key);
                            // Ship_Depot_Logger::wrlog('[Ship_Depot_Base_Model][MapData][Class] this value aft: ' . print_r($to->$key, true));
                        } else {
                            $to->$key = $from->$key;
                            // Ship_Depot_Logger::wrlog('[Ship_Depot_Base_Model][MapData][Normal] this value aft: ' . print_r($to->$key, true));
                        }
                    } else {
                        $k = strtolower($key);
                        if (isset($from->$k)) {
                            // Ship_Depot_Logger::wrlog('[Ship_Depot_Base_Model][MapData] key: ' . print_r($key, true));
                            // Ship_Depot_Logger::wrlog('[Ship_Depot_Base_Model][MapData] k: ' . print_r($k, true));
                            // Ship_Depot_Logger::wrlog('[Ship_Depot_Base_Model][MapData] obj value: ' . print_r($from->$k, true));
                            // Ship_Depot_Logger::wrlog('[Ship_Depot_Base_Model][MapData] this value bf: ' . print_r($to->$key, true));
                            if (is_object($to->$key)) {
                                // Ship_Depot_Logger::wrlog('[Ship_Depot_Base_Model][MapData][Class] this class: ' . print_r(get_class($to->$key), true));
                                $clss = get_class($to->$key);
                                $to->$key = new $clss($from->$k);
                                // Ship_Depot_Logger::wrlog('[Ship_Depot_Base_Model][MapData][Class] this value aft: ' . print_r($to->$key, true));
                            } else {
                                $to->$key = $from->$k;
                                // Ship_Depot_Logger::wrlog('[Ship_Depot_Base_Model][MapData][Normal] this value aft: ' . print_r($to->$key, true));
                            }
                        }
                    }
                }
            }
        }
    }
}
