<?php
if (!defined('WPINC')) {
    die;
}

class Ship_Depot_Order_Shipping
{

    /**
     * Calculate shipping fee
     * @param bool $is_cod If order payment type is COD set this param = true.
     * @param bool $is_insurance If order shipping insurance is checked set this param = true.
     * @param int $insurance_amount Insurance amount of order.
     * @param Ship_Depot_Package[] $list_packages List packages of order.
     * @param Ship_Depot_Item[] $list_items List all items in order.
     * @param Ship_Depot_Receiver $receiver Storage ID.
     * @param string $sender_storage Storage ID.
     * @param Ship_Depot_Shop_Info $sender_info Storage ID.
     * @param int $items_total_price Total price of all items in order.
     * @param int $item_regular_price_total Total regular price of all items in order.
     * @param int $order_total Price after calculate of order.
     * @param string $for_courier Json of List courier calculate.
     * @return Ship_Depot_Courier_Response[]|int
     */
    public static function calculate_shipping_fee($is_cod, $is_insurance, $insurance_amount, $list_packages, $list_items, $receiver, $sender_storage, $sender_info, $items_total_price, $item_regular_price_total, $order_total, $for_courier = '')
    {
        // Ship_Depot_Logger::wrlog('[Ship_Depot_Order_Shipping][calculate_shipping] receiver: ' . print_r($receiver, true));
        if (
            is_null($receiver)
            || Ship_Depot_Helper::check_null_or_empty($receiver->Address)
            || Ship_Depot_Helper::check_null_or_empty($receiver->Ward)
            || Ship_Depot_Helper::check_null_or_empty($receiver->District)
            || Ship_Depot_Helper::check_null_or_empty($receiver->Province)
        ) {
            return false;
        }
        $insurance = new Ship_Depot_Insurance();
        $insurance->IsActive = $is_insurance;
        $insurance->Value = $is_insurance ? $insurance_amount : 0;

        $cod = new Ship_Depot_Cod();
        $cod->IsActive = $is_cod;
        $cod->Value = $is_cod ? $order_total : 0;

        $str_fee_modify = get_option('sd_setting_fee');
        if (!Ship_Depot_Helper::check_null_or_empty($str_fee_modify)) {
            $fee_modify_obj = json_decode($str_fee_modify);
            $fee_modify = new Ship_Depot_Fee_Setting($fee_modify_obj);
            $data_input['fee_setting'] = $fee_modify;
        }

        $dataInput = array(
            "list_package_sizes" => $list_packages,
            "sender_info" => $sender_info,
            "sender_storage" => $sender_storage,
            "receiver" => $receiver,
            "cod" => $cod,
            "insurance" => $insurance,
            "item_total" => $items_total_price,
            "courier" => $for_courier,
            "item_regular_price_total" => $item_regular_price_total,
            "list_items" => $list_items,
            "fee_setting" => isset($fee_modify) ? $fee_modify : ''
        );

        $url_API = SHIP_DEPOT_HOST_API . '/Shipping/CalculateShippingAsync';
        $shop_api_key = get_option('sd_api_key');
        $result = Ship_Depot_Helper::http_post_php($url_API, $dataInput, array('ShopAPIKey' => $shop_api_key));
        // Ship_Depot_Logger::wrlog('[Ship_Depot_Order_Shipping][calculate_shipping] result: ' . print_r($result, true));
        if ($result->Code >= 0) {
            $list_fees_from_api = [];
            foreach ($result->Data as $cour) {
                $courier_response = new Ship_Depot_Courier_Response($cour);
                $list_fees_from_api[] = $courier_response;
            }
            //Calculate Modify/Markup Fee add API => comment call calculate_modify_shipping_fee
            //Ship_Depot_Logger::wrlog('[calculate_shipping_fee] result: ' . print_r($list_fees_from_api, true));
            //$list_fees_from_api = Ship_Depot_Order_Shipping::calculate_modify_shipping_fee($list_fees_from_api, $is_cod, $total_item_price_wo_shipping, $total_item_qty, $list_items);
            Ship_Depot_Logger::wrlog('[calculate_shipping_fee] success');
            return $list_fees_from_api;
        } else {
            return $result->Code;
        }
    }

