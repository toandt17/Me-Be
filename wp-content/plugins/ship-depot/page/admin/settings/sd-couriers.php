<?php

use WSAL\Helpers\Logger;

if (!defined('WPINC')) {
    die;
}

$str_sender_info = get_option('sd_sender_info');
if (!is_null($str_sender_info) && !empty($str_sender_info)) {
    $sender_info_obj = json_decode($str_sender_info);
    $sender_info = new Ship_Depot_Shop_Info($sender_info_obj);
}
?>
<div class="sd-div sd-setting" id="sd-courier">
    <input type="hidden" name="validate_error" id="validate_error" value="false" />
    <p id="title"><?php esc_html_e('Đơn vị vận chuyển và dịch vụ cho đơn hàng', 'ship-depot-translate') ?></p>
    <p class="description"><?php esc_html_e('Chọn các đơn vị vận chuyển cùng với dịch vụ tương ứng sẽ được chọn khi tạo đơn hàng.', 'ship-depot-translate') ?></p>


    <?php
    wp_nonce_field('sd_couriers', 'sd_couriers_nonce');
    $setting_courier = json_decode(get_option('sd_setting_courier'));
    $listCouriers = json_decode(get_option('sd_list_couriers'));
    Ship_Depot_Logger::wrlog('[sd-couriers] setting_courier: ' . print_r($setting_courier, true));
    ?>
    <?php
    if (!$listCouriers) return;
    foreach ($listCouriers as $courier_obj) {
        //Ship_Depot_Logger::wrlog('[sd-couriers] courier: ' . print_r($courier, true));
        $courier = new Ship_Depot_Courier($courier_obj);
        $listServices = $courier->ListServices;
        $courier_only = $courier;
        $courier_only->ListServices = [];
        if (!is_null($setting_courier)) {
            foreach ($setting_courier as $cour_obj) {
                $cour = new Ship_Depot_Courier($cour_obj);
                if ($cour->CourierID == $courier->CourierID) {
                    $st_courier = $cour;
                    Ship_Depot_Logger::wrlog('[sd-couriers] st_courier: ' . print_r($st_courier, true));
                }
            }
        }
    ?>
        <div class="courier-container">
            <input type="hidden" name="couriers_id[]" value="<?php echo esc_attr($courier->CourierID) ?>" />

            <div class="container-fluid col-padding-0">
                <div class="row">
                    <div class="col-xl-4 col-lg-12 col-md-12 div-img">
                        <label for="cb_<?php echo esc_attr($courier->CourierID); ?>_isActive">
                            <?php
                            $checkedAttr = isset($st_courier) ? checked($st_courier->IsUsed, true, false) : '';
                            echo '<input name="' . esc_attr($courier->CourierID) . '[IsUsed]" id="cb_' . esc_attr($courier->CourierID) . '_isActive" class="cb_courier_isUsed" type="checkbox" value="1" ' . $checkedAttr . '  />';
                            ?>
                            <img <?php echo Ship_Depot_Helper::check_null_or_empty($courier->LogoURL) ? '' : 'src="' . esc_url($courier->LogoURL) . '"' ?> alt="<?php echo esc_attr($courier->CourierName) ?>" data-placement="bottom" title="<?php echo esc_attr($courier->CourierName) ?>">
                        </label>
                    </div>
                    <div class="col-xl-8 col-lg-12 col-md-12 container-fluid div_service<?php echo isset($st_courier) && $st_courier->IsUsed ? '' : ' disable-element' ?>">
                        <div class="row">
                            <?php
                            $count = 0;
                            foreach ($listServices as $service_obj) {
                                $service = new Ship_Depot_Courier_Service($service_obj);
                                if (isset($st_courier)) {
                                    foreach ($st_courier->ListServices as $serv_obj) {
                                        $serv = new Ship_Depot_Courier_Service($serv_obj);
                                        if ($serv->ServiceCode == $service->ServiceCode) {
                                            $st_serv = $serv;
                                        }
                                    }
                                }
                                if ($count > 0 && $count % 2 == 0) {
                                    echo '<div class="w-100"></div>';
                                }
                                echo '<div class="col-xl-5 col-lg-6 col-md-6 col-sm-6 col-12 service-col">';
                                $checkedAttr = isset($st_serv) ? checked($st_serv->IsUsed, true, false) : '';
                                $service_id = $courier->CourierID . '[' . $service->ServiceCode . ']';
                                echo '<input name="' . esc_attr($courier->CourierID) . '[service_id][]" type="hidden" value="' . esc_attr($service->ServiceCode) . '"/>';
                                echo '<input name="' . esc_attr($service_id) . '[Data]" type="hidden" value="' . esc_attr(Ship_Depot_Helper::ParseObjectToJsonHTML($service)) . '"/>';
                                echo '<label for="' . esc_attr($service_id) . '"><input name="' . esc_attr($service_id) . '[IsUsed]" id="' . esc_attr($service_id) . '" type="checkbox" value="1" ' . $checkedAttr . '  />' . esc_attr($service->ServiceName) . '</label>';
                                echo '</div>';
                                $count++;
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="div-cod<?php echo $courier->ApplyCOD && isset($st_courier) && $st_courier->IsUsed ? '' : ' disable-element' ?>">
                        <p><?php esc_html_e('Thanh toán khi nhận hàng (COD)', 'ship-depot-translate') ?></p>
                        <?php
                        $checkedAttrYes = '';
                        $checkedAttrNo = '';
                        if ($courier->ApplyCOD) {
                            if (isset($st_courier)) {
                                $checkedAttrYes = checked($st_courier->HasCOD, true, false);
                                $checkedAttrNo = checked($st_courier->HasCOD, false, false);
                            } else {
                                $checkedAttrYes = checked(true, true, false);
                                $checkedAttrNo = checked(true, false, false);
                            }
                        } else {
                            $checkedAttrYes = checked(false, true, false);
                            $checkedAttrNo = checked(true, true, false);
                        }

                        echo '<label for="' . esc_attr($courier->CourierID) . '_cod_yes">
                        <input type="radio" id="' . esc_attr($courier->CourierID) . '_cod_yes" name="' . esc_attr($courier->CourierID) . '[HasCOD]" value="1" ' . $checkedAttrYes . '/>'
                            . esc_html__('Có', 'ship-depot-translate')
                            . '</label>';

                        echo '<label for="' . esc_attr($courier->CourierID) . '_cod_no">
                        <input class="rd-no" type="radio" id="' . esc_attr($courier->CourierID) . '_cod_no" name="' . esc_attr($courier->CourierID) . '[HasCOD]" value="0" ' . $checkedAttrNo . '/>'
                            . esc_html__('Không', 'ship-depot-translate')
                            . '</label>';
                        ?>
                    </div>
                </div>
                <?php
                // COD Failed Area
                if ($courier->CourierID == GHN_COURIER_CODE) {
                ?>
                    <div class="row">
                        <div class="div-cod-failed<?php echo $courier->ApplyCOD && isset($st_courier) && $st_courier->IsUsed ? '' : ' disable-element' ?>">
                            <p><?php esc_html_e('Thu phí "Giao thất bại" đối với khách hàng (do khách hàng không nhận hàng và hoàn hàng) – hay còn gọi là COD thu khách hàng khi giao thất bại', 'ship-depot-translate') ?></p>
                            <?php
                            $checkedAttrYes = '';
                            $checkedAttrNo = '';
                            if (isset($st_courier)) {
                                $checkedAttrYes = checked($st_courier->CODFailed->IsUsed, true, false);
                                $checkedAttrNo = checked($st_courier->CODFailed->IsUsed, false, false);
                            } else {
                                $checkedAttrYes = checked(true, false, false);
                                $checkedAttrNo = checked(true, true, false);
                            }

                            echo '<label for="' . esc_attr($courier->CourierID) . '_codfailed_yes">
                            <input class="rb-cod-failed-use-yes" type="radio" id="' . esc_attr($courier->CourierID) . '_codfailed_yes" name="' . esc_attr($courier->CourierID) . '[CODFailed][IsUsed]" value="1" ' . $checkedAttrYes . '/>'
                                . esc_html__('Có', 'ship-depot-translate')
                                . '</label>';

                            echo '<label for="' . esc_attr($courier->CourierID) . '_codfailed_no">
                            <input class="rd-no rb-cod-failed-use-no" type="radio" id="' . esc_attr($courier->CourierID) . '_codfailed_no" name="' . esc_attr($courier->CourierID) . '[CODFailed][IsUsed]" value="0" ' . $checkedAttrNo . '/>'
                                . esc_html__('Không', 'ship-depot-translate')
                                . '</label>';
                            ?>

                            <div class="grid-cod-failed<?php echo $st_courier->CODFailed->IsUsed ? "" : " disable-element" ?>">
                                <div class="container-fluid col-padding-0">
                                    <div class="row">
                                        <div class="col-auto grid-cell">
                                            <p class="cod-failed-title">
                                                <?php esc_html_e('COD khi giao thất bại (đ)') ?>
                                            </p>
                                        </div>
                                        <div class="col-xl-4 col-lg-8 col-md-8 col-sm-12 grid-cell div-cod-failed-amount">
                                            <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="<?php echo esc_attr($courier->CourierID) ?>[CODFailed][CODFailedAmount]" value="<?php echo esc_attr(Ship_Depot_Helper::currency_format($st_courier->CODFailed->CODFailedAmount, '')) ?>" class="cod-failed-value" />
                                            <p class="description">₫</p>
                                        </div>
                                    </div>

                                    <div class="row div-checkout-content">
                                        <div class="col-xl-auto col-lg-12 col-md-12 grid-cell">
                                            <p class="cod-failed-title">
                                                <?php esc_html_e('Hiển thị câu thông báo trên trang checkout:') ?>
                                            </p>
                                        </div>
                                        <?php
                                        if (isset($st_courier)) {
                                            $checkedAttrYes = checked($st_courier->CODFailed->ContentCheckout->IsShow, true, false);
                                            $checkedAttrNo = checked($st_courier->CODFailed->ContentCheckout->IsShow, false, false);
                                        } else {
                                            $checkedAttrYes = checked(true, false, false);
                                            $checkedAttrNo = checked(true, true, false);
                                        }
                                        ?>
                                        <div class="col-xl-auto col-lg-auto col-md-auto col-sm-auto grid-cell">
                                            <label for="<?php echo esc_attr($courier->CourierID) ?>_content_checkout_codfailed_yes">
                                                <input type="radio" class="rb-checkout-yes" id="<?php echo esc_attr($courier->CourierID) ?>_content_checkout_codfailed_yes" name="<?php echo esc_attr($courier->CourierID) ?>[CODFailed][ContentCheckout][IsShow]" value="1" <?php echo $checkedAttrYes; ?> />
                                                <?php esc_html_e('Có', 'ship-depot-translate') ?>
                                            </label>
                                        </div>
                                        <div class="col-xl-4 col-lg-8 col-md-8 col-sm-10 grid-cell">
                                            <input type="text" class="cod-failed-content cf-content-checkout <?php echo $st_courier->CODFailed->ContentCheckout->IsShow ? "" : " disable-element" ?>" name="<?php echo esc_attr($courier->CourierID) ?>[CODFailed][ContentCheckout][Content]" value="<?php echo esc_attr($st_courier->CODFailed->ContentCheckout->Content) ?>" />
                                        </div>
                                        <div class="col-xl-auto col-lg-auto col-md-auto col-sm-auto grid-cell">
                                            <label for="<?php echo esc_attr($courier->CourierID) ?>_content_checkout_codfailed_no">
                                                <input type="radio" class="rb-checkout-no" id="<?php echo esc_attr($courier->CourierID) ?>_content_checkout_codfailed_no" name="<?php echo esc_attr($courier->CourierID) ?>[CODFailed][ContentCheckout][IsShow]" value="0" <?php echo $checkedAttrNo; ?> />
                                                <?php esc_html_e('Không', 'ship-depot-translate') ?>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="row div-label-content">
                                        <div class="col-xl-auto col-lg-12 col-md-12 grid-cell">
                                            <p class="cod-failed-title">
                                                <?php esc_html_e('Hiển thị câu thông báo trên Phiếu giao hàng:') ?>
                                            </p>
                                        </div>
                                        <?php
                                        if (isset($st_courier)) {
                                            $checkedAttrYes = checked($st_courier->CODFailed->ContentShippingLabel->IsShow, true, false);
                                            $checkedAttrNo = checked($st_courier->CODFailed->ContentShippingLabel->IsShow, false, false);
                                        } else {
                                            $checkedAttrYes = checked(true, false, false);
                                            $checkedAttrNo = checked(true, true, false);
                                        }
                                        ?>
                                        <div class="col-xl-auto col-lg-auto col-md-auto col-sm-auto grid-cell">
                                            <label for="<?php echo esc_attr($courier->CourierID) ?>_content_shiplabel_codfailed_yes">
                                                <input type="radio" class="rb-label-yes" id="<?php echo esc_attr($courier->CourierID) ?>_content_shiplabel_codfailed_yes" name="<?php echo esc_attr($courier->CourierID) ?>[CODFailed][ContentShippingLabel][IsShow]" value="1" <?php echo $checkedAttrYes; ?> />
                                                <?php esc_html_e('Có', 'ship-depot-translate') ?>
                                            </label>
                                        </div>
                                        <div class="col-xl-4 col-lg-8 col-md-8 col-sm-10 grid-cell">
                                            <input type="text" class="cod-failed-content cf-content-label <?php echo $st_courier->CODFailed->ContentShippingLabel->IsShow ? "" : " disable-element" ?>" name="<?php echo esc_attr($courier->CourierID) ?>[CODFailed][ContentShippingLabel][Content]" value="<?php echo esc_attr($st_courier->CODFailed->ContentShippingLabel->Content) ?>" />
                                        </div>
                                        <div class="col-xl-auto col-lg-auto col-md-auto col-sm-auto grid-cell">
                                            <label for="<?php echo esc_attr($courier->CourierID) ?>_content_shiplabel_codfailed_no">
                                                <input type="radio" class="rb-label-no" id="<?php echo esc_attr($courier->CourierID) ?>_content_shiplabel_codfailed_no" name="<?php echo esc_attr($courier->CourierID) ?>[CODFailed][ContentShippingLabel][IsShow]" value="0" <?php echo $checkedAttrNo; ?> />
                                                <?php esc_html_e('Không', 'ship-depot-translate') ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Ship from station -->
                    <div class="row">
                        <div class="div-pick-station<?php echo $st_courier->IsUsed ? '' : ' disable-element' ?>">
                            <input type="hidden" class="courier_data" name="<?php echo esc_attr($courier->CourierID) ?>[Data]" value="<?php echo esc_attr(Ship_Depot_Helper::ParseObjectToJsonHTML($courier_only)) ?>" />
                            <p><?php esc_html_e('Hình thức gửi hàng', 'ship-depot-translate') ?></p>
                            <?php
                            $checkedAttrYes = '';
                            $checkedAttrNo = '';
                            $list_province = Ship_Depot_Address_Helper::get_all_province();
                            $list_district = [];
                            $selected_district = null;
                            $selected_province = null;
                            if ($st_courier->ShipFromStation) {
                                if ($st_courier->ShipFromStation->IsActive != null) {
                                    $checkedAttrYes = checked($st_courier->ShipFromStation->IsActive, true, false);
                                    $checkedAttrNo = checked($st_courier->ShipFromStation->IsActive, false, false);
                                } else {
                                    $checkedAttrYes = checked(false, true, false);
                                    $checkedAttrNo = checked(true, true, false);
                                }
                                Ship_Depot_Logger::wrlog('[sd-couriers] checkedAttrYes: ' . $checkedAttrYes);
                                Ship_Depot_Logger::wrlog('[sd-couriers] checkedAttrNo: ' . $checkedAttrNo);
                                if ($st_courier->ShipFromStation->District->DistrictISN > 0) {
                                    $selected_district = $st_courier->ShipFromStation->District;
                                } else {
                                    if (isset($sender_info)) {
                                        $distr = new stdClass();
                                        $distr->CityISN = $sender_info->CityISN;
                                        $distr->DistrictISN = $sender_info->DistrictISN;
                                        $selected_district = new Ship_Depot_District($distr);
                                    }
                                }

                                if ($st_courier->ShipFromStation->Province->CityISN > 0) {
                                    $selected_province = new Ship_Depot_Province(Ship_Depot_Address_Helper::get_province_by_isn($st_courier->ShipFromStation->Province->CityISN));
                                    $list_district = Ship_Depot_Address_Helper::get_all_district_by_province_isn($st_courier->ShipFromStation->Province->CityISN);
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
                            <div class="container-fluid col-padding-0 pick-station-options">
                                <div class="row">
                                    <div class="col-xl-2 col-lg-4 col-md-8 col-sm-10 pick-station-no">
                                        <label for="ship_from_station_no">
                                            <input type="radio" id="ship_from_station_no" name="<?php echo esc_attr($courier->CourierID) ?>[ShipFromStation][IsActive]" value="0" <?php echo $checkedAttrNo ?> />
                                            <?php esc_html_e('Shipper lấy hàng tận nơi', 'ship-depot-translate') ?>
                                        </label>
                                    </div>
                                    <div class="col-xl-4 col-lg-8 col-md-8 col-sm-10 pick-station-yes">
                                        <input type="hidden" class="selected_district" value="<?php echo esc_attr(Ship_Depot_Helper::ParseObjectToJsonHTML($selected_district)) ?>" />
                                        <label for="ship_from_station_yes">
                                            <input type="radio" id="ship_from_station_yes" name="<?php echo esc_attr($courier->CourierID) ?>[ShipFromStation][IsActive]" value="1" <?php echo $checkedAttrYes ?> />
                                            <?php esc_html_e('Gửi hàng tại điểm giao nhận của GHN', 'ship-depot-translate') ?>
                                        </label>
                                        <div class="pick-station-yes-option" style="<?php echo $st_courier->ShipFromStation->IsActive ? esc_attr('display: block') : 'display: none' ?>">
                                            <div class="pick-station-yes-option-row">
                                                <span class="pick-station-yes-title"><?php esc_html_e('Tỉnh/Thành', 'ship-depot-translate') ?></span>
                                                <select class="sl_province" name="<?php echo esc_attr($courier->CourierID) ?>[ShipFromStation][ProvinceCode]">
                                                    <?php
                                                    foreach ($list_province as $province_obj) {
                                                        $province = new Ship_Depot_Province($province_obj);
                                                        $selectedProvinceAttr = '';
                                                        if ($st_courier->ShipFromStation->Province->CityISN <= 0) {
                                                            if (isset($sender_info)) {
                                                                $selectedProvinceAttr = selected($sender_info->CityISN, $province->CityISN);
                                                            }
                                                        } else {
                                                            $selectedProvinceAttr = selected($st_courier->ShipFromStation->Province->CityISN, $province->CityISN);
                                                        }
                                                        echo '<option data-id="' . esc_attr($province->CityISN) . '" value="' . esc_attr($province->Code) . '" ' . esc_html($selectedProvinceAttr) . '>' . esc_html($province->Name) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="pick-station-yes-option-row">
                                                <span class="pick-station-yes-title"><?php esc_html_e('Quận/Huyện', 'ship-depot-translate') ?></span>
                                                <select class="sl_district" name="<?php echo esc_attr($courier->CourierID) ?>[ShipFromStation][DistrictCode]">
                                                    <option value="-1"><?php esc_html_e(SD_SELECT_DISTRICT_TEXT, 'ship-depot-translate') ?></option>
                                                    <?php
                                                    foreach ($list_district as $district_obj) {
                                                        $district = new Ship_Depot_District($district_obj);
                                                        $selectedDisctrictAttr = '';
                                                        if (Ship_Depot_Helper::check_null_or_empty($st_courier->ShipFromStation->District->Code)) {
                                                            if (isset($sender_info)) {
                                                                $selectedDisctrictAttr = selected($sender_info->DistrictISN, $district->DistrictISN);
                                                            }
                                                        } else {
                                                            $selectedDisctrictAttr = selected($st_courier->ShipFromStation->District->Code, $district->Code);
                                                        }
                                                        echo '<option value="' . esc_attr($district->Code) . '" ' . esc_html($selectedDisctrictAttr) . '>' . esc_html($district->Name) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="pick-station-yes-option-row">
                                                <?php $selected_station = null ?>
                                                <span class="pick-station-yes-title"><?php esc_html_e('Bưu cục', 'ship-depot-translate') ?></span>
                                                <select class="sl_station" name="<?php echo esc_attr($courier->CourierID) ?>[ShipFromStation][StationID]">
                                                    <option value="-1"><?php esc_html_e('Chọn bưu cục', 'ship-depot-translate') ?></option>
                                                    <?php
                                                    // if ($st_courier->ShipFromStation->IsActive) {
                                                    $list_stations = Ship_Depot_Order_Shipping::get_shipping_stations($selected_province, $selected_district, 0, $st_courier->CourierISN);
                                                    if ($list_stations) {
                                                        foreach ($list_stations as $station) {
                                                            $selectedStationAttr = selected($st_courier->ShipFromStation->Station->Id, $station->Id, false);
                                                            if ($st_courier->ShipFromStation->Station->Id > 0 && $station->Id == $st_courier->ShipFromStation->Station->Id) {
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
                                                <input type="hidden" class="selected_station_data" name="<?php echo esc_attr($courier->CourierID) ?>[ShipFromStation][SelectedStation]" value="<?php echo $selected_station == null ? '' : esc_attr(Ship_Depot_Helper::ParseObjectToJsonHTML($selected_station)) ?>" />
                                            </div>
                                            <p class="error_content"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                } else {
                    if ($courier->CourierID == PAS_COURIER_CODE) {
                        if (Ship_Depot_Helper::check_null_or_empty($st_courier->PASAddress) || Ship_Depot_Helper::check_null_or_empty($st_courier->PASPhone)) {
                            if (!isset($sender_info)) {
                                $str_sender_info = get_option('sd_sender_info');
                                if (!is_null($str_sender_info) && !empty($str_sender_info)) {
                                    $sender_info_obj = json_decode($str_sender_info);
                                    $sender_info = new Ship_Depot_Shop_Info($sender_info_obj);
                                }
                            }

                            if (Ship_Depot_Helper::check_null_or_empty($courier->PASAddress)) {
                                $st_courier->PASAddress = esc_html($sender_info->Address . ', ' . $sender_info->Ward . ', ' . $sender_info->District . ', ' . $sender_info->City);
                            }

                            if (Ship_Depot_Helper::check_null_or_empty($courier->PASPhone)) {
                                $st_courier->PASPhone = $sender_info->Phone;
                            }
                        }
                    ?>
                        <div class="div-extra-info <?php echo isset($st_courier) && $st_courier->IsUsed ? '' : ' disable-element' ?>">
                            <div class="row" style="margin-bottom: 15px;">
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                                    <p><?php esc_html_e('Địa chỉ lấy hàng', 'ship-depot-translate') ?></p>
                                    <input class="pas-info" type="text" name="<?php echo esc_attr($courier->CourierID) ?>[PASAddress]" value="<?php echo esc_attr($st_courier->PASAddress) ?>" />
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                                    <p><?php esc_html_e('Điện thoại liên hệ', 'ship-depot-translate') ?></p>
                                    <input type="number" class="pas-info" name="<?php echo esc_attr($courier->CourierID) ?>[PASPhone]" value="<?php echo esc_attr($st_courier->PASPhone) ?>" />
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                    <input type="hidden" class="courier_data" name="<?php echo esc_attr($courier->CourierID) ?>[Data]" value="<?php echo esc_attr(Ship_Depot_Helper::ParseObjectToJsonHTML($courier_only)) ?>" />
                <?php
                }
                ?>
            </div>
        </div>
    <?php
    }
    ?>
</div>