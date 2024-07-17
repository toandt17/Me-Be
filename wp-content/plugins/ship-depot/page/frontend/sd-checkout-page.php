<?php
if (is_plugin_active('checkout-for-woocommerce/checkout-for-woocommerce.php')) {
    Ship_Depot_Logger::wrlog('[Show shipping tab] CheckoutWC plugin is active');
    add_filter('cfw_show_shipping_tab', function ($is_show) {
        Ship_Depot_Logger::wrlog('[Show shipping tab] cfw_show_shipping_tab return false');
        return false;
    }, 10, 1);
}

//
add_filter('woocommerce_shipping_packages', 'hide_shipping_rates_from_packages', 10, 1);
function hide_shipping_rates_from_packages($packags)
{
    return array();
}

add_filter('woocommerce_cart_needs_shipping_address', 'set_cart_needs_shipping_address_filter');
function set_cart_needs_shipping_address_filter($condition)
{
    Ship_Depot_Logger::wrlog('[set_cart_needs_shipping_address_filter] condition: ' . print_r($condition, true));
    return true;
}

add_action('wp_ajax_save_notes_session', 'save_notes_session_init');
add_action('wp_ajax_nopriv_save_notes_session', 'save_notes_session_init');
function save_notes_session_init()
{
    Ship_Depot_Logger::wrlog('[save_notes_session_init] _POST: ' . print_r($_POST, true));
    $notes = (isset($_POST['notes'])) ? sanitize_textarea_field($_POST['notes']) : '';
    WC()->session->set('shipping_notes', $notes);
    wp_send_json_success('success');
}

add_filter('woocommerce_cart_ready_to_calc_shipping', 'sd_show_shipping_in_checkout_page');
function sd_show_shipping_in_checkout_page($isShow)
{
    return false;
}

// Disable all payment gateways on the checkout page and replace the "Pay" button with "Place order".
//add_filter('woocommerce_cart_needs_payment', '__return_false');


