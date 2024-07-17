<?php
add_action('init', 'update_district_ajax');
function update_district_ajax()
{
    if (!isset($_GET['sd-ajax']) || 'update_district' !== $_GET['sd-ajax']) return;
    $province_code = (isset($_POST['province_code'])) ? sanitize_text_field(($_POST['province_code'])) : '';
    Ship_Depot_Address_Helper::get_districts_option_by_province_code($province_code);
    wp_die();
}

add_action('init', 'update_ward_ajax');
function update_ward_ajax()
{
    if (!isset($_GET['sd-ajax']) || 'update_ward' !== $_GET['sd-ajax']) return;

    if (isset($_POST['district_code']) && isset($_POST['province_code'])) {
        $district_code = sanitize_text_field($_POST['district_code']);
        $province_code = sanitize_text_field($_POST['province_code']);
        Ship_Depot_Address_Helper::get_wards_option_by_district_code($province_code, $district_code);
    }
    wp_die();
}



add_action('wp_ajax_load_customer_address', 'load_customer_address_init');
add_action('wp_ajax_nopriv_load_customer_address', 'load_customer_address_init');
function load_customer_address_init()
{

    if (isset($_POST['user_id'])) {
        $user_id = sanitize_text_field($_POST['user_id']);
        // Get an instance of the WC_Customer Object from the user ID
        $customer = new WC_Customer($user_id);
        Ship_Depot_Logger::wrlog('[load_customer_address_init] customer: ' . print_r($customer, true));
        $reponse = new stdClass();
        $reponse->billing = $customer->get_billing();
        $reponse->billing['district'] = $customer->get_meta('billing_district', true);
        $reponse->billing['ward'] = $customer->get_meta('billing_ward', true);
        $reponse->shipping = $customer->get_shipping();
        $reponse->shipping['district'] = $customer->get_meta('shipping_district', true);
        $reponse->shipping['ward'] = $customer->get_meta('shipping_ward', true);
        wp_send_json_success($reponse);
    } else {
        wp_die(); //bắt buộc phải có khi kết thúc
    }
}
