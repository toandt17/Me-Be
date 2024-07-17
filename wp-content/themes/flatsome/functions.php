<?php
/**
 * Flatsome functions and definitions
 *
 * @package flatsome
 */
update_option( 'flatsome_wup_purchase_code', '846b8d75-85b3-4b3e-976c-2e29d386339d' );
update_option( 'flatsome_wup_supported_until', '01.01.2050' );
update_option( 'flatsome_wup_buyer', 'thuthuatdev.com' );
update_option( 'flatsome_wup_sold_at', time() );
delete_option( 'flatsome_wup_errors');
require get_template_directory() . '/inc/init.php';
/**
 * Note: It's not recommended to add any custom code here. Please use a child theme so that your customizations aren't lost during updates.
 * Learn more here: http://codex.wordpress.org/Child_Themes
 */

//Bán chạy
// Thêm nhãn "Bán chạy" vào sản phẩm
function add_best_seller_label() {
    global $product;

    // Kiểm tra nếu sản phẩm là "Bán chạy" (meta key: best_seller, value: 1)
    $is_best_seller = get_post_meta($product->get_id(), 'best_seller', true);

    if ($is_best_seller) {
        echo '<div class="best-seller-label">
    <img src="https://cdn1.concung.com/themes/desktop4.1/image/v40/icon/fire-deal.svg" alt="Bán chạy">
    <span class="best-seller-text">Bán chạy</span>
</div>
';
    }
}

// Gắn hàm vào hook phù hợp
add_action('woocommerce_after_shop_loop_item_title', 'add_best_seller_label', 15);



// Hiển thị phần trăm giảm giá trên trang sản phẩm
add_filter('woocommerce_get_price_html', 'show_sale_percentage_after_price', 100, 2);

function show_sale_percentage_after_price($price, $product) {
    if ( $product->is_on_sale() ) {
        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();

        if ($regular_price && $sale_price) {
            $percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
            $price .= ' <span class="sale-percentage">-' . $percentage . '%</span>';
        }
    }

    return $price;
}


// Hiển thị khuyến mãi sau giá sản phẩm trên trang chi tiết sản phẩm
add_action('woocommerce_single_product_summary', 'add_promotion_after_price', 25);

function add_promotion_after_price() {
    global $product;
    if ( $product->is_on_sale() ) {
        echo '<div class="promotion"><img src="https://concung.com/img/res/payment/zalopay.png" alt="ZaloPay"> Giảm 60.000đ cho đơn từ 899.000đ khi nhập mã ConCung60 thanh toán ví ZaloPay</div>';
    }
}


//bình luận
function custom_comment_script() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#commentform').submit(function(event) {
            var commentContent = $('#comment').val();
            var blacklistedWords = ['chết', 'bậy', 'tự tử', 'shit']; // Các từ ngữ cấm

            var found = blacklistedWords.some(word => commentContent.includes(word));
            if (found) {
                event.preventDefault(); // Ngăn form gửi mặc định nếu có từ cấm
                // Hiển thị thông báo lỗi trong form
                if ($('#comment-error').length === 0) {
                    $('#commentform').prepend('<div id="comment-error" style="color: red; margin-bottom: 10px;">Bình luận của bạn chứa từ ngữ không phù hợp và đã bị chặn.</div>');
                }
            } else {
                $('#comment-error').remove(); // Xóa thông báo lỗi nếu không có từ cấm
            }
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'custom_comment_script');


function filter_product_reviews( $commentdata ) {
    $blacklisted_words = array( 'chết', 'bậy', 'tự tử', 'shit' ); // Thêm từ ngữ bạn muốn chặn vào đây
    foreach ( $blacklisted_words as $word ) {
        if ( strpos( $commentdata['comment_content'], $word ) !== false ) {
            wp_die( 'Bình luận của bạn chứa từ ngữ không phù hợp và đã bị chặn.' );
        }
    }
    return $commentdata;
}
add_filter( 'preprocess_comment', 'filter_product_reviews' );



// Thêm nút "Mua ngay" dưới nút giỏ hàng trên trang sản phẩm
add_action( 'woocommerce_after_add_to_cart_button', 'add_buy_now_button' );

function add_buy_now_button() {
    global $product;
    $buy_now_url = esc_url( add_query_arg( 'buy_now', 'true', $product->get_permalink() ) );
    echo '<a href="' . $buy_now_url . '" class="button buy-now-button">Mua ngay</a>';
}

// Xử lý hành động "Mua ngay"
add_action( 'template_redirect', 'handle_buy_now_action' );

function handle_buy_now_action() {
    if ( isset( $_GET['buy_now'] ) && 'true' === $_GET['buy_now'] ) {
        global $woocommerce;
        $product_id = get_the_ID();
        $found = false;

        // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
        foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
            $_product = $values['data'];
            if ( $_product->get_id() == $product_id ) {
                $found = true;
            }
        }

        // Nếu chưa có trong giỏ hàng, thêm sản phẩm vào giỏ hàng
        if ( ! $found ) {
            WC()->cart->add_to_cart( $product_id );
        }

        // Chuyển hướng đến trang thanh toán
        wp_redirect( wc_get_checkout_url() );
        exit;
    }
}