//Current hook: woocommerce_review_order_before_order_total => after list item
//New hook: woocommerce_review_order_before_submit => before place order
//New hook: woocommerce_review_order_before_payment => before payment
//New hook: woocommerce_checkout_before_terms_and_conditions => before term and conditions
add_action('woocommerce_checkout_before_terms_and_conditions', 'sd_woocommerce_review_order_before_order_total');
function sd_woocommerce_review_order_before_order_total()
{
    Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] needs_shipping_address: ' . print_r(WC()->cart->needs_shipping_address(), true));
    // Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] _POST: ' . print_r($_POST, true));
    // Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] _GET: ' . print_r($_GET, true));
    Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] cart get_total: ' . print_r(WC()->cart->get_total('number'), true));
    $str_courier_setting = get_option('sd_setting_courier');
    if (!Ship_Depot_Helper::check_null_or_empty($str_courier_setting)) {
        $courier_setting = json_decode($str_courier_setting);
        //Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] courier_setting: ' . print_r($courier_setting, true));
    }

    $list_cart_items =  WC()->cart->get_cart_contents();
    $list_packages_sizes = [];
    $list_items = [];
    $total_qty = 0;
    $item_regular_price_total = 0;
    if (!empty($list_cart_items)) {
        foreach ($list_cart_items as $item) {
            //Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] item: ' . print_r($item, true));
            Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] item qty: ' . print_r($item['quantity'], true));
            $total_qty += floatval($item['quantity']);
            $product = $item['data'];
            $product_data = $product->get_data();
            //Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] product_data: ' . print_r($product_data, true));
            $package_size = new Ship_Depot_Package();
            $package_size->Length = isset($product_data['length']) ? Ship_Depot_Helper::ConvertToShipDepotDimension($product_data['length']) : 0;
            $package_size->Width = isset($product_data['width']) ? Ship_Depot_Helper::ConvertToShipDepotDimension($product_data['width']) : 0;
            $package_size->Height = isset($product_data['height']) ? Ship_Depot_Helper::ConvertToShipDepotDimension($product_data['height']) : 0;
            $package_size->Weight = isset($product_data['weight']) ? Ship_Depot_Helper::ConvertToShipDepotWeight($product_data['weight']) : 0;

            $it = new Ship_Depot_Item();
            $it->Sku = $product_data['sku'];
            $it->ID = $item['product_id'];
            $it->Name = $product_data['name'];
            $it->Quantity = $item['quantity'];
            $it->TotalPrice = $item['line_total'];
            $it->Length = isset($product_data['length']) ? Ship_Depot_Helper::ConvertToShipDepotDimension($product_data['length']) : 0;
            $it->Width = isset($product_data['width']) ? Ship_Depot_Helper::ConvertToShipDepotDimension($product_data['width']) : 0;
            $it->Height = isset($product_data['height']) ? Ship_Depot_Helper::ConvertToShipDepotDimension($product_data['height']) : 0;
            $it->Weight = isset($product_data['weight']) ? Ship_Depot_Helper::ConvertToShipDepotWeight($product_data['weight']) : 0;
            //
            $regular_price = $product_data['regular_price'];
            $item_regular_price_total += floatval($regular_price) * floatval($item['quantity']);
            $it->RegularPrice = $regular_price;
            // Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] it: ' . print_r($it, true));
            //
            array_push($list_packages_sizes, $package_size);
            array_push($list_items, $it);
        }
    }
    //Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] list_packages_sizes: ' . print_r($list_packages_sizes, true));

    $shipping_address = WC()->customer->get_shipping();
    //Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] customer: ' . print_r(WC()->customer, true));
    //Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] shipping_address: ' . print_r($shipping_address, true));
    $receiver = new Ship_Depot_Receiver();
    $receiver->FirstName = $shipping_address['first_name'];
    $receiver->LastName = $shipping_address['last_name'];
    $receiver->Province = $shipping_address['city'];
    $receiver->Address = $shipping_address['address_1'];
    $receiver->Phone = $shipping_address['phone'];
    //detect ship to shipping or billing
    $cur_shipping_fee = GetShippingFeeSession();
    if (isset($_POST['post_data']) && !Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST['post_data']))) {
        $post_data = sanitize_text_field($_POST['post_data']);
        // Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] post_data: ' . print_r($post_data, true));
        $data = array();
        $vars = explode('&', $post_data);
        foreach ($vars as $k => $value) {
            $v = explode('=', urldecode($value));
            $data[$v[0]] = $v[1];
        }
        Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] data: ' . print_r($data, true));
        if (isset($data['ship_to_different_address']) && $data['ship_to_different_address'] == '1' && !wc_ship_to_billing_address_only()) {
            Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] ship_to_different_address: ' . print_r($data['ship_to_different_address'], true));
            //Shipping
            $receiver->District = isset($data['shipping_district']) && $data['shipping_district'] != SD_SELECT_DISTRICT_TEXT ? $data['shipping_district'] : '';
            $receiver->Ward = isset($data['shipping_ward']) && $data['shipping_ward'] != SD_SELECT_WARD_TEXT ? $data['shipping_ward'] : '';
        } else {
            //Billing
            $receiver->District = isset($data['billing_district']) && $data['billing_district'] != SD_SELECT_DISTRICT_TEXT ? $data['billing_district'] : '';
            $receiver->Ward = isset($data['billing_ward']) && $data['billing_ward'] != SD_SELECT_WARD_TEXT ? $data['billing_ward'] : '';
        }
    } else {
        //Billing
        $receiver->District = WC()->customer->get_meta('billing_district');
        $receiver->Ward = WC()->customer->get_meta('billing_ward');
    }

    Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] receiver: ' . print_r($receiver, true));
    if (!Ship_Depot_Helper::check_null_or_empty($receiver->Province) && !Ship_Depot_Helper::check_null_or_empty($receiver->District) && !Ship_Depot_Helper::check_null_or_empty($receiver->Ward) && !Ship_Depot_Helper::check_null_or_empty($receiver->Address)) {
        $strListStr = get_option('sd_list_storages');
        $selected_storage = null;
        if (!Ship_Depot_Helper::check_null_or_empty($strListStr)) {
            $listStr = json_decode($strListStr);
            if (count($listStr) > 0) {
                foreach ($listStr as $str) {
                    if ($str->IsDefault) {
                        $selected_storage = $str;
                    }
                }

                if ($selected_storage == null) {
                    $selected_storage = $listStr[0];
                }
            }
        }

        $str_sender_info = get_option('sd_sender_info');
        if (!Ship_Depot_Helper::check_null_or_empty($str_sender_info)) {
            $sender_info_obj = Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($str_sender_info);
            $sender_info = new Ship_Depot_Shop_Info($sender_info_obj);
        }
        Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] cart total: ' . print_r(WC()->cart->get_total('number'), true));
        Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] cart shipping total: ' . print_r(GetShippingFeeSession(), true));
        $cart_total_without_shipping = WC()->cart->get_total('number') - $cur_shipping_fee;
        //
        $is_cod = false;
        if (isset($_POST['payment_method']) && !Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST['payment_method']))) {
            $payment_method = sanitize_text_field($_POST['payment_method']);
            Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] payment_method: ' . print_r($payment_method, true));
            if (!Ship_Depot_Helper::check_null_or_empty($payment_method) && $payment_method == 'cod') {
                $is_cod = true;
            }
        }
        Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] is_cod: ' . print_r($is_cod, true));
        //
        $list_shipping = Ship_Depot_Order_Shipping::calculate_shipping_fee($is_cod, false, 0, $list_packages_sizes, $list_items, $receiver, isset($selected_storage) ? $selected_storage->WarehouseID : '', isset($sender_info) ? $sender_info : '', WC()->cart->get_subtotal(), $item_regular_price_total, WC()->cart->get_total('number'), $courier_setting);
        if ($list_shipping <= -1000) {
            Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] retry get list shipping 1 time');
            $list_shipping = Ship_Depot_Order_Shipping::calculate_shipping_fee($is_cod, false, 0, $list_packages_sizes, $list_items, $receiver, isset($selected_storage) ? $selected_storage->WarehouseID : '', isset($sender_info) ? $sender_info : '', WC()->cart->get_subtotal(), $item_regular_price_total, WC()->cart->get_total('number'), $courier_setting);
        }
    }
    $str_selected_shipping = WC()->session->get('selected_shipping');
    $selected_shipping = new Ship_Depot_Shipping_Fee_Response();
    if ($str_selected_shipping != false && !Ship_Depot_Helper::check_null_or_empty($str_selected_shipping)) {
        $selected_shipping =  new Ship_Depot_Shipping_Fee_Response(json_decode($str_selected_shipping));
        //Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] selected_shipping: ' . print_r($selected_shipping, true));
    }
