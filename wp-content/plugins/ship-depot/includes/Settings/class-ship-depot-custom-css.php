<?php

/**
 * Couriers
 *
 * @link       
 * @since 1.4.2    
 *
 * @package Ship_Depot_Settings
 */
if (!defined('ABSPATH')) {
	exit;
}

class Ship_Depot_Custom_Css
{
	public function __construct()
	{
		add_action('sd_custom_css_section', array($this, 'ShowUI'));
		add_action('admin_enqueue_scripts', array($this, 'codemirror_enqueue_scripts'));
	}

	public function codemirror_enqueue_scripts($hook)
	{
		wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
	}

	public function ShowUI()
	{
		require_once SHIP_DEPOT_DIR_PATH . 'page/admin/settings/sd-custom-css.php';
	}
}
new Ship_Depot_Custom_Css();
