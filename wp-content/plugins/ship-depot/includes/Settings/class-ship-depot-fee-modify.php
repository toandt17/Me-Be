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

class Ship_Depot_Fee_Modify{
	public function __construct()
	{
		add_action('sd_fee_modify_section', array($this, 'ShowUI'));
	}

	
	public function ShowUI()
	{
		require_once SHIP_DEPOT_DIR_PATH . 'page/admin/settings/sd-fee-modify.php';
	}

}
new Ship_Depot_Fee_Modify();