?>

    <tr>
        <td colspan="2">
            <div class="sd-div" id="shipping_fee_content">
                <!-- The Modal -->
                <div id="myModal" class="sd-modal">
                    <!-- Modal content -->
                    <div class="sd-modal-content" style="text-align:center">
                        <p style="text-align: left;"><?php _e('Vui lòng chọn 1 trong những địa điểm dưới đây để tạo vận đơn Giao hàng tiết kiệm.', 'ship-depot-translate') ?></p>
                        <select id="slGHTKHamlet" style="width: 100%;margin: 25px 0;">
                        </select>
                        <a id="btnModalOK" href="javascript:;" class="button-a">Đã chọn</a>
                    </div>
                </div>
                <input type="hidden" id="ghtkHamlet" name="shipdepot[receiver][ghtkHamlet]" value="" />
                <?php
                // Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] list_shipping: ' . print_r($list_shipping, true));
                ?>
                <p style="font-size: 1.5rem; color:#0064ff; margin-bottom: 10px;"><?php _e('Vui lòng chọn đơn vị vận chuyển trước khi bấm đặt hàng.', 'ship-depot-translate') ?></p>
                <?php
                if (isset($list_shipping) && $list_shipping > 0) {
                    foreach ($list_shipping as $courier_obj) {
                        $courier = new Ship_Depot_Courier_Response($courier_obj);
                        $courier_clone = new Ship_Depot_Courier_Response();
                        foreach ($courier as $key => $value) {
                            $courier_clone->$key = $courier->$key;
                        }
                        $courier_clone->ListServices = [];
                        $courier_clone->LogoURL = "";
                ?>
                        <div id="courier_<?php echo esc_attr($courier->CourierID) ?>" class="courier-fee">
                            <img src="<?php echo esc_url($courier->LogoURL) ?>" alt="<?php echo esc_attr($courier->CourierName) ?>" data-placement="bottom" title="<?php echo esc_attr($courier->CourierName) ?>">

                            <input type="hidden" name="shipdepot_courier_data_<?php echo esc_attr($courier->CourierID) ?>" value="<?php echo esc_attr(Ship_Depot_Helper::ParseObjectToJsonHTML($courier_clone)) ?>" />
                            <div>
                                <?php foreach ($courier->ListServices as $serv_obj) {
                                    $serv = new Ship_Depot_Shipping_Fee_Response($serv_obj);
                                    //
                                    $json = json_encode($serv, JSON_UNESCAPED_UNICODE);
                                    $json_html = str_replace('"', "'", $json);
                                    //check selected
                                    $is_selected = false;
                                    if (!isset($selected_shipping) || Ship_Depot_Helper::check_null_or_empty($selected_shipping->CourierID) || Ship_Depot_Helper::check_null_or_empty($selected_shipping->ServiceCode)) {
                                        $selected_shipping = $serv;
                                    }

                                    if ($selected_shipping->CourierID == $serv->CourierID && $selected_shipping->ServiceCode == $serv->ServiceCode) {
                                        $is_selected = true;
                                        Ship_Depot_Logger::wrlog('[CheckData] Set selected_shipping session');
                                        WC()->session->set('selected_shipping', $json);
                                    }
                                ?>
                                    <div class="service-fee">
                                        <input type="hidden" id="shipping_<?php echo esc_attr($serv->ServiceCode) ?>" name="shipdepot_shipping_data_<?php echo esc_attr($serv->ServiceCode) ?>" value="<?php echo esc_attr($json_html) ?>">
                                        <input type="radio" id="rd_<?php echo esc_attr($serv->ServiceCode) ?>" name="shipdepot_shipping_selected" class="radio_shipping_fee" value="<?php echo esc_attr($serv->ServiceCode) ?>" <?php checked($is_selected) ?> />
                                        <div class="service-fee-info">
                                            <label for="rd_<?php echo esc_attr($serv->ServiceCode) ?>" class="service-name">
                                                <?php echo esc_html($serv->ServiceName) ?>
                                            </label>
                                            <label class="fee">
                                                <?php echo esc_html(Ship_Depot_Helper::currency_format(($serv->ShopMarkupShippingFee->IsActive ? $serv->ShopMarkupShippingFee->ShippingFeeTotal : $serv->ShipDepotMarkupShippingFee->ShippingFeeTotal) + $serv->NoMarkupShippingFee->NoMarkupShippingFeeTotal)) ?>
                                            </label>
                                            <?php
                                            //logic pick at shop
                                            if (get_option('sd_show_shipping_fee_detail') == 'yes' && $courier->CourierID != PAS_COURIER_CODE) {
                                                $shipping_fee = $serv->ShopMarkupShippingFee->IsActive ? $serv->ShopMarkupShippingFee->ShippingFeeNet + $serv->ShopMarkupShippingFee->OtherFees : $serv->ShipDepotMarkupShippingFee->ShippingFeeNet + $serv->ShipDepotMarkupShippingFee->OtherFees; ?>
                                                <p class="text-description service-fee-description">
                                                    <?php echo esc_html__('Phí giao hàng:', 'ship-depot-translate') . ' ' . esc_html(Ship_Depot_Helper::currency_format($shipping_fee)) ?> +
                                                    <?php echo esc_html__('Phí thu hộ:', 'ship-depot-translate') . ' ' . esc_html(Ship_Depot_Helper::currency_format($serv->ShopMarkupShippingFee->IsActive ? $serv->ShopMarkupShippingFee->CODFee : $serv->ShipDepotMarkupShippingFee->CODFee)) ?> +
                                                    <?php echo esc_html__('Phí bảo hiểm:', 'ship-depot-translate') . ' ' . esc_html(Ship_Depot_Helper::currency_format($serv->ShopMarkupShippingFee->IsActive ? $serv->ShopMarkupShippingFee->InsuranceFee : $serv->ShipDepotMarkupShippingFee->InsuranceFee)) ?> +
                                                    <?php echo esc_html__('Phí giao thất bại:', 'ship-depot-translate') . ' ' . esc_html(Ship_Depot_Helper::currency_format($serv->NoMarkupShippingFee->CODFailedFee)) ?>
                                                </p>
                                            <?php
                                            }

                                            if ($courier->CourierID == PAS_COURIER_CODE) {
                                                if (Ship_Depot_Helper::check_null_or_empty($courier->PASAddress) || Ship_Depot_Helper::check_null_or_empty($courier->PASPhone)) {
                                                    if (!isset($sender_info)) {
                                                        $str_sender_info = get_option('sd_sender_info');
                                                        if (!is_null($str_sender_info) && !empty($str_sender_info)) {
                                                            $sender_info_obj = json_decode($str_sender_info);
                                                            $sender_info = new Ship_Depot_Shop_Info($sender_info_obj);
                                                        }
                                                    }
                                                    if (Ship_Depot_Helper::check_null_or_empty($courier->PASAddress)) {
                                                        $courier->PASAddress = esc_html($sender_info->Address . ', ' . $sender_info->Ward . ', ' . $sender_info->District . ', ' . $sender_info->City);
                                                    }

                                                    if (Ship_Depot_Helper::check_null_or_empty($courier->PASPhone)) {
                                                        $courier->PASPhone = $sender_info->Phone;
                                                    }
                                                } ?>
                                                <p style="color: var(--text-description-color);" class="text-description service-fee-description">
                                                    <?php echo esc_html__('Địa chỉ lấy hàng:', 'ship-depot-translate') . ' ' . esc_html($courier->PASAddress) ?>
                                                </p>
                                                <p class="text-description service-fee-description">
                                                    <?php echo esc_html__('Điện thoại:', 'ship-depot-translate') . ' ' . esc_html(Ship_Depot_Helper::format_phone($courier->PASPhone)) ?>
                                                </p>
                                                <?php
                                            }
                                            //
                                            if (!Ship_Depot_Helper::check_null_or_empty($serv->TimeExpected) && $courier->CourierID != PAS_COURIER_CODE) {
                                                if ($courier->CourierID == AHA_COURIER_CODE) {
                                                ?>
                                                    <p class="text-description service-fee-description">
                                                        <?php echo esc_html__('Thời gian di chuyển từ lúc lấy hàng:', 'ship-depot-translate') . ' ' . esc_html($serv->TimeExpected) ?>
                                                    </p>
                                                <?php
                                                } else {
                                                ?>
                                                    <p class="text-description service-fee-description">
                                                        <?php echo esc_html__('T.gian nhận hàng ước tính:', 'ship-depot-translate') . ' ' . esc_html($serv->TimeExpected) ?>
                                                    </p>
                                                <?php
                                                }
                                            }

                                            if ($courier->CODFailed->IsUsed && $courier->CODFailed->ContentCheckout->IsShow && !Ship_Depot_Helper::check_null_or_empty($courier->CODFailed->ContentCheckout->Content)) {
                                                ?>
                                                <p class="cod-failed-description"><?php echo esc_html(trim($courier->CODFailed->ContentCheckout->Content) . ' ' . Ship_Depot_Helper::currency_format($courier->CODFailed->CODFailedAmount)) ?></p>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    <?php
                    }

                    $notes = WC()->session->get('shipping_notes');
                    ?>
                    <div id="sd_note" style="<?php echo !isset($list_shipping) || !$list_shipping ? 'display:none;' : '' ?>">
                        <p><?php esc_html_e('Ghi chú cho đơn vị vận chuyển', 'ship-depot-translate') ?></p>
                        <textarea name="shipdepot_shipping_notes"><?php echo isset($notes) ? esc_textarea($notes) : '' ?></textarea>
                    </div>
                <?php
                } else {
                ?>
                    <div style="margin: 10px 0 20px 0; display: flex; flex-wrap: wrap;align-items: center;">
                        <p style="color:#0064ff;margin-right: 10px;"><?php _e('Nếu đã điền đủ thông tin thanh toán/vận chuyển mà không thấy danh sách đơn vị vận chuyển thì vui lòng bấm vào đây', 'ship-depot-translate') ?></p>
                        <a id="sd_reload_shipping"><?php _e('Tải lại', 'ship-depot-translate') ?></a>
                    </div>
                <?php
                }
                ?>
                <input type="hidden" id="SDOrderTotal" value="<?php echo esc_attr($cart_total_without_shipping + GetShippingFeeSession()) ?>" />
                <input type="hidden" id="SDOrderSubTotal" value="<?php echo esc_attr($cart_total_without_shipping) ?>" />
                <script type="text/javascript">
                    console.log('Set total');
                    jQuery(document).ready(function($) {
                        let spanEle = jQuery('tr[class=order-total]').find('.woocommerce-Price-amount');
                        if (spanEle.length <= 0) return;
                        let amountEle = jQuery(spanEle.children()[0]);
                        if (amountEle.length <= 0) return;
                        let currencySymbolEle = amountEle.children()[0];
                        // + currencySymbolEle.outerHTML
                        amountEle.html(formatVNCurrency(<?php echo esc_attr($cart_total_without_shipping + GetShippingFeeSession()) ?>));
                    });

                    //
                    console.log('Validate shipping');
                    if (document.querySelectorAll(".radio_shipping_fee[checked=checked]").length > 0) {
                        document.getElementById('place_order').disabled = false;
                    } else {
                        document.getElementById('place_order').disabled = true;
                    }
                </script>
            </div>
        </td>
    </tr>
<?php
    Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] Finish');
}

