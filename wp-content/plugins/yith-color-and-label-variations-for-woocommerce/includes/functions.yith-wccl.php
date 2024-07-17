<?php
/**
 * Functions
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Colors and Labels Variations
 * @version 1.1.0
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

if ( ! function_exists( 'ywccl_get_term_meta' ) ) {
	/**
	 * Get term meta. If WooCommerce version is >= 2.6 use get_term_meta else use get_woocommerce_term_meta
	 *
	 * @param int    $term_id Term ID.
	 * @param string $key     Optional. The meta key to retrieve. By default,
	 *                        returns data for all keys. Default empty.
	 * @param bool   $single  Optional. Whether to return a single value.
	 *                        This parameter has no effect if $key is not specified.
	 *                        Default false.
	 * @param string $taxonomy Optional. The taxonomy slug.
	 * @return mixed
	 * @depreacted
	 */
	function ywccl_get_term_meta( $term_id, $key, $single = true, $taxonomy = '' ) {
		$value = get_term_meta( $term_id, $key, $single );

		// Compatibility with old format. To be removed on next version.
		if ( apply_filters( 'yith_wccl_get_term_meta', true, $term_id ) && ( false === $value || '' === $value ) && ! empty( $taxonomy ) ) {
			$value = get_term_meta( $term_id, $taxonomy . $key, $single );
			// If meta is not empty, save it with the new key.
			if ( false !== $value && '' !== $value ) {
				ywccl_update_term_meta( $term_id, $key, $value );
			}
		}

		return $value;
	}
}

if ( ! function_exists( 'ywccl_update_term_meta' ) ) {
	/**
	 * Get term meta. If WooCommerce version is >= 2.6 use update_term_meta else use update_woocommerce_term_meta
	 *
	 * @param int    $term_id    Term ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param mixed  $prev_value Optional. Previous value to check before updating.
	 *                           If specified, only update existing metadata entries with
	 *                           this value. Otherwise, update all entries. Default empty.
	 * @return mixed
	 * @depreacted
	 */
	function ywccl_update_term_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ) {
		if ( '' === $meta_value || false === $meta_value ) {
			return delete_term_meta( $term_id, $meta_key );
		}

		return update_term_meta( $term_id, $meta_key, $meta_value, $prev_value );
	}
}

if ( ! function_exists( 'ywccl_check_wc_version' ) ) {
	/**
	 * Check installed WooCommerce version
	 *
	 * @since  1.3.0
	 * @param string $version The version to check.
	 * @param string $operator The operator to use on check function.
	 * @return boolean
	 * @deprecated
	 */
	function ywccl_check_wc_version( $version, $operator ) {
		return version_compare( WC()->version, $version, $operator );
	}
}

