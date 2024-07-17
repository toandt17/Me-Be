<?php 
/**
 * Plugin Name: Push new order to social SW
 * Plugin URI: https://sonweb.net/plugin-gui-thong-bao-don-hang-moi-woocommerce-telegram.html/
 * Description: Push new order product SW to social,telegram
 * Version: 1.0.0
 * Author: SonWeb
 * Author URI: https://sonweb.net
 * Text Domain: sonweb
 * License: GPLv2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists('PONSWP_PushOrderNotifySWPlugin') ) {
	class PONSWP_PushOrderNotifySWPlugin {
		public $plugin;
		/**
		 * Holds the values checkbox to be used in the fields callbacks
		 */
		private $options_setting;

		function __construct() {
			$this->swpon_define_constants();
			$this->plugin = plugin_basename(__FILE__);
			$this->options_setting = $this->swget_options();
		}

		function swpon_define_constants() {
			if ( !defined('PONSW_PLUGIN_PATH' ) ) {
				define('PONSW_PLUGIN_PATH', plugin_dir_path(__FILE__));
			}
			if ( !defined('PONSW_PLUGIN_URL' ) ) {
				define('PONSW_PLUGIN_URL', plugin_dir_url(__FILE__));
			}
		}

		function register() {
			add_filter( "plugin_action_links_$this->plugin", array( $this, 'settings_link' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
			add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
			add_action('admin_init',array($this,'ponsw_register_settings'));
			add_action('woocommerce_thankyou', array($this,'swp_push_order'), 10, 1);
		}

		
		// load page to menu
		public function add_admin_pages() {
			add_menu_page( 'PONSW Plugin', 'PONSW Plugin', 'manage_options', 'pushordernotifysw_plugin', array( $this, 'admin_index' ), 'dashicons-store', 110 );
		}

		public function admin_index() {
			require_once PONSW_PLUGIN_PATH . 'includes/admin.php';
		}

		function ponsw_register_settings() {
			//register our settings
			
			register_setting( 
				'ponsw_options', //  Option group
				'ponsw_setting',//option name
				'sanitize' // callback
			);
		}

		public function sanitize( $input )
		{
			$new_input = array();
			if( isset( $input['ckFace'] ) )
				$new_input['ckFace'] = absint( $input['ckFace'] );
	
			if( isset( $input['ckTel'] ) )
				$new_input['ckTel'] = absint( $input['ckTel'] );
			
			if( isset( $input['ponswFacebook'] ) )
				$new_input['ponswFacebook'] = sanitize_text_field( $input['ponswFacebook'] );
			
			if( isset( $input['ponswTelegram'] ) )
				$new_input['ponswTelegram'] = sanitize_text_field( $input['ponswTelegram'] );

			if( isset( $input['ponswMessage'] ) )
				$new_input['ponswMessage'] = sanitize_text_field( $input['ponswMessage'] );
	
			return $new_input;
		}
		function swget_options() {
			return wp_parse_args(get_option('ponsw_setting'),$this->options_setting );
		}

		public function settings_link( $links ) {
			$url_set = admin_url( 'admin.php?page=pushordernotifysw_plugin' );
			$settings_link = "<a href='$url_set'>". __( 'Settings' ) .' </a>';
			array_push( $links, $settings_link );
			return $links;
		}
		
		function swp_push_order($order_id) {
			$str_mess = $this->options_setting['ponswMessage'];
			if (strlen(trim( $str_mess) ) < 2) {
				$str_mess = "Your order ID: #%%order_id%%" .PHP_EOL;
				$str_mess .= "Products name: %%product_name%%".PHP_EOL;
				$str_mess .= "First name: %%first_name%%" . PHP_EOL;	
				$str_mess .= "Last name: %%last_name%%" . PHP_EOL;		  	
				$str_mess .= "Customer email: %%billing_email%%" . PHP_EOL;	
				$str_mess .= "Phone number: %%billing_phone%%" . PHP_EOL;
				$str_mess .= "Address: %%billing_address%%" . PHP_EOL;
				$str_mess .= "Total money: %%total%%" . PHP_EOL;
			} 

			if ( ! $order_id )
				return;
		
			// Allow code execution only once 
			if( ! get_post_meta( $order_id, '_thankyou_action_done', true ) ) {
				// Get an instance of the WC_Order object
			
				$order = wc_get_order( $order_id );
				$items = $order->get_items();
				$productname = [];
				foreach ($items as $item) {
					$product = wc_get_product($item['product_id']);
					$soluongsanpham = $item['quantity'];
					$productname[] = $product->get_name() . ' × (' . $soluongsanpham . ')';
				}
				$productname = implode(', ', $productname);
				$billing_first_name = $order->get_billing_first_name();
				$billing_last_name = $order->get_billing_last_name();
				$billing_phone = $order->get_billing_phone();
				$billing_email = $order->get_billing_email();
				$billing_address = $order->get_billing_address_1();
				$total = $order->get_total();
				$formattedNum = number_format($total, 0, ',', '.');
				$arr_replace['product_name'] = $productname ;
				$arr_replace['first_name'] = $billing_first_name;
				$arr_replace['last_name'] = $billing_last_name;
				$arr_replace['total'] = $formattedNum . 'VNĐ';
				$arr_replace['billing_address'] = $billing_address;
				$arr_replace['billing_phone'] = $billing_phone;
				$arr_replace['billing_email'] = $billing_email;
				$arr_replace['order_id'] = $order_id;

				preg_match_all('/%%(\w*?)\%%/', $str_mess, $matches);
				foreach ($matches[1] as $m) {
					$pattern = "/%%" . $m . "%%/";

					$str_mess = preg_replace($pattern, $arr_replace[$m], $str_mess);
				}
				//echo "<pre>";
				//print_r($str_mess);
				//echo "</pre>";
				$order->update_meta_data( '_thankyou_action_done', true );
				$order->save();
				$this->push_callmebot_api($str_mess);
			}
		}
		
		public function push_callmebot_api($message = '') {
			$keyFace = $this->options_setting['ponswFacebook'];
			$sendMes = urlencode($message);
			$sendFace = "https://api.callmebot.com/facebook/send.php?apikey=$keyFace&text=$sendMes&disable_web_page_preview=true";
			if ($this->options_setting['ckFace'] ) {
				if (! $sendMes || !$keyFace) return false;
				wp_remote_get($sendFace);
			}
			if ($this->options_setting['ckTel']) {
				$userTel = $this->options_setting['ponswTelegram'];
				$sendTel = "https://api.callmebot.com/text.php?user=$userTel&text=$sendMes";
				if (! $sendMes || !$userTel) return false;
				wp_remote_get($sendTel);
				
			}
		}

		function enqueue() {
			// enqueue all our scripts
			wp_enqueue_style( 'style_ponsw', plugins_url( '/assets/style_ponsw.css', __FILE__ ) );
			
		}

		function activate() {
			require_once PONSW_PLUGIN_PATH . 'includes/activate.php';
			PONSWPluginActivate::activate();
		}

	}

	$ponswplugin = new PONSWP_PushOrderNotifySWPlugin();
	$ponswplugin->register();
}

 ?>