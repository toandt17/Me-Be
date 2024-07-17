<?php
if (!defined('WPINC')) {
    die;
}

$list_update_order_statuses = [];
$str_update_order_statuses = get_option('sd_update_order_statuses');
if (!Ship_Depot_Helper::check_null_or_empty($str_update_order_statuses)) {
    $list_update_order_statuses = json_decode($str_update_order_statuses);
} else {
    $list_update_order_statuses = Ship_Depot_Shipping_Status::GetListStatusDefault();
}

Ship_Depot_Default_Data::DefaultAutoCreateShip();
?>
<div class="sd-div sd-setting" id="sd-general-settings">
    <?php
    wp_nonce_field('sd_general', 'sd_general_nonce');
    ?>
    <div class="general-setting-row setting-api-key">
        <b>API Key</b>
        <p class="description"><?php esc_html_e('Dùng để liên kết tài khoản ShipDepot với vận đơn gửi từ đây.', 'ship-depot-translate') ?></p>
        <p class="description"><?php esc_html_e('API Key nhận được sau khi đăng ký tài khoản với ShipDepot,', 'ship-depot-translate') ?>
            <a href="<?php echo esc_url(SHIP_DEPOT_SITE) ?>/thiet-lap-tai-khoan-1" target="_blank">
                <?php
                echo " ";
                esc_html_e('xem hướng dẫn', 'ship-depot-translate');
                ?>
            </a>
        </p>
        <?php
        echo '<input name="sd_api_key" id="sd_api_key" type="text" value="' . esc_attr(get_option('sd_api_key')) . '">'
        ?>
    </div>

    <div class="general-setting-row setting-sender">
        <b><?php esc_html_e('Thông tin người gửi (chủ shop / chủ cửa hàng)', 'ship-depot-translate') ?></b>
        <p class="description">
            <?php esc_html_e('Thông tin này được in trên phiếu gửi hàng và được thiết lập bởi ShipDepot khi bạn đăng ký tài khoản, bạn có thể vào ShipDepot để thay đổi,', 'ship-depot-translate') ?>
            <a href="<?php echo esc_url(SHIP_DEPOT_SITE) ?>/thiet-lap-shop-portal" target="_blank">
                <?php
                echo " ";
                esc_html_e('xem hướng dẫn', 'ship-depot-translate');
                ?>
            </a>
        </p>
        <div id="sender-info">
            <?php
            $str_sender_info = get_option('sd_sender_info');
            if (!is_null($str_sender_info) && !empty($str_sender_info)) {
                $sender_info_obj = json_decode($str_sender_info);
                $sender_info = new Ship_Depot_Shop_Info($sender_info_obj);
                echo esc_html($sender_info->FirstName);
                echo '<br/>';
                echo esc_html($sender_info->Address . ', ' . $sender_info->Ward . ', ' . $sender_info->District . ', ' . $sender_info->City);
                echo '<br/>';
                echo esc_html(Ship_Depot_Helper::format_phone($sender_info->Phone));
            }
            ?>
        </div>
    </div>
    <div class="general-setting-row setting-storage">
        <b><?php esc_html_e('Kho hàng', 'ship-depot-translate') ?></b>
        <p class="description">
            <?php esc_html_e('Thay đổi hoặc bổ sung kho hàng cần thực hiện tại ShipDepot,', 'ship-depot-translate') ?>
            <a href="<?php echo esc_url(SHIP_DEPOT_SITE) ?>/thiet-lap-shop-portal" target="_blank">
                <?php
                echo " ";
                esc_html_e('xem hướng dẫn', 'ship-depot-translate');
                ?>
            </a>
        </p>
        <div class="container-fluid">
            <div class="row">
                <?php
                $strListStr = get_option('sd_list_storages');
                if (!is_null($strListStr) && !empty($strListStr)) {
                    $listStrBf = json_decode($strListStr);
                    $listStr = [];
                    foreach ($listStrBf as $str) {
                        if ($str->IsDefault) {
                            array_unshift($listStr, $str);
                        } else {
                            array_push($listStr, $str);
                        }
                    }
                    foreach ($listStr as $id => $str) {
                ?>
                        <div class="col-xl-5 col-lg-5 col-md-5 col-sm-12 col-12 storage-panel">
                            <div>
                                <?php echo $str->IsDefault ? '<b>(Mặc định)</b> ' : '' ?>Mã kho: <?php echo esc_html($str->WarehouseID) ?><br />
                                Địa chỉ: <?php echo esc_html($str->WarehouseAddress . ', ' . $str->WarehouseWard . ', ' . $str->WarehouseDistrict . ', ' . $str->WarehouseCity); ?><br />
                                Người liên hệ: <?php echo esc_html($str->ContactName) ?><br />
                                Điện thoại: <?php echo esc_html(Ship_Depot_Helper::format_phone($str->ContactPhone)) ?><br />
                            </div>
                        </div>
                <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <div class="general-setting-row setting-shipping-option">
        <b><?php esc_html_e('Vận đơn', 'ship-depot-translate') ?></b>
        <p class="description"><?php esc_html_e('Vận đơn cần được tạo và gửi đến ShipDepot để đơn vị vận chuyển thực hiện giao hàng. Bạn có thể thiết lập tự động tạo vận đơn và gửi đến ShipDepot dựa theo các tuỳ chọn bên dưới.', 'ship-depot-translate') ?></p>
        <div>
            <p>
                <input name="cb_show_shipping_fee_detail" type="checkbox" value="1" <?php checked(get_option('sd_show_shipping_fee_detail'), 'yes'); ?> id="cb_show_shipping_fee_detail" />
                <?php esc_html_e('Hiển thị chi tiết phí vận chuyển như phí bảo hiểm, phí thu hộ ở trang checkout.', 'ship-depot-translate') ?>
            </p>
        </div>

        <div>
            <p>
                <input name="cb_auto_create_shipping" id="cb_auto_create_shipping" type="checkbox" value="1" <?php checked(get_option('sd_auto_create_shipping'), 'yes'); ?> id="cb_auto_create_shipping" />
                <?php esc_html_e('Tự động tạo và gửi vận đơn đến ShipDepot khi đơn hàng ở các trạng thái sau đây', 'ship-depot-translate') ?>
            </p>
        </div>

        <p class="description"><?php esc_html_e('Nếu không bật tính năng này, bạn sẽ phải tự thực hiện tạo vận đơn.', 'ship-depot-translate') ?></p>
        <div class="container-fluid">
            <div class="row<?php echo get_option('sd_auto_create_shipping') == 'yes' ? '' : ' disable-element' ?>" id="div_auto_shipping">
                <?php
                $listStatus = wc_get_order_statuses();
                $count = 0;
                $str_statuses = get_option('sd_status_auto_create_shipping');
                if (str_contains($str_statuses, ',')) {
                    $statuses = explode(',', $str_statuses);
                } else {
                    $statuses = array($str_statuses);
                }
                foreach ($listStatus as $id => $stt) {
                    if ($count % 3 == 0) {
                        echo '<div class="col-xl-6 offset-xl-6 col-lg-3 offset-lg-9 d-none d-lg-block col-padding-0"></div>';
                    }
                    $checkedAttr = checked(in_array($id, $statuses), true, false);
                    echo '<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-12 col-padding-0">';
                    echo '<label for="' . esc_attr($id) . '" class="label-status"><input name="sd_status_auto_create_shipping[' . esc_attr($id) . ']" id="' . esc_attr($id) . '" type="checkbox" value="' . esc_attr($id) . '" ' . $checkedAttr . '  />' . esc_html($stt) . '</label>';
                    echo '</div>';
                    $count++;
                }
                ?>
            </div>
        </div>
    </div>

    <div class="general-setting-row setting-advance">
        <a href="javascript:;" id="lb_advanced_settings"><?php esc_html_e('[Thiết lập nâng cao]', 'ship-depot-translate') ?></a>
        <div id="div_advanced_settings" class="collapse">
            <p class="description"><?php esc_html_e('Chú ý: Không thay đổi các thiết lập bên dưới nếu bạn chưa thật hiểu rõ về chúng.', 'ship-depot-translate') ?></p>
            <?php foreach ($list_update_order_statuses as $status_obj) {
                $status = new Ship_Depot_Shipping_Status($status_obj);
            ?>
                <div class="advance-settings-section">
                    <input type="hidden" name="shipdepot[status_id][]" value="<?php echo esc_attr($status->ID) ?>" />
                    <input type="hidden" name="shipdepot[<?php echo esc_attr($status->ID) ?>][data]" value="<?php echo esc_attr(Ship_Depot_Helper::ParseObjectToJsonHTML($status)); ?>" />

                    <label for="cb_set_status_<?php echo esc_attr($status->ID) ?>" class="advance-settings-title">
                        <input name="shipdepot[<?php echo esc_attr($status->ID) ?>][IsUsed]" id="cb_set_status_<?php echo esc_attr($status->ID) ?>" type="checkbox" value="1" <?php checked($status->IsUsed); ?> class="cb_woo_status" />
                        <?php echo esc_html($status->Name) ?>
                    </label>

                    <p class="description"><?php echo esc_html($status->Description) ?></p>
                    <select class="select_order_status" name="shipdepot[<?php echo esc_attr($status->ID) ?>][WooOrderStatusID]" id="sd_status_<?php echo esc_attr($status->ID) ?>" <?php echo $status->IsUsed ? '' : ' disabled' ?>>
                        <?php
                        $list_status = wc_get_order_statuses();
                        $selected_status = $status->WooOrderStatusID;
                        if (Ship_Depot_Helper::check_null_or_empty($selected_status)) {
                            switch ($status->ID) {
                                case SD_DELIVERING_STATUS;
                                    $selected_status = 'wc-sd-delivering';
                                    break;
                                case SD_DELI_SUCCESS_STATUS;
                                    $selected_status = 'wc-sd-delivered';
                                    break;
                                case SD_DELI_FAIL_STATUS;
                                    $selected_status = 'wc-sd-delivery-failed';
                                    break;
                                case SD_FOR_CONTROL_STATUS;
                                    $selected_status = 'wc-completed';
                                    break;
                            }
                        }
                        foreach ($list_status as $id => $stt) {
                            $selectedAttr = selected($selected_status, $id, false);
                            echo '<option value="' . esc_attr($id) . '" ' . $selectedAttr . '  />' . esc_html($stt) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            <?php
            } ?>
            <a class="button-a" id="btn_sync_settings">
                <?php esc_html_e('Đồng bộ thiết lập từ ShipDepot', 'ship-depot-translate') ?>
            </a>
            <p class="description"><?php esc_html_e('Các thiết lập trên tài khoản của bạn ở ShipDepot sẽ tự động đồng bộ đến đây, trường hợp ngoài ý muốn không tự động đồng bộ thì bạn có thể chủ động thực hiện bằng cách bấm [Đồng bộ thiết lập từ ShipDepot] ở trên.', 'ship-depot-translate') ?></p>
        </div>
    </div>

    <div class="general-setting-row setting-log-debug">
        <b><?php esc_html_e('Ghi debug log', 'ship-depot-translate') ?></b>
        <?php
        $can_debug_log = Ship_Depot_Helper::check_null_or_empty(get_option('sd_accept_debug_log')) ? 'yes' : get_option('sd_accept_debug_log');
        ?>

        <p class="description">
            <input name="cb_accept_debug_log" id="cb_accept_debug_log" type="checkbox" value="1" <?php checked($can_debug_log, 'yes'); ?> />
            <?php esc_html_e('Debug log cung cấp thêm thông tin chi tiết quá trình xử lý vận đơn từ đây với ShipDepot, những thông tin này sẽ giúp bạn hiểu rõ hơn các vấn đề liên quan tới việc hoàn thành vận đơn. Tuy nhiên thông tin chi tiết có thể làm lộ thông tin cá nhân của bạn và khách hàng, bạn nên cân nhắc khi bật tính năng này.', 'ship-depot-translate') ?>
        </p>
    </div>
</div>