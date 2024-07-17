<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://plugin68.com/
 * @since             1.0.3
 * @package           Isures_Suggest_Discount_Code
 *
 * @wordpress-plugin
 * Plugin Name:       iSures Suggest Discount Code
 * Plugin URI:        https://plugin68.com/sanpham/isures-suggest-discount-code-share-plugin/
 * Description:       The plugin suggests discount codes to customers, applying AJAX discount codes. A trick to increase your order value, let's logically create attractive discount codes.
 * Version:           1.0.3
 * Author:            Plugin68.com
 * Author URI:        https://plugin68.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       isures-suggest-discount-code
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}
if (!defined('PWP_COUPON_URL')) {
	define('PWP_COUPON_URL', plugin_dir_url(__FILE__));
}
if (!defined('PWP_COUPON_PATH')) {
	define('PWP_COUPON_PATH', plugin_dir_path(__FILE__));
}
if (!function_exists('debug')) {
	function debug($v, $die = true)
	{
		echo "<pre>";
		print_r($v);
		echo "</pre>";
		if ($die)
			die();
	}
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('ISURES_SUGGEST_DISCOUNT_CODE_VERSION', '1.0.3');

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	return;
}
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-isures-suggest-discount-code-activator.php
 */
function activate_isures_suggest_discount_code()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-isures-suggest-discount-code-activator.php';
	Isures_Suggest_Discount_Code_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-isures-suggest-discount-code-deactivator.php
 */
function deactivate_isures_suggest_discount_code()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-isures-suggest-discount-code-deactivator.php';
	Isures_Suggest_Discount_Code_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_isures_suggest_discount_code');
register_deactivation_hook(__FILE__, 'deactivate_isures_suggest_discount_code');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
include_once 'classes/class-isures-autoload.php';
include_once 'classes/class-isures-include-file.php';
require plugin_dir_path(__FILE__) . 'includes/class-isures-suggest-discount-code.php';
require plugin_dir_path(__FILE__) . 'includes/class-isures-update-en-free.php';
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_isures_suggest_discount_code()
{

	$plugin = new Isures_Suggest_Discount_Code();
	$plugin->run();
}
run_isures_suggest_discount_code();



