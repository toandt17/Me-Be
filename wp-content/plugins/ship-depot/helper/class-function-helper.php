<?php
defined('ABSPATH') || exit;

use Automattic\WooCommerce\Utilities\OrderUtil;

if (!class_exists('Ship_Depot_Helper')) {
    class Ship_Depot_Helper
    {
        public static function is_admin_user()
        {
            return current_user_can('manage_options');
        }

        public static function alert($msg)
        {
            echo "<script type='text/javascript'>alert('" . esc_js($msg) . "');</script>";
        }

        public static function get_data_from_checkbox($data): bool
        {
            if (isset($data)) {
                if (is_null($data) || empty($data)) return false;
                return '1' === $data || 'yes' === $data || 'on' === $data ? true : false;
            } else {
                return false;
            }
        }

        public static function currency_format($number, $suffix = '₫')
        {
            try {
                if (!empty($number)) {
                    return number_format($number, 0, ',', '.') . "{$suffix}";
                } else {
                    return 0 . "{$suffix}";
                }
            } catch (Exception $e) {
                return $number;
            }
        }

        public static function number_format($number)
        {
            try {
                if (!empty($number)) {
                    return number_format($number, 0, ',', '.');
                }
            } catch (Exception $e) {
                return $number;
            }
        }

        public static function is_woocommerce_activated()
        {
            return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins', array()))) ||
                (is_multisite() && array_key_exists('woocommerce/woocommerce.php', get_site_option('active_sitewide_plugins', array())));
        }

        public static function check_null_or_empty($valueCheck)
        {
            if (!isset($valueCheck)) return true;
            if (is_null($valueCheck) || $valueCheck == '') return true;
            return false;
        }

        public static function format_phone($phone)
        {
            if (!self::check_null_or_empty($phone)) {
                $phone_length = strlen($phone);
                $format = '';
                if ($phone_length > 3 && $phone_length <= 6) {
                    $format = substr($phone, 0, 3) . '-' . substr($phone, 3, $phone_length - 3);
                } else if ($phone_length > 6) {
                    $format = substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6, $phone_length - 6);
                }

                return $format;
            }
            return $phone;
        }

        public static function format_utc_to_date_time($str_date_time)
        {
            $dt = new DateTime($str_date_time, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
            return $dt->format(get_option('date_format') . ' ' . get_option('time_format'));
        }

        public static function format_utc_to_date($str_date_time)
        {
            $dt = new DateTime($str_date_time, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
            return $dt->format(get_option('date_format'));
        }

        public static function http_get_php($url, $header = array())
        {
            Ship_Depot_Logger::wrlog('[http_get_php] url: ' . $url);
            $result = new stdClass();
            $curl = curl_init();
            try {
                // OPTIONS:
                curl_setopt($curl, CURLOPT_URL, $url);
                if (is_array($header)) {
                    $i_header = $header;
                    $header = [];
                    foreach ($i_header as $param => $value) {
                        $header[] = "$param: $value";
                    }
                }
                $header = array_merge(array('Content-type: application/json; charset=utf-8'), $header);
                Ship_Depot_Logger::wrlog('[http_get_php] header: ' . print_r($header, true));
                curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                // EXECUTE:
                $output = curl_exec($curl);
                $info = curl_getinfo($curl);
                curl_close($curl);
                Ship_Depot_Logger::wrlog('[http_get_php] output info: ' . print_r($info, true), 99999);
                Ship_Depot_Logger::wrlog('[http_get_php] output data: ' . print_r($output, true));

                $http_code = isset($info['http_code']) ? $info['http_code'] : 0;
                Ship_Depot_Logger::wrlog('[http_post_php] http_code: ' . print_r($http_code, true));
                if (!$output || $http_code != 200) {
                    Ship_Depot_Logger::wrlog("[http_get_php] Error");
                    $result->Code = -1000;
                    $result->Message = 'HTTP GET error. Error message: Connection Failure.' . $http_code != 0 ? 'HTTP Code = ' . $http_code : '';
                    $result->Data = '';
                } else {
                    $result = json_decode($output);
                }
            } catch (Exception $ex) {
                Ship_Depot_Logger::wrlog('[http_get_php] Exception: ' . print_r($ex, true));
                $result->Code = -1001;
                $result->Msg = $ex->getMessage();
                $result->Data = '';
            }
            return $result;
        }

        public static function http_post_php($url, $body_input = array(), $header = array())
        {
            Ship_Depot_Logger::wrlog('[http_post_php] url: ' . $url);
            $json_post = json_encode($body_input);
            Ship_Depot_Logger::wrlog('[http_post_php] json_post: ' . $json_post);
            $result = new stdClass();
            try {
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($curl, CURLOPT_TIMEOUT, 30); //timeout in seconds
                if (is_array($header)) {
                    $i_header = $header;
                    $header = [];
                    foreach ($i_header as $param => $value) {
                        $header[] = "$param: $value";
                    }
                }
                $header = array_merge(array('Content-type: application/json; charset=utf-8'), $header);
                Ship_Depot_Logger::wrlog('[http_post_php] header: ' . print_r($header, true));
                curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $json_post);
                $output = curl_exec($curl);
                $info = curl_getinfo($curl);
                curl_close($curl);
                //
                Ship_Depot_Logger::wrlog('[http_post_php] output info: ' . print_r($info, true), 99999);
                Ship_Depot_Logger::wrlog('[http_post_php] output data: ' . print_r($output, true));

                $http_code = isset($info['http_code']) ? $info['http_code'] : 0;
                Ship_Depot_Logger::wrlog('[http_post_php] http_code: ' . print_r($http_code, true));
                if (!$output || $http_code != 200) {
                    Ship_Depot_Logger::wrlog("[http_post_php] Error.");
                    $result->Code = -1000;
                    $msg = 'HTTP POST error. Error message: Connection Failure.';
                    if ($http_code != 0) {
                        $msg = $msg . ' HTTP Code = ' . $http_code;
                    }
                    $result->Msg = $msg;
                    $result->Data = '';
                } else {
                    $result = json_decode($output);
                }
            } catch (Exception $ex) {
                Ship_Depot_Logger::wrlog('[http_post_php] Exception: ' . print_r($ex, true));
                $result->Code = -1001;
                $result->Msg = $ex->getMessage();
                $result->Data = '';
            }

            return $result;
        }

        public static function http_get($url, $header_input = array())
        {
            Ship_Depot_Logger::wrlog('[http_get] url: ' . $url);
            Ship_Depot_Logger::wrlog('[http_get] header_input: ' . print_r($header_input, true));
            $header = array('Content-Type' => 'application/json');
            foreach ($header_input as $key => $value) {
                $header = array($key => $value);
            }
            Ship_Depot_Logger::wrlog('[http_get] headers: ' . print_r($header, true));
            $response = wp_remote_get(
                esc_url($url),
                array(
                    'timeout'     => 45,
                    'headers' => $header
                )
            );
            $http_code = wp_remote_retrieve_response_code($response);
            $output = wp_remote_retrieve_body($response);
            $result = new stdClass();
            Ship_Depot_Logger::wrlog('[http_get] http_code: ' . print_r($http_code, true));
            Ship_Depot_Logger::wrlog('[http_get] output: ' . print_r($output, true));
            if (is_wp_error($response)) {
                Ship_Depot_Logger::wrlog("[http_get] Reponse WP Error.");
                $result->Code = -1000;
                $result->Msg = $response->get_error_message();
                $result->Data = '';
            } else if (!$output || $http_code != 200) {
                Ship_Depot_Logger::wrlog("[http_get] HTTP Error or Output error.");
                $result->Code = -1001;
                $result->Message = 'HTTP GET error. Error message: Connection Failure.' . $http_code != 0 ? 'HTTP Code = ' . $http_code : '';
                $result->Data = '';
            } else {
                $result = json_decode($output);
            }
            Ship_Depot_Logger::wrlog('[http_get] result: ' . print_r($result, true));
            return $result;
        }

        /**
         * Calculate shipping fee
         * @param string $url URL request.
         * @param array $body_input Body of request.
         * @param array $header_input Header of request.
         */
        public static function http_post($url, $body_input = array(), $header_input = array())
        {
            Ship_Depot_Logger::wrlog('[http_post] url: ' . $url);
            // Ship_Depot_Logger::wrlog('[http_post] body_input: ' . print_r($body_input, true));
            Ship_Depot_Logger::wrlog('[http_post] json_body_input: ' . print_r(json_encode($body_input), true));
            Ship_Depot_Logger::wrlog('[http_post] header_input: ' . print_r($header_input, true));
            $header = array();
            $header['Expect'] = '';
            foreach ($header_input as $key => $value) {
                $header[$key] = $value;
            }

            Ship_Depot_Logger::wrlog('[http_post] headers: ' . print_r($header, true));
            $response = wp_remote_post(
                esc_url($url),
                array(
                    'headers' => $header,
                    'body' => $body_input
                )
            );
            $http_code = wp_remote_retrieve_response_code($response);
            $output = wp_remote_retrieve_body($response);
            $result = new stdClass();
            Ship_Depot_Logger::wrlog('[http_post] http_code: ' . print_r($http_code, true));
            Ship_Depot_Logger::wrlog('[http_post] output: ' . print_r($output, true));
            if (is_wp_error($response)) {
                Ship_Depot_Logger::wrlog("[http_post] Reponse WP Error. Message: " . print_r($response, true));
                $result->Code = -1000;
                $result->Msg = $response->get_error_message();
                $result->Data = '';
            } else if (!$output || $http_code != 200) {
                Ship_Depot_Logger::wrlog("[http_post] HTTP Error or Output error.");
                Ship_Depot_Logger::wrlog("[http_post] http_code: " . $http_code);
                $result->Code = -1001;
                $msg = 'HTTP POST error. Error message: Connection Failure.';
                if ($http_code != 0) {
                    $msg = $msg . ' HTTP Code = ' . $http_code;
                }
                $result->Msg = $msg;
                $result->Data = '';
            } else {
                $result = json_decode($output);
            }
            return $result;
        }



        public static function ConvertToShipDepotWeight($weight, $unit = '')
        {
            if (!$weight || is_null($weight)) {
                return 0;
            }

            if (self::check_null_or_empty($unit)) {
                $unit = get_option('woocommerce_weight_unit');
            }

            $weight = floatval($weight);
            $sd_weight_unit = strtolower(SHIP_DEPOT_WEIGHT_UNIT);
            $woo_weight_unit = strtolower($unit);
            if ($sd_weight_unit == 'g' || $sd_weight_unit == 'gram') {
                if ($woo_weight_unit == 'g' || $woo_weight_unit == 'gram') {
                    return $weight;
                } else if ($woo_weight_unit == 'kg') {
                    return $weight * 1000;
                } else if ($woo_weight_unit == 'lbs') {
                    return $weight * 453.6;
                } else if ($woo_weight_unit == 'oz') {
                    return $weight * 28.35;
                }
            } else if ($sd_weight_unit == 'kg' || $sd_weight_unit == 'kilogram') {
                if ($woo_weight_unit == 'g' || $woo_weight_unit == 'gram') {
                    return $weight / 1000;
                } else if ($woo_weight_unit == 'kg') {
                    return $weight;
                } else if ($woo_weight_unit == 'lbs') {
                    return $weight * 453.6 / 1000;
                } else if ($woo_weight_unit == 'oz') {
                    return $weight * 28.35 / 1000;
                }
            } else if ($sd_weight_unit == 'lbs') {
                if ($woo_weight_unit == 'g' || $woo_weight_unit == 'gram') {
                    return $weight * 0.002205;
                } else if ($woo_weight_unit == 'kg') {
                    return $weight * 2.205;
                } else if ($woo_weight_unit == 'lbs') {
                    return $weight;
                } else if ($woo_weight_unit == 'oz') {
                    return $weight * 0.0625;
                }
            }
        }

        public static function ConvertToShipDepotDimension($dimension, $unit = '')
        {
            if (!$dimension || is_null($dimension)) {
                return 0;
            }

            if (self::check_null_or_empty($unit)) {
                $unit = get_option('woocommerce_dimension_unit');
            }
            $dimension = floatval($dimension);
            $sd_dimen_unit = strtolower(SHIP_DEPOT_MEASUREMENT_UNIT);
            $woo_dimen_unit = strtolower($unit);
            if ($sd_dimen_unit == 'cm') {
                if ($woo_dimen_unit == 'm') {
                    return $dimension * 100;
                } else if ($woo_dimen_unit == 'cm') {
                    return $dimension;
                } else if ($woo_dimen_unit == 'mm') {
                    return $dimension * 0.1;
                } else if ($woo_dimen_unit == 'in') {
                    return $dimension * 2.54;
                } else if ($woo_dimen_unit == 'yd') {
                    return $dimension * 91.44;
                }
            } else if ($sd_dimen_unit == 'in' || $sd_dimen_unit == 'inch') {
                if ($woo_dimen_unit == 'm') {
                    return $dimension * 39.37;
                } else if ($woo_dimen_unit == 'cm') {
                    return $dimension * 0.3937;
                } else if ($woo_dimen_unit == 'mm') {
                    return $dimension * 0.03937;
                } else if ($woo_dimen_unit == 'in') {
                    return $dimension;
                } else if ($woo_dimen_unit == 'yd') {
                    return $dimension * 36;
                }
            }
        }

        public static function ParseObjectToJsonHTML($object)
        {
            $json_html = json_encode($object, JSON_UNESCAPED_UNICODE);
            $json_html = str_replace('"', "'", $json_html);
            return $json_html;
        }

        public static function CleanJsonFromHTML($json)
        {
            if (Ship_Depot_Helper::check_null_or_empty($json)) {
                return $json;
            }

            $json = stripslashes($json);
            $json = str_replace("'", '"', $json);
            Ship_Depot_Logger::wrlog('[CleanJsonFromHTML] $json aft: ' . $json);
            return $json;
        }

        public static function CleanJsonFromHTMLAndDecode($json)
        {
            if (Ship_Depot_Helper::check_null_or_empty($json)) {
                return null;
            }
            $json = Ship_Depot_Helper::CleanJsonFromHTML($json);
            return json_decode($json);
        }

        public static function ParseObjectToArray($obj): array
        {
            if (is_object($obj)) {
                return json_decode(json_encode($obj), true);
            }
            return [];
        }

        public static function CompareData($old_data, $new_data)
        {
            Ship_Depot_Logger::wrlog('[CompareData] old_data: ' . print_r($old_data, true));
            Ship_Depot_Logger::wrlog('[CompareData] new_data: ' . print_r($new_data, true));
            if (!$old_data || Ship_Depot_Helper::check_null_or_empty($old_data)) {
                if (!Ship_Depot_Helper::check_null_or_empty($new_data)) {
                    Ship_Depot_Logger::wrlog('[diff_data] diff = true');
                    return true;
                }
            } else {
                if ($old_data != $new_data) {
                    Ship_Depot_Logger::wrlog('[CompareData] diff = true');
                    return true;
                }
            }
            Ship_Depot_Logger::wrlog('[CompareData] diff = false');
            return false;
        }

        public static function IsHPOS(): bool
        {
            if (Ship_Depot_Helper::is_woocommerce_activated()) {
                if (OrderUtil::custom_orders_table_usage_is_enabled()) {
                    // Ship_Depot_Logger::wrlog('HPOS usage is enabled');
                    // HPOS usage is enabled.
                    // Arrange meta boxes
                    return true;
                } else {
                    // Ship_Depot_Logger::wrlog('Traditional CPT-based orders are in use');
                    // Traditional CPT-based orders are in use.
                    return false;
                }
            } else {
                return false;
            }
        }

        public static function UpdateOrderMetadata($order_id, $meta_key, $meta_value, $save_after = true)
        {
            Ship_Depot_Logger::wrlog('[UpdateOrderMetadata] order_id: ' . print_r($order_id, true));
            Ship_Depot_Logger::wrlog('[UpdateOrderMetadata] meta_key: ' . print_r($meta_key, true));
            Ship_Depot_Logger::wrlog('[UpdateOrderMetadata] meta_value: ' . print_r($meta_value, true));
            // Ship_Depot_Logger::wrlog('[UpdateOrderMetadata] unhook.');

            $order = wc_get_order($order_id);
            Ship_Depot_Logger::wrlog('[UpdateOrderMetadata] update_meta_data.');
            $order->update_meta_data($meta_key, $meta_value);
            if ($save_after) {
                if (function_exists('sd_save_wc_order_other_fields')) {
                    // unhook this function so it doesn't loop infinitely
                    Ship_Depot_Logger::wrlog('[UpdateOrderMetadata] unhook.');
                    remove_action('save_post', 'sd_save_wc_order_other_fields', 10, 1);
                    //
                }

                //Save order meta data to db
                Ship_Depot_Logger::wrlog('[UpdateOrderMetadata] order save.');
                $order->save();

                if (function_exists('sd_save_wc_order_other_fields')) {
                    // re-hook this function.
                    Ship_Depot_Logger::wrlog('[UpdateOrderMetadata] re-hook.');
                    add_action('save_post', 'sd_save_wc_order_other_fields', 10, 1);
                }
            }
        }

        public static function UpdateOrderMetadataWOSave($order, $meta_key, $meta_value)
        {
            Ship_Depot_Logger::wrlog('[UpdateOrderMetadataWOSave] meta_key: ' . print_r($meta_key, true));
            Ship_Depot_Logger::wrlog('[UpdateOrderMetadataWOSave] meta_value: ' . print_r($meta_value, true));
            Ship_Depot_Logger::wrlog('[UpdateOrderMetadataWOSave] update_meta_data.');
            $order->update_meta_data($meta_key, $meta_value);
        }

        public static function GetOrderMetadata($order_id_or_order, $meta_key = '', $single = true)
        {
            if (is_numeric($order_id_or_order)) {
                $order = wc_get_order($order_id_or_order);
            } else {
                $order = $order_id_or_order;
            }

            $value = $order->get_meta($meta_key, $single, 'edit');
            return $value;
        }
    }
}

// add_action('http_api_debug', function ($response, $context, $class, $parsed_args, $url) {
//     Ship_Depot_Logger::wrlog('[http_api_debug_hook] $url: ' . print_r($url, true));
//     Ship_Depot_Logger::wrlog('[http_api_debug_hook] $parsed_args: ' . print_r($parsed_args, true), 99999);
//     Ship_Depot_Logger::wrlog('[http_api_debug_hook] $response: ' . print_r($response, true), 99999);
// }, 10, 5);
