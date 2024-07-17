<?php
//
add_filter('woocommerce_cart_needs_shipping', 'sd_filter_cart_needs_shipping');
function sd_filter_cart_needs_shipping($needs_shipping)
{
    if (is_cart()) {
        WC()->session->__unset('selected_shipping');
        Ship_Depot_Logger::wrlog('[sd_filter_cart_needs_shipping] cart' . print_r(WC()->cart, true));
        $needs_shipping = false;
    }

    // if (is_checkout()) {
    //     WC()->session->__unset('selected_shipping');
    //     $needs_shipping = false;
    // }
    return $needs_shipping;
}
