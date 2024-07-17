<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Customization ARRAY OPTIONS
 *
 * @since   2.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\ColorAndLabelVariations
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

$customization = array(

	'customization' => array(
		array(
			'title' => __( 'Style options', 'yith-color-and-label-variations-for-woocommerce' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'yith-wccl-customization-style-options',
		),

		array(
			'id'           => 'yith-wccl-form-colors',
			'title'        => __( 'Form colors', 'yith-color-and-label-variations-for-woocommerce' ),
			'type'         => 'yith-field',
			'yith-type'    => 'multi-colorpicker',
			'colorpickers' => array(
				array(
					'id'      => 'border',
					'name'    => __( 'Border', 'yith-color-and-label-variations-for-woocommerce' ),
					'default' => '#ffffff',
                    'alpha_enabled'     => false,
				),
				array(
					'id'      => 'accent',
					'name'    => __( 'Accent', 'yith-color-and-label-variations-for-woocommerce' ),
					'default' => '#448a85',
                    'alpha_enabled'     => false,
				),
			),
		),
		array(
			'id'        => 'yith-wccl-customization-color-swatches-size',
			'title'     => __( 'Color swatches size (px)', 'yith-color-and-label-variations-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'number',
			'default'   => 25,
            'min'       => 0,
		),
		array(
			'id'        => 'yith-wccl-customization-color-swatches-border-radius',
			'title'     => __( 'Color swatches border radius (px)', 'yith-color-and-label-variations-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'number',
			'default'   => 25,
            'min'       => 0,

        ),
		array(
			'id'        => 'yith-wccl-customization-option-border-radius',
			'title'     => __( 'Options border radius (px)', 'yith-color-and-label-variations-for-woocommerce' ),
			'type'      => 'yith-field',
			'yith-type' => 'number',
			'default'   => 25,
            'min'       => 0,

        ),
		array(
			'type' => 'sectionend',
			'id'   => 'yith-wccl-customization-style-options-end',
		),

	),
);

return $customization;