add_action('woocommerce_checkout_update_order_review', 'sd_woocommerce_checkout_update_order_review');
function sd_woocommerce_checkout_update_order_review($post_data)
{
    Ship_Depot_Logger::wrlog('[CheckData][sd_woocommerce_checkout_update_order_review]');
    $data = array();
    $vars = explode('&', $post_data);
    foreach ($vars as $k => $value) {
        $v = explode('=', urldecode($value));
        $data[$v[0]] = $v[1];
    }
    Ship_Depot_Logger::wrlog('[sd_woocommerce_checkout_update_order_review] data: ' . print_r($data, true));
    if (isset($data['shipdepot_shipping_selected']) && !Ship_Depot_Helper::check_null_or_empty($data['shipdepot_shipping_selected'])) {
        //Save selected shipping before binding shipping fee to get service code and courier id for reselect shipping after binding complete.
        $selected_shipping_id = $data['shipdepot_shipping_selected'];
        $selected_shipping = $data['shipdepot_shipping_data_' . $selected_shipping_id];
        Ship_Depot_Logger::wrlog('[CheckData][sd_woocommerce_checkout_update_order_review] selected_shipping_id: ' . print_r($selected_shipping_id, true));
        Ship_Depot_Logger::wrlog('[CheckData][sd_woocommerce_checkout_update_order_review] selected_shipping: ' . print_r($selected_shipping, true));
        WC()->session->set('selected_shipping', Ship_Depot_Helper::CleanJsonFromHTML($selected_shipping));
        Ship_Depot_Logger::wrlog('[CheckData][sd_woocommerce_checkout_update_order_review] get selected_shipping: ' . print_r(WC()->session->get('selected_shipping'), true));
    }
}

