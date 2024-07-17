<?php
/**
 * Main class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Colors and Labels Variations
 * @version 1.1.1
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCCL' ) ) {
	/**
	 * YITH WooCommerce Colors and Labels Variations
	 *
	 * @since 1.0.0
	 */
	class YITH_WCCL {

		/**
		 * Plugin object
		 *
		 * @since 1.0.0
		 * @var mixed
		 */
		public $obj = null;

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function __construct() {

			add_action( 'plugins_loaded', array( $this, 'plugin_fw_loader' ), 15 );
            add_action( 'before_woocommerce_init', array( $this, 'declare_wc_features_support' ) );


            if ( is_admin() ) {
				$this->obj = new YITH_WCCL_Admin();
			} else {
				$this->obj = new YITH_WCCL_Frontend();
			}

			// Add new attribute types.
			add_filter( 'product_attributes_type_selector', array( $this, 'attribute_types' ), 10, 1 );
			// AJAX Filter plugin compatibility.
			add_filter( 'yith_wcan_attribute_filter_item_args', array( $this, 'ajax_filter_compatibility' ), 10, 2 );
		}

		/**
		 * Plugin Framework loader
		 *
		 * @since 1.0.0
		 */
		public function plugin_fw_loader() {
			if ( ! defined( 'YIT_CORE_PLUGIN' ) ) {
				global $plugin_fw_data;
				if ( ! empty( $plugin_fw_data ) ) {
					$plugin_fw_file = array_shift( $plugin_fw_data );
					require_once $plugin_fw_file;
				}
			}
		}

        /**
         * Declare support for WooCommerce features.
         *
         * @since 2.0.0
         */
        public function declare_wc_features_support() {
            if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
                $init = defined( 'YITH_WCCL_FREE_INIT' ) ? YITH_WCCL_FREE_INIT : false;
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $init, true );
            }
        }

		/**
		 * Add new attribute types to standard WooCommerce
		 *
		 * @since  1.5.0
		 * @param array $default_type Array of default types.
		 * @return array
		 */
		public function attribute_types( $default_type ) {
			$custom = ywccl_get_custom_tax_types();
			return is_array( $custom ) ? array_merge( $default_type, $custom ) : $default_type;
		}

		/**
		 * AJAX Filter compatibility method
		 *
		 * @since 1.15.1
		 * @param array   $data The data array to filter.
		 * @param integer $term_id The term id to process.
		 * @return array
		 */
		public function ajax_filter_compatibility( $data, $term_id ) {

			$term = get_term( $term_id );
			if ( ! $term instanceof WP_Term ) {
				return $data;
			}

			$attribute_id = wc_attribute_taxonomy_id_by_name( str_replace( 'pa_', '', $term->taxonomy ) );
			if ( empty( $attribute_id ) ) {
				return $data;
			}

			$value = ywccl_get_term_meta( $term_id, '_yith_wccl_value' );
			if ( ! empty( $value ) ) {
				$attribute = wc_get_attribute( $attribute_id );
				switch ( $attribute->type ) {
					case 'colorpicker':
						$data['color_1'] = $value;
						break;
					case 'image':
						$media_id = attachment_url_to_postid( $value );
						if ( $media_id ) {
							$data['image'] = $media_id;
							$data['mode']  = 'image';

							// Replace tooltip placeholder if any.
							$thumb_src       = wp_get_attachment_image_url( $media_id, 'thumbnail' );
							$image           = '<img src="' . $thumb_src . '" />';
							$data['tooltip'] = str_replace( '{show_image}', $image, $data['tooltip'] );
						}
						break;
					default:
						$data['label'] = $value;
						break;
				}
			}

			return $data;
		}
	}
}
