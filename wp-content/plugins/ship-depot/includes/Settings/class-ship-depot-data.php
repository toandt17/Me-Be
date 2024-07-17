<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class Ship_Depot_Data
{
    public function __construct()
    {
        $this->get_couriers_data();
        $this->get_couriers_settings_data();
        $this->get_storages_data();
        $this->get_sender_info();
        $this->get_all_provinces();
    }

    public static function update_data_new_api_key()
    {
        Ship_Depot_Logger::wrlog('[update_data_new_api_key]');
        delete_option('sd_list_couriers');
        delete_option('sd_setting_courier');
        delete_option('sd_list_storages');
        delete_option('sd_sender_info');
        (new self)->clear_all_provinces();

        (new self)->get_couriers_data();
        (new self)->get_couriers_settings_data();
        (new self)->get_storages_data();
        (new self)->get_sender_info();
        (new self)->get_all_provinces();
    }

    function get_couriers_data()
    {
        //Ship_Depot_Logger::wrlog('[get_couriers_data]');
        if (Ship_Depot_Helper::check_null_or_empty(get_option('sd_list_couriers'))) {
            $url = SHIP_DEPOT_HOST_API . '/Data/GetCouriers';
            $resultObj = Ship_Depot_Helper::http_get_php($url);
            //Ship_Depot_Logger::wrlog('[get_couriers_data] resultObj: ' . print_r($resultObj, true));
            if ($resultObj->Code >= 0) {
                Ship_Depot_Logger::wrlog('[get_couriers_data] success');
                $listCouriers = $resultObj->Data;
                update_option('sd_list_couriers', json_encode($listCouriers, JSON_UNESCAPED_UNICODE));
            }
        } else {
            //Ship_Depot_Logger::wrlog('[get_couriers_data] list_couriers: ' . get_option('sd_list_couriers')); 
        }
    }

    function get_couriers_settings_data()
    {
        //Ship_Depot_Logger::wrlog('[get_couriers_settings_data]');
        if (Ship_Depot_Helper::check_null_or_empty(get_option('sd_setting_courier'))) {
            $url = SHIP_DEPOT_HOST_API . '/Data/GetCouriersShopSetting';
            $shop_api_key = get_option('sd_api_key');
            if (Ship_Depot_Helper::check_null_or_empty($shop_api_key)) return false;
            $resultObj = Ship_Depot_Helper::http_get_php($url, array('ShopAPIKey' => $shop_api_key));
            //Ship_Depot_Logger::wrlog('[get_couriers_settings_data] resultObj: ' . print_r($resultObj, true));
            if ($resultObj->Code >= 0) {
                Ship_Depot_Logger::wrlog('[get_couriers_settings_data] success');
                $listCouriers = $resultObj->Data;
                update_option('sd_setting_courier', json_encode($listCouriers, JSON_UNESCAPED_UNICODE));
            }
        } else {
            //Ship_Depot_Logger::wrlog('[get_couriers_settings_data] list_couriers: ' . get_option('sd_setting_courier')); 
        }
    }

    function get_storages_data()
    {
        //Ship_Depot_Logger::wrlog('[get_storages_data]');
        if (Ship_Depot_Helper::check_null_or_empty(get_option('sd_list_storages'))) {
            $url = SHIP_DEPOT_HOST_API . '/Data/GetStorages';
            $shop_api_key = get_option('sd_api_key');
            if (Ship_Depot_Helper::check_null_or_empty($shop_api_key)) return false;
            $resultObj = Ship_Depot_Helper::http_get_php($url, array('ShopAPIKey' => $shop_api_key));
            //Ship_Depot_Logger::wrlog('[get_storages_data] resultObj: ' . print_r($resultObj, true));
            if ($resultObj->Code >= 0) {
                Ship_Depot_Logger::wrlog('[get_storages_data] success');
                $listStorages = $resultObj->Data;
                update_option('sd_list_storages', json_encode($listStorages, JSON_UNESCAPED_UNICODE));
            }
        } else {
            //Ship_Depot_Logger::wrlog('[get_storages_data] list_storages: ' . get_option('sd_list_storages')); 
        }
    }

    function get_sender_info()
    {
        //Ship_Depot_Logger::wrlog('[get_sender_info]');
        if (Ship_Depot_Helper::check_null_or_empty(get_option('sd_sender_info'))) {
            $url = SHIP_DEPOT_HOST_API . '/Data/GetShopInfo';
            $shop_api_key = get_option('sd_api_key');
            if (Ship_Depot_Helper::check_null_or_empty($shop_api_key)) return false;
            $resultObj = Ship_Depot_Helper::http_get_php($url, array('ShopAPIKey' => $shop_api_key));
            if ($resultObj->Code >= 0) {
                $senderInfo = $resultObj->Data;
                update_option('sd_sender_info', json_encode($senderInfo, JSON_UNESCAPED_UNICODE));
            }
        } else {
            //Ship_Depot_Logger::wrlog('[get_sender_info] sender_info: ' . get_option('sd_sender_info'));
        }
    }

    function get_all_provinces()
    {
        //Ship_Depot_Logger::wrlog('[get_all_provinces]');
        $json_provinces = file_get_contents(SHIP_DEPOT_DIR_PATH . "src/all_VN_province.json");
        if (Ship_Depot_Helper::check_null_or_empty($json_provinces)) {
            $url = SHIP_DEPOT_HOST_API . '/Address/GetAllCity';
            $resultObj = Ship_Depot_Helper::http_get_php($url);
            //Ship_Depot_Logger::wrlog('[get_all_provinces] resultObj: ' . print_r($resultObj, true));
            if ($resultObj->Code >= 0) {
                $data = $resultObj->Data;
                $myfile = fopen(SHIP_DEPOT_DIR_PATH . "src/all_VN_province.json", "w") or die("Unable to open file!");
                $txt = json_encode($data, JSON_UNESCAPED_UNICODE);
                fwrite($myfile, $txt);
                fclose($myfile);
            }
        } else {
            //Ship_Depot_Logger::wrlog('[get_all_provinces] all_provinces: ' . get_option('sd_all_provinces')); 
        }
    }

    function clear_all_provinces()
    {
        $myfile = fopen(SHIP_DEPOT_DIR_PATH . "src/all_VN_province.json", "w") or die("Unable to open file!");
        $txt = "";
        fwrite($myfile, $txt);
        fclose($myfile);
    }
}

add_action('wp_ajax_sync_setting', 'sync_setting_init');
add_action('wp_ajax_nopriv_sync_setting', 'sync_setting_init');
function sync_setting_init()
{
    Ship_Depot_Data::update_data_new_api_key();
    wp_send_json_success("sync success");
}
