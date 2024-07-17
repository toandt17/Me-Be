<?php
// Save the data of the Meta field
add_action('woocommerce_new_order', 'sd_woocommerce_new_order_action', 10, 2);

function sd_woocommerce_new_order_action($order_id, $order)
{
    Ship_Depot_Logger::wrlog('[sd_woocommerce_new_order_action] Begin');
    //Verify that the nonce is valid.
    Ship_Depot_Logger::wrlog('[sd_woocommerce_new_order_action] order_id: ' . print_r($order_id, true));
    // Ship_Depot_Logger::wrlog('[sd_woocommerce_new_order_action] order: ' . print_r($order, true));
}

add_action('woocommerce_update_order', 'sd_save_wc_order_other_fields', 10, 1);
if (!function_exists('sd_save_wc_order_other_fields')) {

    function sd_save_wc_order_other_fields($order_id)
    {
        Ship_Depot_Logger::wrlog('[sd_save_wc_order_other_fields] Begin');
        //Verify that the nonce is valid.
        Ship_Depot_Logger::wrlog('[sd_save_wc_order_other_fields] order_id: ' . print_r($order_id, true));
        Ship_Depot_Logger::wrlog('[sd_save_wc_order_other_fields] _POST: ' . print_r($_POST, true));

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            Ship_Depot_Logger::wrlog('[sd_save_wc_order_other_fields] DOING_AUTOSAVE');
            return $order_id;
        }

        // Check the user's permissions.
        if (!isset($_POST['action'])) {
            Ship_Depot_Logger::wrlog('[sd_save_wc_order_other_fields] action not found');
            return $order_id;
        }

        Ship_Depot_Logger::wrlog('[sd_save_wc_order_other_fields] action: ' . sanitize_key($_POST['action']));
        if ('edit_order' == sanitize_key($_POST['action']) || 'editpost' == sanitize_key($_POST['action'])) {
            if (!current_user_can('edit_post', $order_id)) {
                return $order_id;
            }
        }

        if (!isset($_POST['sd_order_detail_nonce']) || !wp_verify_nonce($_POST['sd_order_detail_nonce'], 'sd_order_detail')) {
            Ship_Depot_Logger::wrlog('[sd_save_wc_order_other_fields] Verify nonce failed.');
            return $order_id;
        }
        Ship_Depot_Logger::wrlog('[sd_save_wc_order_other_fields] Start SAVE.');
        //
        $str_ship_info = Ship_Depot_Helper::GetOrderMetadata($order_id, 'sd_ship_info', true);
        Ship_Depot_Logger::wrlog('[sd_save_wc_order_other_fields] str_ship_info: ' . $str_ship_info);
        if (!Ship_Depot_Helper::check_null_or_empty($str_ship_info)) {
            //Tạm thời ko cho sửa vận đơn
            //sd_submit_data_and_save_to_order_meta_data($order_id, true);
            sd_edit_cod_amount($order_id);
        } else {
            sd_woocommerce_new_shipping($order_id);
        }
    }
}

//add_action('woocommerce_new_order', 'sd_woocommerce_new_order', 10, 1);
function sd_edit_cod_amount($order_id)
{
    //Ship_Depot_Logger::wrlog('[sd_edit_cod_amount] _POST: ' . print_r($_POST, true));
    Ship_Depot_Logger::wrlog('[sd_edit_cod_amount] order_id: ' . $order_id);
    Ship_Depot_Logger::wrlog('[sd_edit_cod_amount] is_cod_edit: ' . sanitize_text_field(['shipdepot']['is_cod_edit']));
    Ship_Depot_Logger::wrlog('[sd_edit_cod_amount] cod_edit_amount: ' . sanitize_text_field($_POST['shipdepot']['cod_edit_amount']));
    if (isset($_POST['shipdepot']['is_cod_edit']) && !Ship_Depot_Helper::check_null_or_empty(sanitize_text_field($_POST['shipdepot']['is_cod_edit'])) && sanitize_text_field($_POST['shipdepot']['is_cod_edit']) == 'true') {
        $order = wc_get_order($order_id);
        $get_data = new Ship_Depot_Get_Data($_POST, $order_id);
        $str_cod = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_cod', true);
        if (!Ship_Depot_Helper::check_null_or_empty($str_cod)) {
            $cod = new Ship_Depot_Cod(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($str_cod));
            if ($cod->Value == $get_data->get_cod_edit_amount()) {
                return;
            }
        }
        $shop_api_key = get_option('sd_api_key');
        $data_input = new stdClass();
        $ship_info = json_decode(Ship_Depot_Helper::GetOrderMetadata($order, 'sd_ship_info', true));
        Ship_Depot_Logger::wrlog('[sd_edit_cod_amount] ship_info: ' . print_r($ship_info, true));
        $data_input->tracking_number = isset($ship_info->TrackingNumber) ? $ship_info->TrackingNumber : '';
        //
        $selected_courier = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_selected_courier', true);
        $data_input->selected_courier = $selected_courier;
        Ship_Depot_Logger::wrlog('[sd_edit_cod_amount] data_input: ' . print_r($data_input, true));
        if (Ship_Depot_Helper::check_null_or_empty($data_input->tracking_number)) {
            $order_note = __('Thay đổi tiền thu hộ thất bại. Lý do: Không tìm thấy mã vận đơn.', 'ship-depot-translate');
        }

        if (Ship_Depot_Helper::check_null_or_empty($data_input->selected_courier)) {
            $order_note = __('Thay đổi tiền thu hộ cho vận đơn [Tracking_number] thất bại. Lý do: Không xác định được đơn vị vận chuyển để hủy.', 'ship-depot-translate');
        }
        //

        $data_input->cod_amount = $get_data->get_cod_edit_amount();
        //
        $url = SHIP_DEPOT_HOST_API . '/Shipping/UpdateCODAmount';
        $rs = Ship_Depot_Helper::http_post_php($url, Ship_Depot_Helper::ParseObjectToArray($data_input), array('ShopAPIKey' => $shop_api_key));
        Ship_Depot_Logger::wrlog('[sd_edit_cod_amount] result call API: ' . print_r($rs, true));
        if ($rs->Code > 0) {
            $result = $rs->Data;
            Ship_Depot_Logger::wrlog('[sd_edit_cod_amount] result: ' . print_r($result, true));
            if ($result) {
                $old_cod = 0;
                //Update cod object save in order
                if (isset($cod)) {
                    $old_cod = $cod->Value;
                    $cod->Value = $data_input->cod_amount;
                    $json_cod = json_encode($cod);
                    Ship_Depot_Helper::UpdateOrderMetadata($order_id, 'sd_cod', $json_cod);
                }

                $order_note = __('Thay đổi tiền thu hộ cho vận đơn [Tracking_number] thành công. Tiền thu hộ thay đổi từ [from] sang [to].', 'ship-depot-translate');
                $order_note = str_replace('[from]', Ship_Depot_Helper::currency_format($old_cod), $order_note);
                $order_note = str_replace('[to]', Ship_Depot_Helper::currency_format($data_input->cod_amount), $order_note);
            } else {
                $order_note = __('Thay đổi tiền thu hộ cho vận đơn [Tracking_number] thất bại. Lý do:', 'ship-depot-translate');
                $order_note = $order_note . ' ' . $rs->Msg;
            }
        } else {
            $order_note = __('Thay đổi tiền thu hộ cho vận đơn [Tracking_number] thất bại. Lý do:', 'ship-depot-translate');
            $order_note = $order_note . ' ' . $rs->Msg;
        }

        $order_note = str_replace('[Tracking_number]', $ship_info->TrackingNumber, $order_note);
        $order->add_order_note($order_note);
    } else {
        Ship_Depot_Logger::wrlog('[sd_edit_cod_amount] is_cod_edit: ' . sanitize_text_field($_POST['shipdepot']['is_cod_edit']) . '=> do nothing');
    }
}

function sd_woocommerce_new_shipping($order_id)
{
    $order = wc_get_order($order_id);
    Ship_Depot_Logger::wrlog('[sd_woocommerce_new_shipping] Start.');
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_is_edit_order', 'yes');
    //
    Ship_Depot_Logger::wrlog('[sd_woocommerce_new_shipping] order_id: ' . $order_id);
    Ship_Depot_Logger::wrlog('[sd_woocommerce_new_shipping] order status: ' . $order->get_status());
    Ship_Depot_Logger::wrlog('[sd_woocommerce_new_shipping] _POST: ' . print_r($_POST, true));
    Ship_Depot_Logger::wrlog('[sd_woocommerce_new_shipping] shipdepot data: ' . print_r($_POST['shipdepot'], true));
    if ($order->get_status() == 'cancelled') {
        Ship_Depot_Logger::wrlog('[sd_woocommerce_new_shipping] Cancel order => Not create shipping.');
    } else {
        $not_create_ship = false;
        if (isset($_POST['shipdepot']['notCreateShipping'])) {
            $not_create_ship = Ship_Depot_Helper::get_data_from_checkbox(sanitize_text_field($_POST['shipdepot']['notCreateShipping']));
        }
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_not_create_ship', json_encode($not_create_ship),);
        sd_submit_data_and_save_to_order_meta_data($order, false, $not_create_ship);
    }
}

