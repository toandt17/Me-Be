<?php
/**
 * Frontend class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Colors and Labels Variations
 * @version 1.1.0
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCCL_Frontend' ) ) {
	/**
	 * Frontend class.
	 * Manage all the frontend behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_WCCL_Frontend {

		/**
		 * Constructor
		 *
		 * @since  1.0.0
		 */
		public function __construct() {

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_static' ) );
			// Override default WooCommerce add-to-cart/variable.php template.
			add_action( 'template_redirect', array( $this, 'override' ) );

            // YITH WCCL Loaded.
			do_action( 'yith_wccl_loaded' );
		}

		/**
		 * Override default template
		 *
		 * @since  1.0.0
		 */
		public function override() {
			remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
			add_action( 'woocommerce_variable_add_to_cart', array( $this, 'variable_add_to_cart' ), 30 );
		}

		/**
		 * Output the variable product add to cart area.
		 *
		 * @since  1.0.0
		 */
		public function variable_add_to_cart() {
			global $product;

			// Enqueue variation scripts.
			wp_enqueue_script( 'wc-add-to-cart-variation' );

			$attributes = $product->get_variation_attributes();
			// Load the template.
			wc_get_template(
				'single-product/add-to-cart/variable-wccl.php',
				array(
					'available_variations' => $product->get_available_variations(),
					'attributes'           => $attributes,
					'selected_attributes'  => $product->get_default_attributes(),
					'attributes_types'     => $this->get_variation_attributes_types( $attributes ),
				),
				'',
				YITH_WCCL_DIR . 'templates/'
			);
		}


		/**
		 * Get an array of types and values for each attribute
		 *
		 * @since  1.0.0
		 * @param array $attributes An array of product attributes.
		 * @return array
		 */
		public function get_variation_attributes_types( $attributes ) {
			global $wpdb;
			$types = array();

			if ( ! empty( $attributes ) ) {
				foreach ( $attributes as $name => $options ) {
					$attribute_name = substr( $name, 3 );
					$attribute      = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s", $attribute_name ) ); // phpcs:ignore
					if ( isset( $attribute ) ) {
						$types[ $name ] = $attribute->attribute_type;
					} else {
						$types[ $name ] = 'select';
					}
				}
			}

			return $types;
		}


		/**
		 * Enqueue frontend styles and scripts
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function enqueue_static() {
			global $post;

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$css    = file_exists( get_stylesheet_directory() . '/woocommerce/yith_wccl.css' ) ? get_stylesheet_directory_uri() . '/woocommerce/yith_magnifier.css' : YITH_WCCL_URL . 'assets/css/frontend.css';

			wp_register_script( 'yith_wccl_frontend', YITH_WCCL_URL . 'assets/js/frontend' . $suffix . '.js', array( 'jquery', 'wc-add-to-cart-variation' ), YITH_WCCL_VERSION, true );
			wp_register_style( 'yith_wccl_frontend', $css, false, YITH_WCCL_VERSION );

			if ( is_product() || ( ! empty( $post->post_content ) && strstr( $post->post_content, '[product_page' ) ) ) {
				wp_enqueue_script( 'yith_wccl_frontend' );
				wp_enqueue_style( 'yith_wccl_frontend' );

                $custom_css = $this->build_custom_css();

                if ( ! empty( $custom_css ) ) {
                    wp_add_inline_style( 'yith_wccl_frontend', $custom_css );
                }
			}
		}

        /**
         * Build custom CSS template
         *
         * @since 2.0.0
         * @return bool|string Custom CSS template, ro false when no content should be output.
         */
        protected function build_custom_css() {

            $variables = array();
            $options   = array(
                'form-colors'                        => array(
                    'default' => array(
                        'border' => '#ffffff',
                        'accent' => '#448a85',

                    ),
                ),
                'form-colors-accent-hover'           => array(
                    'default'  => yith_wccl_hex2rgba( '#448a85', 0.4 ),
                    'callback' => function( $color ) {
                        $form_color = get_option( 'yith-wccl-form-colors', array() );
                        if ( ! empty( $form_color ) && isset( $form_color['accent'] ) ) {
                            $color = yith_wccl_hex2rgba( $form_color['accent'], 0.4 );
                        }
                        return $color;
                    },

                ),
                'customization-color-swatches-size'  => array(
                    'default'  => 25,
                    'callback' => function( $raw_value ) {
                        return $raw_value . 'px';
                    },
                ),
                'customization-color-swatches-border-radius' => array(
                    'default'  => 25,
                    'callback' => function( $raw_value ) {
                        return $raw_value . 'px';
                    },
                ),
                'customization-option-border-radius' => array(
                    'default'  => 25,
                    'callback' => function( $raw_value ) {
                        return $raw_value . 'px';
                    },
                ),

            );

            // cycles through options.
            foreach ( $options as $variable => $settings ) {
                $option   = "yith-wccl-{$variable}";
                $variable = '--yith-wccl-' . ( isset( $settings['variable'] ) ? $settings['variable'] : $variable );
                $value    = get_option( $option, $settings['default'] );

                if ( isset( $settings['callback'] ) && is_callable( $settings['callback'] ) ) {
                    $value = $settings['callback']( $value );
                }

                if ( empty( $value ) ) {
                    continue;
                }

                if ( is_array( $value ) ) {
                    foreach ( $value as $sub_variable => $sub_value ) {
                        $variables[ "{$variable}_{$sub_variable}" ] = $sub_value;
                    }
                } else {
                    $variables[ $variable ] = $value;
                }
            }

            if ( empty( $variables ) ) {
                return false;
            }

            // start CSS snippet.
            $template = ":root{\n";

            // cycles through variables.
            foreach ( $variables as $variable => $value ) {
                $template .= "\t{$variable}: {$value};\n";
            }

            // close :root directive.
            $template .= '}';

            return apply_filters( 'yith_wccl_custom_css', $template );
        }
	}
}
