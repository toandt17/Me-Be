<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Custom_Profile_Fields')) {
    class Ship_Depot_Custom_Profile_Fields
    {
        function __construct()
        {
            add_filter('woocommerce_customer_meta_fields', array($this, 'sd_admin_address_field'), 10, 1);
        }

        function sd_admin_address_field($admin_fields)
        {
            //Ship_Depot_Logger::wrlog('[sd_admin_address_field] begin ');
            if (!Ship_Depot_Address_Helper::can_shipping_vietnam()) return $admin_fields;

            // If is current user's profile (profile.php)
            if (defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE) {
                // get user_id when show/save your profile
                $user_id = get_current_user_id();
            } elseif (!empty($_GET['user_id']) && is_numeric($_GET['user_id'])) {
                // get user_id when show user profile
                $user_id = sanitize_key($_GET['user_id']);
            } elseif (!empty(sanitize_key($_POST['user_id'])) && is_numeric(sanitize_key($_POST['user_id']))) {
                // get user_id when save user profile
                $user_id = sanitize_key($_POST['user_id']);
            } else {
                return $admin_fields;
            }
            //Ship_Depot_Logger::wrlog('[sd_admin_address_field] check success ');
            // return $admin_fields;
            $types = array('billing', 'shipping');
            $array_arrange = ['_last_name', '_first_name', '_country', '_city', '_district', '_ward', '_address_1', '_phone', '_email'];
            foreach ($types as $item) {
                $option_ct = array('' => SD_SELECT_CITY_TEXT) + Ship_Depot_Address_Helper::get_all_province_key_value();

                $option_dt = array('' => SD_SELECT_DISTRICT_TEXT) + Ship_Depot_Address_Helper::get_all_district_key_value(get_user_meta($user_id, "{$item}_city", true));

                $option_wd = array('' => SD_SELECT_WARD_TEXT) + Ship_Depot_Address_Helper::get_all_wards_key_value(get_user_meta($user_id, "{$item}_city", true), get_user_meta($user_id, "{$item}_district", true));
                unset($admin_fields[$item]['fields'][$item . '_state']);
                unset($admin_fields[$item]['fields'][$item . '_address_2']);
                unset($admin_fields[$item]['fields'][$item . '_company']);
                unset($admin_fields[$item]['fields'][$item . '_postcode']);



                $backup_fields = $admin_fields[$item]['fields']; //Clone data for re-add fields after custom field
                //Ship_Depot_Logger::wrlog('[sd_admin_address_field] backup_fields: ' . print_r($backup_fields, true));
                //Ship_Depot_Logger::wrlog('[sd_admin_address_field] admin_fields: ' . print_r($admin_fields[$item]['fields'], true));
                foreach ($admin_fields[$item]['fields'] as $key => $field) {
                    unset($admin_fields[$item]['fields'][$key]);
                }

                if (array_key_exists('copy_billing', $backup_fields)) {
                    $admin_fields[$item]['fields']['copy_billing'] = $backup_fields['copy_billing'];
                }

                foreach ($array_arrange as $key) {
                    $exact_key = $item . $key;

                    if ($key == '_city') {
                        $admin_fields[$item]['fields'][$exact_key] = array(
                            'label'       => esc_html__('Tỉnh/Thành Phố', 'ship-depot-translate'),
                            'description' => '',
                            'type'        => 'select',
                            'options'     => $option_ct,
                            'class'       => 'wc-enhanced-select __sd_city',
                        );
                    } else if ($key == '_district') {
                        $admin_fields[$item]['fields'][$exact_key] = array(
                            'label'       => esc_html__('Quận', 'ship-depot-translate'),
                            'description' => '',
                            'type'        => 'select',
                            'options'     => $option_dt,
                            'class'       => 'wc-enhanced-select __sd_district'
                        );
                    } else if ($key == '_ward') {
                        $admin_fields[$item]['fields'][$exact_key] = array(
                            'label'       => esc_html__('Phường', 'ship-depot-translate'),
                            'description' => '',
                            'type'        => 'select',
                            'options'     => $option_wd,
                            'class'       => 'wc-enhanced-select __sd_ward'
                        );
                    } else {
                        //normal fields
                        //Ship_Depot_Logger::wrlog('[sd_admin_address_field] exact_key: ' . $exact_key);
                        if (!isset($backup_fields[$exact_key]) || is_null($backup_fields[$exact_key]) || empty($backup_fields[$exact_key])) continue;
                        //Ship_Depot_Logger::wrlog('[sd_admin_address_field] exact_key existed: ' . $exact_key);
                        $admin_fields[$item]['fields'][$exact_key] = $backup_fields[$exact_key];
                    }
                }

                foreach ($backup_fields as $key => $field) {
                    if (!array_key_exists($key, $admin_fields[$item]['fields'])) {
                        $admin_fields[$item]['fields'][$key] = $backup_fields[$key];
                    }
                }

                if ($this->check_fields_existed($admin_fields[$item]['fields'], $item . '_last_name')) {
                    $admin_fields[$item]['fields'][$item . '_last_name']['label'] = esc_html__('Họ', 'ship-depot-translate');
                }

                if ($this->check_fields_existed($admin_fields[$item]['fields'], $item . '_first_name')) {
                    $admin_fields[$item]['fields'][$item . '_first_name']['label'] = esc_html__('Tên', 'ship-depot-translate');
                }

                if ($this->check_fields_existed($admin_fields[$item]['fields'], $item . '_phone')) {
                    $admin_fields[$item]['fields'][$item . '_phone']['label'] = esc_html__('Điện Thoại', 'ship-depot-translate', 'html');
                }

                if ($this->check_fields_existed($admin_fields[$item]['fields'], $item . '_address_1')) {
                    $admin_fields[$item]['fields'][$item . '_address_1']['label'] = esc_html__('Địa chỉ', 'ship-depot-translate', 'html');
                }

                if ($this->check_fields_existed($admin_fields[$item]['fields'], $item . '_country')) {
                    $admin_fields[$item]['fields'][$item . '_country']['label'] = esc_html__('Quốc Gia', 'ship-depot-translate', 'html');
                    $admin_fields[$item]['fields'][$item . '_country']['options'] = ['VN' => $backup_fields[$item . '_country']['options']['VN']];
                }

                if ($this->check_fields_existed($admin_fields[$item]['fields'], $item . '_email')) {
                    $admin_fields[$item]['fields'][$item . '_email']['label'] = esc_html__('Email', 'ship-depot-translate', 'html');
                    $admin_fields[$item]['fields'][$item . '_email']['description'] = '';
                }
            }
            return $admin_fields;
        }

        function check_fields_existed($list_fields, $key)
        {
            if (isset($list_fields[$key]) && !is_null($list_fields[$key]) && !empty($list_fields[$key])) {
                return true;
            }
            return false;
        }
    }

    new Ship_Depot_Custom_Profile_Fields();
}