function sd_submit_data_and_save_to_order_meta_data(WC_Order $order, $is_edit, $save_only)
{
    $need_call_update = false;

    //Check api key first
    $shop_api_key = get_option('sd_api_key');
    if (Ship_Depot_Helper::check_null_or_empty($shop_api_key)) {
        Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] API Key is empty => cannot save other info');
        if (function_exists('sd_save_wc_order_other_fields')) {
            // unhook this function so it doesn't loop infinitely
            Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] unhook.');
            remove_action('woocommerce_update_order', 'sd_save_wc_order_other_fields', 10);
            //
        }

        //Save order meta data to db
        Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] order save.');
        $order->save();

        if (function_exists('sd_save_wc_order_other_fields')) {
            // re-hook this function.
            Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] re-hook.');
            add_action('woocommerce_update_order', 'sd_save_wc_order_other_fields', 10, 1);
        }
        return false;
    }
    $get_data = new Ship_Depot_Get_Data($_POST, $order->get_id());

    //Create flag to detect order create from checkout
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_from_fe', json_encode(false));

    //List package sizes
    $list_package_size = $get_data->get_package_sizes();
    $json_packages = json_encode($list_package_size);
    if ($is_edit) {
        $need_call_update = compare_to_order_meta_and_save($order, 'sd_list_package_size', $json_packages);
    } else {
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_list_package_size', $json_packages);
    }

    //sender
    $sender_storage = $get_data->get_json_sender_storage();
    if ($is_edit) {
        $need_call_update = compare_to_order_meta_and_save($order, 'sd_sender_storage', $sender_storage);
    } else {
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_sender_storage', $sender_storage);
    }

    $str_sender_info = $get_data->get_json_sender_info();
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_sender_info', $str_sender_info);

    //receiver
    $receiver = $get_data->get_receiver_info();
    $json_rcv = json_encode($receiver, JSON_UNESCAPED_UNICODE);
    if ($is_edit) {
        $need_call_update = compare_to_order_meta_and_save($order, 'sd_receiver', $json_rcv);
    } else {
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_receiver', $json_rcv);
    }

    //insurance
    $insurance = $get_data->get_insurance_info();
    $json_insr = json_encode($insurance);
    if ($is_edit) {
        $need_call_update = compare_to_order_meta_and_save($order, 'sd_insurance', $json_insr);
    } else {
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_insurance', $json_insr);
    }

    //cod
    $cod = $get_data->get_cod_info();
    $json_cod = json_encode($cod);
    if ($is_edit) {
        $need_call_update = compare_to_order_meta_and_save($order, 'sd_cod', $json_cod);
    } else {
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_cod', $json_cod);
    }

    //selected_shipping
    $json_selected_shipping = $get_data->get_json_selected_shipping();
    Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] json_selected_shipping data: ' . print_r($json_selected_shipping, true));
    if ($is_edit) {
        $need_call_update = compare_to_order_meta_and_save($order, 'sd_selected_shipping', $json_selected_shipping);
    } else {
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_selected_shipping', $json_selected_shipping);
    }

    //selected_courier
    $selected_courier = $get_data->get_selected_courier();
    if ($is_edit) {
        $need_call_update = compare_to_order_meta_and_save($order, 'sd_selected_courier', $selected_courier);
    } else {
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_selected_courier', $selected_courier);
    }

    //selected_courier_info
    $json_selected_courier_info = $get_data->get_json_selected_courier_info();
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_selected_courier_info', $json_selected_courier_info);

    //Ship from station
    $ship_from_station = null;
    if ($selected_courier == GHN_COURIER_CODE) {
        $ship_from_station = $get_data->get_ship_from_station();
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_ship_from_station', json_encode($ship_from_station, JSON_UNESCAPED_UNICODE));
    } else {
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_ship_from_station', '');
    }
    Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] ship_from_station: ' . print_r($ship_from_station, true));
    //cod failed info
    $cod_failed_info =  $get_data->get_cod_failed_info();
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_cod_failed_info', json_encode($cod_failed_info, JSON_UNESCAPED_UNICODE));
    //shipping_notes
    $shipping_notes = $get_data->get_shipping_notes();
    if ($is_edit) {
        $need_call_update = compare_to_order_meta_and_save($order, 'sd_shipping_notes', $shipping_notes);
    } else {
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_shipping_notes', $shipping_notes);
    }

    //
    $cus_pay_ship = false;
    //Comment this code because we just use master account => shipping fee always shop pay
    //$cus_pay_ship = $get_data->get_is_customer_pay_ship();
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_customer_pay_shipping', json_encode($cus_pay_ship));

    //list_items
    $get_items = $get_data->get_items();
    Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] get_items: ' . print_r($get_items, true));
    $list_items = [];
    if (isset($get_items["list_items"])) {
        $list_items = $get_items["list_items"];
    }

    $json_items = json_encode($list_items, JSON_UNESCAPED_UNICODE);
    if ($is_edit) {
        $need_call_update = compare_to_order_meta_and_save($order, 'sd_list_items', $json_items);
    } else {
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_list_items', $json_items);
    }

    $item_regular_price_total = 0;
    if (isset($get_items["item_regular_price_total"])) {
        $item_regular_price_total = $get_items["item_regular_price_total"];
    }
    //
    Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] save_only: ' . $save_only);
    if (!$save_only) {
        Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] is_edit: ' . $is_edit);
        Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] need_call_update: ' . $need_call_update);
        if (!$is_edit || $need_call_update) {
            if ((Ship_Depot_Helper::check_null_or_empty($receiver->FirstName) &&  Ship_Depot_Helper::check_null_or_empty($receiver->LastName)) ||
                Ship_Depot_Helper::check_null_or_empty($receiver->Province) ||
                Ship_Depot_Helper::check_null_or_empty($receiver->District) ||
                Ship_Depot_Helper::check_null_or_empty($receiver->Ward) ||
                Ship_Depot_Helper::check_null_or_empty($receiver->Address) ||
                Ship_Depot_Helper::check_null_or_empty($receiver->Phone)
            ) {
                Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] $receiver properties null => Not call API');
                Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] $receiver: ' . print_r($receiver, true));
                // unhook this function so it doesn't loop infinitely
                Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] unhook.');
                remove_action('woocommerce_update_order', 'sd_save_wc_order_other_fields', 10);
                //
                //Save order meta data to db
                Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] order save.');
                $order->save();
                // re-hook this function.
                Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] re-hook.');
                add_action('woocommerce_update_order', 'sd_save_wc_order_other_fields', 10, 1);
                return;
            }

            Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] Call API');
            $order_created_date = $order->get_date_created();
            Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] order_created_date: ' . print_r($order_created_date, true));
            Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] order_created_date string: ' . print_r($order_created_date->__toString(), true));
            // $coupons_amount = 0;
            // // GET THE ORDER COUPON ITEMS
            // $coupon_items = $order->get_items('coupon');

            // Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] coupon_items: ' . print_r($coupon_items, true)); // For testing

            // // LOOP THROUGH ORDER COUPON ITEMS
            // foreach ($order_items as $coupon_id => $item) {
            //     // Get an instance of WC_Coupon object (necessary to use WC_Coupon methods)
            //     $coupon = new WC_Coupon($coupon_id);
            //     $coupons_amount += $coupon->get_amount();
            // }

            $data_input = array(
                "order_id" => $order->get_id(),
                "order_created_date" => $order_created_date->__toString(),
                "item_total" => $order->get_subtotal(),
                "order_total" => $order->get_total(),
                "selected_courier" => $selected_courier,
                "cod_failed_info" => $cod_failed_info,
                "shipping_notes" => $shipping_notes,
                "sender_info" => Ship_Depot_Helper::check_null_or_empty($str_sender_info) ? new stdClass : json_decode($str_sender_info),
                "sender_storage" => $sender_storage,
                "receiver" => $receiver,
                "insurance" => $insurance,
                "cod" => $cod,
                "cus_pay_ship" => $cus_pay_ship,
                "weight_unit" => SHIP_DEPOT_WEIGHT_UNIT,
                "measurement_unit" => SHIP_DEPOT_MEASUREMENT_UNIT,
                "list_package_sizes" => $list_package_size,
                "list_items" => $list_items,
                "item_regular_price_total" => $item_regular_price_total,
                "ship_from_station" => $ship_from_station,
                "total_discount_amount" => $order->get_discount_total(),
                "from_checkout" => false
            );


            if (Ship_Depot_Helper::check_null_or_empty($json_selected_shipping)) {
                $data_input["selected_shipping"] = new stdClass();
            } else {
                $data_input["selected_shipping"] = json_decode($json_selected_shipping);
            }

            if ($is_edit) {
                $ship_info = json_decode(Ship_Depot_Helper::GetOrderMetadata($order, 'sd_ship_info', true));
                $data_input["tracking_number"] = isset($ship_info->TrackingNumber) ? $ship_info->TrackingNumber : '';
                $data_input["shipment_ISN"] = isset($ship_info->ShipmentISN) ? $ship_info->ShipmentISN : '';
            } else {
                $data_input["tracking_number"] = '';
                $data_input["shipment_ISN"] = 0;
            }
            // Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] data_input: ' . print_r($data_input, true));
            $url = SHIP_DEPOT_HOST_API . '/Shipping/CreateShipping';
            $rs = Ship_Depot_Helper::http_post_php($url, $data_input, array('ShopAPIKey' => $shop_api_key));
            // Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] result call API: ' . print_r($rs, true));
            if ($rs->Code > 0) {
                if (!$is_edit) {
                    if (Ship_Depot_Helper::check_null_or_empty($rs->Data->TrackingNumber)) {
                        $order_note = __('Vận đơn tạo thất bại. Lý do:', 'ship-depot-translate');
                        $order_note = $order_note . ' ' . $rs->Msg;
                        $order->add_order_note($order_note);
                    } else {
                        $order_note = __('Vận đơn tạo thành công. Mã vận đơn', 'ship-depot-translate');
                        $order_note = $order_note . ' ' . $rs->Data->TrackingNumber;
                        $order->add_order_note($order_note);
                        if (!Ship_Depot_Helper::check_null_or_empty($rs->Data->ShipDepotID)) {
                            $order_note = 'order.id (GHTK): ' . $rs->Data->ShipDepotID;
                            $order->add_order_note($order_note);
                        }
                        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_ship_info', json_encode($rs->Data, JSON_UNESCAPED_UNICODE));
                    }
                }
            } else {
                $order_note = __('Vận đơn tạo thất bại. Lý do:', 'ship-depot-translate');
                $order_note = $order_note . ' ' . $rs->Msg;
                $order->add_order_note($order_note);
            }
        }
    }
    // unhook this function so it doesn't loop infinitely
    Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] unhook.');
    remove_action('woocommerce_update_order', 'sd_save_wc_order_other_fields', 10);
    //
    //Save order meta data to db
    Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] order save.');
    $order->save();
    // re-hook this function.
    Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] re-hook.');
    add_action('woocommerce_update_order', 'sd_save_wc_order_other_fields', 10, 1);
}

function compare_to_order_meta_and_save(WC_Order $order, $order_meta_key, $data): bool
{
    $meta_data = Ship_Depot_Helper::GetOrderMetadata($order, $order_meta_key, true);
    if (Ship_Depot_Helper::CompareData($meta_data, $data)) {
        Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'order_meta_key', $data);
        return true;
    }
    return false;
}

if (Ship_Depot_Helper::IsHPOS()) {
    add_filter('get_user_option_meta-box-order_woocommerce_page_wc-orders', 'metabox_order');
} else {
    add_filter('get_user_option_meta-box-order_shop_order', 'metabox_order');
}
//Arrange meta boxes
function metabox_order($order)
{
    Ship_Depot_Logger::wrlog('[metabox_order] $order: ' . json_encode($order));

    if (is_bool($order) && !$order) {
        $order = [];
        Ship_Depot_Logger::wrlog('[metabox_order] $order metabox is null.');
        $array_side_box = ["woocommerce-order-actions,woocommerce-order-notes"];
        $array_normal_box = ["woocommerce-order-data,sd_meta_boxes,woocommerce-order-items,postcustom,woocommerce-order-downloads"];
        $array_advance_box = [];
        $order['side'] = join(",", $array_side_box);
        $order['normal'] = join(",", $array_normal_box);
        $order['advanced'] = join(",", $array_advance_box);
    } else {
        $array_side_box = [];
        foreach (explode(",", $order['side']) as $side_box) {
            if ($side_box != 'sd_meta_boxes') {
                array_push($array_side_box, $side_box);
            }
        }

        $order['side'] = join(",", $array_side_box);
        //
        $array_normal_box = [];
        foreach (explode(",", $order['normal']) as $idx => $side_box) {
            if ($idx == 0) {
                array_push($array_normal_box, 'woocommerce-order-data');
                array_push($array_normal_box, 'sd_meta_boxes');
                array_push($array_normal_box, 'woocommerce-order-items');
            }

            if ($side_box != 'sd_meta_boxes' && $side_box != 'woocommerce-order-data' && $side_box != 'woocommerce-order-items') {
                array_push($array_normal_box, $side_box);
            }
        }

        $order['normal'] = join(",", $array_normal_box);
    }
    Ship_Depot_Logger::wrlog('[metabox_order] $order aft: ' . json_encode($order));
    return $order;
}

add_action('woocommerce_admin_order_totals_after_discount', 'sd_add_after_sub_total', 10, 1);
function sd_add_after_sub_total($order_id)
{
    Ship_Depot_Logger::wrlog('[sd_add_after_sub_total]');
    $order = wc_get_order($order_id);
?>
    <tr>
        <td class="label"><?php esc_html_e('Phí bảo hiểm (phí khai giá):', 'ship-depot-translate') ?></td>
        <td width="1%"></td>
        <td class="total" id="lb_insr_fee">0₫</td>

    </tr>
    <tr id="cod_price_content">
        <td class="label"><?php esc_html_e('Thu hộ (COD):', 'ship-depot-translate') ?></td>
        <td width="1%"></td>
        <td>
            <div id="cod_price">
                <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="shipdepot[advance][cod][value]" id="tb_cod_amount" class="no-spin" />
                <p class="description">₫</p>
            </div>
        </td>

    </tr>
    <tr>
        <td class="label"><?php esc_html_e('Phí thu hộ:', 'ship-depot-translate') ?></td>
        <td width="1%"></td>
        <td class="total" id="lb_cod_fee">0₫</td>
    </tr>
    <tr>
        <td class="label"><?php esc_html_e('Phí giao thất bại:', 'ship-depot-translate') ?></td>
        <td width="1%"></td>
        <td class="total" id="lb_cod_failed_fee">0₫</td>
    </tr>
    <tr>
        <td class="label" id="bf_shipping"><?php esc_html_e('Phí giao hàng:', 'ship-depot-translate') ?></td>
        <td width="1%"></td>
        <td class="total" id="lb_shipping_fee">0₫</td>
    </tr>
    <?php
    $is_edit = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_is_edit_order', true);
    $str_ship_info = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_ship_info', true);
    $idxShipping = 1;
    if (!$is_edit || $is_edit != 'yes' || Ship_Depot_Helper::check_null_or_empty($str_ship_info)) {
        $idxShipping = 2;
    ?>
        <tr style="display: none;">
            <td class="label" id="lb_cus_pay_ship"><?php esc_html_e('Khách hàng trả phí giao hàng:', 'ship-depot-translate') ?></td>
            <td width="1%"></td>
            <td class="total">
                <input name="shipdepot[customer_pay_shipping]" type="checkbox" id="cb_cus_pay_ship" />
            </td>
        </tr>
    <?php
    }
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            let isAddNew = jQuery('#is_add_new').val() == "true" ? true : false;
            let cod_amount = parseFloat(<?php echo esc_js($order->get_subtotal() - floatval($order->get_discount_total())) ?>);
            if ($('#selectedShipping').length > 0) {
                console.log('selected shipping existed');
                if (($('#cb_not_create_shipping').length > 0 && $('#cb_not_create_shipping').is(':checked')) || (!validateData2() && isAddNew)) {} else {
                    const json = $('#selectedShipping').val();
                    if (json != '') {
                        const serv = ParseHTMLJson(json);
                        console.log(serv)
                        //
                        console.log(serv);
                        $("#lb_insr_fee").text(formatVNCurrency(serv.ShopMarkupShippingFee.IsActive ? serv.ShopMarkupShippingFee.InsuranceFee : serv.ShipDepotMarkupShippingFee.InsuranceFee));
                        $("#lb_cod_fee").text(formatVNCurrency(serv.ShopMarkupShippingFee.IsActive ? serv.ShopMarkupShippingFee.CODFee : serv.ShipDepotMarkupShippingFee.CODFee));
                        $("#lb_cod_failed_fee").text(formatVNCurrency(serv.NoMarkupShippingFee.CODFailedFee));
                        $("#lb_shipping_fee").text(formatVNCurrency(parseFloat(serv.ShopMarkupShippingFee.IsActive ? serv.ShopMarkupShippingFee.ShippingFeeNet : serv.ShipDepotMarkupShippingFee.ShippingFeeNet) + parseFloat(serv.ShopMarkupShippingFee.IsActive ? serv.ShopMarkupShippingFee.OtherFees : serv.ShipDepotMarkupShippingFee.OtherFees)));
                        $("#lb_total_shipping_fee").text(formatVNCurrency((serv.ShopMarkupShippingFee.IsActive ? serv.ShopMarkupShippingFee.ShippingFeeTotal : serv.ShipDepotMarkupShippingFee.ShippingFeeTotal) + serv.NoMarkupShippingFee.NoMarkupShippingFeeTotal));
                        //
                        cod_amount += serv.ShopMarkupShippingFee.IsActive ? serv.ShopMarkupShippingFee.ShippingFeeTotal : serv.ShipDepotMarkupShippingFee.ShippingFeeTotal;
                        cod_amount += serv.NoMarkupShippingFee.NoMarkupShippingFeeTotal;
                    }
                }
            }

            if ($('#user_modify_cod_amount').val() == 'no') {
                $('#tb_cod_amount').val(formatNumber(cod_amount.toString()));
            } else {
                let amountModify = parseFloat($('#cod_amount').val());
                $('#tb_cod_amount').val(formatNumber(amountModify.toString()));
            }

            console.log('load total form');
            console.log(`load total form => is_select_shipping = ${$('#is_select_shipping').val()}`);
            if ($('#is_select_shipping').val() == 'true') {
                $('#is_select_shipping').val('false');
            } else {
                if ($('#cb_not_create_shipping').is(':checked') == false && isAddNew) {
                    if (validateData2()) {
                        console.log('load total form => calc sfee');
                        CalculateShippingFee('<?php echo esc_js($order_id) ?>', true);
                    }
                }
            }

            function validateData2() {
                let pkCheck = checkPackage(false);
                let rcCheck = checkReceiver(false);
                if (pkCheck && rcCheck) {
                    return true;
                } else {
                    return false;
                }
            }

            if ($('#cb_cod').is(':checked') == true && !$('#cb_not_create_shipping').is(':checked')) {
                $('#cod_price_content').show();
            } else {
                $('#cod_price_content').hide();
            }

            <?php
            $order = wc_get_order($order_id);
            $shippings = $order->get_items('shipping');
            if (count($shippings) > 0) {
            ?>
                let bf_shippingIdx = $('#woocommerce-order-items').find('.label').index($('#bf_shipping'));
                let shippingColumn = $($('#woocommerce-order-items').find('.label')[bf_shippingIdx + <?php echo esc_js($idxShipping) ?>]);
                let shippingRow = shippingColumn.parent();
                if (shippingRow.length > 0) {
                    shippingRow.hide();
                }
            <?php
            }
            ?>

            if ($("#items_total_field").length > 0) {
                $("#items_total_field").val(<?php echo esc_js($order->get_subtotal()) ?>);
            }

            if ($("#cb_cus_pay_ship").length > 0) {
                if ($('#sd_cus_pay_ship').val() == 'true') {
                    $("#cb_cus_pay_ship").prop('checked', 'checked');
                } else {
                    $("#cb_cus_pay_ship").prop('checked', '');
                }

                $("#cb_cus_pay_ship").change(function() {
                    if ($(this).is(':checked') == true) {
                        $('#sd_cus_pay_ship').val('true');
                    } else {
                        $('#sd_cus_pay_ship').val('false');
                    }
                });
            }
        });
    </script>