    /**
     * Calculate modify shipping fee
     * @param Ship_Depot_Courier_Response[] $list_fees_from_api List services fee return from API.
     * @param bool $is_cod If order payment type is COD set this param = true.
     * @param int $total_item_price_wo_shipping Total price of all items in order without shipping.
     * @param int $total_item_qty Total quantity of all items in order.
     * @param Ship_Depot_Item[] $list_items List all items in order for check condition modify fee.
     * @return Ship_Depot_Courier_Response[]|bool
     */
    public static function calculate_modify_shipping_fee($list_fees_from_api, $is_cod, $total_item_price_wo_shipping, $total_item_qty, $list_items)
    {
        $str_fee_modify = get_option('sd_setting_fee');
        if (!Ship_Depot_Helper::check_null_or_empty($str_fee_modify)) {
            $fee_modify_obj = json_decode($str_fee_modify);
            $fee_modify = new Ship_Depot_Fee_Setting($fee_modify_obj);
            Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] fee_modify: ' . print_r($fee_modify, true));
            if ($fee_modify->IsActive && count($fee_modify->ListFeeMarkups) > 0) {
                foreach ($fee_modify->ListFeeMarkups as $fee_group_condition_obj) {
                    $fee_group_condition = new Ship_Depot_Fee_Markup($fee_group_condition_obj);
                    $satisfy = true;
                    //Check active -> time apply -> Condition -> Service apply
                    //Check active
                    if (!$fee_group_condition->IsActive) {
                        continue;
                    }

                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] fee_group_condition active: ' . print_r($fee_group_condition, true));
                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] Check time');
                    //Check time apply
                    if ($fee_group_condition->TimeApply->Type == 'period') {
                        if (Ship_Depot_Helper::check_null_or_empty($fee_group_condition->TimeApply->FromDate) && Ship_Depot_Helper::check_null_or_empty($fee_group_condition->TimeApply->ToDate)) {
                            $satisfy = false;
                            continue;
                        } else if (Ship_Depot_Helper::check_null_or_empty($fee_group_condition->TimeApply->FromDate)) {
                            $date_to_format = date('d-m-Y', $fee_group_condition->TimeApply->ToDate);
                            $current_date_format = date('d-m-Y', time());
                            Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee][Date from empty] date_to_format: ' . print_r($date_to_format, true));
                            Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee][Date from empty] current_date_format: ' . print_r($current_date_format, true));
                            if ($current_date_format > $date_to_format) {
                                $satisfy = false;
                                continue;
                            }
                        } else if (Ship_Depot_Helper::check_null_or_empty($fee_group_condition->TimeApply->ToDate)) {
                            $date_from_format = date('d-m-Y', $fee_group_condition->TimeApply->FromDate);
                            $current_date_format = date('d-m-Y', time());
                            Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee][Date to empty] date_from_format: ' . print_r($date_from_format, true));
                            Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee][Date to empty] current_date_format: ' . print_r($current_date_format, true));
                            if ($current_date_format < $date_from_format) {
                                $satisfy = false;
                                continue;
                            }
                        } else {
                            $date_from_format = date('d-m-Y', $fee_group_condition->TimeApply->FromDate);
                            $date_to_format = date('d-m-Y', $fee_group_condition->TimeApply->ToDate);
                            $current_date_format = date('d-m-Y', time());
                            Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] date_from_format: ' . print_r($date_from_format, true));
                            Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] date_to_format: ' . print_r($date_to_format, true));
                            Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] current_date_format: ' . print_r($current_date_format, true));
                            if ($current_date_format < $date_from_format || $current_date_format > $date_to_format) {
                                $satisfy = false;
                                continue;
                            }
                        }
                    }

                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] Check condition');
                    //Check condition
                    if (count($fee_group_condition->ListOrderConditions) > 0) {
                        foreach ($fee_group_condition->ListOrderConditions as $condition_obj) {
                            $condition = new Ship_Depot_Fee_Markup_Order_Condition($condition_obj);
                            //FromValue, ToValue, Value = -9999 =>null or empty
                            switch ($condition->Type) {
                                case 'total_amount':
                                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] Check condition total_amount');
                                    $total_from = $condition->FromValue;
                                    $total_to = $condition->ToValue;
                                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] total_from: ' . print_r($total_from, true));
                                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] total_to: ' . print_r($total_to, true));
                                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] total_item_price_wo_shipping: ' . print_r($total_item_price_wo_shipping, true));
                                    if ($total_to == -9999) {
                                        if ($total_item_price_wo_shipping < $total_from) {
                                            Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] satisfy = false');
                                            $satisfy = false;
                                        }
                                    } else {
                                        if ($total_from == -9999) {
                                            $total_from = 0;
                                        }

                                        if ($total_item_price_wo_shipping < $total_from || $total_item_price_wo_shipping > $total_to) {
                                            Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] satisfy = false');
                                            $satisfy = false;
                                        }
                                    }

                                    break;
                                case 'item_existed':
                                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] Check condition item_existed');
                                    $satisfy = false;
                                    $str_list_items = $condition->FixedValue;
                                    $list_sku = explode(',', $str_list_items);
                                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] list_sku: ' . print_r($list_sku, true));
                                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] list_items: ' . print_r($list_items, true));
                                    foreach ($list_items as $item) {
                                        $is_existed = false;
                                        foreach ($list_sku as $sku) {
                                            if ($item->Sku == $sku) {
                                                $is_existed = true;
                                                $satisfy = true;
                                                break;
                                            }
                                        }

                                        if ($is_existed) {
                                            break;
                                        }
                                    }
                                    break;
                                case 'item_quantity':
                                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] Check condition item_quantity');
                                    $total_qty_from = $condition->FromValue;
                                    $total_qty_to = $condition->ToValue;
                                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] total_qty_from: ' . print_r($total_qty_from, true));
                                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] total_qty_to: ' . print_r($total_qty_to, true));
                                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] total_item_qty: ' . print_r($total_item_qty, true));
                                    if ($total_qty_to == -9999) {
                                        if ($total_item_qty < $total_qty_from) {
                                            Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] satisfy = false');
                                            $satisfy = false;
                                        }
                                    } else {
                                        if ($total_qty_from == -9999) {
                                            $total_qty_from = 0;
                                        }

                                        if ($total_item_qty < $total_qty_from || $total_item_qty > $total_qty_to) {
                                            Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] satisfy = false');
                                            $satisfy = false;
                                        }
                                    }
                                    break;
                                case 'payment_cod':
                                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] Check condition payment_cod');
                                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] is_cod: ' . print_r($is_cod, true));
                                    if (!$is_cod) {
                                        $satisfy = false;
                                    }
                                    break;
                                case 'payment_transfer':
                                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] Check condition payment_transfer');
                                    Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] is_cod: ' . print_r($is_cod, true));
                                    if ($is_cod) {
                                        $satisfy = false;
                                    }
                                    break;
                            }

                            if (!$satisfy) {
                                Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] satisfy = false => break');
                                break;
                            }
                        }

                        if (!$satisfy) {
                            continue;
                        }
                    }

                    //Check service
                    if ($fee_group_condition->CourierApply->Type == 'customize') {
                        foreach ($fee_group_condition->CourierApply->ListCouriers as $courApply_obj) {
                            $courApply = new Ship_Depot_Fee_Markup_Courier_Service($courApply_obj);
                            if (!$courApply->IsActive) {
                                continue;
                            }

                            foreach ($courApply->ListServicesSelected as $servApply) {
                                $is_modify = false;
                                foreach ($list_fees_from_api as $courier) {
                                    if ($courier->CourierID == $courApply->CourierID) {
                                        foreach ($courier->ListServices as $service) {
                                            if ($service->ServiceCode == $servApply) {
                                                //Set fee
                                                (new Ship_Depot_Order_Shipping)->set_shipping_fee_modify($fee_group_condition, $service);
                                                $is_modify = true;
                                                break;
                                            }
                                        }
                                    }

                                    if ($is_modify) {
                                        //Because 1 service exist in only one courier
                                        break;
                                    }
                                }
                            }
                        }
                    } else {
                        foreach ($list_fees_from_api as $courier) {
                            foreach ($courier->ListServices as $service) {
                                //Set fee
                                $service = (new Ship_Depot_Order_Shipping)->set_shipping_fee_modify($fee_group_condition, $service);
                            }
                        }
                    }
                    break;
                }
            }
        }
        Ship_Depot_Logger::wrlog('[calculate_modify_shipping_fee] list_fees_from_api: ' . print_r($list_fees_from_api, true));
        return $list_fees_from_api;
    }

    /**
     * Set shipping fee modify for each service.
     * @param Ship_Depot_Fee_Markup $fee_group_condition
     * @param Ship_Depot_Shipping_Fee_Response $service
     */
    private function set_shipping_fee_modify($fee_group_condition, $service)
    {
        Ship_Depot_Logger::wrlog('[set_shipping_fee_modify] $service bf: ' . print_r($service, true));
        $service->ShopMarkupShippingFee->IsActive = true;
        $service->ShopMarkupShippingFee->MarkupOption = $fee_group_condition->Option;
        if ($fee_group_condition->Option->Type == 'fixed_fee') {
            $service->ShopMarkupShippingFee->ShippingFeeTotal = intval($fee_group_condition->Option->Value);
        } else if ($fee_group_condition->Option->Type == 'depend_shipdepot') {
            switch ($fee_group_condition->Option->SubType) {
                case 'inc_percent':
                    $service->ShopMarkupShippingFee->ShippingFeeTotal = $service->ShipDepotMarkupShippingFee->ShippingFeeTotal + ($service->ShipDepotMarkupShippingFee->ShippingFeeTotal * $fee_group_condition->Option->Value / 100);
                    break;
                case 'dec_percent':
                    $service->ShopMarkupShippingFee->ShippingFeeTotal = $service->ShipDepotMarkupShippingFee->ShippingFeeTotal - ($service->ShipDepotMarkupShippingFee->ShippingFeeTotal * $fee_group_condition->Option->Value / 100);
                    break;
                case 'inc_amount':
                    $service->ShopMarkupShippingFee->ShippingFeeTotal = $service->ShipDepotMarkupShippingFee->ShippingFeeTotal + $fee_group_condition->Option->Value;
                    break;
                case 'dec_amount':
                    $service->ShopMarkupShippingFee->ShippingFeeTotal = $service->ShipDepotMarkupShippingFee->ShippingFeeTotal - $fee_group_condition->Option->Value;
                    break;
            }
        }
        //Prevent shipping fee < 0
        if ($service->ShopMarkupShippingFee->ShippingFeeTotal < 0) {
            $service->ShopMarkupShippingFee->ShippingFeeTotal = 0;
        }
        //
        $service->ShopMarkupShippingFee->ShippingFeeTotal = round($service->ShopMarkupShippingFee->ShippingFeeTotal);
        if ($service->ShipDepotMarkupShippingFee->ShippingFeeTotal != 0) {
            Ship_Depot_Logger::wrlog('[set_shipping_fee_modify] ShopMarkupShippingFee->ShippingFeeTotal: ' . print_r($service->ShopMarkupShippingFee->ShippingFeeTotal, true));
            $rate = $service->ShopMarkupShippingFee->ShippingFeeTotal / $service->ShipDepotMarkupShippingFee->ShippingFeeTotal;
            Ship_Depot_Logger::wrlog('[set_shipping_fee_modify] rate: ' . print_r($rate, true));
            $service->ShopMarkupShippingFee->ShippingFeeNet = floor($service->ShipDepotMarkupShippingFee->ShippingFeeNet * $rate);
            $service->ShopMarkupShippingFee->InsuranceFee = floor($service->ShipDepotMarkupShippingFee->InsuranceFee * $rate);
            $service->ShopMarkupShippingFee->CODFee = floor($service->ShipDepotMarkupShippingFee->CODFee * $rate);
            $remain_amount = $service->ShopMarkupShippingFee->ShippingFeeTotal - $service->ShopMarkupShippingFee->ShippingFeeNet - $service->ShopMarkupShippingFee->InsuranceFee - $service->ShopMarkupShippingFee->CODFee;
            if ($remain_amount != 0) {
                if ($service->ShipDepotMarkupShippingFee->OtherFees != 0) {
                    $service->ShopMarkupShippingFee->OtherFees = $remain_amount;
                } else {
                    if ($service->ShipDepotMarkupShippingFee->CODFee != 0) {
                        $service->ShopMarkupShippingFee->CODFee += $remain_amount;
                    } else if ($service->ShipDepotMarkupShippingFee->InsuranceFee != 0) {
                        $service->ShopMarkupShippingFee->InsuranceFee += $remain_amount;
                    }
                }
            }
        } else {
            $service->ShopMarkupShippingFee->ShippingFeeNet = $service->ShopMarkupShippingFee->ShippingFeeTotal;
        }

        Ship_Depot_Logger::wrlog('[set_shipping_fee_modify] $service aft: ' . print_r($service, true));
    }

    /**
     * @param Ship_Depot_Province $city
     * @param Ship_Depot_District $district
     * @param int $ward_isn
     * @param int $courier_isn
     * @return Ship_Depot_Station[]|bool
     */
    public static function get_shipping_stations($city, $district, $ward_isn, $courier_isn)
    {
        if ($city == null || $district == null || $city->CityISN <= 0 || $district->DistrictISN <= 0 || $courier_isn <= 0) {
            return false;
        }

        $dataInput = array(
            "City" => $city,
            "District" => $district,
            "WardISN" => $ward_isn,
            "CourierISN" => $courier_isn
        );

        $url_API = SHIP_DEPOT_HOST_API . '/Shipping/GetShipStations';
        $shop_api_key = get_option('sd_api_key');
        $result = Ship_Depot_Helper::http_post_php($url_API, $dataInput, array('ShopAPIKey' => $shop_api_key));
        Ship_Depot_Logger::wrlog('[Ship_Depot_Order_Shipping][get_shipping_stations] result: ' . print_r($result, true));
        if ($result->Code >= 0) {
            $list_stations = [];
            foreach ($result->Data as $station_obj) {
                $station = new Ship_Depot_Station($station_obj);
                $list_stations[] = $station;
            }
            return $list_stations;
        } else {
            return false;
        }
    }

    public static function cancel_shipping(WC_Order $order, bool $update_status = true): Ship_Depot_ResultDTO
    {
        $shop_api_key = get_option('sd_api_key');
        $data_input = array();
        $ship_info = json_decode(Ship_Depot_Helper::GetOrderMetadata($order, 'sd_ship_info', true));
        Ship_Depot_Logger::wrlog('[cancel_shipping] ship_info: ' . print_r($ship_info, true));
        $data_input["tracking_number"] = isset($ship_info->TrackingNumber) ? $ship_info->TrackingNumber : '';
        //
        $selected_courier = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_selected_courier', true);
        $data_input["selected_courier"] = $selected_courier;
        Ship_Depot_Logger::wrlog('[cancel_shipping] data_input: ' . print_r($data_input, true));
        if (Ship_Depot_Helper::check_null_or_empty($data_input["tracking_number"])) {
            return new Ship_Depot_ResultDTO(-1, __('Đơn hàng chưa có vận đơn.', 'ship-depot-translate'));
        }

        if (Ship_Depot_Helper::check_null_or_empty($data_input["selected_courier"])) {
            return new Ship_Depot_ResultDTO(-1, __('Không xác định được đơn vị vận chuyển để hủy.', 'ship-depot-translate'));
        }

        $url = SHIP_DEPOT_HOST_API . '/Shipping/CancelShipping';
        $rs = Ship_Depot_Helper::http_post_php($url, $data_input, array('ShopAPIKey' => $shop_api_key));
        Ship_Depot_Logger::wrlog('[cancel_shipping] result call API: ' . print_r($rs, true));
        if ($rs->Code > 0) {
            $result = $rs->Data;
            Ship_Depot_Logger::wrlog('[cancel_shipping] result: ' . print_r($result, true));
            if ($result) {
                //Remove shipping info
                $order_note = __('Hủy vận đơn ' . $ship_info->TrackingNumber . ' thành công.', 'ship-depot-translate');
                Ship_Depot_Shipping_Helper::update_cancel_shipping_info($order, $ship_info->TrackingNumber, $order_note, $update_status);
            } else {
                $order_note = __('Hủy vận đơn ' . $ship_info->TrackingNumber . ' thất bại. Lý do:', 'ship-depot-translate');
                $order_note = $order_note . ' ' . $rs->Msg;
                $order->add_order_note($order_note);
            }
        } else {
            $order_note = __('Hủy vận đơn ' . $ship_info->TrackingNumber . ' thất bại. Lý do:', 'ship-depot-translate');
            $order_note = $order_note . ' ' . $rs->Msg;
            $order->add_order_note($order_note);
        }
        return new Ship_Depot_ResultDTO(1, 'Success');
    }
}

