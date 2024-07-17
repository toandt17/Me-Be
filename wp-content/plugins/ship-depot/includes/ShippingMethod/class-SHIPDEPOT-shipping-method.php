<?php

/**
 * Check if WooCommerce is active
 */
if (Ship_Depot_Helper::is_woocommerce_activated()) {

	function sd_shipping_method_init()
	{
		if (!class_exists('WC_ShipDepot_Shipping_Method')) {
			class WC_ShipDepot_Shipping_Method extends WC_Shipping_Method
			{
				/**
				 * Constructor for your shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct($instance_id = 0)
				{
					parent::__construct($instance_id);
					$this->id                 = esc_html(SHIP_DEPOT_SHIPPING_METHOD); // Id for your shipping method. Should be unique.
					$this->method_title       = esc_html__('Ship Depot', 'ship-depot-translate');  // Title shown in admin
					$this->method_description = esc_html__('Giao hàng qua đơn vị Ship Depot', 'ship-depot-translate'); // Description shown in admin

					$this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
					$this->title              = esc_html__('Ship Depot', 'ship-depot-translate'); // This can be added as an setting but for this example its forced.
					$this->tax_status = 'none';
					$this->init();
					//Uncomment to show this shipping method 
					//$this->setup_show_in_shipping_zone();
				}

				function setup_show_in_shipping_zone()
				{
					$this->supports = [
						'settings',
						//'shipping-zones',
						'instance-settings',
						'instance-settings-modal',
					];

					$this->instance_form_fields = [
						'title' => [
							'title' => esc_html__('Method title', 'woocommerce'),
							'type' => 'text',
							'description' => esc_html__('This controls the title which the user sees during checkout.', 'woocommerce'),
							'default' => esc_html__('Ship Depot', 'ship-depot-translate'),
							'desc_tip' => true,
						],
					];

					$form_fields = [];

					// $form_fields['title'] = [
					// 	'title'       => __('Title', 'dc_raq'),
					// 	'type'        => 'text',
					// 	'description' => __('Title to be displayed on site', 'dc_raq'),
					// 	'default'     => __('Request a Quote', 'dc_raq')
					// ];

					// $form_fields['enabled'] = [
					// 	'title'       => __('Enable', 'dc_raq'),
					// 	'type'        => 'checkbox',
					// 	'description' => __('Enable this shipping method.', 'dc_raq'),
					// 	'default'     => 'yes'
					// ];

					$this->form_fields = $form_fields;
				}
				/**
				 * Init your settings
				 *
				 * @access public
				 * @return void
				 */
				function init()
				{
					// Load the settings API
					$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
					$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

					// Save settings in admin if you have any defined
					add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
				}

				/**
				 * calculate_shipping function.
				 *
				 * @access public
				 * @param mixed $package
				 * @return void
				 */
				public function calculate_shipping($package = [])
				{
					$rate = array(
						'label' => esc_html($this->title),
						'cost' => '0'
					);

					// Register the rate
					$this->add_rate($rate);
				}
			}
		}
	}

	add_action('woocommerce_shipping_init', 'sd_shipping_method_init');

	function add_sd_shipping_method($methods)
	{
		$methods[SHIP_DEPOT_SHIPPING_METHOD] = 'WC_ShipDepot_Shipping_Method';
		return $methods;
	}

	add_filter('woocommerce_shipping_methods', 'add_sd_shipping_method');
}
