<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class Ship_Depot_Settings_Init
{
    public function __construct()
    {
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Settings/class-ship-depot-general-settings.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Settings/class-ship-depot-fee-modify.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Settings/class-ship-depot-custom-css.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Settings/class-ship-depot-couriers.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Settings/class-ship-depot-about.php';
        require_once SHIP_DEPOT_DIR_PATH . 'includes/Settings/class-ship-depot-classic-checkout-direction.php';
        if (is_admin()) {
            add_filter('woocommerce_get_settings_pages', array($this, 'add_woocommerce_settings_tab'), PHP_INT_MAX);
        }
    }

    public function add_woocommerce_settings_tab($settings)
    {
        $settings[] = include plugin_dir_path(__FILE__) . 'class-ship-depot-settings-backend.php';
        return $settings;
    }
}