add_action('woocommerce_order_status_changed', 'sd_handle_woo_order_status_changed', 10, 3);

function sd_handle_woo_order_status_changed($order_id, $old_status, $new_status)
{
    $order = wc_get_order($order_id);
    Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed] order_id: ' . print_r($order_id, true));
    Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed] _POST: ' . print_r($_POST, true));
    Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed] old_status: ' . print_r($old_status, true));
    Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed] new_status: ' . print_r($new_status, true));
    // Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed] post_type: ' . sanitize_text_field($_POST['post_type']));
    // Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed] action: ' . sanitize_text_field($_POST['action']));

    /*
    pending
	processing
	on-hold
	completed
	cancelled
	refunded
    failed

    sd-delivering
    sd-delivered
    sd-delivery-failed
    */

    //Auto cancel shipping
    if ($new_status == 'cancelled') {
        Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed][auto_cancel_shipping] Begin');
        $str_ship_info = Ship_Depot_Helper::GetOrderMetadata($order_id, 'sd_ship_info', true);
        Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed][auto_cancel_shipping] str_ship_info: ' . print_r($str_ship_info, true));
        $order = wc_get_order($order_id);
        $result = Ship_Depot_Order_Shipping::cancel_shipping($order, false);
        if ($result->Code < 0) {
            Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed][auto_cancel_shipping] Cancel shipping of order ' . $order_id . ' error. Result: ' . print_r($result, true));
        }
        Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed][auto_cancel_shipping] End');
    }

    //Auto create ship
    if (sanitize_key($_POST['post_type']) == 'shop_order' || sanitize_key($_POST['post_type']) == 'woocommerce_page_wc-orders' || sanitize_key($_POST['action']) == 'editpost' || sanitize_key($_POST['action']) == 'edit_order') {
        Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed] from edit order => Not create shipping auto ');
        return;
    }

    $order_from_checkout = Ship_Depot_Helper::GetOrderMetadata($order_id, 'sd_from_fe', true);
    if (Ship_Depot_Helper::check_null_or_empty($order_from_checkout) || $order_from_checkout == 'false') {
        Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed] order not from checkout => Not create shipping auto ');
        return;
    }

    Ship_Depot_Default_Data::DefaultAutoCreateShip();
    $auto_create_ship = get_option('sd_auto_create_shipping');
    Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed][auto_create_shipping] auto_create_ship: ' . $auto_create_ship);
    if ($auto_create_ship == 'yes' || $auto_create_ship == '1') {
        $stt_auto_create_ship = get_option('sd_status_auto_create_shipping');
        Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed][auto_create_shipping] stt_auto_create_ship: ' . $stt_auto_create_ship);
        if (str_contains($stt_auto_create_ship, ',')) {
            $statuses = explode(',', $stt_auto_create_ship);
        } else {
            $statuses = array($stt_auto_create_ship);
        }
        $new_status = 'wc-' . $new_status;
        Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed][auto_create_shipping] new_status: ' . $new_status);
        if (in_array($new_status, $statuses)) {
            $str_ship_info = Ship_Depot_Helper::GetOrderMetadata($order_id, 'sd_ship_info', true);
            // $not_create_ship = Ship_Depot_Helper::GetOrderMetadata($order_id, 'sd_not_create_ship', true);
            // if (Ship_Depot_Helper::check_null_or_empty($not_create_ship) || $not_create_ship != 'true') {

            // } else {
            //     Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed][auto_create_shipping] not_create_ship = ' . $not_create_ship . ' => Not create shipping');
            // }

            //Auto create ship from checkout page so always create shipping if status matches status auto create ship and change param sd_not_create_ship to false
            if (!Ship_Depot_Helper::check_null_or_empty($str_ship_info)) {
                $ship_info = json_decode($str_ship_info);
                if (Ship_Depot_Helper::check_null_or_empty($ship_info->TrackingNumber)) {
                    Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed][auto_create_shipping] TrackingNumber empty => Create shipping');
                    save_shipping_to_order_meta_data($order_id);
                    $rs = create_ship($order_id, true);
                    if ($rs) {
                        Ship_Depot_Helper::UpdateOrderMetadata($order_id, 'sd_not_create_ship', json_encode(false));
                    }
                } else {
                    Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed][auto_create_shipping] TrackingNumber existed: ' . $ship_info->TrackingNumber . ' => Not create shipping');
                }
            } else {
                Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed][auto_create_shipping] str_ship_info = null => Create shipping');
                save_shipping_to_order_meta_data($order_id);
                $rs = create_ship($order_id, true);
                if ($rs) {
                    Ship_Depot_Helper::UpdateOrderMetadata($order_id, 'sd_not_create_ship', json_encode(false));
                }
            }
        } else {
            Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed][auto_create_shipping] order_status not existed stt_auto_create_ship => Not create shipping');
        }
    } else {
        Ship_Depot_Logger::wrlog('[sd_handle_woo_order_status_changed][auto_create_shipping] auto_create_ship = false => Not create shipping ');
    }
}