<?php
}

add_action('woocommerce_admin_order_totals_after_total', 'sd_add_after_total', 10, 1);
function sd_add_after_total($order_id)
{ ?>
    <tr>
        <td class="label"><?php esc_html_e('Thanh toán cho đơn vị vận chuyển:', 'ship-depot-translate') ?></td>
        <td width="1%"></td>
        <td class="total" id="lb_total_shipping_fee">0₫</td>
    </tr>
    <?php
}

add_action('wp_ajax_calculateTotal', 'calculateTotal_init');
add_action('wp_ajax_nopriv_calculateTotal', 'calculateTotal_init');
function calculateTotal_init()
{
    Ship_Depot_Logger::wrlog('[calculateTotal_init]');
    try {
        //do bên js để dạng json nên giá trị trả về dùng phải encode
        $json_shippingObj = (isset($_POST['shippingObj'])) ? sanitize_text_field($_POST['shippingObj']) : '';
        Ship_Depot_Logger::wrlog('[calculateTotal_init] json_shippingObj_bf: ' . $json_shippingObj);
        $selected_shipping = new Ship_Depot_Shipping_Fee_Response(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($json_shippingObj));
        Ship_Depot_Logger::wrlog('[calculateTotal_init] selected_shipping: ' . print_r($selected_shipping, true));
        //
        $clearShipping = (isset($_POST['clearShipping'])) ? sanitize_text_field($_POST['clearShipping']) : '';
        Ship_Depot_Logger::wrlog('[calculateTotal_init] clearShipping: ' . $clearShipping);
        //
        //
        $order_id = (isset($_POST['orderID'])) ? sanitize_text_field($_POST['orderID']) : '';
        Ship_Depot_Logger::wrlog('[calculateTotal_init] order_id: ' . $order_id);
        $order = wc_get_order($order_id);
        // Ship_Depot_Logger::wrlog('[calculateTotal_init] order: ' . print_r($order, true));
        $oldTotal = $order->get_total();
        $shippings = $order->get_items('shipping');
        if (count($shippings) <= 0) {
        } else {
            foreach ($order->get_items('shipping') as $item_id => $item) {
                Ship_Depot_Logger::wrlog('[calculateTotal_init] shipping item: ' . print_r($item, true));
                $order->remove_item($item_id);
            }
        }

        if ($clearShipping == 'false') {
            $total_shipping = ($selected_shipping->ShopMarkupShippingFee->IsActive ? $selected_shipping->ShopMarkupShippingFee->ShippingFeeTotal : $selected_shipping->ShipDepotMarkupShippingFee->ShippingFeeTotal) + $selected_shipping->NoMarkupShippingFee->NoMarkupShippingFeeTotal;
            $shipping_methods = Ship_Depot_Shipping_Zone::get_shipping_method(SHIP_DEPOT_SHIPPING_METHOD);

            Ship_Depot_Logger::wrlog('[calculateTotal_init] shipping_methods id: ' . json_encode($shipping_methods->id));
            $item = new WC_Order_Item_Shipping();
            $item->set_props(array('method_id' => $shipping_methods->id, 'total' => wc_format_decimal($total_shipping)));
            $item->set_name('Ship Depot');
            $order->add_item($item);
        }

        $order->calculate_shipping();
        $order->calculate_totals();
        Ship_Depot_Logger::wrlog('[calculateTotal_init] order total: ' . $order->get_total());
        $need_reload = false;
        if ($clearShipping == 'false' || $oldTotal != $order->get_total()) {
            $need_reload = true;
        }
        Ship_Depot_Logger::wrlog('[calculateTotal_init] need_reload: ' . print_r($need_reload, true));
        wp_send_json_success($need_reload);
    } catch (Exception $e) {
        Ship_Depot_Logger::wrlog('[calculateTotal_init] Exception: ' . print_r($e, true));
        wp_send_json_error($e->getMessage());
    }
}

add_action('wp_ajax_calculate_shipping', 'calculate_shipping_init');
add_action('wp_ajax_nopriv_calculate_shipping', 'calculate_shipping_init');
function calculate_shipping_init()
{
    $shop_api_key = get_option('sd_api_key');
    $data_input = json_decode(stripslashes(sanitize_text_field($_POST['dataInput'])));
    Ship_Depot_Logger::wrlog('[calculate_shipping_init] data_input: ' . print_r($data_input, true));
    $order_id = isset($data_input->orderID) ? $data_input->orderID : '';
    $order = wc_get_order($order_id);
    // Ship_Depot_Logger::wrlog('[calculate_shipping_init] order: ' . print_r($order, true));
    $order_subtotal = $order->get_subtotal();
    $data_input->item_total = $order_subtotal;
    //
    $item_regular_price_total = 0;
    $order_items = $order->get_items();
    $list_items = [];
    foreach ($order_items as $item) {
        $item_data = $item->get_data();
        $it = new Ship_Depot_Item();

        $it->ID = $item_data['id'];
        $it->Name = $item_data['name'];
        $it->Quantity = $item_data['quantity'];
        $it->TotalPrice = $item_data['total'];
        //
        $item_product = new WC_Order_Item_Product($item->get_id());
        $product = $item_product->get_product();
        $regular_price = $product->get_regular_price();
        $it->Sku = $product->get_sku();
        $it->RegularPrice = $regular_price;
        $item_regular_price_total += floatval($regular_price) * $item->get_quantity();
        //
        array_push($list_items, $it);
    }

    Ship_Depot_Logger::wrlog('[calculate_shipping_init] item_regular_price_total: ' . print_r($item_regular_price_total, true));
    $data_input->item_regular_price_total = $item_regular_price_total;
    $data_input->list_items = $list_items;
    $str_fee_modify = get_option('sd_setting_fee');
    if (!Ship_Depot_Helper::check_null_or_empty($str_fee_modify)) {
        $fee_modify_obj = json_decode($str_fee_modify);
        $fee_modify = new Ship_Depot_Fee_Setting($fee_modify_obj);
        $data_input->fee_setting = $fee_modify;
    }

    //Get cod failed and PAS info
    $list_couriers =  [];
    $setting_courier = json_decode(get_option('sd_setting_courier'));
    if (!is_null($setting_courier)) {
        foreach ($setting_courier as $cour_obj) {
            $st_courier = new Ship_Depot_Courier($cour_obj);
            array_push($list_couriers, $st_courier);
        }
    }
    $data_input->courier = $list_couriers;
    //
    Ship_Depot_Logger::wrlog('[calculate_shipping_init] order_id: ' . print_r($order_id, true));
    $url = SHIP_DEPOT_HOST_API . '/Shipping/CalculateShippingAsync';
    $rs = Ship_Depot_Helper::http_post_php($url, Ship_Depot_Helper::ParseObjectToArray($data_input), array('ShopAPIKey' => $shop_api_key));
    //Ship_Depot_Logger::wrlog('[calculate_shipping_init] result call API: ' . print_r($rs, true));
    if ($rs->Code > 0) {
        $list_fees_from_api = $rs->Data;
        //Ship_Depot_Logger::wrlog('[calculate_shipping_init] result: ' . print_r($list_fees_from_api, true));
        // $order->calculate_totals();
        // Ship_Depot_Logger::wrlog('[calculate_shipping_init] order total: ' . print_r($order->get_total(), true));
        // Ship_Depot_Logger::wrlog('[calculate_shipping_init] order shipping total: ' . print_r($order->get_shipping_total(), true));
        // $order_items = array();
        // $order_total_wo_shipping = $order->get_total() - $order->get_shipping_total();
        // $item_qty_total = 0;
        // $is_cod = isset($data_input->cod) ? $data_input->cod->IsActive : false;
        // foreach ($order->get_items('line_item') as $item) {
        //     $item_qty_total += $item->get_quantity();
        //     $item_product = new WC_Order_Item_Product($item->get_id());
        //     $product = $item_product->get_product();
        //     $it = new Ship_Depot_Item();
        //     $it->Sku = $product->get_sku();
        //     $order_items[] = $it;
        // }
        //$list_fees_from_api = Ship_Depot_Order_Shipping::calculate_modify_shipping_fee($list_fees_from_api, $is_cod, $order_total_wo_shipping, $item_qty_total, $order_items);
        wp_send_json_success(json_encode($list_fees_from_api, JSON_UNESCAPED_UNICODE));
    } else {
        wp_send_json_error($rs->Code);
    }
}

add_action('wp_ajax_cancel_shipping', 'cancel_shipping_init');
add_action('wp_ajax_nopriv_cancel_shipping', 'cancel_shipping_init');
function cancel_shipping_init()
{
    $order_id = (isset($_POST['orderID'])) ? sanitize_text_field($_POST['orderID']) : '';
    $order = wc_get_order($order_id);
    $result = Ship_Depot_Order_Shipping::cancel_shipping($order);
    wp_send_json_success('success');
    // Ship_Depot_Logger::wrlog('[cancel_shipping_init] result: ' . print_r($result, true));
    // if ($result->Code < 0) {
    //     Ship_Depot_Logger::wrlog('[cancel_shipping_init] wp_send_json_success. Message: ' . $result->Message);
    //     wp_send_json_success($result->Message);
    // } else {
    //     Ship_Depot_Logger::wrlog('[cancel_shipping_init] wp_send_json_success');
    //     wp_send_json_success('success');
    // }
}

function get_list_meta_keys()
{
    //return [];
    return ['sd_not_create_ship', 'sd_list_package_size', 'sd_sender_storage', 'sd_sender_info', 'sd_ship_info', 'sd_receiver', 'sd_insurance', 'sd_cod', 'sd_selected_shipping', 'sd_selected_courier', 'sd_shipping_notes', 'sd_customer_pay_shipping', 'sd_is_edit_order', 'sd_list_items', 'sd_cod_failed_info', 'sd_ship_from_station', 'sd_from_fe'];
}

