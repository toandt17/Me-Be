<?php
/**
 * Admin class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Colors and Labels Variations
 * @version 1.1.0
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCCL_Admin' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_WCCL_Admin {

		/**
		 * Plugin panel page
		 *
		 * @const string
		 */
		const PANEL_PAGE = 'yith_ywcl_panel';

		/**
		 * Premium landing url
		 *
		 * @const string
		 */
		const PREMIUM_LANDING_URI = 'https://yithemes.com/themes/plugins/yith-woocommerce-color-and-label-variations/';

		/**
		 * Panel instance
		 *
		 * @var YIT_Plugin_Panel_WooCommerce | null
		 */
		protected $panel = null;

        /**
         * Custom types variable
         *
         * @var array
         */
        public $custom_types = array();

		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function __construct() {

            $this->custom_types = ywccl_get_custom_tax_types();


            // Product attribute taxonomies.
			add_action( 'init', array( $this, 'attribute_taxonomies' ) );

			// Add action links.
			add_filter( 'plugin_action_links_' . plugin_basename( YITH_WCCL_DIR . '/' . basename( YITH_WCCL_FILE ) ), array( $this, 'action_links' ) );
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );

			// Print attribute field type.
			add_action( 'yith_wccl_print_attribute_field', array( $this, 'print_attribute_type' ), 10, 3 );

			// Save new term.
			add_action( 'created_term', array( $this, 'attribute_save' ), 10, 3 );
			add_action( 'edit_term', array( $this, 'attribute_save' ), 10, 3 );

			// Choose variations in product page.
			add_action( 'woocommerce_product_option_terms', array( $this, 'product_option_terms' ), 10, 2 );

			// Enqueue static content.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

			// Add YITH Plugin Panel.
			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );

            // Colorpicker attribute.
            add_action( 'yith_wccl_colorpicker_attribute', array( $this, 'print_colorpicker_attribute' ) );


            // YITH WCCL Loaded.
			do_action( 'yith_wccl_loaded' );

		}


		/**
		 * Enqueue scripts and style
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function enqueue() {
			global $pagenow;

			if ( in_array( $pagenow, array( 'term.php', 'edit-tags.php' ), true ) && isset( $_GET['post_type'] ) && 'product' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_enqueue_media();
				wp_enqueue_style( 'yith-wccl-admin', YITH_WCCL_URL . '/assets/css/admin.css', array( 'wp-color-picker', 'yith-plugin-fw-fields' ), YITH_WCCL_VERSION );
				wp_enqueue_script( 'yith-wccl-admin', YITH_WCCL_URL . '/assets/js/admin.js', array( 'jquery', 'wp-color-picker', 'yith-plugin-fw-fields' ), YITH_WCCL_VERSION, true );
			}
		}

		/**
		 * Init product attribute taxonomies
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function attribute_taxonomies() {
			$attribute_taxonomies = wc_get_attribute_taxonomies();
			if ( $attribute_taxonomies ) {
				foreach ( $attribute_taxonomies as $tax ) {

					$name = wc_attribute_taxonomy_name( $tax->attribute_name );

					add_action( $name . '_add_form_fields', array( $this, 'add_attribute_field' ) );
					add_action( $name . '_edit_form_fields', array( $this, 'edit_attribute_field' ), 10, 2 );

					add_filter( 'manage_edit-' . $name . '_columns', array( $this, 'product_attribute_columns' ) );
					add_filter( 'manage_' . $name . '_custom_column', array( $this, 'product_attribute_column' ), 10, 3 );
				}
			}
		}

		/**
		 * Action Links: add the action links to plugin admin page
		 *
		 * @since 1.0.0
		 * @param array $links The links plugin array.
		 * @return mixed
		 */
		public function action_links( $links ) {
			$links = yith_add_action_links( $links, self::PANEL_PAGE, false );

			return $links;
		}

		/**
		 * Plugin row meta
		 *
		 * @since    1.0
		 * @use      plugin_row_meta
		 * @param array    $new_row_meta_args An array of plugin row meta.
		 * @param string[] $plugin_meta An array of the plugin's metadata,
		 *                                    including the version, author,
		 *                                    author URI, and plugin URI.
		 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
		 * @param array    $plugin_data An array of plugin data.
		 * @param string   $status Status of the plugin. Defaults are 'All', 'Active',
		 *                                    'Inactive', 'Recently Activated', 'Upgrade', 'Must-Use',
		 *                                    'Drop-ins', 'Search', 'Paused'.
		 * @return   Array
		 */
		public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status ) {

			if ( defined( 'YITH_WCCL_FREE_INIT' ) && YITH_WCCL_FREE_INIT === $plugin_file ) {
				$new_row_meta_args['slug'] = YITH_WCCL_SLUG;

				$new_row_meta_args['live_demo']       = array(
					'url' => 'https://plugins.yithemes.com/yith-woocommerce-color-and-label-variations/',
				);
				$new_row_meta_args['documentation']   = array(
					'url' => 'https://docs.yithemes.com/yith-woocommerce-color-label-variations/',
				);
				$new_row_meta_args['premium_version'] = array(
					'url' => self::PREMIUM_LANDING_URI,
				);
			}

			return $new_row_meta_args;
		}


		/**
		 * Add field for each product attribute taxonomy
		 *
		 * @since  1.0.0
		 * @param string $taxonomy The taxonomy slug.
		 * @return void
		 */
		public function add_attribute_field( $taxonomy ) {
			global $wpdb;

			$attribute = substr( $taxonomy, 3 );
			$attribute = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s", $attribute ) ); // phpcs:ignore

            $values = yith_wccl_get_term_field( $attribute->attribute_type, '', $taxonomy, $this->custom_types );

            do_action( 'yith_wccl_print_attribute_field', $attribute->attribute_type, $values );
		}

		/**
		 * Edit field for each product attribute taxonomy
		 *
		 * @since  1.0.0
		 * @param WP_Term $term The term to edit.
		 * @param string  $taxonomy The taxonomy slug.
		 * @return void
		 */
		public function edit_attribute_field( $term, $taxonomy ) {
			global $wpdb;

			$attribute = substr( $taxonomy, 3 );
			$attribute = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s", $attribute ) ); // phpcs:ignore

            $values = yith_wccl_get_term_field( $attribute->attribute_type, $term, $taxonomy, $this->custom_types );

            /**
             * DO_ACTION: yith_wccl_print_attribute_field
             *
             * @param string $type Attribute type.
             * @param array  $values Attribute data.
             * @param bool   $table True if is in table, false otherwise.
             */
            do_action( 'yith_wccl_print_attribute_field', $attribute->attribute_type, $values, true );
		}


		/**
		 * Print Color Picker Type HTML
		 *
		 * @since  1.0.0
		 * @param object  $attribute Current attribute.
		 * @param string  $value The attribute value slug.
		 * @param boolean $table True if is in a table, false otherwise.
		 * @return void
		 */
		public function print_attribute_type( string $type, array $args, bool $table = false ) {

            foreach ( $args as $key => $arg ) :
                $class = $arg['class'] ?? '';
                if ( $table ) : ?>
                    <tr class="form-field <?php echo 'term-' . esc_attr( $key ) . '-wrap yith-wccl-term-type-' . esc_attr( $type ) . ' ' . esc_attr( $class ); ?>">
                    <th scope="row">
                        <label for="term_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $arg['label'] ); ?></label>
                    </th>
                    <td>
                <?php else : ?>
                    <div class="form-field <?php echo 'term-' . esc_attr( $key ) . '-wrap yith-wccl-term-type-' . esc_attr( $type ) . ' ' . esc_attr( $class ); ?>">
                    <label for="term_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $arg['label'] ); ?></label>
                <?php endif ?>

                <?php echo yith_wccl_get_field( $arg ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

                <?php if ( $table ) : ?>
                </td>
                </tr>
            <?php else : ?>
                </div>
            <?php
            endif;
            endforeach;
		}


		/**
		 * Save attribute field
		 *
		 * @since  1.0.0
		 * @param integer $term_id The term ID.
		 * @param integer $tt_id The term taxonomy ID.
		 * @param string  $taxonomy Taxonomy slug.
		 * @return void
		 */
		public function attribute_save( $term_id, $tt_id, $taxonomy ) {

			if ( isset( $_POST['term_value'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				ywccl_update_term_meta( $term_id, '_yith_wccl_value', wc_clean( $_POST['term_value'] ) ); // phpcs:ignore
			}
		}

		/**
		 * Create new column for product attributes
		 *
		 * @since  1.0.0
		 * @param array $columns Product attribute columns.
		 * @return array
		 */
		public function product_attribute_columns( $columns ) {

			if ( empty( $columns ) ) {
				return $columns;
			}

			$temp_cols                    = array();
			$temp_cols['cb']              = $columns['cb'];
			$temp_cols['yith_wccl_value'] = esc_html__( 'Value', 'yith-color-and-label-variations-for-woocommerce' );
			unset( $columns['cb'] );
			$columns = array_merge( $temp_cols, $columns );

			return $columns;
		}

		/**
		 * Print the column content
		 *
		 * @since  1.0.0
		 * @param array   $columns Product attribute columns.
		 * @param string  $column Current column.
		 * @param integer $id The term ID.
		 * @return array
		 */
		public function product_attribute_column( $columns, $column, $id ) {
			global $taxonomy, $wpdb;

			if ( 'yith_wccl_value' === $column ) {
				$attribute = substr( $taxonomy, 3 );
				$attribute = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s", $attribute ) ); // phpcs:ignore
				$att_type  = $attribute->attribute_type;

				$value    = ywccl_get_term_meta( $id, '_yith_wccl_value', true, $taxonomy );
                $value    = is_array( $value ) ? $value[0] : $value;
				$columns .= $this->print_attribute_column( $value, $att_type );
			}

			return $columns;
		}


		/**
		 * Print the column content according to attribute type
		 *
		 * @since  1.0.0
		 * @param string $value Attribute value.
		 * @param string $type Attribute type.
		 * @return string
		 */
		protected function print_attribute_column( $value, $type ) {
			$output = '';

			if ( 'colorpicker' === $type ) {
				$output = '<span class="yith-wccl-color" style="background-color:' . $value . '"></span>';
			} elseif ( 'label' === $type ) {
				$output = '<span class="yith-wccl-label">' . $value . '</span>';
			} elseif ( 'image' === $type ) {
				$output = '<img class="yith-wccl-image" src="' . $value . '" alt="" />';
			}

			return $output;
		}

		/**
		 * Print select for product variations
		 *
		 * @since  1.0.0
		 * @param WP_Term $tax Attribute term.
		 * @param integer $i Row index value.
		 */
		public function product_option_terms( $tax, $i ) {
			global $woocommerce, $thepostid;

			if ( in_array( $tax->attribute_type, array( 'colorpicker', 'image', 'label' ), true ) ) {

				$attribute_taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );
				if ( is_null( $thepostid ) && isset( $_REQUEST['post_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$thepostid = absint( $_REQUEST['post_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}

				?>
				<select multiple="multiple"
						data-placeholder="<?php esc_html_e( 'Select terms', 'yith-color-and-label-variations-for-woocommerce' ); ?>"
						class="multiselect attribute_values wc-enhanced-select"
						name="attribute_values[<?php echo absint( $i ); ?>][]">
					<?php
					$all_terms = $this->get_terms( $attribute_taxonomy_name );
					if ( $all_terms ) {
						foreach ( $all_terms as $term ) {
							echo '<option value="' . esc_attr( $term['value'] ) . '" ' . selected( has_term( absint( $term['id'] ), $attribute_taxonomy_name, $thepostid ), true, false ) . '>' . esc_html( $term['name'] ) . '</option>';
						}
					}
					?>
				</select>
				<button class="button plus select_all_attributes"><?php esc_html_e( 'Select all', 'yith-color-and-label-variations-for-woocommerce' ); ?></button>
				<button class="button minus select_no_attributes"><?php esc_html_e( 'Select none', 'yith-color-and-label-variations-for-woocommerce' ); ?></button>
				<button class="button fr plus add_new_attribute" data-attribute="<?php echo esc_attr( $attribute_taxonomy_name ); ?>"><?php esc_html_e( 'Add new', 'yith-color-and-label-variations-for-woocommerce' ); ?></button>
				<?php
			}
		}

		/**
		 * Get terms attributes array
		 *
		 * @since  1.3.0
		 * @param string $tax_name The tax name.
		 * @return array
		 */
		protected function get_terms( $tax_name ) {

			global $wp_version;

			$args = array(
				'taxonomy'   => $tax_name,
				'orderby'    => 'name',
				'hide_empty' => '0',
			);
			// Get terms.
			$terms     = get_terms( $args );
			$all_terms = array();

			foreach ( $terms as $term ) {
				$all_terms[] = array(
					'id'    => $term->term_id,
					'value' => $term->term_id,
					'name'  => $term->name,
				);
			}

			return $all_terms;
		}


		/**
		 * Register YITH Panel
		 *
		 * @since   1.2.4
		 * @return  void
		 */
		public function register_panel() {

			if ( ! empty( $this->panel ) ) {
				return;
			}

            $admin_tabs = array(
                'customization' => array(
                    'title'       => __( 'Customization', 'yith-color-and-label-variations-for-woocommerce' ),
                    'icon'        => '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 11.25l1.5 1.5.75-.75V8.758l2.276-.61a3 3 0 10-3.675-3.675l-.61 2.277H12l-.75.75 1.5 1.5M15 11.25l-8.47 8.47c-.34.34-.8.53-1.28.53s-.94.19-1.28.53l-.97.97-.75-.75.97-.97c.34-.34.53-.8.53-1.28s.19-.94.53-1.28L12.75 9M15 11.25L12.75 9"></path>
                                      </svg>',
                    'description' => __( 'Set the customization options for the plugin behavior.', 'yith-color-and-label-variations-for-woocommerce' ),
                ),
            );

			$args = array(
                'ui_version'         => 2,
				'create_menu_page' => true,
				'parent_slug'      => '',
				'page_title'       => 'YITH WooCommerce Color and Label Variations',
				'menu_title'       => 'Color and Label Variations',
				'capability'       => 'manage_options',
				'parent'           => '',
				'parent_page'      => 'yit_plugin_panel',
				'page'             => self::PANEL_PAGE,
				'admin-tabs'       => $admin_tabs,
				'options-path'     => YITH_WCCL_DIR . '/plugin-options',
				'class'            => yith_set_wrapper_class(),
                'landing_page_url' => self::PREMIUM_LANDING_URI,
                'plugin_slug'      => YITH_WCCL_SLUG,
                'is_free'          => defined( YITH_WCCL_FREE_INIT ),
                'is_premium'       => false,
                'is_extended'      => false,
                'plugin_version'   => YITH_WCCL_VERSION,
                'premium_tab'      => array(
                    'features' => array(
                        array(
                            'title'       => __( 'Show the available options for your products also in the shop page ', 'yith-color-and-label-variations-for-woocommerce' ),
                            'description' => __( 'By showing the available options also on the standard WooCommerce pages, your users will be able to quickly see the available sizes, colors, etc., and speed up the selection and purchase process.', 'yith-color-and-label-variations-for-woocommerce' ),
                        ),
                        array(
                            'title'       => __( 'Use bicolor swatches for greater versatility', 'yith-color-and-label-variations-for-woocommerce' ),
                            'description' => __( 'Use the "bicolor" option to create samples with two colors: This option is useful if you sell products that have two dominant colors and you want to consistently show them to your users.', 'yith-color-and-label-variations-for-woocommerce' ),
                        ),
                        array(
                            'title'       => __( 'Enable a tooltip with text and a preview when hovering over the options', 'yith-color-and-label-variations-for-woocommerce' ),
                            'description' => __( 'Show a tooltip to display the option name (e.g., the name of the color or style) and also a preview: in this way, you can show, for example, a preview of the "blue" sweatshirt when the user hovers over the blue color sample.', 'yith-color-and-label-variations-for-woocommerce' ),
                        ),
                        array(
                            'title'       => __( 'Set an image gallery for every product variation', 'yith-color-and-label-variations-for-woocommerce' ),
                            'description' => __( 'For products available in different colors, you can, for example, set up an image gallery for each color to let users have a wider overview of the products they want to purchase.', 'yith-color-and-label-variations-for-woocommerce' ),
                        ),
                        array(
                            'title'       => __( 'Change the attributes’ global options in specific products', 'yith-color-and-label-variations-for-woocommerce' ),
                            'description' => __( 'In the premium version of the plugin it is possible to change the style of the attributes for each product. This allows you to quickly set a different color shade in a specific product with just two clicks and without changing the color attributes globally configured.', 'yith-color-and-label-variations-for-woocommerce' ),
                        ),
                        // ...
                    ),
                ),
			);

			if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
				require_once 'plugin-fw/lib/yit-plugin-panel-wc.php';
			}

			$this->panel = new YIT_Plugin_Panel_WooCommerce( $args );
		}
	}
}
