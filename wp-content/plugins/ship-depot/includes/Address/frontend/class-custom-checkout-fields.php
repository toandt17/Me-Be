<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Custom_fields')) {
    class Ship_Depot_Custom_fields
    {

        function __construct()
        {
            add_filter('woocommerce_default_address_fields', array($this, 'sd_woocommerce_default_address_fields'), 999, 1);
            add_filter('woocommerce_checkout_fields', array($this, 'sd_custom_override_fields'));
        }

        function sd_custom_override_fields($fields)
        {
            try {
                $user_id = get_current_user_id();
                $types = array('billing', 'shipping');
                foreach ($types as $item) {
                    $option_ct = array('' => SD_SELECT_CITY_TEXT) + Ship_Depot_Address_Helper::get_all_province_key_value(true);

                    $option_dt = array('' => SD_SELECT_DISTRICT_TEXT) + Ship_Depot_Address_Helper::get_all_district_key_value(get_user_meta($user_id, "{$item}_city", true));

                    $option_wd = array('' => SD_SELECT_WARD_TEXT) + Ship_Depot_Address_Helper::get_all_wards_key_value(get_user_meta($user_id, "{$item}_city", true), get_user_meta($user_id, "{$item}_district", true));


                    $fields[$item][$item . '_city'] = array(
                        'label'       => esc_html__('Tỉnh/Thành Phố', 'ship-depot-translate'),
                        'required'    => true,
                        'description' => '',
                        'type'        => 'select',
                        'options'     => $option_ct
                    );

                    // $fields[$item][$item . '_state'] = array(
                    //     'label'       => esc_html__('Quận/Huyện', 'ship-depot-translate'),
                    //     'required'    => true,
                    //     'description' => '',
                    //     'type'        => 'select',
                    //     'options'     => $option_dt,
                    //     'priority'    => 50
                    // );

                    // $fields[$item][$item . '_address_2'] = array(
                    //     'label'       => esc_html__('Phường/Xã', 'ship-depot-translate'),
                    //     'required'    => true,
                    //     'description' => '',
                    //     'type'        => 'select',
                    //     'options'     => $option_wd,
                    //     'priority'    => 60
                    // );

                    $fields[$item][$item . '_district'] = array(
                        'label'       => esc_html__('Quận/Huyện', 'ship-depot-translate'),
                        'required'    => true,
                        'description' => '',
                        'type'        => 'select',
                        'options'     => $option_dt,
                        'priority'    => 50
                    );

                    $fields[$item][$item . '_ward'] = array(
                        'label'       => esc_html__('Phường/Xã', 'ship-depot-translate'),
                        'required'    => true,
                        'description' => '',
                        'type'        => 'select',
                        'options'     => $option_wd,
                        'priority'    => 60
                    );

                    $sd_country_field = array(
                        'label'        => $fields[$item][$item . '_country']['label'],
                        'required'     => $fields[$item][$item . '_country']['required'],
                        'class'        => $fields[$item][$item . '_country']['class'],
                        'autocomplete' => $fields[$item][$item . '_country']['autocomplete'],
                        'type'         => 'select',
                        'options'      => array('VN' => 'Vietnam'),
                        'priority'     => $fields[$item][$item . '_country']['priority']
                    );
                    $fields[$item][$item . '_country'] = $sd_country_field;

                    //Change position last_name and first_name: Remove field size and position classes
                    if (isset($fields[$item][$item . '_last_name']) && !is_null($fields[$item][$item . '_last_name']) && isset($fields[$item][$item . '_last_name']['class']) && !is_null($fields[$item][$item . '_last_name']['class'])) {
                        if (false !== ($key = array_search('form-row-first', $fields[$item][$item . '_first_name']['class']))) {
                            unset($fields[$item][$item . '_first_name']['class'][$key]);
                            $fields[$item][$item . '_first_name']['class'] = array_merge($fields[$item][$item . '_first_name']['class'], array('form-row-last'));
                        }
                    } else {
                        Ship_Depot_Logger::wrlog('[Ship_Depot_Custom_fields][sd_custom_override_fields] field first name or its class is null.');
                    }


                    if (isset($fields[$item][$item . '_last_name']) && !is_null($fields[$item][$item . '_last_name']) && isset($fields[$item][$item . '_last_name']['class']) && !is_null($fields[$item][$item . '_last_name']['class'])) {
                        if (false !== ($key = array_search('form-row-last', $fields[$item][$item . '_last_name']['class']))) {
                            unset($fields[$item][$item . '_last_name']['class'][$key]);
                            $fields[$item][$item . '_last_name']['class'] = array_merge($fields[$item][$item . '_last_name']['class'], array('form-row-first'));
                        }
                    } else {
                        Ship_Depot_Logger::wrlog('[Ship_Depot_Custom_fields][sd_custom_override_fields] field last name or its class is null.');
                    }
                }
            } catch (Exception $e) {
                Ship_Depot_Logger::wrlog('[Ship_Depot_Custom_fields][sd_custom_override_fields] Exception: ' . $e->getMessage());
            }
            return $fields;
        }

        function sd_woocommerce_default_address_fields($fields)
        {
            try {
                unset($fields['company']);
                unset($fields['postcode']);
                unset($fields['state']);
                unset($fields['address_2']);
                $fields['first_name']['label'] = esc_html__('Tên', 'ship-depot-translate');

                $fields['last_name']['label'] = esc_html__('Họ', 'ship-depot-translate');

                $fields['country']['label'] = esc_html__('Quốc Gia', 'ship-depot-translate');

                $fields['city']['label'] = esc_html__('Tỉnh/Thành Phố', 'ship-depot-translate');

                $fields['address_1']['label'] = esc_html__('Địa chỉ', 'ship-depot-translate');

                //
                $fields['last_name']['priority'] = 10;
                $fields['first_name']['priority'] = 20;
                $fields['country']['priority'] = 30;
                $fields['city']['priority'] = 40;
                $fields['address_1']['priority'] = 70;
            } catch (Exception $e) {
                Ship_Depot_Logger::wrlog('[Ship_Depot_Custom_fields][sd_woocommerce_default_address_fields] Exception: ' . $e->getMessage());
            }
            //Ship_Depot_Logger::wrlog('[sd_woocommerce_default_address_fields] fields: ' . print_r($fields, true));
            if (!Ship_Depot_Address_Helper::can_shipping_vietnam()) {
                Ship_Depot_Logger::wrlog('[sd_woocommerce_default_address_fields] This page is not support shipping to Vietnam.');
                return $fields;
            }
            //Ship_Depot_Logger::wrlog('[sd_woocommerce_default_address_fields] fields aft: ' . print_r($fields, true));
            return $fields;
        }

        function check_fields_existed($list_fields, $key)
        {
            if (isset($list_fields[$key]) && !is_null($list_fields[$key]) && !empty($list_fields[$key])) {
                return true;
            }
            return false;
        }
    }

    new Ship_Depot_Custom_fields();
}


add_action(
    'woocommerce_blocks_loaded',
    function () {
        $option_ct = array(['value' => -1, 'label' => SD_SELECT_CITY_TEXT]) + Ship_Depot_Address_Helper::get_all_province_key_value_block_checkout(true);
        __experimental_woocommerce_blocks_register_checkout_field(
            array(
                'id'       => 'namespace/sd-city',
                'label'    => esc_html__('Tỉnh/Thành Phố', 'ship-depot-translate'),
                'required' => true,
                'location' => 'address',
                'type'     => 'select',
                'options'  => $option_ct
            )
        );
    }
);
