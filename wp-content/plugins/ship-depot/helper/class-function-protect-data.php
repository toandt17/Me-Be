<?php
defined('ABSPATH') || exit;

if (!class_exists('Ship_Depot_ProtectData')) {
    class Ship_Depot_ProtectData
    {

        public static function EncryptData($plaintext )
        {
            $password = get_option('sd_api_key');
            if(Ship_Depot_Helper::check_null_or_empty($password) || Ship_Depot_Helper::check_null_or_empty($plaintext)){
                return '';
            }
            
            $method = 'aes-256-cbc';
            // Must be exact 32 chars (256 bit)
            $password = substr(hash('sha256', $password, true), 0, 32);

            // IV must be exact 16 chars (128 bit)
            $iv = chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0);
            $encrypted = base64_encode(openssl_encrypt($plaintext, $method, $password, OPENSSL_RAW_DATA, $iv));

            // Ship_Depot_Logger::wrlog('cipher=' . $method . "\n");
            // Ship_Depot_Logger::wrlog('Password:' . $password . "\n");
            // Ship_Depot_Logger::wrlog('encrypted: ' . $encrypted . "\n");
            // Ship_Depot_Logger::wrlog('decrypted to: ' . $decrypted . "\n\n");
            return $encrypted;
        }

        public static function DecryptData($encrypted)
        {
            $password = get_option('sd_api_key');
            if(Ship_Depot_Helper::check_null_or_empty($password) || Ship_Depot_Helper::check_null_or_empty($encrypted)){
                return '';
            }
            
            $method = 'aes-256-cbc';
            // Must be exact 32 chars (256 bit)
            $password = substr(hash('sha256', $password, true), 0, 32);

            // IV must be exact 16 chars (128 bit)
            $iv = chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0);
            
            $decrypted = openssl_decrypt(base64_decode($encrypted), $method, $password, OPENSSL_RAW_DATA, $iv);

            // Ship_Depot_Logger::wrlog('cipher=' . $method . "\n");
            // Ship_Depot_Logger::wrlog('Password:' . $password . "\n");
            // Ship_Depot_Logger::wrlog('encrypted: ' . $encrypted . "\n");
            // Ship_Depot_Logger::wrlog('decrypted to: ' . $decrypted . "\n\n");
            return $decrypted;
        }
    }
}
