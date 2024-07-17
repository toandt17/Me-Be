<?php
/**
 * General Settings
 *
 * @link       
 * @since 1.4.2    
 *
 * @package Ship_Depot_Settings
 */
if (!defined('ABSPATH')) {
    exit;
}

class Ship_Depot_General_Settings
{
	public function __construct()
	{
		add_action('sd_general_settings_section', array($this, 'ShowUI'));
	}

	public function ShowUI()
	{
		require_once SHIP_DEPOT_DIR_PATH . 'page/admin/settings/sd-general-settings.php';
	}

}
new Ship_Depot_General_Settings();