<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Webhook')) {
    class Ship_Depot_Webhook
    {
        function __construct()
        {
            add_action('rest_api_init', [$this, 'init']);
        }

        function init()
        {
            register_rest_route('shipdepot/webhook/v1', '/UpdateStorages', [
                'methods' => 'POST',
                'callback' => [$this, 'sd_update_storages'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route('shipdepot/webhook/v1', '/UpdateShopInfo', [
                'methods' => 'POST',
                'callback' => [$this, 'sd_update_shop_info'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route('shipdepot/webhook/v1', '/UpdateCourierSettings', [
                'methods' => 'POST',
                'callback' => [$this, 'sd_update_courier_settings'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route('shipdepot/webhook/v1', '/UpdateCouriers', [
                'methods' => 'POST',
                'callback' => [$this, 'sd_update_couriers'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route('shipdepot/webhook/v1', '/UpdateProvinces', [
                'methods' => 'POST',
                'callback' => [$this, 'sd_update_provinces'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route('shipdepot/webhook/v1', '/SyncDataFromAdmin', [
                'methods' => 'POST',
                'callback' => [$this, 'sd_sync_data_from_admin'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route('shipdepot/webhook/v1', '/UpdateShippingStatus', [
                'methods' => 'POST',
                'callback' => [$this, 'sd_update_shipping_status'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route('shipdepot/webhook/v1', '/UpdateCancelShipping', [
                'methods' => 'POST',
                'callback' => [$this, 'sd_update_cancel_shipping'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route('shipdepot/webhook/v1', '/PostHello', [
                'methods' => 'POST',
                'callback' => [$this, 'sd_post_hello'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route('shipdepot/webhook/v1', '/GetHello', [
                'methods' => 'GET',
                'callback' => [$this, 'sd_get_hello'],
                'permission_callback' => '__return_true'
            ]);
        }

        function sd_update_storages($request)
        {
            $params = wp_parse_args($request->get_params(), '');
            Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_storages] params: ' . print_r($params, true));
            if (!Ship_Depot_Helper::check_null_or_empty($params)) {
                $rs =  (object) $params;
                $shop_api_key = get_option('sd_api_key');
                Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_storages] shop_api_key: ' . print_r($shop_api_key, true));
                Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_storages] sd_list_storages: ' . print_r(get_option('sd_list_storages'), true));
                if (
                    !Ship_Depot_Helper::check_null_or_empty($shop_api_key)
                    && !Ship_Depot_Helper::check_null_or_empty($rs->APIKey)
                    && $shop_api_key == $rs->APIKey
                ) {
                    $list_storages = $rs->ListStorages;
                    // Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_storages] list_storages encrypted: ' . print_r($list_storages, true));
                    $list_storages_decrypted = Ship_Depot_ProtectData::DecryptData($list_storages);
                    Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_storages] list_storages_decrypted: ' . print_r($list_storages_decrypted, true));
                    update_option('sd_list_storages', $list_storages_decrypted);
                }
            }
            return 'OK';
        }

        function sd_update_shop_info($request)
        {
            $params = wp_parse_args($request->get_params(), '');
            Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_shop_info] params: ' . print_r($params, true));
            if (!Ship_Depot_Helper::check_null_or_empty($params)) {
                $rs =  (object) $params;
                $shop_api_key = get_option('sd_api_key');
                Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_shop_info] shop_api_key: ' . print_r($shop_api_key, true));
                Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_shop_info] sd_sender_info: ' . print_r(get_option('sd_sender_info'), true));
                if (
                    !Ship_Depot_Helper::check_null_or_empty($shop_api_key)
                    && !Ship_Depot_Helper::check_null_or_empty($rs->APIKey)
                    && $shop_api_key == $rs->APIKey
                ) {
                    $shop_info = $rs->ShopInfo;
                    // Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_shop_info] shop_info encrypted: ' . print_r($shop_info, true));
                    $shop_info_decrypted = Ship_Depot_ProtectData::DecryptData($shop_info);
                    Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_shop_info] shop_info_decrypted: ' . print_r($shop_info_decrypted, true));
                    update_option('sd_sender_info', $shop_info_decrypted);
                }
            }
            return 'OK';
        }

        function sd_update_courier_settings($request)
        {
            $params = wp_parse_args($request->get_params(), '');
            Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_courier_settings] params: ' . print_r($params, true));

            if (!Ship_Depot_Helper::check_null_or_empty($params)) {
                $rs =  (object) $params;
                $shop_api_key = get_option('sd_api_key');
                Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_courier_settings] shop_api_key: ' . print_r($shop_api_key, true));
                Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_courier_settings] sd_setting_courier: ' . print_r(get_option('sd_setting_courier'), true));
                if (
                    !Ship_Depot_Helper::check_null_or_empty($shop_api_key)
                    && !Ship_Depot_Helper::check_null_or_empty($rs->APIKey)
                    && $shop_api_key == $rs->APIKey
                ) {
                    $list_cour_settings = $rs->ListCourierSettings;
                    // Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_courier_settings] list_cour_settings encrypted: ' . print_r($list_cour_settings, true));
                    $list_cour_settings_decrypted = Ship_Depot_ProtectData::DecryptData($list_cour_settings);
                    Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_courier_settings] list_cour_settings_decrypted: ' . print_r($list_cour_settings_decrypted, true));
                    update_option('sd_setting_courier', $list_cour_settings_decrypted);
                }
            }
            return 'OK';
        }

        function sd_update_couriers($request)
        {
            $params = wp_parse_args($request->get_params(), '');
            Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_couriers] params: ' . print_r($params, true));
            if (!Ship_Depot_Helper::check_null_or_empty($params)) {
                $rs =  (object) $params;
                $shop_api_key = get_option('sd_api_key');
                Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_couriers] shop_api_key: ' . print_r($shop_api_key, true));
                Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_couriers] sd_list_couriers: ' . print_r(get_option('sd_list_couriers'), true));
                if (
                    !Ship_Depot_Helper::check_null_or_empty($shop_api_key)
                    && !Ship_Depot_Helper::check_null_or_empty($rs->APIKey)
                    && $shop_api_key == $rs->APIKey
                ) {
                    $list_cours = $rs->ListCouriers;
                    // Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_couriers] shop_info encrypted: ' . print_r($list_cours, true));
                    $list_cour_decrypted = Ship_Depot_ProtectData::DecryptData($list_cours);
                    Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_couriers] shop_info_decrypted: ' . print_r($list_cour_decrypted, true));
                    update_option('sd_list_couriers', $list_cour_decrypted);
                }
            }
            return 'OK';
        }

        function sd_update_provinces($request)
        {
            $params = wp_parse_args($request->get_params(), '');
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_provinces] params: ' . print_r($params, true));
            if (!Ship_Depot_Helper::check_null_or_empty($params)) {
                $rs =  (object) $params;
                $shop_api_key = get_option('sd_api_key');
                Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_provinces] shop_api_key: ' . print_r($shop_api_key, true));
                if (
                    !Ship_Depot_Helper::check_null_or_empty($shop_api_key)
                    && !Ship_Depot_Helper::check_null_or_empty($rs->APIKey)
                    && $shop_api_key == $rs->APIKey
                ) {
                    $myfile = fopen(SHIP_DEPOT_DIR_PATH . "src/all_VN_province.json", "w");
                    if (!$myfile) return;
                    $all_province_encrypted = $rs->AllProvinces;
                    $all_province_decrypted = Ship_Depot_ProtectData::DecryptData($all_province_encrypted);
                    fwrite($myfile, $all_province_decrypted);
                    fclose($myfile);
                }
            }
            return 'OK';
        }

        function sd_sync_data_from_admin($request)
        {
            $params = wp_parse_args($request->get_params(), '');
            // Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_sync_data_from_admin] params: ' . print_r($params, true));
            if (!Ship_Depot_Helper::check_null_or_empty($params)) {
                $rs =  (object) $params;
                $shop_api_key = get_option('sd_api_key');
                Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_sync_data_from_admin] shop_api_key: ' . print_r($shop_api_key, true));
                if (
                    !Ship_Depot_Helper::check_null_or_empty($shop_api_key)
                    && !Ship_Depot_Helper::check_null_or_empty($rs->APIKey)
                    && $shop_api_key == $rs->APIKey
                ) {
                    $list_storages = $rs->ListStorages;
                    $list_storages_decrypted = Ship_Depot_ProtectData::DecryptData($list_storages);
                    update_option('sd_list_storages', $list_storages_decrypted);
                    //
                    $shop_info =  $rs->ShopInfo;
                    $shop_info_decrypted = Ship_Depot_ProtectData::DecryptData($shop_info);
                    update_option('sd_sender_info', $shop_info_decrypted);
                    //
                    $list_cours = $rs->ListCouriers;
                    $list_cours_decrypted = Ship_Depot_ProtectData::DecryptData($list_cours);
                    update_option('sd_list_couriers', $list_cours_decrypted);
                    //
                    $list_cour_settings = $rs->ListCourierSettings;
                    $list_cour_settings_decrypted = Ship_Depot_ProtectData::DecryptData($list_cour_settings);
                    update_option('sd_setting_courier', $list_cour_settings_decrypted);
                }
            }
            return 'OK';
        }

        function sd_update_shipping_status($request)
        {
            $params = wp_parse_args($request->get_params(), '');
            Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_shipping_status] params: ' . print_r($params, true));
            if (!Ship_Depot_Helper::check_null_or_empty($params)) {
                $data =  (object) $params;
                $shop_api_key = get_option('sd_api_key');
                Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_shipping_status] shop_api_key: ' . print_r($shop_api_key, true));
                if (
                    !Ship_Depot_Helper::check_null_or_empty($shop_api_key)
                    && !Ship_Depot_Helper::check_null_or_empty($data->APIKey)
                    && $shop_api_key == $data->APIKey
                ) {
                    $ship_info_encrypted = $data->ShipInfo;
                    $ship_info_decrypted = Ship_Depot_ProtectData::DecryptData($ship_info_encrypted);
                    $ship_param = json_decode($ship_info_decrypted);
                    Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_shipping_status] ship_param from post data: ' . print_r($ship_param, true));
                    $str_ship_info = get_post_meta($ship_param->WOOOrderID, 'sd_ship_info', true);
                    Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_shipping_status] current ship_info: ' . print_r($str_ship_info, true));
                    if (!Ship_Depot_Helper::check_null_or_empty($str_ship_info)) {
                        $ship_info = json_decode($str_ship_info);
                        if ($ship_info->TrackingNumber == $ship_param->TrackingNumber) {
                            $ship_info->ShipStatus = $ship_param->ShipStatus;
                            $ship_info->ShipStatusCode = $ship_param->ShipStatusCode;
                            Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_shipping_status] updated ship_info: ' . json_encode($ship_info, JSON_UNESCAPED_UNICODE));
                            Ship_Depot_Helper::UpdateOrderMetadata($ship_param->WOOOrderID, 'sd_ship_info', json_encode($ship_info, JSON_UNESCAPED_UNICODE));
                            $order = new WC_Order($ship_param->WOOOrderID);
                            /*
                            pending
                            processing
                            on-hold
                            completed
                            cancelled
                            refunded
                            failed

                            sd-delivering
                            sd-delivered
                            sd-delivery-failed
                            */
                            $status_lower = mb_strtolower($ship_param->ShipStatusCode, "UTF-8");
                            $str_update_order_statuses = get_option('sd_update_order_statuses');
                            if (str_contains($status_lower, strtolower(SD_CANCEL_STATUS))) {
                                $order_note = __('Vận đơn ' . $ship_info->TrackingNumber . ' đã bị hủy. Lý do: ', 'ship-depot-translate');
                                if (!Ship_Depot_Helper::check_null_or_empty($ship_param->CancelDescription)) {
                                    $order_note = $order_note . ' ' . $ship_param->CancelDescription;
                                }
                                Ship_Depot_Shipping_Helper::update_cancel_shipping_info($order, $ship_info->TrackingNumber, $order_note);
                            }
                            //
                            if (!Ship_Depot_Helper::check_null_or_empty($str_update_order_statuses)) {
                                $list_update_order_statuses = json_decode($str_update_order_statuses);
                                foreach ($list_update_order_statuses as $status_obj) {
                                    $status = new Ship_Depot_Shipping_Status($status_obj);
                                    if (str_contains($status_lower, strtolower($status->ID))) {
                                        Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_shipping_status][update status auto] updated status in option: ' . print_r($status, true));
                                        if ($status->IsUsed && !Ship_Depot_Helper::check_null_or_empty($status->WooOrderStatusID)) {
                                            Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_shipping_status][update status auto] order status changed to ' . $status->WooOrderStatusID);
                                            $order->update_status(str_replace('wc-', '', $status->WooOrderStatusID), 'Update auto by Ship Depot.');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return 'OK';
        }

        function sd_update_cancel_shipping($request)
        {
            $params = wp_parse_args($request->get_params(), '');
            Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_cancel_shipping] params: ' . print_r($params, true));
            if (!Ship_Depot_Helper::check_null_or_empty($params)) {
                $data =  (object) $params;
                $shop_api_key = get_option('sd_api_key');
                Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_cancel_shipping] shop_api_key: ' . print_r($shop_api_key, true));
                if (
                    !Ship_Depot_Helper::check_null_or_empty($shop_api_key)
                    && !Ship_Depot_Helper::check_null_or_empty($data->APIKey)
                    && $shop_api_key == $data->APIKey
                ) {
                    $ship_info_encrypted = $data->ShipInfo;
                    $ship_info_decrypted = Ship_Depot_ProtectData::DecryptData($ship_info_encrypted);
                    $ship_param = json_decode($ship_info_decrypted);
                    Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_cancel_shipping] ship_param from post data: ' . print_r($ship_param, true));
                    $str_ship_info = get_post_meta($ship_param->WOOOrderID, 'sd_ship_info', true);
                    Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_cancel_shipping] current ship_info: ' . print_r($str_ship_info, true));
                    if (!Ship_Depot_Helper::check_null_or_empty($str_ship_info)) {
                        $ship_info = json_decode($str_ship_info);
                        Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_cancel_shipping] ship_info->TrackingNumber: ' . print_r($ship_info->TrackingNumber, true));
                        Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_update_cancel_shipping] ship_info->TrackingNumber: ' . print_r($ship_param->TrackingNumber, true));
                        if ($ship_info->TrackingNumber == $ship_param->TrackingNumber) {
                            $order = new WC_Order($ship_param->WOOOrderID);
                            //Update empty ship info
                            $order_note = __('Vận đơn ' . $ship_info->TrackingNumber . ' đã bị hủy. Lý do: ', 'ship-depot-translate');
                            if (!Ship_Depot_Helper::check_null_or_empty($ship_param->CancelDescription)) {
                                $order_note = $order_note . ' ' . $ship_param->CancelDescription;
                            }
                            Ship_Depot_Shipping_Helper::update_cancel_shipping_info($order, $ship_info->TrackingNumber, $order_note);
                            //
                        }
                    }
                }
            }
            return 'OK';
        }

        function sd_post_hello($request)
        {
            Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_post_hello] Begin');
            $params = wp_parse_args($request->get_params(), '');
            Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_post_hello] params: ' . print_r($params, true));
            Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_post_hello] End');

            nocache_headers();
            $result = new WP_REST_Response($params, 200);
            return $result;
        }

        function sd_get_hello($request)
        {
            Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_get_hello] Begin');
            $params = wp_parse_args($request->get_params(), '');
            Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_get_hello] params: ' . print_r($params, true));
            Ship_Depot_Logger::wrlog('[Ship_Depot_Webhook][sd_get_hello] End');

            nocache_headers();
            $reponse = new stdClass();
            $reponse->message = 'ShipDepot Hello!';
            $reponse->params = $params;
            $result = new WP_REST_Response($reponse, 200);
            return $result;
        }
    }
    new Ship_Depot_Webhook();
}
