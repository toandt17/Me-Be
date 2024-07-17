<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_Logger')) {
    class Ship_Depot_Logger
    {

        public static $logger;

        /**
         * Utilize WC logger class
         *
         * @since 4.0.0
         * @version 4.0.0
         */
        public static function wrlog($message, $limit_length = 500)
        {
            if (!class_exists('WC_Logger')) {
                return;
            }

            if (!Ship_Depot_Logger::get_can_write_log()) {
                return;
            }

            $log_file_name = "ship-depot-log";
            if (empty(self::$logger)) {
                self::$logger = wc_get_logger();
            }

            if (strlen($message) > $limit_length) {
                $message = '---------------- Sub string 0-' . $limit_length . "---------------- \n" .  substr($message, 0, $limit_length) . "\n" . '---------------- End sub string ----------------';
            }

            $log_entry = "\n" . $message . "\n";

            self::$logger->debug($log_entry, ['source' => $log_file_name]);
        }

        public static function wh_log($log_msg)
        {
            try {
                if (!class_exists('WC_Logger')) {
                    return;
                }

                if (!Ship_Depot_Logger::get_can_write_log()) {
                    return;
                }

                $log_filename = "logs";
                if (!file_exists($log_filename)) {
                    // create directory/folder uploads.
                    mkdir($log_filename, 0777, true);
                }

                $log_file_data = $log_filename . '/ship-depot-log_' . date('d-M-Y') . '.log';
                // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
                file_put_contents($log_file_data,  date("y-m-d H:i:s.") . gettimeofday()["usec"] . ': ' . $log_msg . "\n", FILE_APPEND);
            } catch (Exception $e) {
            }
        }

        private static function get_can_write_log()
        {
            $can_log = get_option('sd_accept_debug_log');
            if (!Ship_Depot_Helper::check_null_or_empty($can_log) && $can_log == 'yes') {
                return true;
            }
            return false;
        }
    }
}