//Hide ship depot meta data in order detail page
add_filter('is_protected_meta', 'ship_depot_protected_meta_filter', 10, 2);
function ship_depot_protected_meta_filter($protected, $meta_key)
{
    if (in_array($meta_key, get_list_meta_keys())) {
        return true;
    }
    return $protected;
}

function set_shipping($order_id, $ShippingFeeTotal)
{
    $order = wc_get_order($order_id);
    $shipping_total = $order->get_shipping_total();
    if ($shipping_total != $ShippingFeeTotal) {
        $shippings = $order->get_items('shipping');

        if (count($shippings) <= 0) {
        } else {
            foreach ($order->get_items('shipping') as $item_id => $item) {
                $order->remove_item($item_id);
            }
        }

        $shipping_methods = Ship_Depot_Shipping_Zone::get_shipping_method(SHIP_DEPOT_SHIPPING_METHOD);

        $item = new WC_Order_Item_Shipping();
        $item->set_props(array('method_id' => $shipping_methods->id, 'total' => wc_format_decimal($ShippingFeeTotal)));
        $item->set_name('Ship Depot');
        $order->add_item($item);
        $order->calculate_shipping();
        $order->calculate_totals();
    }
}

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
// Adding Meta container admin shop_order pages
add_action('add_meta_boxes', 'sd_add_meta_boxes', 30);
if (!function_exists('sd_add_meta_boxes')) {
    function sd_add_meta_boxes($post_type)
    {
        $screen = wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';
        Ship_Depot_Logger::wrlog('[sd_add_meta_boxes] $post_type: ' . $post_type);
        Ship_Depot_Logger::wrlog('[sd_add_meta_boxes] $screen: ' . $screen);
        add_meta_box('sd_meta_boxes', __('Vận đơn - ShipDepot', 'ship-depot-translate'), 'sd_meta_boxes_content', $screen, 'normal', 'core');
    }
}

