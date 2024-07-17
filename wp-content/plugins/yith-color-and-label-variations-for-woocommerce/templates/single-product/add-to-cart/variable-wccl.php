<?php
/**
 * Variable product add to cart
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Colors and Labels Variations
 * @version 1.2.5
 */

defined( 'YITH_WCCL' ) || exit; // Exit if accessed directly.

global $woocommerce, $product, $post;

$product_id      = $product->get_id();
$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form class="variations_form cart" method="post" enctype='multipart/form-data'
	data-product_id="<?php echo absint( $product_id ); ?>"
	data-product_variations="<?php echo wc_esc_json( $variations_json ); // phpcs:ignore ?>" data-wccl="true">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>

		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>

	<?php else : ?>

		<table class="variations" cellspacing="0">
			<tbody>
			<?php foreach ( $attributes as $name => $options ) : ?>
				<tr>
					<td class="label"><label
							for="<?php echo esc_attr( sanitize_title( $name ) ); ?>"><?php echo wc_attribute_label( $name ); // phpcs:ignore ?></label>
					</td>
					<td class="value">
						<select id="<?php echo esc_attr( sanitize_title( $name ) ); ?>"
							name="<?php echo 'attribute_' . esc_attr( sanitize_title( $name ) ); ?>"
							data-attribute_name="attribute_<?php echo esc_attr( sanitize_title( $name ) ); ?>"
							data-type="<?php echo esc_attr( $attributes_types[ $name ] ); ?>">
							<option value=""><?php echo esc_html__( 'Choose an option', 'woocommerce' ); ?>&hellip;
							</option>
							<?php
							if ( ! empty( $options ) ) {

								$selected_value = isset( $_REQUEST[ 'attribute_' . sanitize_title( $name ) ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ 'attribute_' . sanitize_title( $name ) ] ) ) : $product->get_variation_default_attribute( $name ); // phpcs:ignore WordPress.Security.NonceVerification

								// Get terms if this is a taxonomy - ordered.
								if ( $product && taxonomy_exists( $name ) ) {

									$terms = array();

									if ( function_exists( 'wc_get_product_terms' ) ) {
										$terms = wc_get_product_terms( $post->ID, $name, array( 'fields' => 'all' ) );
									}

									foreach ( $terms as $term ) {
										if ( ! in_array( $term->slug, $options, true ) ) {
											continue;
										}
										$value = ywccl_get_term_meta( $term->term_id, '_yith_wccl_value', true, $name );
										echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $selected_value ), sanitize_title( $term->slug ), false ) . ' data-value="' . esc_attr( $value ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name ) ) . '</option>';
									}
								} else {

									foreach ( $options as $option ) {
										// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
										$selected = sanitize_title( $selected_value ) === $selected_value ? selected( $selected_value, sanitize_title( $option ), false ) : selected( $selected_value, $option, false );
										echo '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
									}
								}
							}
							?>
						</select>

						<?php echo end( $attribute_keys ) === $name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>' ) ) : ''; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<div class="single_variation_wrap" style="display:none;">
			<?php
			/**
			 * Hook woocommerce_before_single_variation.
			 */
			do_action( 'woocommerce_before_single_variation' );

			/**
			 * Hook woocommerce_single_variation. Used to output the cart button and placeholder for variation data.
			 *
			 * @since  2.4.0
			 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
			 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
			 */
			do_action( 'woocommerce_single_variation' );

			/**
			 * Hook woocommerce_after_single_variation.
			 */
			do_action( 'woocommerce_after_single_variation' );
			?>
		</div>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>

<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>
