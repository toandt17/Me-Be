<?php

/**
 *	WooCommerce settings page
 *
 *	This code creates a full WooCommerce settings page by extending the WC_Settings_Page class.
 *	By extending the WC_Settings_Page class, we can control every part of the settings page.
 *
 */


// Exit if accessed directly
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Settings')) :

    class Ship_Depot_Settings extends WC_Settings_Page
    {

        public function __construct()
        {

            $this->id = 'sd_settings';
            $this->label = __('Ship Depot', 'ship-depot-translate');

            /**
             *	Define all hooks instead of inheriting from parent
             */

            // parent::__construct();

            // Add the tab to the tabs array
            add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_page'), 20);

            // Add new section to the page
            add_action('woocommerce_sections_' . $this->id, array($this, 'output_sections'));

            // Add settings
            add_action('woocommerce_settings_' . $this->id, array($this, 'output'));

            // Save settings
            add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));

            //Custom code for save fee modify section after validate = jquery in admin-fee-modify.js. Because call $('#mainform').submit(); $_POST['save'] does not set => Woocommerce default does not save (detail in function save_settings() of class WC_Admin_Menus)
            add_filter('woocommerce_save_settings_sd_settings_fee_modify', array($this, 'validate_before_save'));
            add_filter('woocommerce_save_settings_sd_settings_couriers', array($this, 'validate_before_save'));
        }

        public function validate_before_save($allow)
        {
            Ship_Depot_Logger::wrlog("[Ship_Depot_Settings][validate_before_save] allow: {$allow}");
            // Ship_Depot_Logger::wrlog("[Ship_Depot_Settings][validate_before_save] _POST: " . print_r($_POST, true));
            if (isset($_POST['validate_error']) && !Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST['validate_error'])) && sanitize_text_field($_POST['validate_error']) == "false") {
                Ship_Depot_Logger::wrlog("[Ship_Depot_Settings][validate_before_save] return true");
                return true;
            }
            return $allow;
        }
        /**
         *	Get sections
         *
         *	@return array
         */
        public function get_sections()
        {

            // Must contain more than one section to display the links
            // Make first element's key empty ('')
            $sections = array(
                '' => __('Thiết lập', 'ship-depot-translate'),
                'couriers' => __('Đơn vị vận chuyển', 'ship-depot-translate'),
                'fee_modify' => __('Thay đổi phí vận chuyển', 'ship-depot-translate'),
                //Tạm đóng chức năng này
                // 'custom_css' => __('Thay đổi CSS', 'ship-depot-translate'),
                'about' => __('Giới thiệu & Trợ giúp', 'ship-depot-translate'),
                'classic_checkout_direction' => __('Hướng dẫn trở về Classic Checkout', 'ship-depot-translate')
            );

            return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
        }

        /**
         *	Output sections
         */
        public function output_sections()
        {

            global $current_section;

            $sections = $this->get_sections();

            if (empty($sections) || 1 === sizeof($sections)) {
                return;
            }

            echo '<ul class="subsubsub">';
            $array_keys = array_keys($sections);

            foreach ($sections as $id => $label) {
                echo '<li class="' . esc_attr(($current_section == $id ? 'selected-section' : '')) . ' ' . esc_attr('section-setting') . ' sd-sub-section"><a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=' . $this->id . '&section=' . sanitize_title($id))) . '" class="' . esc_attr(($current_section == $id ? 'current' : '')) . ' ">' . esc_html($label) . '</a></li>';
            }

            echo '</ul><br class="clear" />';
        }

        /**
         *	Output the settings
         */
        public function output()
        {
            // add free_vs_pro page
            if (isset($_GET['page']) && $_GET['page'] == 'wc-settings' && isset($_GET['tab']) && $_GET['tab'] == 'sd_settings') {
                if (isset($_GET['section'])) {
                    switch ($_GET['section']) {
                        case '':
                            do_action('sd_general_settings_section');
                            break;
                        case 'couriers':
                            do_action('sd_couriers_section');
                            break;
                        case 'fee_modify':
                            do_action('sd_fee_modify_section');
                            break;
                            //Tạm đóng chức năng này
                            // case 'custom_css':
                            //     do_action('sd_custom_css_section');
                            break;
                        case 'about':
                            do_action('sd_about_section');
                            break;
                        case 'classic_checkout_direction':
                            do_action('sd_classic_checkout_direction_section');
                            break;
                    }
                } else {
                    do_action('sd_general_settings_section');
                }
            }
        }

        public function save()
        {
            global $current_tab;
            global $current_section;
            Ship_Depot_Logger::wrlog('[Ship_Depot_Settings][Ship_Depot_Settings][save] $current_tab: ' . json_encode($current_tab));
            Ship_Depot_Logger::wrlog('[Ship_Depot_Settings][Ship_Depot_Settings][save] $current_section: ' . json_encode($current_section));
            if (!Ship_Depot_Helper::is_admin_user()) {
                //if logged user is not administrator =? return
                Ship_Depot_Logger::wrlog('[Ship_Depot_Settings][Ship_Depot_Settings][save] logged user is not administrator');
                return;
            }

            if ($current_tab == 'sd_settings') {
                if (is_null($current_section) || empty($current_section)) {
                    //Empty is general setting section
                    $this->save_general_settings();
                } else {
                    switch ($current_section) {
                        case 'couriers':
                            $this->save_couriers();
                            break;
                        case 'fee_modify':
                            $this->save_fee_modify();
                            break;
                            //Tạm đóng chức năng này
                            // case 'custom_css':
                            //     $this->save_custom_css();
                            //     break;
                    }
                }
            }
        }

        public function save_general_settings()
        {
            if (is_null($_POST) || empty($_POST)) {
                return false;
            }

            Ship_Depot_Logger::wrlog('==================================================================================================================');
            if (!isset($_POST['sd_general_nonce']) || !wp_verify_nonce($_POST['sd_general_nonce'], 'sd_general')) {
                print 'Sorry, your nonce did not verify.';
                Ship_Depot_Logger::wrlog('[save_general_settings] Nonce did not verify');
                exit;
            } else {
                Ship_Depot_Logger::wrlog('[save_general_settings] _POST: ' . print_r($_POST, true));
                // process form data
                //Check APIkey change
                $new_api_key = Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST['sd_api_key'])) ? '' : sanitize_text_field($_POST['sd_api_key']);
                $current_api_key = get_option('sd_api_key');
                $this->save_option('sd_api_key');
                Ship_Depot_Logger::wrlog('[save_general_settings] new_api_key: ' . $new_api_key);
                Ship_Depot_Logger::wrlog('[save_general_settings] current_api_key: ' . $current_api_key);
                if (!Ship_Depot_Helper::check_null_or_empty($new_api_key)) {
                    if (Ship_Depot_Helper::check_null_or_empty($current_api_key) || $current_api_key != $new_api_key) {
                        //Update data with new API Key
                        Ship_Depot_Logger::wrlog('[save_general_settings] Update data with new API Key');
                        Ship_Depot_Data::update_data_new_api_key();
                    }
                } else {
                    if (!Ship_Depot_Helper::check_null_or_empty($current_api_key)) {
                        Ship_Depot_Logger::wrlog('[save_general_settings] Clear data because use clear API Key');
                        Ship_Depot_Data::update_data_new_api_key();
                    }
                }
                //
                //

                $this->save_checkbox('cb_show_shipping_fee_detail', 'sd_show_shipping_fee_detail');
                $this->save_checkbox('cb_auto_create_shipping', 'sd_auto_create_shipping');

                // $this->save_radio_button('sd_status_auto_create_shipping');
                //Change sd_status_auto_create_shipping to checkbox => select many status
                if (isset($_POST['sd_status_auto_create_shipping'])) {
                    if (is_array($_POST['sd_status_auto_create_shipping'])) {
                        $statuses = '';
                        foreach ($_POST['sd_status_auto_create_shipping'] as $status) {
                            $statuses = $statuses . $status . ',';
                        }

                        if (!Ship_Depot_Helper::check_null_or_empty($statuses)) {
                            $statuses = trim($statuses, ',');
                        }
                        Ship_Depot_Logger::wrlog('[save_general_settings] list statuses auto create shipping: ' . print_r($statuses, true));
                        update_option('sd_status_auto_create_shipping', $statuses);
                    } else {
                        $this->save_radio_button('sd_status_auto_create_shipping');
                    }
                }

                delete_option('cb_set_status_for_delivery');
                delete_option('sd_status_for_delivery');

                delete_option('cb_set_status_delivery_success');
                delete_option('sd_status_delivery_success');

                delete_option('sd_status_delivery_failed');
                delete_option('sd_status_delivery_failed');

                delete_option('cb_set_status_for_control');
                delete_option('sd_status_for_control');

                $list_statuses = [];
                foreach ($_POST['shipdepot']['status_id'] as $id) {
                    $status_id = sanitize_text_field($id);
                    $json_data = sanitize_text_field($_POST['shipdepot'][$status_id]['data']);
                    $status = new Ship_Depot_Shipping_Status(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($json_data));
                    $status->IsUsed = isset($_POST['shipdepot'][$status_id]['IsUsed']) ? Ship_Depot_Helper::get_data_from_checkbox(sanitize_text_field($_POST['shipdepot'][$status_id]['IsUsed'])) : false;
                    if ($status->IsUsed) {
                        $status->WooOrderStatusID = sanitize_text_field($_POST['shipdepot'][$status_id]['WooOrderStatusID']);
                    } else {
                        $status->WooOrderStatusID = "";
                    }

                    array_push($list_statuses, $status);
                }

                Ship_Depot_Logger::wrlog('[save_general_settings] list_statuses: ' . print_r($list_statuses, true));
                update_option('sd_update_order_statuses', json_encode($list_statuses, JSON_UNESCAPED_UNICODE));
                //
                // $this->save_checkbox('cb_set_status_for_delivery', 'sd_set_status_for_delivery');
                // $this->save_option('sd_status_for_delivery');

                // $this->save_checkbox('cb_set_status_delivery_success', 'sd_set_status_delivery_success');
                // $this->save_option('sd_status_delivery_success');

                // $this->save_checkbox('cb_set_status_delivery_failed', 'sd_set_status_delivery_failed');
                // $this->save_option('sd_status_delivery_failed');

                // $this->save_checkbox('cb_set_status_for_control', 'sd_set_status_for_control');
                // $this->save_option('sd_status_for_control');

                $this->save_checkbox('cb_accept_debug_log', 'sd_accept_debug_log');
            }
        }

        public function save_couriers()
        {
            if (is_null($_POST) || empty($_POST)) {
                return false;
            }

            Ship_Depot_Logger::wrlog('==================================================================================================================');
            if (!isset($_POST['sd_couriers_nonce']) || !wp_verify_nonce($_POST['sd_couriers_nonce'], 'sd_couriers')) {
                print 'Sorry, your nonce did not verify.';
                Ship_Depot_Logger::wrlog('[save_couriers] Nonce did not verify');
                exit;
            } else {
                // process form data
                Ship_Depot_Logger::wrlog('[save_couriers] data: ' . print_r($_POST, true), 999999);
                Ship_Depot_Logger::wrlog('[save_couriers] courier: ' . print_r(sanitize_text_field($_POST['couriers_id']), true));
                $list_couriers =  [];
                foreach ($_POST['couriers_id'] as $id) {
                    $courier_id = sanitize_text_field($id);
                    Ship_Depot_Logger::wrlog('[save_couriers] courier_id: ' . $courier_id);
                    $courier = new Ship_Depot_Courier();
                    $courier->CourierID = intval($courier_id);
                    if (isset($_POST[$courier_id])) {
                        Ship_Depot_Logger::wrlog('[save_couriers] courier data: ' . print_r($_POST[$courier_id], true), 999999);
                        //Get old data
                        if (isset($_POST[$courier_id]['Data'])) {
                            $json_cour =  sanitize_text_field($_POST[$courier_id]['Data']);
                            Ship_Depot_Logger::wrlog('[save_couriers] json_cour: ' . print_r($json_cour, true));
                            $courier = new Ship_Depot_Courier(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($json_cour));
                        }

                        //Get is used 
                        if (isset($_POST[$courier_id]['IsUsed'])) {
                            $courier->IsUsed = Ship_Depot_Helper::get_data_from_checkbox(sanitize_text_field($_POST[$courier_id]['IsUsed']));
                        } else {
                            $courier->IsUsed = false;
                        }

                        //Get COD
                        if (isset($_POST[$courier_id]['HasCOD'])) {
                            $courier->HasCOD = Ship_Depot_Helper::get_data_from_checkbox(sanitize_text_field($_POST[$courier_id]['HasCOD']));
                        } else {
                            $courier->HasCOD = false;
                        }

                        //Get service
                        $courier->ListServices = [];
                        if (isset($_POST[$courier_id]['service_id']) && !is_null(sanitize_text_field($_POST[$courier_id]['service_id']))) {
                            Ship_Depot_Logger::wrlog('[save_couriers] service_id: ' . print_r(sanitize_text_field($_POST[$courier_id]['service_id']), true));
                            foreach ($_POST[$courier_id]['service_id'] as $id) {
                                $serv_id = sanitize_text_field($id);
                                $service = new Ship_Depot_Courier_Service();
                                $service->ServiceCode = $serv_id;
                                if (isset($_POST[$courier_id][$serv_id])) {
                                    if (isset($_POST[$courier_id][$serv_id]['Data'])) {
                                        $json_serv =  sanitize_text_field($_POST[$courier_id][$serv_id]['Data']);
                                        Ship_Depot_Logger::wrlog('[save_couriers] json_serv: ' . print_r($json_serv, true));
                                        $service = new Ship_Depot_Courier_Service(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($json_serv));
                                    }

                                    Ship_Depot_Logger::wrlog('[save_couriers] service: ' . print_r(sanitize_text_field($_POST[$courier_id][$serv_id]), true));
                                    if (isset($_POST[$courier_id][$serv_id]['IsUsed'])) {
                                        $service->IsUsed = Ship_Depot_Helper::get_data_from_checkbox(sanitize_text_field($_POST[$courier_id][$serv_id]['IsUsed']));
                                    } else {
                                        $service->IsUsed = false;
                                    }
                                } else {
                                    $service->IsUsed = false;
                                }
                                array_push($courier->ListServices, $service);
                            }
                        }

                        //Get cod failed
                        if (isset($_POST[$courier_id]['CODFailed'])) {
                            $courier->CODFailed->IsUsed = Ship_Depot_Helper::get_data_from_checkbox(sanitize_text_field($_POST[$courier_id]['CODFailed']['IsUsed']));
                            $courier->CODFailed->CODFailedAmount = intval(str_replace('.', '', sanitize_text_field($_POST[$courier_id]['CODFailed']['CODFailedAmount'])));
                            $courier->CODFailed->ContentCheckout->IsShow = Ship_Depot_Helper::get_data_from_checkbox(sanitize_text_field($_POST[$courier_id]['CODFailed']['ContentCheckout']['IsShow']));
                            $courier->CODFailed->ContentCheckout->Content = trim(sanitize_text_field($_POST[$courier_id]['CODFailed']['ContentCheckout']['Content']));
                            $courier->CODFailed->ContentShippingLabel->IsShow = Ship_Depot_Helper::get_data_from_checkbox(sanitize_text_field($_POST[$courier_id]['CODFailed']['ContentShippingLabel']['IsShow']));
                            $courier->CODFailed->ContentShippingLabel->Content = trim(sanitize_text_field($_POST[$courier_id]['CODFailed']['ContentShippingLabel']['Content']));
                        }

                        //Get ship from station
                        if (isset($_POST[$courier_id]['ShipFromStation'])) {
                            Ship_Depot_Logger::wrlog('[save_couriers][' . $courier_id . '] ShipFromStation data: ' . print_r($_POST[$courier_id]['ShipFromStation'], true));
                            $courier->ShipFromStation->IsActive = Ship_Depot_Helper::get_data_from_checkbox(sanitize_text_field($_POST[$courier_id]['ShipFromStation']['IsActive']));
                            Ship_Depot_Logger::wrlog('[save_couriers][' . $courier_id . '] ShipFromStation IsActive: ' . print_r($courier->ShipFromStation->IsActive, true));
                            if ($courier->ShipFromStation->IsActive) {
                                $province_code = sanitize_text_field($_POST[$courier_id]['ShipFromStation']['ProvinceCode']);
                                Ship_Depot_Logger::wrlog('[save_couriers][' . $courier_id . '] ShipFromStation province_code: ' . print_r($province_code, true));
                                $province = new Ship_Depot_Province(Ship_Depot_Address_Helper::get_province_by_id($province_code));
                                $district_code = sanitize_text_field($_POST[$courier_id]['ShipFromStation']['DistrictCode']);
                                Ship_Depot_Logger::wrlog('[save_couriers][' . $courier_id . '] ShipFromStation district_code: ' . print_r($district_code, true));
                                if ($district_code == "-1") {
                                    $district = new Ship_Depot_District();
                                } else {
                                    $district = new Ship_Depot_District(Ship_Depot_Address_Helper::get_district_by_id($province_code, $district_code));
                                }

                                $courier->ShipFromStation->Province = $province;
                                $courier->ShipFromStation->District = $district;
                                if (isset($_POST[$courier_id]['ShipFromStation']['SelectedStation']) && !Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST[$courier_id]['ShipFromStation']['SelectedStation']))) {
                                    $courier->ShipFromStation->Station = new Ship_Depot_Station(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode(sanitize_text_field($_POST[$courier_id]['ShipFromStation']['SelectedStation'])));
                                } else {
                                    $courier->ShipFromStation->Station = new Ship_Depot_Station();
                                    if (isset($_POST[$courier_id]['ShipFromStation']['StationID']) && !Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST[$courier_id]['ShipFromStation']['StationID'])) && sanitize_text_field($_POST[$courier_id]['ShipFromStation']['StationID']) != "-1") {
                                        $courier->ShipFromStation->Station->Id = intval(sanitize_text_field($_POST[$courier_id]['ShipFromStation']['StationID']));
                                    }
                                }
                            } else {
                                $courier->ShipFromStation->Province = new Ship_Depot_Province();
                                $courier->ShipFromStation->District = new Ship_Depot_District();
                                $courier->ShipFromStation->Station = new Ship_Depot_Station();
                            }
                        }

                        //Get PASInfo
                        if (isset($_POST[$courier_id]['PASAddress'])) {
                            $courier->PASAddress = trim(sanitize_text_field($_POST[$courier_id]['PASAddress']));
                        }

                        if (isset($_POST[$courier_id]['PASPhone'])) {
                            $courier->PASPhone = trim(sanitize_text_field($_POST[$courier_id]['PASPhone']));
                        }
                    }

                    array_push($list_couriers, $courier);
                }
                Ship_Depot_Logger::wrlog('[save_couriers] List couriers aft: ' . print_r($list_couriers, true), 999999);
                $json = json_encode($list_couriers, JSON_UNESCAPED_UNICODE);
                Ship_Depot_Logger::wrlog('[save_couriers] List couriers json: ' . $json);
                // update_option('sd_setting_courier', $json);
                $url = SHIP_DEPOT_HOST_API . '/ShopSetting/SaveCourierSettings';
                $shop_api_key = get_option('sd_api_key');
                $request_param = array(
                    "list_couriers" => $list_couriers
                );
                $rs = Ship_Depot_Helper::http_post_php($url, $request_param, array('ShopAPIKey' => $shop_api_key));
                Ship_Depot_Logger::wrlog('[save_couriers] result call api save: ' . print_r($rs, true));
                if ($rs->Code >= 0) {
                    update_option('sd_setting_courier', $json);
                }
            }
        }

        public function save_fee_modify()
        {
            if (is_null($_POST) || empty($_POST)) {
                return false;
            }

            Ship_Depot_Logger::wrlog('==================================================================================================================');
            if (!isset($_POST['sd_fee_modify_nonce']) || !wp_verify_nonce($_POST['sd_fee_modify_nonce'], 'sd_fee_modify')) {
                print 'Sorry, your nonce did not verify.';
                Ship_Depot_Logger::wrlog('[save_fee_modify] Nonce did not verify');
                exit;
            } else {
                // process form data
                Ship_Depot_Logger::wrlog('[save_fee_modify] _POST: ' . print_r($_POST, true));
                $fee_setting = new Ship_Depot_Fee_Setting();
                if (isset($_POST['fee_modify']['isActive'])) {
                    $fee_setting->IsActive = Ship_Depot_Helper::get_data_from_checkbox(sanitize_text_field($_POST['fee_modify']['isActive']));
                } else {
                    $fee_setting->IsActive = false;
                }

                if (isset($_POST['fee_modify']['fees']['id'])) {
                    Ship_Depot_Logger::wrlog('[save_fee_modify] list fee_id: ' . print_r(sanitize_text_field($_POST['fee_modify']['fees']['id']), true));
                    $fee_setting->ListFeeMarkups = [];
                    foreach ($_POST['fee_modify']['fees']['id'] as $key => $id) {
                        $fee_index_id = sanitize_text_field($id);
                        $fee = new Ship_Depot_Fee_Markup();
                        if (Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST[$fee_index_id]['id']))) {
                            //Add new
                            $fee->ID = $fee_index_id;
                        } else {
                            $fee->ID = Ship_Depot_Helper::check_null_or_empty(($_POST[$fee_index_id]['id'])) ? '' : sanitize_text_field($_POST[$fee_index_id]['id']);
                        }

                        if (isset($_POST[$fee_index_id]['isActive'])) {
                            $fee->IsActive = Ship_Depot_Helper::get_data_from_checkbox(sanitize_text_field($_POST[$fee_index_id]['isActive']));
                        } else {
                            $fee->IsActive = false;
                        }

                        Ship_Depot_Logger::wrlog('[save_fee_modify] ' . $fee_index_id . ' data: ' . print_r(($_POST[$fee_index_id]), true));
                        //Order conditions
                        $fee->ListOrderConditions = [];
                        if (isset($_POST[$fee_index_id]['if'])) {
                            foreach ($_POST[$fee_index_id]['if'] as $index => $id) {
                                $if_id = sanitize_text_field($id);
                                Ship_Depot_Logger::wrlog('[save_fee_modify] ' . $if_id . ' data: ' . print_r(($_POST[$fee_index_id][$if_id]), true));
                                $if = new Ship_Depot_Fee_Markup_Order_Condition();
                                $idx = intval($index);
                                $if->ID = 'if' . ($idx + 1);
                                $if->Type = sanitize_text_field($_POST[$fee_index_id][$if_id]['type']);
                                switch ($if->Type) {
                                    case "total_amount":
                                        $from = sanitize_text_field($_POST[$fee_index_id][$if_id]['from']);
                                        $to = sanitize_text_field($_POST[$fee_index_id][$if_id]['to']);
                                        Ship_Depot_Logger::wrlog('[save_fee_modify] ' . $if_id . ' from: ' . print_r($from, true));
                                        Ship_Depot_Logger::wrlog('[save_fee_modify] ' . $if_id . ' to: ' . print_r($to, true));
                                        Ship_Depot_Logger::wrlog('[save_fee_modify] ' . $if_id . ' check_null_or_empty to: ' . print_r(Ship_Depot_Helper::check_null_or_empty(($to)), true));
                                        Ship_Depot_Logger::wrlog('[save_fee_modify] ' . $if_id . ' check_null_or_empty from: ' . print_r(Ship_Depot_Helper::check_null_or_empty(($from)), true));
                                        $if->FromValue = Ship_Depot_Helper::check_null_or_empty($from) ? -9999 : intval(str_replace('.', '', $from));
                                        $if->ToValue = Ship_Depot_Helper::check_null_or_empty($to) ? -9999 : intval(str_replace('.', '', $to));
                                        break;
                                    case "item_existed":
                                        $if->FixedValue = Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST[$fee_index_id][$if_id]['value'])) ? -9999 : sanitize_text_field($_POST[$fee_index_id][$if_id]['value']);
                                        break;
                                    case "item_quantity":
                                        $if->FromValue = Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST[$fee_index_id][$if_id]['from'])) ? -9999 : str_replace('.', '', sanitize_text_field($_POST[$fee_index_id][$if_id]['from']));
                                        $if->ToValue = Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST[$fee_index_id][$if_id]['to'])) ? -9999 : str_replace('.', '', sanitize_text_field($_POST[$fee_index_id][$if_id]['to']));
                                        break;
                                    case "payment_transfer":
                                        break;
                                    case "payment_cod":
                                        break;
                                }
                                array_push($fee->ListOrderConditions, $if);
                            }
                        }
                        //Fee option
                        Ship_Depot_Logger::wrlog('[save_fee_modify] FeeOption: ' . print_r(sanitize_text_field($_POST[$fee_index_id]['feeOption']), true));
                        $feeOpt = new Ship_Depot_Fee_Markup_Option();
                        $feeOpt->Type = sanitize_text_field($_POST[$fee_index_id]['feeOption']['type']);
                        if ($feeOpt->Type == 'depend_shipdepot') {
                            $feeOpt->SubType = sanitize_text_field($_POST[$fee_index_id]['feeOption']['sub']);
                        } else {
                            $feeOpt->SubType = '';
                        }

                        $feeOpt->Value = Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST[$fee_index_id]['feeOption']['value'])) ? 0 : str_replace('.', '', sanitize_text_field($_POST[$fee_index_id]['feeOption']['value']));
                        $fee->Option = $feeOpt;
                        //Time Apply
                        Ship_Depot_Logger::wrlog('[save_fee_modify] timeApply: ' . print_r(sanitize_text_field($_POST[$fee_index_id]['timeApply']), true));
                        $timeApply = new Ship_Depot_Fee_Markup_Time_Condition();
                        $timeApply->Type = sanitize_text_field($_POST[$fee_index_id]['timeApply']['type']);
                        if ($timeApply->Type == 'period') {
                            if (!Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST[$fee_index_id]['timeApply']['from']))) {
                                $date_from = sanitize_text_field($_POST[$fee_index_id]['timeApply']['from']);
                                $date_from_format = DateTime::createFromFormat(get_option('date_format'), $date_from)->format('Y/m/d');
                                $timeApply->FromDate = strtotime($date_from_format);
                            }
                            if (!Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST[$fee_index_id]['timeApply']['to']))) {
                                $date_to = sanitize_text_field($_POST[$fee_index_id]['timeApply']['to']);
                                $date_to_format = DateTime::createFromFormat(get_option('date_format'), $date_to)->format('Y/m/d');
                                $timeApply->ToDate = strtotime($date_to_format);
                            }
                        }
                        $fee->TimeApply = $timeApply;
                        //Couriers Apply
                        Ship_Depot_Logger::wrlog('[save_fee_modify] couriersApply: ' . print_r(sanitize_text_field($_POST[$fee_index_id]['couriersApply']), true));
                        $couriersApply = new Ship_Depot_Fee_Markup_Courier_Condition();
                        $couriersApply->Type = sanitize_text_field($_POST[$fee_index_id]['couriersApply']['type']);
                        if ($couriersApply->Type == 'customize') {
                            $couriersApply->ListCouriers = [];
                            if (isset($_POST[$fee_index_id]['couriersApply']['courierSelected'])) {
                                foreach ($_POST[$fee_index_id]['couriersApply']['courierSelected'] as $courierID => $isSelected) {
                                    $courier = new Ship_Depot_Fee_Markup_Courier_Service();
                                    $courier->CourierID = sanitize_text_field($courierID);
                                    $courier->IsActive = Ship_Depot_Helper::get_data_from_checkbox(sanitize_text_field($isSelected));
                                    $courier->ListServicesSelected = [];
                                    if ($courier->IsActive) {
                                        if (isset($_POST[$fee_index_id]['couriersApply'][$courierID]['serviceSelected'])) {
                                            foreach ($_POST[$fee_index_id]['couriersApply'][$courierID]['serviceSelected'] as $servID => $isSelected) {
                                                if (Ship_Depot_Helper::get_data_from_checkbox(sanitize_text_field($isSelected))) {
                                                    array_push($courier->ListServicesSelected, sanitize_text_field($servID));
                                                }
                                            }
                                        }
                                    }
                                    array_push($couriersApply->ListCouriers, $courier);
                                }
                            }
                        }
                        $fee->CourierApply = $couriersApply;
                        //Notes
                        $fee->Notes = Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST[$fee_index_id]['notes'])) ? '' : sanitize_textarea_field($_POST[$fee_index_id]['notes']);
                        array_push($fee_setting->ListFeeMarkups, $fee);
                    }
                }
                Ship_Depot_Logger::wrlog('[save_fee_modify] fee_setting object: ' . print_r($fee_setting, true));
                update_option('sd_setting_fee', json_encode($fee_setting, JSON_UNESCAPED_UNICODE));
            }
        }

        public function save_custom_css()
        {
            if (is_null($_POST) || empty($_POST)) {
                return false;
            }

            Ship_Depot_Logger::wrlog('==================================================================================================================');
            if (!isset($_POST['sd_custom_css_nonce']) || !wp_verify_nonce($_POST['sd_custom_css_nonce'], 'sd_custom_css')) {
                print 'Sorry, your nonce did not verify.';
                Ship_Depot_Logger::wrlog('[save_custom_css] Nonce did not verify');
                exit;
            } else {
                Ship_Depot_Logger::wrlog('[save_custom_css] _POST: ' . print_r($_POST, true));
                Ship_Depot_Logger::wrlog('[save_custom_css] sd_checkout_custom_css: ' . print_r($_POST['sd_checkout_custom_css'], true));
                Ship_Depot_Logger::wrlog('[save_custom_css] sd_restore_css: ' . print_r($_POST['sd_restore_css'], true));
                $file_update = SHIP_DEPOT_DIR_PATH . 'assets/css/fe-checkout-custom.css';
                if (isset($_POST['sd_restore_css']) && sanitize_text_field($_POST['sd_restore_css']) == 'true') {
                    //Restore data
                    $df_css = file_get_contents(SHIP_DEPOT_DIR_PATH . 'assets/css/fe-checkout-custom.default');
                    file_put_contents($file_update, $df_css);
                } else {
                    //Update new data
                    file_put_contents($file_update, str_replace('\"', '"', $_POST['sd_checkout_custom_css']));
                }
            }
        }

        public function save_option($id)
        {
            if (is_null($_POST) || empty($_POST)) {
                return false;
            }

            $value = isset($_POST[$id]) ? sanitize_text_field($_POST[$id]) : '';
            Ship_Depot_Logger::wrlog('[Ship_Depot_Settings][save_option] ' . $id . ':' . json_encode($value));
            update_option($id, $value);
            Ship_Depot_Logger::wrlog('[Ship_Depot_Settings][save_option] update_option - ' . $id . ' = ' . $value);
            Ship_Depot_Logger::wrlog('[Ship_Depot_Settings][save_option] get_option - ' . $id . ' aft: ' . json_encode(get_option($id)));
        }

        public function save_checkbox($fromid, $key_save)
        {
            if (is_null($_POST) || empty($_POST)) {
                return false;
            }

            $raw_value = isset($_POST[$fromid]) ? sanitize_text_field($_POST[$fromid]) : 'no';
            Ship_Depot_Logger::wrlog('[Ship_Depot_Settings][save_checkbox] ' . $fromid . ':' . json_encode($raw_value));
            $value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
            update_option($key_save, $value);
            Ship_Depot_Logger::wrlog('[Ship_Depot_Settings][save_checkbox] update_option - ' . $key_save . ' = ' . $value);
            Ship_Depot_Logger::wrlog('[Ship_Depot_Settings][save_checkbox] get_option - ' . $key_save . ' aft: ' . json_encode(get_option($key_save)));
        }

        public function save_radio_button($id)
        {
            if (is_null($_POST) || empty($_POST)) {
                return false;
            }

            $value = isset($_POST[$id]) ? sanitize_text_field($_POST[$id]) : '';
            Ship_Depot_Logger::wrlog('[Ship_Depot_Settings][save_radio_button] ' . $id . ':' . json_encode($value));
            update_option($id, $value);
            Ship_Depot_Logger::wrlog('[Ship_Depot_Settings][save_radio_button] update_option - ' . $id . ' = ' . $value);
            Ship_Depot_Logger::wrlog('[Ship_Depot_Settings][save_radio_button] get_option - ' . $id . ' aft: ' . json_encode(get_option($id)));
        }

        public static function get_list_status_map()
        {
        }
    }

endif;

new Ship_Depot_Settings;