function save_shipping_to_order_meta_data($order_id)
{
    Ship_Depot_Logger::wrlog('[save_shipping_to_order_meta_data]');
    if (!isset($_POST['sd_order_detail_nonce']) || !wp_verify_nonce($_POST['sd_order_detail_nonce'], 'sd_order_detail')) {
        return $order_id;
    }

    if (sanitize_key($_POST['post_type']) != 'shop_order' || sanitize_key($_POST['post_type']) == 'woocommerce_page_wc-orders' || sanitize_key($_POST['action']) != 'editpost') {
        return $order_id;
    }
    //Check api key first
    $shop_api_key = get_option('sd_api_key');
    if (Ship_Depot_Helper::check_null_or_empty($shop_api_key)) {
        Ship_Depot_Logger::wrlog('[save_shipping_to_order_meta_data] API Key is empty => cannot save ');
        return false;
    }

    Ship_Depot_Logger::wrlog('[save_shipping_to_order_meta_data] Start save');
    $get_data = new Ship_Depot_Get_Data($_POST, $order_id);

    $order = wc_get_order($order_id);
    //List package sizes
    $list_package_size = $get_data->get_package_sizes();
    $json_packages = json_encode($list_package_size);
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_list_package_size', $json_packages);

    //sender
    $sender_storage = $get_data->get_json_sender_storage();
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_sender_storage', $sender_storage);

    $str_sender_info = $get_data->get_json_sender_info();
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_sender_info', $str_sender_info);

    //receiver
    $receiver = $get_data->get_receiver_info();
    $json_rcv = json_encode($receiver, JSON_UNESCAPED_UNICODE);
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_receiver', $json_rcv);

    //insurance
    $insurance = $get_data->get_insurance_info();
    $json_insr = json_encode($insurance);
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_insurance', $json_insr);

    //cod
    $cod = $get_data->get_cod_info();
    $json_cod = json_encode($cod);
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_cod', $json_cod);

    //selected_shipping
    $json_selected_shipping = $get_data->get_json_selected_shipping();
    Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] json_selected_shipping data: ' . print_r($json_selected_shipping, true));
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_selected_shipping', $json_selected_shipping);

    //selected_courier
    $selected_courier = $get_data->get_selected_courier();
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_selected_courier', $selected_courier);

    //shipping_notes
    $shipping_notes = $get_data->get_shipping_notes();
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_shipping_notes', $shipping_notes);

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
    Ship_Depot_Helper::UpdateOrderMetadataWOSave($order, 'sd_list_items', $json_items);
    //
    if (function_exists('sd_save_wc_order_other_fields')) {
        // unhook this function so it doesn't loop infinitely
        Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] unhook.');
        remove_action('save_post', 'sd_save_wc_order_other_fields', 10, 1);
        //
    }

    //Save order meta data to db
    Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] order save.');
    $order->save();

    if (function_exists('sd_save_wc_order_other_fields')) {
        // re-hook this function.
        Ship_Depot_Logger::wrlog('[sd_submit_data_and_save_to_order_meta_data] re-hook.');
        add_action('save_post', 'sd_save_wc_order_other_fields', 10, 1);
    }
}