if ( ! function_exists( 'ywccl_get_custom_tax_types' ) ) {
	/**
	 * Return custom product's attributes type
	 *
	 * @since  1.2.0
	 * @return array
	 */
	function ywccl_get_custom_tax_types() {
		return apply_filters(
			'yith_wccl_get_custom_tax_types',
			array(
				'colorpicker' => __( 'Colorpicker', 'yith-color-and-label-variations-for-woocommerce' ),
				'image'       => __( 'Image', 'yith-color-and-label-variations-for-woocommerce' ),
				'label'       => __( 'Label', 'yith-color-and-label-variations-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'yith_wccl_hex2rgba' ) ) {
    /**
     * Convert value from hex to rgba
     *
     * @param string $color The Hex color.
     * @param number $opacity The opacity value.
     * @return string
     * @since 2.0.0
     */
    function yith_wccl_hex2rgba( $color, $opacity ) {
        $default = 'rgb(0,0,0)';

        // Return default if no color provided.
        if ( empty( $color ) ) {
            return $default;
        }

        // Sanitize $color if "#" is provided.
        if ( '#' === $color[0] ) {
            $color = substr( $color, 1 );
        }

        // Check if color has 6 or 3 characters and get values.
        if ( strlen( $color ) === 6 ) {
            $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
        } elseif ( strlen( $color ) === 3 ) {
            $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
        } else {
            return $default;
        }

        // Convert hexadec to rgb.
        $rgb = array_map( 'hexdec', $hex );

        // Check if opacity is set(rgba or rgb).
        if ( $opacity ) {
            $output = 'rgba(' . implode( ',', $rgb ) . ',' . $opacity . ')';
        } else {
            $output = 'rgb(' . implode( ',', $rgb ) . ')';
        }

        // Return rgb(a) color string.
        return $output;
    }
}


if ( ! function_exists( 'yith_wccl_get_field' ) ) {
    /**
     * Print a form field for an attribute field
     *
     * @param array $field The field.
     * @since 2.0.0
     */
    function yith_wccl_get_field( $field ) {
        $defaults = array(
            'class'     => '',
            'title'     => '',
            'label_for' => '',
            'desc'      => '',
            'data'      => array(),
            'fields'    => array(),
        );

        /**
         * APPLY_FILTERS: yith_wccl_form_field_args
         *
         * Filter the array of the arguments for the fields in the product metabox.
         *
         * @param array $args  Array of arguments.
         * @param array $field Field.
         *
         * @return array
         */
        $field = apply_filters( 'yith_wccl_form_field_args', wp_parse_args( $field, $defaults ), $field );

        /**
         * Variable information for extract
         *
         * @var string $class
         * @var string $title
         * @var string $label_for
         * @var string $desc
         * @var array  $data
         * @var array  $fields
         */
        extract( $field ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

        if ( ! $label_for && $fields ) {
            $first_field = current( $fields );

            if ( isset( $first_field['id'] ) ) {
                $label_for = $first_field['id'];
            }
        }

        $data_html = '';

        foreach ( $data as $key => $value ) {
            $data_html .= "data-{$key}='{$value}' ";
        }

        $html  = '';
        $html .= "<div class='yith-wccl-form-field yith-plugin-ui {$class}' {$data_html}>";
        $html .= "<label class='yith-wccl-form-field__label' for='{$label_for}' style='display: none'>{$label}</label>";

        $html .= "<div class='yith-wccl-form-field__container'>";

        ob_start();
        yith_plugin_fw_get_field( $fields, true ); // Print field using plugin-fw.
        $html .= ob_get_clean();
        if ( $desc ) {
            $html .= "<div class='yith-wccl-form-field__description'>{$desc}</div>";
        }
        $html .= '</div><!-- yith-wccl-form-field__container -->';

        $html .= '</div><!-- yith-wccl-form-field -->';

        /**
         * APPLY_FILTERS: yith_wccl_form_field_html
         *
         * Filter the HTML for the fields in the product metabox.
         *
         * @param string $html  Field HTML
         * @param array  $field Field
         *
         * @return string
         */
        echo apply_filters( 'yith_wccl_form_field_html', $html, $field ); // phpcs:ignore
    }
}



if ( ! function_exists( 'yith_wccl_get_term_field' ) ) {
    /**
     * Get the fields for each attribute.
     *
     * @param string $type Attribute type.
     * @param object $term the term taxonomy.
     * @param string $taxonomy The taxonomy slug.
     * @param array  $custom_types The custom attribute type added by the plugin.
     * @return array
     *
     * @since 2.0.0
     */
    function yith_wccl_get_term_field( $type, $term, $taxonomy, $custom_types ) {

        $value =  $term ? ywccl_get_term_meta( $term->term_id, '_yith_wccl_value', true, $taxonomy ) : '';

        $fields = array(
            // Colorpicker fields.
            'colorpicker' => array(
                'value'           => array(
                    'label'  => isset( $custom_types[ $type ] ) ? $custom_types[ $type ] : __( 'Value', 'yith-woocommerce-color-label-variations' ),
                    'desc'   => '',
                    'class'  => 'ywccl_show_if_no_image_color',
                    'fields' => array(
                        'type'   => 'colorpicker',
                        'alpha_enabled'     => false,
                        'value'  => is_array( $value ) ? $value[0] : $value,
                        'id'     => 'term_value',
                        'name'   => 'term_value',
                        'default'           => '#ffffff'
                    ),
                ),
            ),
            // Image fields.
            'image'       => array(
                'value'              => array(
                    'label'  => isset( $custom_types[ $type ] ) ? $custom_types[ $type ] : __( 'Value', 'yith-woocommerce-color-label-variations' ),
                    'desc'   => '',
                    'class'  => '',
                    'fields' => array(
                        'class' => 'ywccl',
                        'type'  => 'media',
                        'value' => $value,
                        'id'    => 'term_value',
                        'name'  => 'term_value',
                        'allow_custom_url' => false,
                        'data'  => array(
                            'type' => 'image',
                        ),
                    ),
                ),
            ),
            // Label fields.
            'label'       => array(

                'value'           => array(
                    'label'  => isset( $custom_types[ $type ] ) ? $custom_types[ $type ] : __( 'Value', 'yith-woocommerce-color-label-variations' ),
                    'desc'   => '',
                    'class'  => 'ywccl_show_if_use_for_label',
                    'fields' => array(
                        'class' => 'ywccl',
                        'type'  => 'text',
                        'value' => $value,
                        'id'    => 'term_value',
                        'name'  => 'term_value',
                        'data'  => array(
                            'type' => 'label',
                        ),
                    ),
                ),
            ),
            // Select fields.
            'select'      => array(),
        );

        $fields_type = $fields[ $type ] ?? array();

        if ( ! empty( $fields_type ) ) {
            $fields_type['hidden'] = array(
                'label'  => '',
                'class'  => '',
                'fields' => array(
                    'type'  => 'hidden',
                    'value' => $type,
                    'id'    => 'term_attribute_type',
                    'name'  => 'term_attribute_type',
                ),
            );
        }

        return apply_filters( 'yith_wccl_gel_fields_type', $fields_type, $type, $term, $taxonomy, $custom_types );

    }
}