add_filter('woocommerce_calculated_total', 'sd_change_calculated_total', 10, 2);
function sd_change_calculated_total($total, $cart)
{
    //Ship_Depot_Logger::wrlog('[sd_change_calculated_total] cart: ' . print_r($cart, true));
    Ship_Depot_Logger::wrlog('[CheckData][sd_change_calculated_total] total: ' . print_r($total, true));
    //Ship_Depot_Logger::wrlog('[sd_change_calculated_total] _POST: ' . print_r($_POST, true));
    $shipping_fee = 0;
    $shipping_fee = GetShippingFeeSession();
    Ship_Depot_Logger::wrlog('[CheckData][sd_change_calculated_total] total aft: ' . print_r($total + $shipping_fee, true));
    return $total + $shipping_fee;
}

function GetShippingFeeSession()
{
    $json_selected_shipping = WC()->session->get('selected_shipping');
    if ($json_selected_shipping != false && !Ship_Depot_Helper::check_null_or_empty($json_selected_shipping)) {
        $selected_shipping = new Ship_Depot_Shipping_Fee_Response(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($json_selected_shipping));
        //Ship_Depot_Logger::wrlog('[sd_change_calculated_total] selected_shipping: ' . print_r($selected_shipping, true));
        return floatval(($selected_shipping->ShopMarkupShippingFee->IsActive ? $selected_shipping->ShopMarkupShippingFee->ShippingFeeTotal : $selected_shipping->ShipDepotMarkupShippingFee->ShippingFeeTotal) + $selected_shipping->NoMarkupShippingFee->NoMarkupShippingFeeTotal);
    }
    return 0;
}