function create_ship($order_id, bool $from_checkout)
{
    $order = wc_get_order($order_id);
    Ship_Depot_Logger::wrlog('[create_ship] get_post_meta: ' . print_r(Ship_Depot_Helper::GetOrderMetadata($order, '', true), true));
    //Selected courier
    $selected_courier = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_selected_courier', true);
    Ship_Depot_Logger::wrlog('[create_ship] selected_courier: ' . $selected_courier);

    //Selected Shipping
    $json_selected_shipping = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_selected_shipping', true);
    Ship_Depot_Logger::wrlog('[create_ship] json_selected_shipping: ' . $json_selected_shipping);
    $selected_shipping = new Ship_Depot_Shipping_Fee_Response();
    if (!Ship_Depot_Helper::check_null_or_empty($json_selected_shipping)) {
        $selected_shipping = new Ship_Depot_Shipping_Fee_Response(json_decode($json_selected_shipping));
    } else {
        return false;
    }
    //List package sizes
    $list_package_size = [];
    $json_list_package_size = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_list_package_size', true);
    Ship_Depot_Logger::wrlog('[create_ship] json_list_package_size: ' . $json_list_package_size);
    if (!Ship_Depot_Helper::check_null_or_empty($json_list_package_size)) {
        $list_package_size = json_decode($json_list_package_size);
    } else {
        return false;
    }

    //Sender
    $json_sender_info = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_sender_info', true);
    Ship_Depot_Logger::wrlog('[create_ship] json_sender_info: ' . $json_sender_info);
    $sender_info = new Ship_Depot_Shop_Info();
    if (!Ship_Depot_Helper::check_null_or_empty($json_sender_info)) {
        $sender_info_obj = json_decode($json_sender_info);
        $sender_info = new Ship_Depot_Shop_Info($sender_info_obj);
    } else {
        return false;
    }

    //Sender storage
    $sender_storage = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_sender_storage', true);
    Ship_Depot_Logger::wrlog('[create_ship] sender_storage: ' . $sender_storage);
    //Receiver
    $json_rcv = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_receiver', true);
    $receiver = new Ship_Depot_Receiver();
    if (!Ship_Depot_Helper::check_null_or_empty($json_rcv)) {
        $receiver = new Ship_Depot_Receiver(json_decode($json_rcv));
    } else {
        return false;
    }

    //Insurance
    $json_insurance = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_insurance', true);
    Ship_Depot_Logger::wrlog('[create_ship] json_insurance: ' . $json_insurance);
    $insurance = new Ship_Depot_Insurance();
    if (!Ship_Depot_Helper::check_null_or_empty($json_insurance)) {
        $insurance = new Ship_Depot_Insurance(json_decode($json_insurance));
    }

    //Cod
    $json_cod = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_cod', true);
    Ship_Depot_Logger::wrlog('[create_ship] json_cod: ' . $json_cod);
    $cod = new Ship_Depot_Cod();
    if (!Ship_Depot_Helper::check_null_or_empty($json_cod)) {
        $cod = new Ship_Depot_Cod(json_decode($json_cod));
    }

    //Shipping notes
    $shipping_notes = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_shipping_notes', true);
    Ship_Depot_Logger::wrlog('[create_ship] shipping_notes: ' . $shipping_notes);
    //Customer pay shipping
    $str_cus_pay_ship = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_customer_pay_shipping', true);
    Ship_Depot_Logger::wrlog('[create_ship] str_cus_pay_ship: ' . $str_cus_pay_ship);
    $cus_pay_ship = Ship_Depot_Helper::check_null_or_empty($str_cus_pay_ship) || $str_cus_pay_ship != 'true' ? false : true;

    //list_items
    $list_items = [];
    $order_items = $order->get_items();
    $item_regular_price_total = 0;
    foreach ($order_items as $item) {
        $item_data = $item->get_data();
        // Ship_Depot_Logger::wrlog('[create_ship] item_data: ' . print_r($item_data, true));
        $product_id = $item_data['product_id'];
        $product_image = wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'single-post-thumbnail');
        $it = new Ship_Depot_Item();
        $it->ID = $item_data['id'];
        if ($product_image != false) {
            Ship_Depot_Logger::wrlog('[create_ship] item_image: ' . $product_image[0]);
            $it->ImageURL = $product_image[0];
        }
        $it->Name = $item_data['name'];
        $it->Quantity = $item_data['quantity'];
        $it->TotalPrice = $item_data['total'];
        $item_product = new WC_Order_Item_Product($item->get_id());
        $product = $item_product->get_product();
        // Ship_Depot_Logger::wrlog('[create_ship] product: ' . print_r($product, true));
        $it->Length = Ship_Depot_Helper::ConvertToShipDepotDimension($product->get_length());
        $it->Width = Ship_Depot_Helper::ConvertToShipDepotDimension($product->get_width());
        $it->Height = Ship_Depot_Helper::ConvertToShipDepotDimension($product->get_height());
        $it->Weight = Ship_Depot_Helper::ConvertToShipDepotWeight($product->get_weight());
        //
        $regular_price = $product->get_regular_price();
        $item_regular_price_total += floatval($regular_price) * $item->get_quantity();
        $it->RegularPrice = $regular_price;
        //
        Ship_Depot_Logger::wrlog('[create_ship] it: ' . print_r($it, true));
        array_push($list_items, $it);
    }

    //cod failed info
    $json_cod_failed_info = Ship_Depot_Helper::GetOrderMetadata($order, 'sd_cod_failed_info', true);
    Ship_Depot_Logger::wrlog('[create_ship] json_cod_failed_info: ' . $json_cod_failed_info);
    $cod_failed_info = new Ship_Depot_COD_Failed();
    if (!Ship_Depot_Helper::check_null_or_empty($json_cod_failed_info)) {
        $cod_failed_info = new Ship_Depot_COD_Failed(json_decode($json_cod_failed_info));
    }

    //ship from station
    $ship_from_station = null;
    Ship_Depot_Helper::UpdateOrderMetadata($order_id, 'sd_ship_from_station', '');
    if ($selected_courier == GHN_COURIER_CODE) {
        $setting_courier = json_decode(get_option('sd_setting_courier'));
        if (!is_null($setting_courier)) {
            foreach ($setting_courier as $cour_obj) {
                $cour = new Ship_Depot_Courier($cour_obj);
                if ($cour->CourierID == 'GHN') {
                    $ship_from_station = $cour->ShipFromStation;
                    Ship_Depot_Helper::UpdateOrderMetadata($order_id, 'sd_ship_from_station', json_encode($ship_from_station, JSON_UNESCAPED_UNICODE));
                }
            }
        }
    }
    $order_created_date = $order->get_date_created();
    Ship_Depot_Logger::wrlog('[create_ship] order_created_date string: ' . print_r($order_created_date->__toString(), true));
    Ship_Depot_Logger::wrlog('[create_ship] ship_from_station: ' . print_r($ship_from_station, true));

    Ship_Depot_Logger::wrlog('[create_ship] Call API');
    $data_input = array(
        "order_id" => $order_id,
        "order_created_date" => $order_created_date->__toString(),
        "order_total" => $order->get_total(),
        "item_total" => $order->get_subtotal(),
        "selected_courier" => $selected_courier,
        "cod_failed_info" => $cod_failed_info,
        "selected_shipping" => $selected_shipping,
        "shipping_notes" => $shipping_notes,
        "sender_info" => $sender_info,
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
        "tracking_number" => '',
        "shipment_ISN" => 0,
        "ship_from_station" => $ship_from_station,
        "total_discount_amount" => $order->get_discount_total(),
        "from_checkout" => $from_checkout,
    );

    $shop_api_key = get_option('sd_api_key');
    // Ship_Depot_Logger::wrlog('[create_ship] data_input: ' . print_r($data_input, true));
    $url = SHIP_DEPOT_HOST_API . '/Shipping/CreateShipping';
    $rs = Ship_Depot_Helper::http_post_php($url, $data_input, array('ShopAPIKey' => $shop_api_key));
    // Ship_Depot_Logger::wrlog('[create_ship] result call API: ' . print_r($rs, true));
    if ($rs->Code > 0) {
        $order_note = __('Vận đơn tạo thành công. Mã vận đơn', 'ship-depot-translate');
        if ($from_checkout) {
            $order_note = __('Vận đơn được tạo tự động thành công. Mã vận đơn', 'ship-depot-translate');
        }
        $order_note = $order_note . ' ' . $rs->Data->TrackingNumber;
        $order->add_order_note($order_note);
        Ship_Depot_Helper::UpdateOrderMetadata($order_id, 'sd_ship_info', json_encode($rs->Data, JSON_UNESCAPED_UNICODE));
        Ship_Depot_Helper::UpdateOrderMetadata($order, 'sd_from_fe', json_encode(false));
        return true;
    } else {
        $order_note = __('Vận đơn được tạo tự động thất bại. Lý do:', 'ship-depot-translate');
        $order_note = $order_note . ' ' . $rs->Msg;
        $order->add_order_note($order_note);
    }
    return false;
}