// Adding Meta field in the meta container admin shop_order pages
if (!function_exists('sd_meta_boxes_content')) {
    function sd_meta_boxes_content($post_or_order_object)
    {
        //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] _POST: ' . print_r($_POST, true));
        // Get an instance of the WC_Order Object
        $order = ($post_or_order_object instanceof WP_Post) ? wc_get_order($post_or_order_object->ID) : $post_or_order_object;
        $post_id = $order->get_id();
        //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] order data: ' . print_r($order, true));
        //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] post_id: ' . print_r($post_id, true));
        $screen = get_current_screen();
        //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] screen data: ' . print_r($screen, true));
        if ($screen->post_type != 'shop_order') {
            return;
        }

        $not_create_ship = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_not_create_ship', true);
        $str_ship_info = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_ship_info', true);
        $is_add_new = false;
        if ($screen->action == 'add' || Ship_Depot_Helper::check_null_or_empty($str_ship_info)) {
            $is_add_new = true;
        }
        Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] str_ship_info: ' . print_r($str_ship_info, true));
        Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] is_add_new: ' . print_r($is_add_new, true));
        $str_courier_setting = get_option('sd_setting_courier');
        if (!Ship_Depot_Helper::check_null_or_empty($str_courier_setting)) {
            $courier_setting = json_decode($str_courier_setting);
            //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] courier_setting: ' . print_r($courier_setting, true));
        }

        $str_list_couriers = get_option('sd_list_couriers');
        if (!Ship_Depot_Helper::check_null_or_empty($str_list_couriers)) {
            $listCouriers = json_decode($str_list_couriers);
            foreach ($listCouriers as $courier_obj) {
                $courier = new Ship_Depot_Courier($courier_obj);
                if ($courier->CourierID == Ship_Depot_Helper::GetOrderMetadata($order, 'sd_selected_courier', true)) {
                    $selected_courier = $courier;
                    //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] selected_courier: ' . print_r($selected_courier, true));
                }
            }
        }

        $json_shipping = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_selected_shipping', true);
        Ship_Depot_Logger::wrlog('json_shipping: ' . $json_shipping);
        if (!Ship_Depot_Helper::check_null_or_empty($json_shipping)) {
            $selected_shipping = new Ship_Depot_Shipping_Fee_Response(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($json_shipping));
            //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] selected_shipping: ' . print_r($selected_shipping, true));
            $json_shipping = json_encode($selected_shipping, JSON_UNESCAPED_UNICODE);
            set_shipping($post_id, ($selected_shipping->ShopMarkupShippingFee->IsActive ? $selected_shipping->ShopMarkupShippingFee->ShippingFeeTotal : $selected_shipping->ShipDepotMarkupShippingFee->ShippingFeeTotal) + $selected_shipping->NoMarkupShippingFee->NoMarkupShippingFeeTotal);
        }

        $json_courier_info = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_selected_courier_info', true);
        Ship_Depot_Logger::wrlog('json_courier_info: ' . $json_courier_info);
        if (!Ship_Depot_Helper::check_null_or_empty($json_courier_info)) {
            $selected_courier_info = new Ship_Depot_Courier_Response(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($json_courier_info));
        }
        setlocale(LC_MONETARY, SHIP_DEPOT_LOCALE);
        //
        if (!Ship_Depot_Helper::check_null_or_empty($str_ship_info)) {
            $ship_info = json_decode($str_ship_info);
            $tracking_number = isset($ship_info->TrackingNumber) ? $ship_info->TrackingNumber : '';
            $shipping_status = isset($ship_info->ShipStatus) ? $ship_info->ShipStatus : '';
            $create_date = isset($ship_info->ShipCreatedDate) ? $ship_info->ShipCreatedDate : '';
            $create_date_format = '';
            if (!Ship_Depot_Helper::check_null_or_empty($create_date)) {
                $create_date_format = Ship_Depot_Helper::format_utc_to_date_time($create_date); //date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($create_date));
                //Ship_Depot_Logger::wrlog("[sd_meta_boxes_content] create_date_format: " . print_r($create_date_format, true));
            }
        }

        $json_list_package_size = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_list_package_size', true);
        $total_weight = 0;
        if (!Ship_Depot_Helper::check_null_or_empty($json_list_package_size)) {
            $list_package_size = json_decode($json_list_package_size);
            foreach ($list_package_size as $pk_size_obj) {
                $pk_size = new Ship_Depot_Package($pk_size_obj);
                $total_weight += (float) $pk_size->Weight;
            }
        }

        if ($is_add_new) {
            $str_sender_info = get_option('sd_sender_info');
        } else {
            $str_sender_info = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_sender_info', true);
        }

        if (!Ship_Depot_Helper::check_null_or_empty($str_sender_info)) {
            $sender_info_obj = Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($str_sender_info);
            $sender_info = new Ship_Depot_Shop_Info($sender_info_obj);
            //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] sender_info: ' . print_r($sender_info, true));
        }
        //
        $str_list_storages = get_option('sd_list_storages');
        if (!Ship_Depot_Helper::check_null_or_empty($str_list_storages) && !Ship_Depot_Helper::check_null_or_empty(Ship_Depot_Helper::GetOrderMetadata($order, 'sd_sender_storage', true))) {
            $list_storages = Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($str_list_storages);
            foreach ($list_storages as $str) {
                if ($str->WarehouseID == Ship_Depot_Helper::GetOrderMetadata($order, 'sd_sender_storage', true)) {
                    $storage = $str;
                }
            }
        }
        //
        $str_rcv = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_receiver', true);
        Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] $str_rcv bf: ' . $str_rcv);
        if (!Ship_Depot_Helper::check_null_or_empty($str_rcv)) {
            $receiver_obj =  Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($str_rcv);
            $receiver = new Ship_Depot_Receiver($receiver_obj);
            //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] receiver: ' . print_r($receiver, true));
        }
        //
        $str_cod = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_cod', true);
        if (!Ship_Depot_Helper::check_null_or_empty($str_cod)) {
            $cod = new Ship_Depot_Cod(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($str_cod));
        }
        //
        //Tạm thời ko cho sửa vận đơn
        $str_insurance = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_insurance', true);
        if (!Ship_Depot_Helper::check_null_or_empty($str_insurance)) {
            $insurance = new Ship_Depot_Insurance(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($str_insurance));
            //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content]insurance: ' . print_r($insurance, true));
        }

        //
        $notes = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_shipping_notes', true);
        //
        $str_cod_failed_info = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_cod_failed_info', true);
        if (!Ship_Depot_Helper::check_null_or_empty($str_cod_failed_info)) {
            $cod_failed_info = new Ship_Depot_COD_Failed(Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($str_cod_failed_info));
            //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content]cod_failed_info: ' . print_r($cod_failed_info, true));
        }
        $cod_failed_amount = isset($cod_failed_info) ? $cod_failed_info->CODFailedAmount : 0;

        //Ship from station get data
        $ship_from_station = new Ship_Depot_Ship_From_Station();
        $json_ship_fr_station = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_ship_from_station', true);
        //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] json_ship_fr_station: ' . print_r($json_ship_fr_station, true));
        if (!Ship_Depot_Helper::check_null_or_empty($json_ship_fr_station)) {
            //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] json_ship_fr_station not empty');
            $ship_from_station_obj =  Ship_Depot_Helper::CleanJsonFromHTMLAndDecode($json_ship_fr_station);
            $ship_from_station = new Ship_Depot_Ship_From_Station($ship_from_station_obj);
        }
        //
        //
        //Tạm thời ko cho sửa vận đơn
        // if (isset($selected_shipping)) {
        //     $list_shipping = Ship_Depot_Order_Shipping::calculate_shipping_fee(isset($cod) ? $cod : false, isset($insurance) ? $insurance->IsActive : false, isset($insurance) && isset($insurance->Value) ? $insurance->Value : 0, $list_package_size, $receiver, Ship_Depot_Helper::GetOrderMetadata($order, 'sd_sender_storage', true), isset($sender_info) ? $sender_info : '', $order->get_subtotal(), $courier_setting);
        //     //Ship_Depot_Logger::wrlog('[Ship_Depot_Order_Shipping] list_shipping: ' . print_r($list_shipping, true));
        // }

        wp_nonce_field('sd_order_detail', 'sd_order_detail_nonce');
    ?>
        <script>
            jQuery(document).ready(function($) {

                //
                let clickPrint = false;
                if ($('#btn_print_shipping').length > 0) {
                    $('#btn_print_shipping').click(function() {
                        if (clickPrint) {
                            return;
                        }

                        let dataInput = {
                            selected_courier: '<?php echo isset($selected_shipping) && isset($selected_shipping->CourierID) ? esc_js($selected_shipping->CourierID) : '' ?>',
                            tracking_number: '<?php echo isset($ship_info) && isset($ship_info->TrackingNumber) ? esc_js($ship_info->TrackingNumber) : '' ?>'
                        }
                        if (dataInput.selected_courier == 'AHA') {
                            alert('<?php esc_html_e('AhaMove không hỗ trợ chức năng này.', 'ship-depot-translate') ?>');
                        }

                        $('#btn_print_shipping').css('cursor', 'not-allowed');
                        clickPrint = true;
                        $.ajax({
                            url: '<?php echo esc_url(SHIP_DEPOT_HOST_API) ?>' + '/Shipping/PrintLabel',
                            headers: {
                                'ShopAPIKey': '<?php echo esc_js(get_option('sd_api_key')) ?>'
                            },
                            dataType: 'json',
                            contentType: 'application/json',
                            data: JSON.stringify(dataInput),
                            type: 'POST',
                            success: function(response) {
                                if (response.Code >= 0) {
                                    if (response.Data != '') {
                                        if (dataInput.selected_courier == "<?php echo esc_js(GHN_COURIER_CODE) ?>") {
                                            window.open(response.Data, '_blank');
                                        } else if (dataInput.selected_courier == "<?php echo esc_js(GHTK_COURIER_CODE) ?>") {
                                            let blob = b64toBlob(response.Data, 'application/pdf')
                                            let blobURL = window.URL.createObjectURL(blob);
                                            window.open(blobURL, '_blank');
                                        }
                                    } else {
                                        alert("Unable to Print !!!");
                                    }
                                } else {
                                    alert("Unable to Print !!!");
                                }
                                clickPrint = false;
                                $('#btn_print_shipping').css('cursor', '');
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                alert("<?php esc_html_e('In vận đơn bị lỗi. Vui lòng thử lại sau.', 'ship-depot-translate') ?>");
                                clickPrint = false;
                                $('#btn_print_shipping').css('cursor', '');
                            }
                        });
                    });
                }

                //

                if ($('#btn_cancel_edit_cod').length > 0) {
                    $('#btn_cancel_edit_cod').click(function() {
                        $('#cod_info_area').css('display', '');
                        $('#cod_edit_area').css('display', 'none');
                        $('#tb_cod_edit_amount').removeClass('error-class');
                        $('#tb_cod_edit_amount').val('<?php echo isset($cod) ? esc_js(Ship_Depot_Helper::currency_format($cod->Value, '')) : '' ?>');
                    });
                }
            });
        </script>
        <!-- The Modal -->
        <div id="myModal" class="sd-modal">
            <!-- Modal content -->
            <div class="sd-modal-content" id="sd-modal-order-detail">
                <p style="text-align: left;"><?php _e('Vui lòng chọn 1 trong những địa điểm dưới đây để tạo vận đơn Giao hàng tiết kiệm.', 'ship-depot-translate') ?></p>
                <select id="slGHTKHamlet" style="width: 100%;margin: 0 0 25px 0;">
                </select>
                <a id="btnModalOK" href="javascript:;" class="button-a">Đã chọn</a>
            </div>
        </div>
        <input type="hidden" id="last_ghtk_hamlet_data" />
        <input type="hidden" id="ghtkHamlet" name="shipdepot[receiver][ghtkHamlet]" value="<?php echo isset($receiver) ? esc_attr($receiver->Hamlet) : '' ?>" />
        <input type="hidden" id="items_total_field" value="<?php echo esc_attr($order->get_subtotal()) ?>" />
        <input type="hidden" id="is_select_shipping" value="false" />
        <input type="hidden" id="is_add_new" value="<?php echo esc_attr($is_add_new ? 'true' : 'false') ?>" />
        <input type="hidden" id="order_id" value="<?php echo esc_attr($post_id) ?>" />
        <?php
        if (!$is_add_new) {
        ?>
            <div class="sd-div sd-edit-order">
                <div class="container-fluid">
                    <div class="row" id="div-ship-info">
                        <?php
                        if (!Ship_Depot_Helper::check_null_or_empty($json_shipping)) {
                            $img_src = '';
                            if (isset($selected_courier) && !Ship_Depot_Helper::check_null_or_empty($selected_courier->LogoURL)) {
                                $img_src = esc_url($selected_courier->LogoURL);
                                //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] img_src: ' . $img_src);
                            }

                            $other_fees = 0;
                            if ($selected_shipping->ShopMarkupShippingFee->IsActive) {
                                $other_fees = $selected_shipping->ShopMarkupShippingFee->OtherFees;
                            } else {
                                if (isset($selected_shipping->ShipDepotMarkupShippingFee->OtherFees)) {
                                    $other_fees = $selected_shipping->ShipDepotMarkupShippingFee->OtherFees;
                                }
                            }

                        ?>
                            <div class="col-auto">
                                <img id="img-selected-courier" src="<?php echo $img_src ?>" <?php echo isset($selected_courier) ? esc_html('alt="' . $selected_courier->CourierName . '"') : '' ?> data-placement="bottom" title="<?php echo isset($selected_courier) ? esc_attr($selected_courier->CourierName) : '' ?>" />
                            </div>
                            <div class="col-auto">
                                <div>
                                    <span><?php echo esc_html($selected_shipping->ServiceName) . ' <b>' . esc_html(Ship_Depot_Helper::currency_format(($selected_shipping->ShopMarkupShippingFee->IsActive ? $selected_shipping->ShopMarkupShippingFee->ShippingFeeTotal : $selected_shipping->ShipDepotMarkupShippingFee->ShippingFeeTotal) + $selected_shipping->NoMarkupShippingFee->NoMarkupShippingFeeTotal)) . '</b>' ?></span>
                                    <?php
                                    if ($selected_shipping->ShopMarkupShippingFee->IsActive) {
                                    ?>
                                        <p class="description" style="margin: 5px 0;">
                                            <?php
                                            esc_html_e('Phí giao hàng:', 'ship-depot-translate');
                                            echo ' ' . esc_html(Ship_Depot_Helper::currency_format($selected_shipping->ShopMarkupShippingFee->ShippingFeeNet + $other_fees)) . ' + ';
                                            esc_html_e('Phí thu hộ:', 'ship-depot-translate');
                                            echo ' ' . esc_html(Ship_Depot_Helper::currency_format($selected_shipping->ShopMarkupShippingFee->CODFee)) . ' + ';
                                            esc_html_e('Phí bảo hiểm:', 'ship-depot-translate');
                                            echo ' ' . esc_html(Ship_Depot_Helper::currency_format($selected_shipping->ShopMarkupShippingFee->InsuranceFee)) . ' + ';
                                            esc_html_e('Phí giao thất bại:', 'ship-depot-translate');
                                            echo ' ' . esc_html(Ship_Depot_Helper::currency_format($selected_shipping->NoMarkupShippingFee->CODFailedFee));
                                            ?>
                                        </p>

                                        <p class="description" style="margin: 5px 0;">
                                            <?php
                                            esc_html_e('Tổng phí chưa qua thay đổi:', 'ship-depot-translate');
                                            echo ' ' . esc_html(Ship_Depot_Helper::currency_format($selected_shipping->ShipDepotMarkupShippingFee->ShippingFeeTotal + $selected_shipping->NoMarkupShippingFee->NoMarkupShippingFeeTotal)) . ' = ';
                                            esc_html_e('Phí giao hàng:', 'ship-depot-translate');
                                            echo ' ' . esc_html(Ship_Depot_Helper::currency_format($selected_shipping->ShipDepotMarkupShippingFee->ShippingFeeNet + $other_fees)) . ' + ';
                                            esc_html_e('Phí thu hộ:', 'ship-depot-translate');
                                            echo ' ' . esc_html(Ship_Depot_Helper::currency_format($selected_shipping->ShipDepotMarkupShippingFee->CODFee)) . ' + ';
                                            esc_html_e('Phí bảo hiểm:', 'ship-depot-translate');
                                            echo ' ' . esc_html(Ship_Depot_Helper::currency_format($selected_shipping->ShipDepotMarkupShippingFee->InsuranceFee)) . ' + ';
                                            esc_html_e('Phí giao thất bại:', 'ship-depot-translate');
                                            echo ' ' . esc_html(Ship_Depot_Helper::currency_format($selected_shipping->NoMarkupShippingFee->CODFailedFee));
                                            ?>
                                        </p>
                                    <?php
                                    } else {
                                    ?>
                                        <p class="description" style="margin: 5px 0;">
                                            <?php
                                            esc_html_e('Phí giao hàng:', 'ship-depot-translate');
                                            echo ' ' . esc_html(Ship_Depot_Helper::currency_format($selected_shipping->ShipDepotMarkupShippingFee->ShippingFeeNet + $other_fees)) . ' + ';
                                            esc_html_e('Phí thu hộ:', 'ship-depot-translate');
                                            echo ' ' . esc_html(Ship_Depot_Helper::currency_format($selected_shipping->ShipDepotMarkupShippingFee->CODFee)) . ' + ';
                                            esc_html_e('Phí bảo hiểm:', 'ship-depot-translate');
                                            echo ' ' . esc_html(Ship_Depot_Helper::currency_format($selected_shipping->ShipDepotMarkupShippingFee->InsuranceFee)) . ' + ';
                                            esc_html_e('Phí giao thất bại:', 'ship-depot-translate');
                                            echo ' ' . esc_html(Ship_Depot_Helper::currency_format($selected_shipping->NoMarkupShippingFee->CODFailedFee));
                                            ?>
                                        </p>
                                    <?php
                                    }

                                    if ($selected_shipping->CourierID != PAS_COURIER_CODE) : ?>
                                        <p class="description">
                                            <?php esc_html_e('Thời gian nhận hàng ước tính:', 'ship-depot-translate') ?> <?php echo isset($ship_info->TimeExpected) ? esc_html($ship_info->TimeExpected) : ''; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php
                        }
                        ?>
                    </div>

                    <div>
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-12 col-padding-0">
                                    <b><?php echo esc_html__('Mã vận đơn:', 'ship-depot-translate') . ' ' ?></b><b id="lb_tracking_number"><?php echo esc_html($tracking_number) ?></b>
                                    <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_copy.png') ?>" id="btn_copy_tracking" onclick="copyTrackingNumber('lb_tracking_number')" data-toggle="tooltip" data-placement="top" title="<?php echo esc_attr__('Sao chép mã vận đơn.', 'ship-depot-translate') . ' ' ?>" />
                                    <span style="display: none;" id="lb_copied"><?php esc_html_e('Đã sao chép xxx vào bộ nhớ tạm.', 'ship-depot-translate') ?></span>
                                </div>
                                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-12 col-padding-0">
                                    <span><?php echo esc_html__('Ngày tạo:', 'ship-depot-translate') . ' ' . esc_html($create_date_format); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="sd-status-shipping-area">
                        <span><?php esc_html_e('Trạng thái:', 'ship-depot-translate') . ' ' ?></span>
                        <span id="sd-status-shipping"><?php echo esc_html($shipping_status) ?></span>
                    </div>

                    <div id="div-shipping-function">
                        <?php if ($selected_shipping->CourierID != PAS_COURIER_CODE) : ?>
                            <a href="javascript:;" class="button-a" id="btn_print_shipping"><?php esc_html_e('In Phiếu Giao Hàng', 'ship-depot-translate') ?></a>
                        <?php endif; ?>
                        <a href="javascript:;" class="button-a" id="btn_cancel_shipping"><?php esc_html_e('Huỷ Vận đơn', 'ship-depot-translate') ?></a>
                        <!-- <a href="javascript:;" class="button-a" id="btn_edit_shipping"><?php esc_html_e('Sửa Vận đơn', 'ship-depot-translate') ?></a> -->
                        <!-- <a href="javascript:;" class="button-a" id="btn_observe_shipping"><?php esc_html_e('Theo dõi trên ShipDepot', 'ship-depot-translate') ?></a> -->
                    </div>

                    <div class="sd-row">
                        <b><?php esc_html_e('Kiện hàng', 'ship-depot-translate') ?></b>
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-xl-5 col-lg-6 col-md-6 col-sm-6 col-12 col-padding-0">
                                    <?php esc_html_e('Tổng khối lượng (gram):', 'ship-depot-translate') ?> <?php echo esc_html(Ship_Depot_Helper::number_format($total_weight)) ?>
                                </div>
                                <div class="col-xl-5 col-lg-6 col-md-6 col-sm-6 col-12 col-padding-0">
                                    <?php
                                    if (isset($list_package_size)) {
                                        foreach ($list_package_size as $idx => $pk_size_obj) {
                                            $pk_size = new Ship_Depot_Package($pk_size_obj);
                                            echo '<div class="package-info">
                                        <u>Gói ' . ($idx + 1) . '</u>
                                        <span>'
                                                . esc_html__('Kích thước (cm):', 'ship-depot-translate') . ' '
                                                . esc_html(Ship_Depot_Helper::number_format($pk_size->Length)) . ' x '
                                                . esc_html(Ship_Depot_Helper::number_format($pk_size->Width)) . ' x '
                                                . esc_html(Ship_Depot_Helper::number_format($pk_size->Height)) .
                                                '</span>
                                        <span>'
                                                . esc_html__('Khối lượng (gram):', 'ship-depot-translate') . ' '
                                                . esc_html(Ship_Depot_Helper::number_format($pk_size->Weight)) .
                                                '</span>
                                        </div>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sd-row">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-xl-5 col-lg-6 col-md-6 col-sm-6 col-12 col-padding-0">
                                    <b><?php esc_html_e('Địa điểm lấy hàng', 'ship-depot-translate') ?></b>
                                    <?php if ($ship_from_station->IsActive) {
                                        echo '<div>';
                                        if ($ship_from_station->Station) {
                                            echo '<p id="ship-from-station-notice">';
                                            echo esc_html__('Gửi hàng tại điểm giao nhận GHN', 'ship-depot-translate');
                                            echo '</p>';
                                            echo esc_html__('ID:', 'ship-depot-translate') . ' ' . esc_html($ship_from_station->Station->Id);
                                            echo '<br/>';
                                            echo esc_html__('Bưu cục:', 'ship-depot-translate') . ' ' . esc_html($ship_from_station->Station->Name);
                                            echo '<br/>';
                                            echo esc_html__('Địa chỉ:', 'ship-depot-translate') . ' ' . esc_html($ship_from_station->Station->Address);
                                        }
                                        echo '</div>';
                                    } else {
                                        echo '<div>';
                                        if ($selected_shipping->CourierID == PAS_COURIER_CODE && isset($selected_courier_info)) {
                                            echo esc_html__('Địa chỉ:', 'ship-depot-translate') . ' ' . esc_html($selected_courier_info->PASAddress);
                                            echo '<br/>';
                                            echo esc_html__('Điện thoại:', 'ship-depot-translate') . ' ' . esc_html(Ship_Depot_Helper::format_phone($selected_courier_info->PASPhone));
                                        } else {
                                            if (isset($storage)) {
                                                echo esc_html__('Mã kho:', 'ship-depot-translate') . ' ' . esc_html($storage->WarehouseID);
                                                echo '<br/>';
                                                echo esc_html__('Địa chỉ:', 'ship-depot-translate') . ' ' . esc_html($storage->WarehouseAddress . ', ' . $storage->WarehouseWard . ', ' . $storage->WarehouseDistrict . ', ' . $storage->WarehouseCity);
                                                echo '<br/>';
                                                echo esc_html__('Người liên hệ:', 'ship-depot-translate') . ' ' . esc_html($storage->ContactName);
                                                echo '<br/>';
                                                echo esc_html__('Điện thoại:', 'ship-depot-translate') . ' ' . esc_html(Ship_Depot_Helper::format_phone($storage->ContactPhone));
                                            }
                                        }

                                        echo '</div>';
                                    }
                                    ?>

                                </div>
                                <div class="col-xl-5 col-lg-6 col-md-6 col-sm-6 col-12 col-padding-0">
                                    <?php if ($selected_shipping->CourierID != PAS_COURIER_CODE) : ?>
                                        <b><?php esc_html_e('Địa điểm giao hàng', 'ship-depot-translate') ?></b>
                                        <div>
                                            <?php if (isset($receiver)) {
                                                $ward_name = Ship_Depot_Address_Helper::get_ward_by_id($receiver->Province, $receiver->District, $receiver->Ward) != false ? Ship_Depot_Address_Helper::get_ward_by_id($receiver->Province, $receiver->District, $receiver->Ward)->Name : '';
                                                $district_name = Ship_Depot_Address_Helper::get_district_by_id($receiver->Province, $receiver->District) != false ? Ship_Depot_Address_Helper::get_district_by_id($receiver->Province, $receiver->District)->Name : '';
                                                $province_name = Ship_Depot_Address_Helper::get_province_by_id($receiver->Province) != false ? Ship_Depot_Address_Helper::get_province_by_id($receiver->Province)->Name : '';
                                                $full_addr = $receiver->Address;
                                                if (isset($receiver->Hamlet) && !Ship_Depot_Helper::check_null_or_empty($receiver->Hamlet)) {
                                                    $full_addr .= ', ' . $receiver->Hamlet;
                                                }
                                                $full_addr .= ', ' . $ward_name  . ', ' . $district_name . ', ' . $province_name;
                                                echo esc_html__('Địa chỉ:', 'ship-depot-translate') . ' ' . esc_html($full_addr);
                                                echo '<br/>';
                                                echo esc_html__('Người liên hệ:', 'ship-depot-translate') . ' ' . esc_html($receiver->LastName . ' ' . $receiver->FirstName);
                                                echo '<br/>';
                                                echo esc_html__('Điện thoại:', 'ship-depot-translate') . ' ' . esc_html(Ship_Depot_Helper::format_phone($receiver->Phone));
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sd-row">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-xl-2 col-lg-6 col-md-6 col-sm-6 col-12 col-padding-0">
                                    <b><?php esc_html_e('Thu hộ:', 'ship-depot-translate') ?></b>
                                    <?php
                                    $can_edit_cod = false;
                                    if (isset($selected_shipping) && strtolower($selected_shipping->CourierID) == 'ghn' && $cod->IsActive) {
                                        $can_edit_cod = true;
                                    }
                                    ?>
                                    <span id="cod_info_area"><?php echo ' ' . esc_html(Ship_Depot_Helper::currency_format($cod->Value)) ?><img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_edit.png') ?>" id="btn_edit_cod" data-toggle="tooltip" data-placement="top" title="<?php echo esc_attr__('Thay đổi tiền thu hộ.', 'ship-depot-translate') . ' ' ?>" style="<?php echo $can_edit_cod ? '' : 'display:none;' ?>" /></span>
                                    <span id="cod_edit_area" style="display: none;">
                                        <div id="div_cod_edit_amount">
                                            <input type="hidden" name="shipdepot[is_cod_edit]" id="is_cod_edit" value="false" />
                                            <input id="tb_cod_edit_amount" name="shipdepot[cod_edit_amount]" pattern="^[0-9\.\/]+$" data-type="currency" type="text" class="no-spin" value="<?php echo esc_attr(Ship_Depot_Helper::currency_format($cod->Value, '')) ?>" />
                                            <p class="description">₫</p>
                                        </div>
                                        <a href="javascript:;" class="button-a" id="btn_save_cod">Cập nhật</a>
                                        <a href="javascript:;" class="button-a" id="btn_cancel_edit_cod">Hủy bỏ</a>
                                    </span>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 col-padding-0" <?php echo $cod_failed_info->IsUsed ? '' : 'style="visibility: hidden;"' ?>>
                                    <b><?php esc_html_e('Thu hộ khi giao thất bại:', 'ship-depot-translate') ?></b>
                                    <?php
                                    echo ' ' . esc_html(Ship_Depot_Helper::currency_format($cod_failed_amount));
                                    ?>
                                </div>
                                <div class="col-xl-5 col-lg-6 col-md-6 col-sm-6 col-12 col-padding-0">
                                    <b><?php esc_html_e('Bảo hiểm (Khai giá):', 'ship-depot-translate') ?></b>
                                    <?php
                                    $ins = 0;
                                    if (isset($insurance) && $insurance->IsActive) {
                                        $ins = $insurance->Value;
                                    }
                                    echo ' ' . esc_html(Ship_Depot_Helper::currency_format($ins));
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sd-row">
                        <b><?php esc_html_e('Ghi chú:', 'ship-depot-translate') ?></b>
                        <?php
                        $cod_failed_notes = '';
                        if (isset($cod_failed_info) && $cod_failed_info->IsUsed && $cod_failed_info->ContentShippingLabel->IsShow) {
                            if (!Ship_Depot_Helper::check_null_or_empty($notes)) {
                                $cod_failed_notes = $cod_failed_notes . ' - ';
                            }
                            $cod_failed_notes = $cod_failed_notes . trim($cod_failed_info->ContentShippingLabel->Content) . ' ' . Ship_Depot_Helper::currency_format($cod_failed_amount);
                        }
                        echo ' ' . esc_textarea($notes . $cod_failed_notes);
                        ?>
                    </div>
                </div>
            <?php

        } else {
            //Tạm thời ko cho sửa shipping. Sau này có sửa thì đem toàn bộ code trong else ra khỏi if else này.
            ?>
                <div id="ship_depot_box" class="sd-div" style="<?php echo !$is_add_new ? 'display:none;' : '' ?>">
                    <?php
                    $list_provinces = Ship_Depot_Address_Helper::get_all_province();
                    if ($is_add_new) {
                        $not_create_ship_checked = checked($not_create_ship == 'true', true, false);
                    ?>
                        <input id="cb_not_create_shipping" name="shipdepot[notCreateShipping]" type="checkbox" value="1" <?php echo $not_create_ship_checked ?> /><?php esc_html_e('Không tạo vận đơn', 'ship-depot-translate') ?>
                        <p class="description"><?php esc_html_e('Chọn tính năng này để không tạo vận đơn cho đơn hàng, sử dụng cho trường hợp đơn hàng không cần giao cho khách.', 'ship-depot-translate') ?></p>
                    <?php
                    }
                    ?>
                    <div id="shipping_info" <?php echo $not_create_ship == 'true' ? 'style="display:none"' : '' ?>>
                        <input type="hidden" id="sd_cus_pay_ship" value="false" />
                        <div id="package" class="shipping-info-property">
                            <b>
                                <?php esc_html_e('Đóng gói', 'ship-depot-translate') ?>
                                <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_up_arrow_black_16px.png') ?>" id="btn_expand_package" class="sd-button" />
                            </b>
                            <p class="description"><?php esc_html_e('Nhớ điều chỉnh lại kích thước thực để tránh bị chênh phí ship.', 'ship-depot-translate') ?></p>
                            <div id="package_content">
                                <div id="package_size_header">
                                    <img class="button-virtual" src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_close_12px.png') ?>" />
                                    <div class="container-fluid col-padding-0">
                                        <div class="row">
                                            <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3"><?php esc_html_e('Dài (cm)', 'ship-depot-translate') ?></div>
                                            <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3"><?php esc_html_e('Rộng (cm)', 'ship-depot-translate') ?></div>
                                            <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3"><?php esc_html_e('Cao (cm)', 'ship-depot-translate') ?></div>
                                            <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3"><?php esc_html_e('Khối lượng (gram)', 'ship-depot-translate') ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div id="package_size_container">
                                    <!-- Edit ship area -->
                                    <?php
                                    if (isset($list_package_size) && count($list_package_size) > 0) {
                                        $pk_df = new Ship_Depot_Package($list_package_size[0]);
                                    ?>
                                        <div class="package_size_row">
                                            <img class="button-virtual" disabled="true" src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_close_12px.png') ?>" />
                                            <div class="container-fluid col-padding-0">
                                                <div class="row">
                                                    <input type="hidden" class="shipdepot_package" name="shipdepot[package_id][]" value="pk_df" />
                                                    <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3">
                                                        <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="shipdepot[pk_df][length]" class="package_length no-spin" id="pk_df_package_length" value="<?php echo esc_attr(Ship_Depot_Helper::number_format($pk_df->Length)) ?>" />
                                                    </div>
                                                    <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3">
                                                        <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="shipdepot[pk_df][width]" class="package_width no-spin" id="pk_df_package_width" value="<?php echo esc_attr(Ship_Depot_Helper::number_format($pk_df->Width)) ?>" />
                                                    </div>
                                                    <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3">
                                                        <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="shipdepot[pk_df][height]" class="package_height no-spin" id="pk_df_package_height" value="<?php echo esc_attr(Ship_Depot_Helper::number_format($pk_df->Height)) ?>" />
                                                    </div>
                                                    <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3">
                                                        <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="shipdepot[pk_df][weight]" class="package_weight no-spin" id="pk_df_package_weight" value="<?php echo esc_attr(Ship_Depot_Helper::number_format($pk_df->Weight)) ?>" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        foreach ($list_package_size as $idx => $pk_size_obj) {
                                            $pk_size = new Ship_Depot_Package($pk_size_obj);
                                            if ($idx > 0) {
                                                $pk_id = 'pk_' . $idx;
                                        ?>
                                                <div class="package_size_row">
                                                    <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_close_12px.png') ?>" class="btn_delete_package_size" />
                                                    <div class="container-fluid col-padding-0">
                                                        <div class="row">
                                                            <input type="hidden" class="shipdepot_package" name="shipdepot[package_id][]" value="<?php echo esc_attr($pk_id) ?>" />
                                                            <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3">
                                                                <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="shipdepot[<?php echo esc_attr($pk_id) ?>][length]" class="package_length no-spin" id="<?php echo esc_attr($pk_id) ?>_package_length" value="<?php echo esc_attr(Ship_Depot_Helper::number_format($pk_size->Length)) ?>" />
                                                            </div>
                                                            <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3">
                                                                <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="shipdepot[<?php echo esc_attr($pk_id) ?>][width]" class="package_width no-spin" id="<?php echo esc_attr($pk_id) ?>_package_width" value="<?php echo esc_attr(Ship_Depot_Helper::number_format($pk_size->Width)) ?>" />
                                                            </div>
                                                            <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3">
                                                                <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="shipdepot[<?php echo esc_attr($pk_id) ?>][height]" class="package_height no-spin" id="<?php echo esc_attr($pk_id) ?>_package_height" value="<?php echo esc_attr(Ship_Depot_Helper::number_format($pk_size->Height)) ?>" />
                                                            </div>
                                                            <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3">
                                                                <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="shipdepot[<?php echo esc_attr($pk_id) ?>][weight]" class="package_weight no-spin" id="<?php echo esc_attr($pk_id) ?>_package_weight" value="<?php echo esc_attr(Ship_Depot_Helper::number_format($pk_size->Weight)) ?>" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                        <?php
                                            }
                                        }
                                    } else {
                                        ?>
                                        <div class="package_size_row">
                                            <img class="button-virtual" disabled="true" src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_close_12px.png') ?>" />
                                            <div class="container-fluid col-padding-0">
                                                <div class="row">
                                                    <input type="hidden" class="shipdepot_package" name="shipdepot[package_id][]" value="pk_df" />
                                                    <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3">
                                                        <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="shipdepot[pk_df][length]" class="package_length no-spin" id="pk_df_package_length" value="<?php echo esc_attr(Ship_Depot_Helper::number_format(10)) ?>" />
                                                    </div>
                                                    <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3">
                                                        <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="shipdepot[pk_df][width]" class="package_width no-spin" id="pk_df_package_width" value="<?php echo esc_attr(Ship_Depot_Helper::number_format(10)) ?>" />
                                                    </div>
                                                    <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3">
                                                        <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="shipdepot[pk_df][height]" class="package_height no-spin" id="pk_df_package_height" value="<?php echo esc_attr(Ship_Depot_Helper::number_format(10)) ?>" />
                                                    </div>
                                                    <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-3">
                                                        <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="shipdepot[pk_df][weight]" class="package_weight no-spin" id="pk_df_package_weight" value="<?php echo esc_attr(Ship_Depot_Helper::number_format(1000)) ?>" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php
                                    }
                                    ?>
                                </div>

                                <div class="package_size_footer">
                                    <img class="button-virtual" src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_close_12px.png') ?>" />
                                    <a href="javascript:;" id="btn_add_package_size"><?php esc_html_e('[Thêm]', 'ship-depot-translate') ?></a>
                                </div>
                            </div>
                        </div>

                        <div id="sender" class="shipping-info-property">
                            <div class="container-fluid col-padding-0">
                                <div class="row">
                                    <div class="col-xl-4 col-lg-5 col-md-6 col-sm-12 col-12">
                                        <div>
                                            <b><?php esc_html_e('Người gửi', 'ship-depot-translate') ?></b>
                                        </div>
                                        <?php
                                        if (!Ship_Depot_Helper::check_null_or_empty($str_sender_info)) {
                                            echo '<input type="hidden" value="' . esc_attr(str_replace('"', "'", $str_sender_info)) . '" name="shipdepot[sender_info]" id="sender_info">';
                                            echo esc_html($sender_info->FirstName);
                                            echo '<br/>';
                                            echo esc_html($sender_info->Address . ', ' . $sender_info->Ward . ', ' . $sender_info->District . ', ' . $sender_info->City);
                                            echo '<br/>';
                                            echo esc_html(Ship_Depot_Helper::format_phone($sender_info->Phone));
                                        }
                                        ?>
                                    </div>

                                    <?php
                                    $strListStr = get_option('sd_list_storages');

                                    if (!Ship_Depot_Helper::check_null_or_empty($strListStr)) {
                                        $listStrBf = json_decode($strListStr);
                                        $listStr = [];
                                        foreach ($listStrBf as $str) {
                                            if ($str->IsDefault) {
                                                $senderDf = $str;
                                                array_unshift($listStr, $senderDf);
                                            } else {
                                                array_push($listStr, $str);
                                            }
                                        }
                                    }
                                    ?>
                                    <div class="col-xl-2 col-lg-5 col-md-5 col-sm-12 col-12" id="div-storages">
                                        <div>
                                            <b><?php esc_html_e('Gửi từ kho', 'ship-depot-translate') ?></b>
                                        </div>
                                        <select name="shipdepot[sender_storage]" id="sl_storage">
                                            <?php
                                            foreach ($listStr as $id => $str) {
                                                $storage_selectedAttr = selected(false);
                                                if (isset($storage)) {
                                                    $storage_selectedAttr = selected($storage->WarehouseID, $str->WarehouseID, false);
                                                }

                                                echo '<option value="' . esc_attr($str->WarehouseID) . '" ' . $storage_selectedAttr . '>' . esc_html__('Mã kho:', 'ship-depot-translate') . ' ' . esc_html($str->WarehouseID) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="pick-station" class="div-pick-station shipping-info-property">
                            <p class="pick-station-header"><?php esc_html_e('Hình thức gửi hàng', 'ship-depot-translate') ?></p>
                            <?php
                            $checkedAttrYes = '';
                            $checkedAttrNo = '';
                            $list_province = Ship_Depot_Address_Helper::get_all_province();
                            $list_district = [];
                            $selected_district = null;
                            $selected_province = null;
                            $setting_courier = json_decode(get_option('sd_setting_courier'));
                            $st_courier = null;
                            //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] setting_courier: ' . print_r($setting_courier, true));
                            if (!is_null($setting_courier)) {
                                foreach ($setting_courier as $cour_obj) {
                                    $cour = new Ship_Depot_Courier($cour_obj);
                                    if ($cour->CourierID == 'GHN') {
                                        $st_courier = $cour;
                                        $st_courier->ListServices = [];
                                        //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] st_courier: ' . print_r($st_courier, true));
                                    }
                                }
                            }

                            if (Ship_Depot_Helper::check_null_or_empty($json_ship_fr_station)) {
                                //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] json_ship_fr_station empty');
                                $ship_from_station = $st_courier->ShipFromStation;
                            }

                            if ($ship_from_station) {
                                //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] ship_from_station: ' . print_r($ship_from_station, true));
                                if ($ship_from_station->IsActive != null) {
                                    $checkedAttrYes = checked($ship_from_station->IsActive, true, false);
                                    $checkedAttrNo = checked($ship_from_station->IsActive, false, false);
                                } else {
                                    $checkedAttrYes = checked(false, true, false);
                                    $checkedAttrNo = checked(true, true, false);
                                }
                                if ($ship_from_station->District->DistrictISN > 0) {
                                    $selected_district = $ship_from_station->District;
                                } else {
                                    if (isset($sender_info)) {
                                        $distr = new stdClass();
                                        $distr->CityISN = $sender_info->CityISN;
                                        $distr->DistrictISN = $sender_info->DistrictISN;
                                        $selected_district = new Ship_Depot_District($distr);
                                    }
                                }

                                if ($ship_from_station->Province->CityISN > 0) {
                                    $selected_province = new Ship_Depot_Province(Ship_Depot_Address_Helper::get_province_by_isn($ship_from_station->Province->CityISN));
                                    //Ship_Depot_Logger::wrlog('[sd_meta_boxes_content] selected_province: ' . print_r($selected_province, true));
                                    $list_district = Ship_Depot_Address_Helper::get_all_district_by_province_isn($ship_from_station->Province->CityISN);
                                } else {
                                    if (isset($sender_info)) {
                                        $selected_province = new Ship_Depot_Province(Ship_Depot_Address_Helper::get_province_by_isn($sender_info->CityISN));
                                        $list_district = Ship_Depot_Address_Helper::get_all_district_by_province_isn($sender_info->CityISN);
                                    }
                                }
                            } else {
                                $checkedAttrYes = checked(false, true, false);
                                $checkedAttrNo = checked(true, true, false);
                                if (isset($sender_info)) {
                                    $list_district = Ship_Depot_Address_Helper::get_all_district_by_province_isn($sender_info->CityISN);
                                }
                            }
                            ?>
                            <input type="hidden" class="courier_data" value="<?php echo $st_courier == null ? '' : esc_attr(Ship_Depot_Helper::ParseObjectToJsonHTML($st_courier)) ?>" />
                            <div class="container-fluid col-padding-0 pick-station-options">
                                <div class="row">
                                    <div class="col-xl-2 col-lg-4 col-md-8 col-sm-10 pick-station-no">
                                        <label for="ship_from_station_no">
                                            <input type="radio" id="ship_from_station_no" name="shipdepot[ShipFromStation][IsActive]" value="0" <?php echo $checkedAttrNo ?> />
                                            <?php esc_html_e('Shipper lấy hàng tận nơi', 'ship-depot-translate') ?>
                                        </label>
                                    </div>
                                    <div class="col-xl-4 col-lg-8 col-md-8 col-sm-10 pick-station-yes">
                                        <input type="hidden" class="selected_district" value="<?php echo esc_attr(Ship_Depot_Helper::ParseObjectToJsonHTML($selected_district)) ?>" />
                                        <label for="ship_from_station_yes">
                                            <input type="radio" id="ship_from_station_yes" name="shipdepot[ShipFromStation][IsActive]" value="1" <?php echo $checkedAttrYes ?> />
                                            <?php esc_html_e('Gửi hàng tại điểm giao nhận của GHN', 'ship-depot-translate') ?>
                                        </label>
                                        <p class="description">Chỉ áp dụng với đơn vị vận chuyển Giao Hàng Nhanh.</p>
                                        <div class="pick-station-yes-option" style="<?php echo $ship_from_station->IsActive ? esc_attr('display: block') : 'display: none' ?>">
                                            <div class="pick-station-yes-option-row">
                                                <span class="pick-station-yes-title"><?php esc_html_e('Tỉnh/Thành', 'ship-depot-translate') ?></span>
                                                <select class="sl_province" name="shipdepot[ShipFromStation][ProvinceCode]">
                                                    <?php
                                                    foreach ($list_province as $province_obj) {
                                                        $province = new Ship_Depot_Province($province_obj);
                                                        $selectedProvinceAttr = '';
                                                        if ($ship_from_station->Province->CityISN <= 0) {
                                                            if (isset($sender_info)) {
                                                                $selectedProvinceAttr = selected($sender_info->CityISN, $province->CityISN);
                                                            }
                                                        } else {
                                                            $selectedProvinceAttr = selected($ship_from_station->Province->CityISN, $province->CityISN);
                                                        }
                                                        echo '<option data-id="' . esc_attr($province->CityISN) . '" value="' . esc_attr($province->Code) . '" ' . esc_html($selectedProvinceAttr) . '>' . esc_html($province->Name) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="pick-station-yes-option-row">
                                                <span class="pick-station-yes-title"><?php esc_html_e('Quận/Huyện', 'ship-depot-translate') ?></span>
                                                <select class="sl_district" name="shipdepot[ShipFromStation][DistrictCode]">
                                                    <option value="-1"><?php esc_html_e(SD_SELECT_DISTRICT_TEXT, 'ship-depot-translate') ?></option>
                                                    <?php
                                                    foreach ($list_district as $district_obj) {
                                                        $district = new Ship_Depot_District($district_obj);
                                                        $selectedDisctrictAttr = '';
                                                        if (Ship_Depot_Helper::check_null_or_empty($ship_from_station->District->Code)) {
                                                            if (isset($sender_info)) {
                                                                $selectedDisctrictAttr = selected($sender_info->DistrictISN, $district->DistrictISN);
                                                            }
                                                        } else {
                                                            $selectedDisctrictAttr = selected($ship_from_station->District->Code, $district->Code);
                                                        }
                                                        echo '<option value="' . esc_attr($district->Code) . '" ' . esc_html($selectedDisctrictAttr) . '>' . esc_html($district->Name) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="pick-station-yes-option-row">
                                                <?php $selected_station = null ?>
                                                <label class="pick-station-yes-title"><?php esc_html_e('Bưu cục', 'ship-depot-translate') ?></label>
                                                <select class="sl_station" name="shipdepot[ShipFromStation][StationID]">
                                                    <option value="-1"><?php esc_html_e('Chọn bưu cục', 'ship-depot-translate') ?></option>
                                                    <?php
                                                    // if ($ship_from_station->IsActive) {
                                                    $list_stations = Ship_Depot_Order_Shipping::get_shipping_stations($selected_province, $selected_district, 0, $st_courier->CourierISN);
                                                    if ($list_stations) {
                                                        foreach ($list_stations as $station) {
                                                            $selectedStationAttr = selected($ship_from_station->Station->Id, $station->Id, false);
                                                            if ($ship_from_station->Station->Id > 0 && $station->Id == $ship_from_station->Station->Id) {
                                                                $selected_station = $station;
                                                            }
                                                    ?>
                                                            <option data-json="<?php echo esc_attr(Ship_Depot_Helper::ParseObjectToJsonHTML($station)) ?>" title="<?php echo esc_attr($station->Address) ?>" value="<?php echo esc_attr($station->Id) ?>" <?php echo esc_html($selectedStationAttr) ?>>
                                                                <?php echo esc_html($station->Name) ?>
                                                            </option>
                                                    <?php
                                                        }
                                                    }
                                                    // }

                                                    ?>
                                                </select>
                                                <input type="hidden" class="selected_station_data" name="shipdepot[ShipFromStation][SelectedStation]" value="<?php echo $selected_station == null ? '' : esc_attr(Ship_Depot_Helper::ParseObjectToJsonHTML($selected_station)) ?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="receiver" class="shipping-info-property">
                            <div>
                                <b><?php esc_html_e('Người nhận', 'ship-depot-translate') ?></b>
                            </div>
                            <div class="container-fluid col-padding-0">
                                <div class="row" id="sd-receiver-type-row">
                                    <?php
                                    if (isset($receiver)) {
                                        $rcv_current_checkedAttr = checked('current', $receiver->Type, false);
                                        $rcv_other_checkedAttr = checked('other', $receiver->Type, false);
                                    }
                                    ?>
                                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 col-padding-0" id="sd_current_rcv">
                                        <label>
                                            <input name="shipdepot[receiver][type]" class="rd_receiver_type" type="radio" value="current" <?php echo isset($rcv_current_checkedAttr) ? $rcv_current_checkedAttr : checked(true, true, false) ?> /> <?php esc_html_e('[Người nhận hàng] của đơn hàng', 'ship-depot-translate') ?>
                                        </label>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 col-padding-0">
                                        <label>
                                            <input name="shipdepot[receiver][type]" class="rd_receiver_type" id="rd_receiver_type_other" type="radio" value="other" <?php echo isset($rcv_other_checkedAttr) ? $rcv_other_checkedAttr : '' ?> /> <?php esc_html_e('Khác', 'ship-depot-translate') ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $display_rcv = false;
                            if (isset($receiver) && $receiver->Type == 'other') {
                                $display_rcv = true;
                            }
                            ?>
                            <div id="receiver_info" style="<?php echo $display_rcv ? '' : 'display: none;' ?>">
                                <div class="container-fluid col-padding-0">
                                    <div class="row receiver-info-row">
                                        <div class="col-xl-4 col-lg-5 col-md-5 col-sm-5 col-12">
                                            <?php esc_html_e('Họ:', 'ship-depot-translate') ?>
                                            <br />
                                            <input type="text" name="shipdepot[receiver][lastName]" id="receiver_last_name" value="<?php echo $display_rcv && isset($receiver) ? esc_attr($receiver->LastName) : '' ?>" />
                                        </div>
                                        <div class="col-xl-4 col-lg-5 col-md-5 col-sm-5 col-12">
                                            <?php esc_html_e('Tên:', 'ship-depot-translate') ?>
                                            <br />
                                            <input type="text" name="shipdepot[receiver][firstName]" id="receiver_first_name" value="<?php echo $display_rcv && isset($receiver) ? esc_attr($receiver->FirstName) : '' ?>" />
                                        </div>
                                    </div>
                                    <div class="row receiver-info-row">
                                        <div class="col-xl-4 col-lg-5 col-md-5 col-sm-5 col-12">
                                            <?php esc_html_e('Tỉnh / Thành phố:', 'ship-depot-translate') ?>
                                            <br />
                                            <select id="sl_receiver_province" name="shipdepot[receiver][province]">
                                                <?php
                                                $selectedPro = null;
                                                if ($display_rcv && isset($receiver)) {
                                                    foreach ($list_provinces as $province) {
                                                        if ($province->Code == $receiver->Province) {
                                                            $selectedPro = $province;
                                                        }
                                                    }
                                                }

                                                echo '<option value="">' . esc_html(SD_SELECT_CITY_TEXT) . '</option>';
                                                foreach ($list_provinces as $province) {
                                                    $province_selectedAttr = '';
                                                    if (!is_null($selectedPro)) $province_selectedAttr = selected($selectedPro->Code, $province->Code, false);
                                                    echo '<option value="' . esc_attr($province->Code) . '" ' . $province_selectedAttr . '>' . esc_html($province->Name) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-xl-4 col-lg-5 col-md-5 col-sm-5 col-12">
                                            <?php esc_html_e('Quận / Huyện:', 'ship-depot-translate') ?>
                                            <br />
                                            <select id="sl_receiver_district" name="shipdepot[receiver][district]">
                                                <?php
                                                $selectedDist = null;
                                                if (!is_null($selectedPro)) {
                                                    if ($display_rcv && isset($receiver)) {
                                                        foreach ($selectedPro->ListDistricts as $district) {
                                                            if ($district->Code == $receiver->District) {
                                                                $selectedDist = $district;
                                                            }
                                                        }
                                                    }
                                                    foreach ($selectedPro->ListDistricts as $dist) {
                                                        $district_selectedAttr = '';
                                                        if (!is_null($selectedDist)) $district_selectedAttr = selected($selectedDist->Code, $dist->Code, false);
                                                        echo '<option value="' . esc_attr($dist->Code) . '" ' . $district_selectedAttr . '>' . esc_html($dist->Name) . '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row receiver-info-row">
                                        <div class="col-xl-4 col-lg-5 col-md-5 col-sm-5 col-12">
                                            <?php esc_html_e('Phường / Xã:', 'ship-depot-translate') ?>
                                            <br />
                                            <select id="sl_receiver_ward" name="shipdepot[receiver][ward]">
                                                <?php
                                                $selectedWard = null;
                                                if (!is_null($selectedDist)) {
                                                    if ($display_rcv && isset($receiver)) {
                                                        foreach ($selectedDist->ListWards as $ward) {
                                                            if ($ward->Code == $receiver->Ward) {
                                                                $selectedWard = $ward;
                                                            }
                                                        }
                                                    }

                                                    foreach ($selectedDist->ListWards as $ward) {
                                                        $ward_selectedAttr = '';
                                                        if (!is_null($selectedWard)) $ward_selectedAttr = selected($selectedWard->Code, $ward->Code, false);
                                                        echo '<option value="' . esc_attr($ward->Code) . '" ' . $ward_selectedAttr . '>' . esc_html($ward->Name) . '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-xl-4 col-lg-5 col-md-5 col-sm-5 col-12">
                                            <?php esc_html_e('Địa chỉ:', 'ship-depot-translate') ?>
                                            <br />
                                            <input type="text" name="shipdepot[receiver][address]" id="receiver_address" placeholder="<?php esc_html_e('Số nhà và tên đường', 'ship-depot-translate') ?>" value="<?php echo $display_rcv && isset($receiver) ? esc_attr($receiver->Address) : '' ?>" />
                                        </div>
                                    </div>
                                    <div class="row receiver-info-row">
                                        <div class="col-xl-4 col-lg-5 col-md-5 col-sm-5 col-12">
                                            <?php esc_html_e('Điện thoại:', 'ship-depot-translate') ?>
                                            <br />
                                            <input type="text" name="shipdepot[receiver][phone]" id="receiver_phone" value="<?php echo $display_rcv && isset($receiver) ? esc_attr($receiver->Phone) : '' ?>" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="advance" class="shipping-info-property">
                            <b>
                                <?php esc_html_e('Nâng cao', 'ship-depot-translate') ?>
                                <img class="sd-button" src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_up_arrow_black_16px.png') ?>" id="btn_expand_advance" />
                            </b>
                            <div class="container-fluid col-padding-0" id="advance_content">
                                <div class="row" id="sd-advance-row">
                                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 col-padding-0 insurance-detail-col">
                                        <?php
                                        $display_insr_price = false;
                                        if (isset($insurance)) {
                                            $insr_checkedAttr = checked($insurance->IsActive, true, false);
                                            $display_insr_price = $insurance->IsActive;
                                        }
                                        ?>
                                        <label>
                                            <input type="checkbox" name="shipdepot[advance][insurance][isActive]" id="cb_ins_fee" <?php echo isset($insr_checkedAttr) ? $insr_checkedAttr : '' ?> /><?php esc_html_e('Mua bảo hiểm (khai giá)', 'ship-depot-translate') ?>
                                        </label>
                                        <div style="<?php echo $display_insr_price ? '' : 'display: none;' ?>" id="insr_price">
                                            <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="shipdepot[advance][insurance][value]" id="tb_ins_fee" class="no-spin" value="<?php echo isset($insurance) ? esc_attr(Ship_Depot_Helper::currency_format(floatval($insurance->Value), ""))  : '0' ?>" />
                                            <p class="description">₫</p>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 col-padding-0">
                                        <?php
                                        if (isset($cod)) {
                                            $cod_checkedAttr = checked($cod->IsActive, true, false);
                                        }
                                        ?>
                                        <label>
                                            <input type="checkbox" name="shipdepot[advance][cod][isActive]" id="cb_cod" <?php echo isset($cod_checkedAttr) ? $cod_checkedAttr : '' ?> /><?php esc_html_e('Thu hộ (COD)', 'ship-depot-translate') ?>
                                        </label>
                                        <input type="hidden" id="cod_amount" value="<?php echo !$is_add_new && isset($cod) && $cod->IsActive ? esc_attr($cod->Value) : 0 ?>">
                                        <input type="hidden" id="user_modify_cod_amount" value="<?php echo !$is_add_new ? 'yes' : 'no' ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="shipping_fee" class="shipping-info-property">
                            <b>
                                <?php esc_html_e('Phí xử lý vận đơn', 'ship-depot-translate') ?>
                                <img class="sd-button" src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_reload_black_16px.png') ?>" id="btn_reload_shipping_fee" />
                            </b>
                            <p class="description"><?php esc_html_e('Chọn dịch vụ và đơn vị vận chuyển xử lý vận đơn.', 'ship-depot-translate') ?></p>
                            <p class="description"><?php esc_html_e('Phí giao hàng có thể khác nhau đối với mỗi đơn vị vận chuyển và loại dịch vụ của đơn vị vận chuyển.', 'ship-depot-translate') ?></p>
                            <p class="description"><?php esc_html_e('Chú ý: Nhập đủ các thông tin ở trên để kiểm tra giá chính xác nhất.', 'ship-depot-translate') ?></p>
                            <input type="hidden" id="selectedCourier" name="shipdepot[selectedCourier]" value="<?php echo esc_attr(Ship_Depot_Helper::GetOrderMetadata($order, 'sd_selected_courier', true)) ?>" />
                            <p id="loading_text" style="display: none;"><?php esc_html_e('Đang tính phí vận chuyển, xin vui lòng chờ trong giây lát ...', 'ship-depot-translate') ?></p>
                            <div id="shipping_fee_content">
                                <?php
                                if (isset($list_shipping) && $list_shipping != false) {
                                    foreach ($list_shipping as $shipping_cour) {
                                        $isEnabled = $selected_courier->CourierID == $shipping_cour->CourierID;
                                ?>

                                        <div id="<?php echo esc_attr($shipping_cour->CourierID) ?>" class="courier-fee<?php echo $isEnabled ? '' : ' disable-element' ?>">
                                            <img src="<?php echo esc_url($shipping_cour->LogoURL) ?>" alt="<?php echo esc_attr($shipping_cour->CourierName) ?>" data-placement="bottom" title="<?php echo esc_attr($shipping_cour->CourierName) ?>">
                                            <div class="service-fee">
                                                <?php foreach ($shipping_cour->ListServices as $serv) {
                                                    $json = Ship_Depot_Helper::ParseObjectToJsonHTML($serv);
                                                ?>
                                                    <div>
                                                        <input type="hidden" id="<?php echo esc_attr($serv->ServiceCode) ?>" name="shipdepot[radio_shipping_fee][<?php echo esc_attr($serv->ServiceCode) ?>]" value="<?php echo esc_attr($json) ?>">
                                                        <input type="radio" name="shipdepot[radio_shipping_fee]" class="radio_shipping_fee<?php echo $isEnabled ? '' : ' disable-element' ?>" value="<?php echo esc_attr($serv->ServiceCode) ?>" <?php echo $isEnabled ? checked($selected_shipping->ServiceCode, $serv->ServiceCode, false) : '' ?> /> <?php echo esc_html($serv->ServiceName) ?>
                                                        <?php if ($isEnabled && $selected_shipping->ServiceCode == $serv->ServiceCode) {
                                                        ?>
                                                            <b>
                                                                <?php echo esc_html(Ship_Depot_Helper::currency_format($selected_shipping->ShipDepotMarkupShippingFee->ShippingFeeTotal)) ?>
                                                            </b>
                                                            <p class="description">
                                                                Phí giao hàng: <?php echo esc_html(Ship_Depot_Helper::currency_format($selected_shipping->ShipDepotMarkupShippingFee->ShippingFeeNet + $selected_shipping->ShipDepotMarkupShippingFee->OtherFees)) ?> +
                                                                Phí thu hộ: <?php echo esc_html(Ship_Depot_Helper::currency_format($selected_shipping->ShipDepotMarkupShippingFee->CODFee)) ?> +
                                                                Phí bảo hiểm: <?php echo esc_html(Ship_Depot_Helper::currency_format($selected_shipping->ShipDepotMarkupShippingFee->InsuranceFee)) ?>
                                                            </p>
                                                        <?php
                                                        } else {
                                                        ?>
                                                            <b><?php echo esc_html(Ship_Depot_Helper::currency_format($serv->ShippingFeeTotal)) ?></b>
                                                            <p class="description">
                                                                Phí giao hàng: <?php echo esc_html(Ship_Depot_Helper::currency_format($serv->ShippingFeeNet + (isset($serv->OtherFees) ? $serv->OtherFees : 0))) ?> +
                                                                Phí thu hộ: <?php echo esc_html(Ship_Depot_Helper::currency_format($serv->CODFee)) ?> +
                                                                Phí bảo hiểm: <?php echo esc_html(Ship_Depot_Helper::currency_format($serv->InsuranceFee)) ?></p>
                                                            <?php
                                                        }

                                                        if (!Ship_Depot_Helper::check_null_or_empty($serv->TimeExpected)) {
                                                            if ($shipping_cour->CourierID == AHA_COURIER_CODE) {
                                                            ?>
                                                                <p class="description"><?php echo esc_html__('Thời gian di chuyển từ lúc lấy hàng:', 'ship-depot-translate') . ' ' . esc_html($serv->TimeExpected) ?></p>
                                                            <?php
                                                            } else {
                                                            ?>
                                                                <p class="description"><?php echo esc_html__('T.gian nhận hàng ước tính:', 'ship-depot-translate') . ' ' . esc_html($serv->TimeExpected) ?></p>
                                                        <?php
                                                            }
                                                        }
                                                        ?>

                                                    </div>
                                                <?php
                                                }
                                                ?>
                                            </div>
                                        </div>
                                <?php
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <div id="sd_note" class="shipping-info-property" style="<?php echo !isset($list_shipping) || !$list_shipping ? 'display:none;' : '' ?>"> <?php esc_html_e('Ghi chú cho đơn vị vận chuyển', 'ship-depot-translate') ?>
                            <textarea name="shipdepot[shipping_notes]"><?php echo isset($notes) ? esc_textarea($notes) : '' ?></textarea>
                        </div>
                    </div>
                    <div class="error-message-area">
                        <p class="error_content" id="error_message_content"></p>
                        <p class="error_content" id="error_station_content"></p>
                    </div>
                </div>
            <?php

        }
            ?>
            <input type="hidden" id="selectedShipping" name="shipdepot[selectedShipping]" value="<?php echo isset($json_shipping) && !Ship_Depot_Helper::check_null_or_empty($json_shipping) ? esc_attr(str_replace('"', "'", $json_shipping)) : '' ?>" />
            <div id="shipping_fee_area">
        <?php

    }
}