function GetShippingFeeFromPostData($data)
{
    $selected_shipping_id = Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($data['shipdepot_shipping_selected'])) ? '' : sanitize_text_field($data['shipdepot_shipping_selected']);
    $json_selected_shipping = '';
    Ship_Depot_Logger::wrlog('[CheckData][GetShippingFeeFromPostData] selected_shipping_id: ' . print_r($selected_shipping_id, true));
    $json_selected_shipping = sanitize_text_field($data['shipdepot_shipping_data_' . $selected_shipping_id]);

    Ship_Depot_Logger::wrlog('[GetShippingFeeFromPostData] json_selected_shipping: ' . print_r($json_selected_shipping, true));
    if ($json_selected_shipping != false && !Ship_Depot_Helper::check_null_or_empty($json_selected_shipping)) {
        $selected_shipping = new Ship_Depot_Shipping_Fee_Response(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($json_selected_shipping));
        Ship_Depot_Logger::wrlog('[CheckData][GetShippingFeeFromPostData] selected_shipping: ' . print_r($selected_shipping, true));
        return floatval(($selected_shipping->ShopMarkupShippingFee->IsActive ? $selected_shipping->ShopMarkupShippingFee->ShippingFeeTotal : $selected_shipping->ShipDepotMarkupShippingFee->ShippingFeeTotal) + $selected_shipping->NoMarkupShippingFee->NoMarkupShippingFeeTotal);
    }
    return 0;
}

function GetSelectedShippingFromPostData($data): string
{
    $selected_shipping_id = Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($data['shipdepot_shipping_selected'])) ? '' : sanitize_text_field($data['shipdepot_shipping_selected']);
    $json_selected_shipping = '';
    Ship_Depot_Logger::wrlog('[GetSelectedShippingFromPostData] selected_shipping_id: ' . print_r($selected_shipping_id, true));
    $json_selected_shipping = sanitize_text_field($data['shipdepot_shipping_data_' . $selected_shipping_id]);
    Ship_Depot_Logger::wrlog('[GetSelectedShippingFromPostData] json_selected_shipping: ' . print_r($json_selected_shipping, true));
    return Ship_Depot_Helper::CleanJsonFromHTML($json_selected_shipping);
}

