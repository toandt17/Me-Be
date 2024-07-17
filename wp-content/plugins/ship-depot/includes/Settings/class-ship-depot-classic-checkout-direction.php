<?php

/**
 * Classic_Checkout_Direction
 *
 * @link       
 * @since 1.4.2    
 *
 * @package Ship_Depot_Settings
 */
if (!defined('ABSPATH')) {
	exit;
}

class Ship_Depot_Classic_Checkout_Direction
{
	public function __construct()
	{
		add_action('sd_classic_checkout_direction_section', array($this, 'ShowUI'));
	}


	public function ShowUI()
	{
		require_once SHIP_DEPOT_DIR_PATH . 'page/admin/settings/sd-classic-checkout-direction.php';
	}
}
new Ship_Depot_Classic_Checkout_Direction();
