<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Custom_Admin_Order_Fields')) {
    class Ship_Depot_Custom_Admin_Order_Fields
    {
        function __construct()
        {

            // Khai báo các fields nào được hiển thị, fields nào không và label của chúng khi xem chi tiết 1 order trong admin thông qua giá trị show
            add_filter('woocommerce_admin_billing_fields', array($this, 'sd_admin_billing_fields'), 999);
            add_filter('woocommerce_admin_shipping_fields', array($this, 'sd_admin_shipping_fields'), 999);

            // Sửa link google maps
            //add_filter('woocommerce_shipping_address_map_url_parts', array($this, 'fsw_woocommerce_shipping_address_map_url_parts'), 10, 1);
        }

        function sd_admin_billing_fields($billing_fields)
        {
            ////Ship_Depot_Logger::wrlog('[sd_admin_billing_fields] begin ');
            global $theorder;
            $billing_ct = 0;
            $billing_ds = 0;
            if (get_the_ID()) {
                $billing_ct = get_post_meta(get_the_ID(), '_billing_city', true);
                $billing_ds = get_post_meta(get_the_ID(), '_billing_district', true);
            } else if ($theorder) {
                $billing_ct = $theorder->get_billing_city();
                $billing_ds = $theorder->get_meta('_billing_district', true);
            }

            $array_arrange = ['last_name', 'first_name', 'country', 'city', 'district', 'ward', 'address_1', 'phone', 'email'];
            $option_ct = array('' => SD_SELECT_CITY_TEXT) + Ship_Depot_Address_Helper::get_all_province_key_value();

            $option_dt = array('' => SD_SELECT_DISTRICT_TEXT) + Ship_Depot_Address_Helper::get_all_district_key_value($billing_ct);

            $option_wd = array('' => SD_SELECT_WARD_TEXT) + Ship_Depot_Address_Helper::get_all_wards_key_value($billing_ct, $billing_ds);
            unset($billing_fields['state']);
            unset($billing_fields['address_2']);
            unset($billing_fields['company']);
            unset($billing_fields['postcode']);
            $backup_fields = $billing_fields; //Clone data for re-add fields after custom field
            ////Ship_Depot_Logger::wrlog('[sd_admin_billing_fields] backup_fields: ' . print_r($backup_fields, true));
            ////Ship_Depot_Logger::wrlog('[sd_admin_billing_fields] billing_fields: ' . print_r($billing_fields, true));
            foreach ($billing_fields as $key => $field) {
                unset($billing_fields[$key]);
            }

            if (array_key_exists('copy_billing', $backup_fields)) {
                $billing_fields['copy_billing'] = $backup_fields['copy_billing'];
            }

            foreach ($array_arrange as $key) {

                if ($key == 'city') {
                    $billing_fields[$key] = array(
                        'label'       => esc_html__('Tỉnh/Thành Phố', 'ship-depot-translate'),
                        'description' => '',
                        'type'        => 'select',
                        'options'     => $option_ct,
                        'class'       => 'wc-enhanced-select __sd_city',
                        'show'     => false,
                    );
                } else if ($key == 'district') {
                    $billing_fields[$key] = array(
                        'label'       => esc_html__('Quận', 'ship-depot-translate'),
                        'description' => '',
                        'type'        => 'select',
                        'options'     => $option_dt,
                        'class'       => 'wc-enhanced-select __sd_district',
                        'show'     => false,
                    );
                } else if ($key == 'ward') {
                    $billing_fields[$key] = array(
                        'label'       => esc_html__('Phường', 'ship-depot-translate'),
                        'description' => '',
                        'type'        => 'select',
                        'options'     => $option_wd,
                        'class'       => 'wc-enhanced-select __sd_ward',
                        'show'     => false,
                    );
                } else {
                    //normal fields
                    ////Ship_Depot_Logger::wrlog('[sd_admin_shipping_fields] key: ' . $key);
                    if (!isset($backup_fields[$key]) || is_null($backup_fields[$key]) || empty($backup_fields[$key])) continue;
                    ////Ship_Depot_Logger::wrlog('[sd_admin_shipping_fields] key existed: ' . $key);
                    $billing_fields[$key] = $backup_fields[$key];
                }
            }

            foreach ($backup_fields as $key => $field) {
                if (!array_key_exists($key, $billing_fields)) {
                    $billing_fields[$key] = $backup_fields[$key];
                }
            }

            if ($this->check_fields_existed($billing_fields, 'last_name')) {
                $billing_fields['last_name']['label'] = esc_html__('Họ', 'ship-depot-translate');
            }

            if ($this->check_fields_existed($billing_fields, 'first_name')) {
                $billing_fields['first_name']['label'] = esc_html__('Tên', 'ship-depot-translate');
            }

            if ($this->check_fields_existed($billing_fields, 'phone')) {
                $billing_fields['phone']['label'] = esc_html__('Điện Thoại', 'ship-depot-translate', 'html');
            }

            if ($this->check_fields_existed($billing_fields, 'address_1')) {
                $billing_fields['address_1']['label'] = esc_html__('Địa chỉ', 'ship-depot-translate', 'html');
            }

            if ($this->check_fields_existed($billing_fields, 'country')) {
                $billing_fields['country']['label'] = esc_html__('Quốc Gia', 'ship-depot-translate', 'html');
                $billing_fields['country']['options'] = ['VN' => $backup_fields['country']['options']['VN']];
            }

            if ($this->check_fields_existed($billing_fields, 'email')) {
                $billing_fields['email']['label'] = esc_html__('Email', 'ship-depot-translate', 'html');
                $billing_fields['email']['description'] = '';
            }
            return $billing_fields;
        }

        function sd_admin_shipping_fields($shipping_fields)
        {
            global $theorder;
            // Ship_Depot_Logger::wrlog('[sd_admin_shipping_fields] begin ');
            // $order_id = $theorder->get_id();
            // Ship_Depot_Logger::wrlog('[sd_admin_shipping_fields] order_id: ' . print_r($order_id, true));
            // Ship_Depot_Logger::wrlog('[sd_admin_shipping_fields] order get_meta_data: ' . print_r($theorder->get_meta_data(), true));
            $shipping_ct = 0;
            $shipping_ds = 0;
            if (get_the_ID()) {
                $shipping_ct = get_post_meta(get_the_ID(), '_shipping_city', true);
                $shipping_ds = get_post_meta(get_the_ID(), '_shipping_district', true);
            } else if ($theorder) {
                $shipping_ct = $theorder->get_shipping_city();
                $shipping_ds = $theorder->get_meta('_shipping_district', true);
            }

            // Ship_Depot_Logger::wrlog('[sd_admin_shipping_fields] shipping city: ' . print_r($shipping_ct, true));
            // Ship_Depot_Logger::wrlog('[sd_admin_shipping_fields] shipping district: ' . print_r($shipping_ds, true));
            // Ship_Depot_Logger::wrlog('[sd_admin_shipping_fields] shipping ward: ' . print_r($shipping_wd, true));

            $array_arrange = ['last_name', 'first_name', 'country', 'city', 'district', 'ward', 'address_1', 'phone', 'email'];
            $option_ct = array('' => SD_SELECT_CITY_TEXT) + Ship_Depot_Address_Helper::get_all_province_key_value();

            $option_dt = array('' => SD_SELECT_DISTRICT_TEXT) + Ship_Depot_Address_Helper::get_all_district_key_value($shipping_ct);

            $option_wd = array('' => SD_SELECT_WARD_TEXT) + Ship_Depot_Address_Helper::get_all_wards_key_value($shipping_ct, $shipping_ds);
            unset($shipping_fields['state']);
            unset($shipping_fields['address_2']);
            unset($shipping_fields['company']);
            unset($shipping_fields['postcode']);
            $backup_fields = $shipping_fields; //Clone data for re-add fields after custom field
            ////Ship_Depot_Logger::wrlog('[sd_admin_shipping_fields] backup_fields: ' . print_r($backup_fields, true));
            ////Ship_Depot_Logger::wrlog('[sd_admin_shipping_fields] shipping_fields: ' . print_r($shipping_fields, true));
            foreach ($shipping_fields as $key => $field) {
                unset($shipping_fields[$key]);
            }

            if (array_key_exists('copy_billing', $backup_fields)) {
                $shipping_fields['copy_billing'] = $backup_fields['copy_billing'];
            }

            foreach ($array_arrange as $key) {
                if ($key == 'city') {
                    $shipping_fields[$key] = array(
                        'label'       => esc_html__('Tỉnh/Thành Phố', 'ship-depot-translate'),
                        'description' => '',
                        'type'        => 'select',
                        'options'     => $option_ct,
                        'class'       => 'wc-enhanced-select __sd_city',
                        'show'     => false,
                    );
                } else if ($key == 'district') {
                    $shipping_fields[$key] = array(
                        'label'       => esc_html__('Quận', 'ship-depot-translate'),
                        'description' => '',
                        'type'        => 'select',
                        'options'     => $option_dt,
                        'class'       => 'wc-enhanced-select __sd_district',
                        'show'     => false,
                    );
                } else if ($key == 'ward') {
                    $shipping_fields[$key] = array(
                        'label'       => esc_html__('Phường', 'ship-depot-translate'),
                        'description' => '',
                        'type'        => 'select',
                        'options'     => $option_wd,
                        'class'       => 'wc-enhanced-select __sd_ward',
                        'show'     => false,
                    );
                } else {
                    //normal fields
                    ////Ship_Depot_Logger::wrlog('[sd_admin_shipping_fields] key: ' . $key);
                    if (!isset($backup_fields[$key]) || is_null($backup_fields[$key]) || empty($backup_fields[$key])) continue;
                    ////Ship_Depot_Logger::wrlog('[sd_admin_shipping_fields] key existed: ' . $key);
                    $shipping_fields[$key] = $backup_fields[$key];
                }
            }

            foreach ($backup_fields as $key => $field) {
                if (!array_key_exists($key, $shipping_fields)) {
                    $shipping_fields[$key] = $backup_fields[$key];
                }
            }

            if ($this->check_fields_existed($shipping_fields, 'last_name')) {
                $shipping_fields['last_name']['label'] = esc_html__('Họ', 'ship-depot-translate');
            }

            if ($this->check_fields_existed($shipping_fields, 'first_name')) {
                $shipping_fields['first_name']['label'] = esc_html__('Tên', 'ship-depot-translate');
            }

            if ($this->check_fields_existed($shipping_fields, 'address_1')) {
                $shipping_fields['address_1']['label'] = esc_html__('Địa chỉ', 'ship-depot-translate', 'html');
            }

            if ($this->check_fields_existed($shipping_fields, 'phone')) {
                $shipping_fields['phone']['label'] = esc_html__('Điện Thoại', 'ship-depot-translate', 'html');
            }

            if ($this->check_fields_existed($shipping_fields, 'country')) {
                $shipping_fields['country']['label'] = esc_html__('Quốc Gia', 'ship-depot-translate', 'html');
                $shipping_fields['country']['options'] = ['VN' => $backup_fields['country']['options']['VN']];
            }

            if ($this->check_fields_existed($shipping_fields, 'email')) {
                $shipping_fields['email']['label'] = esc_html__('Email', 'ship-depot-translate', 'html');
                $shipping_fields['email']['description'] = '';
            }
            return $shipping_fields;
        }

        function check_fields_existed($list_fields, $key)
        {
            if (isset($list_fields[$key]) && !is_null($list_fields[$key]) && !empty($list_fields[$key])) {
                return true;
            }
            return false;
        }
    }

    new Ship_Depot_Custom_Admin_Order_Fields();
}