add_action('woocommerce_checkout_order_processed', 'sd_action_checkout_order_processed', 10, 3);
function sd_action_checkout_order_processed($order_id, $posted_data, WC_Order $order)
{
    Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] order_id: ' . print_r($order_id, true));
    // Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] posted_data: ' . print_r($posted_data, true));
    Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] _POST: ' . print_r($_POST, true));
    // Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] order bf: ' . print_r($order, true));
    $shipping_fee = GetShippingFeeFromPostData($_POST); //GetShippingFeeSession();
    Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] shipping_fee: ' . print_r($shipping_fee, true));
    // get an instance of the order object
    if (!isset($order) || !$order || is_null($order)) {
        $order = wc_get_order($order_id);
    }
    //$order = wc_get_order($order_id);
    //Set shipping item
    $shippings = $order->get_items('shipping');
    if (count($shippings) <= 0) {
    } else {
        foreach ($order->get_items('shipping') as $item_id => $item) {
            //Ship_Depot_Logger::wrlog('shipping item: ' . print_r($item, true));
            $order->remove_item($item_id);
        }
    }

    $shipping_methods = Ship_Depot_Shipping_Zone::get_shipping_method(SHIP_DEPOT_SHIPPING_METHOD);
    Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] shipping_methods id: ' . json_encode($shipping_methods->id));
    $item = new WC_Order_Item_Shipping();
    $item->set_props(array('method_id' => $shipping_methods->id, 'total' => wc_format_decimal($shipping_fee)));
    $item->set_name('Ship Depot');
    $order->add_item($item);
    $order->calculate_shipping();
    $order->calculate_totals();
    // Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] order aft add shipping: ' . print_r($order, true));
    //
    //Save ship depot data
    //From checkout always not create ship, just create ship if status order matches status auto create ship
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_not_create_ship', json_encode(true));
    //
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_is_edit_order', 'yes');
    //Get list packages and list item
    $list_items = $order->get_items();
    $list_packages_sizes = [];
    foreach ($list_items as $item) {
        // Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] item: ' . print_r($item, true));
        $item_product = new WC_Order_Item_Product($item->get_id());
        // Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] item_product: ' . print_r($item_product, true));
        $product = $item_product->get_product();
        // Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] product: ' . print_r($product, true));
        // $item_product = new WC_Order_Item_Product($item);
        // $product = new WC_Product($item_product->get_product_id());

        $package_size = new Ship_Depot_Package();
        $package_size->Length = Ship_Depot_Helper::ConvertToShipDepotDimension($product->get_length());
        $package_size->Width = Ship_Depot_Helper::ConvertToShipDepotDimension($product->get_width());
        $package_size->Height = Ship_Depot_Helper::ConvertToShipDepotDimension($product->get_height());
        $package_size->Weight = Ship_Depot_Helper::ConvertToShipDepotWeight($product->get_weight());
        array_push($list_packages_sizes, $package_size);
    }
    Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] list_packages_sizes: ' . print_r($list_packages_sizes, true));
    $json_packages = json_encode($list_packages_sizes);
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_list_package_size', $json_packages);
    //sender
    $str_sender_info = get_option('sd_sender_info');
    if (!Ship_Depot_Helper::check_null_or_empty($str_sender_info)) {
        $sender_info_obj = Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($str_sender_info);
        $sender_info = new Ship_Depot_Shop_Info($sender_info_obj);
        Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] sender_info: ' . print_r($sender_info, true));
    }
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_sender_info', $str_sender_info);

    $strListStr = get_option('sd_list_storages');
    $selected_storage = null;
    if (!Ship_Depot_Helper::check_null_or_empty($strListStr)) {
        $listStr = json_decode($strListStr);
        foreach ($listStr as $str) {
            if ($str->IsDefault) {
                $selected_storage = $str;
            }
        }

        if ($selected_storage == null) {
            $selected_storage = $listStr[0];
        }

        Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] selected_storage: ' . print_r($selected_storage, true));
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_sender_storage', isset($selected_storage) ? $selected_storage->WarehouseID : '');
    }
    //insurance
    $insurance = new Ship_Depot_Insurance();
    $insurance->IsActive = false;
    $insurance->Value = 0;
    $json_insr = json_encode($insurance);
    Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] insurance: ' . print_r($insurance, true));
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_insurance', $json_insr);
    //cod
    $payment_method = $order->get_payment_method();
    Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] payment_method: ' . print_r($payment_method, true));
    $cod = new Ship_Depot_Cod();
    if (!Ship_Depot_Helper::check_null_or_empty($payment_method) && $payment_method == 'cod') {
        $cod->IsActive = true;
        Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] order total: ' . print_r($order->get_total(), true));
        $cod->Value = $order->get_total();
    } else {
        $cod->IsActive = false;
        $cod->Value = 0;
    }
    $json_cod = json_encode($cod);
    Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] cod: ' . print_r($cod, true));
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_cod', $json_cod);
    //receiver
    $receiver = new Ship_Depot_Receiver();
    $receiver->Type = 'current';
    $shipping_address = WC()->customer->get_shipping();
    Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] shipping_address: ' . print_r($shipping_address, true));
    $receiver->FirstName = $shipping_address['first_name'];
    $receiver->LastName = $shipping_address['last_name'];
    $receiver->Province = $shipping_address['city'];
    $receiver->Address = $shipping_address['address_1'];
    $receiver->Phone = $shipping_address['phone'];
    $GHTK_hamlet = Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST['shipdepot']['receiver']['ghtkHamlet'])) ? '' : sanitize_text_field($_POST['shipdepot']['receiver']['ghtkHamlet']);
    Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] GHTK_hamlet: ' . print_r($GHTK_hamlet), true);
    $receiver->Hamlet = $GHTK_hamlet;
    if (isset($posted_data['ship_to_different_address']) && $posted_data['ship_to_different_address'] == '1' && !wc_ship_to_billing_address_only()) {
        Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] ship_to_different_address: ' . print_r($posted_data['ship_to_different_address']), true);
        //Shipping
        $receiver->District = Ship_Depot_Helper::check_null_or_empty($posted_data['shipping_district']) ? '' : sanitize_textarea_field($posted_data['shipping_district']);
        $receiver->Ward = Ship_Depot_Helper::check_null_or_empty($posted_data['shipping_ward']) ? '' : sanitize_textarea_field($posted_data['shipping_ward']);
    } else {
        //Billing
        $receiver->District = Ship_Depot_Helper::check_null_or_empty($posted_data['billing_district']) ? '' : sanitize_textarea_field($posted_data['billing_district']);
        $receiver->Ward = Ship_Depot_Helper::check_null_or_empty($posted_data['billing_ward']) ? '' : sanitize_textarea_field($posted_data['billing_ward']);
    }
    Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] receiver: ' . print_r($receiver), true);
    $json_rcv = json_encode($receiver, JSON_UNESCAPED_UNICODE);
    Ship_Depot_Logger::wrlog('[sd_woocommerce_review_order_before_order_total] json_rcv: ' . print_r($json_rcv), true);
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_receiver', $json_rcv);
    //Set shipping phone
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, '_shipping_phone', $receiver->Phone);
    Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] order aft set shipping phone: ' . print_r($order, true));
    //
    //selected_shipping and selected_courier
    Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed]get selected_shipping: ' . print_r(WC()->session->get('selected_shipping'), true));
    $str_selected_shipping = '';
    if (Ship_Depot_Helper::check_null_or_empty(WC()->session->get('selected_shipping'))) {
        $str_selected_shipping = GetSelectedShippingFromPostData($_POST);
    } else {
        $str_selected_shipping = WC()->session->get('selected_shipping');
    }
    Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] str_selected_shipping: ' . print_r($str_selected_shipping, true));
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_selected_shipping', $str_selected_shipping);

    if ($str_selected_shipping != false && !Ship_Depot_Helper::check_null_or_empty($str_selected_shipping)) {
        $selected_shipping = new Ship_Depot_Shipping_Fee_Response(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($str_selected_shipping));
        Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] selected_shipping: ' . print_r($str_selected_shipping, true));
        //selected_courier
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_selected_courier', $selected_shipping->CourierID);

        //courier_info
        if (!Ship_Depot_Helper::check_null_or_empty($selected_shipping->CourierID)) {
            $json_selected_courier = sanitize_text_field($_POST['shipdepot_courier_data_' . $selected_shipping->CourierID]);
            if (!Ship_Depot_Helper::check_null_or_empty($json_selected_courier)) {
                $selected_courier_info = new Ship_Depot_Courier_Response(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($json_selected_courier));
                Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_selected_courier_info', json_encode($selected_courier_info, JSON_UNESCAPED_UNICODE));
                //cod_failed_info
                $cod_failed_info = $selected_courier_info->CODFailed;
                Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] json_cod_failed: ' . print_r(json_encode($cod_failed_info, JSON_UNESCAPED_UNICODE), true));
                Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_cod_failed_info', json_encode($cod_failed_info, JSON_UNESCAPED_UNICODE));
            }
        }
    } else {
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_selected_courier', '');
    }
    //shipping notes
    $shipping_notes = Ship_Depot_Helper::check_null_or_empty(sanitize_textarea_field($_POST['shipdepot_shipping_notes'])) ? '' : sanitize_textarea_field($_POST['shipdepot_shipping_notes']);
    Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] shipping_notes: ' . print_r($shipping_notes, true));
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_shipping_notes', $shipping_notes);
    //Create flag to detect order create from checkout
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_from_fe', json_encode(true));
    if (function_exists('sd_save_wc_order_other_fields')) {
        // unhook this function so it doesn't loop infinitely
        Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] unhook.');
        remove_action('save_post', 'sd_save_wc_order_other_fields', 10, 1);
        //
    }

    //Save order meta data to db
    Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] order save.');
    $order->save();

    if (function_exists('sd_save_wc_order_other_fields')) {
        // re-hook this function.
        Ship_Depot_Logger::wrlog('[sd_action_checkout_order_processed] re-hook.');
        add_action('save_post', 'sd_save_wc_order_other_fields', 10, 1);
    }

    WC()->session->__unset('selected_shipping');
    WC()->session->__unset('shipping_notes');
}

add_action('woocommerce_after_checkout_validation', 'sd_woocommerce_after_checkout_validation', 10, 2);
function sd_woocommerce_after_checkout_validation($data, $errors)
{
    Ship_Depot_Logger::wrlog('[sd_woocommerce_after_checkout_validation] data: ' . print_r($data, true));
    Ship_Depot_Logger::wrlog('[sd_woocommerce_after_checkout_validation] _POST: ' . print_r($_POST, true));
    // if (isset($_POST['sd_call_validate']) && sanitize_text_field($_POST['sd_call_validate']) == '1') {
    //     Ship_Depot_Logger::wrlog('[sd_woocommerce_after_checkout_validation] sd_call_validate: ' . print_r(sanitize_text_field($_POST['sd_call_validate']), true));
    //     Ship_Depot_Logger::wrlog('[sd_woocommerce_after_checkout_validation] add fake error');
    //     //Add fake error
    //     wc_add_notice(__("custom_notice", 'fake_error'), 'error');
    // }
    //Check address
    if (isset($data['ship_to_different_address']) && sanitize_text_field($data['ship_to_different_address']) == '1') {
        //Check shipping
    } else {
        //Check billing
    }

    if (isset($_POST['shipdepot_shipping_selected']) && Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST['shipdepot_shipping_selected']))) {
        $errors->add('selected_shipping', __('Vui lòng chọn đơn vị vận chuyển.', 'ship-depot-translate'));
    }
}

// add_action('woocommerce_before_thankyou', 'sd_woocommerce_before_thankyou');
// function sd_woocommerce_before_thankyou($order)
// {
// 
// }

add_action('woocommerce_thankyou', 'sd_woocommerce_thankyou');
function sd_woocommerce_thankyou($order)
{
    $json_selected_courier_info = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_selected_courier_info', true);
    Ship_Depot_Logger::wrlog('[create_ship] json_selected_courier_info: ' . $json_selected_courier_info);
    $selected_courier_info = new Ship_Depot_Courier_Response();
    if (!Ship_Depot_Helper::check_null_or_empty($json_selected_courier_info)) {
        $selected_courier_info = new Ship_Depot_Courier_Response(json_decode($json_selected_courier_info));
        if ($selected_courier_info->CourierID == PAS_COURIER_CODE) {
            $json_shipping = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_selected_shipping', true);
            // Ship_Depot_Logger::wrlog('[sd_woocommerce_thankyou] json_shipping: ' . $json_shipping);
            echo '<div style="margin-bottom: 20px;">';
            if (!Ship_Depot_Helper::check_null_or_empty($json_shipping)) {
                $selected_shipping = new Ship_Depot_Shipping_Fee_Response(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($json_shipping));
                echo '<div><b>' . esc_html($selected_shipping->ServiceName) . '.</b></div>';
            }

            echo '<div><b>' . esc_html__('Địa chỉ lấy hàng:', 'ship-depot-translate') . ' ' . esc_html($selected_courier_info->PASAddress) . '</b></div>';
            echo '<div><b>' . esc_html__('Điện thoại:', 'ship-depot-translate') . ' ' . esc_html(Ship_Depot_Helper::format_phone($selected_courier_info->PASPhone)) . '</b></div>';
            echo '</div>';
        }
    }
}

// add_action('woocommerce_order_details_after_customer_address', 'sd_woocommerce_order_details_after_customer_address', 10, 2);
// function sd_woocommerce_order_details_after_customer_address($address_type, $order)
// {
// }
