<?php
if (!defined('WPINC')) {
    die;
}

$listConditions = [
    '' => 'Số lượng mặt hàng trong đơn',
    '' => 'Có sản phẩm trong đơn hàng',
    '' => 'Giá trị đơn hàng',
    '' => 'Thanh toán: Chuyển khoản',
    '' => 'Thanh toán: COD',
];

$listFeeOptions = [];
if (!Ship_Depot_Helper::check_null_or_empty(get_option('sd_setting_fee'))) {
    Ship_Depot_Logger::wrlog('sd_setting_fee data: ' . get_option('sd_setting_fee'));
    $fee_setting_obj = json_decode(get_option('sd_setting_fee'));
    $fee_setting = new Ship_Depot_Fee_Setting($fee_setting_obj);
    if (isset($fee_setting->ListFeeMarkups)) {
        $listFeeOptions = $fee_setting->ListFeeMarkups;
    }
} else {
    $fee_setting = new Ship_Depot_Fee_Setting();
}


Ship_Depot_Logger::wrlog('listFeeOptions: ' . print_r($listFeeOptions, true));
$qty_fee = count($listFeeOptions);

if (!function_exists('wp_date_format_php_to_js')) {
    /**
     * Convert a date format to a jQuery UI DatePicker format
     *
     * @param string $dateFormat a date format
     *
     * @return string
     */
    function wp_date_format_php_to_js($dateFormat)
    {
        $chars = array(
            // Day
            'd' => 'dd',
            'j' => 'd',
            'l' => 'DD',
            'D' => 'D',
            // Month
            'm' => 'mm',
            'n' => 'm',
            'F' => 'MM',
            'M' => 'M',
            // Year
            'Y' => 'yy',
            'y' => 'y',
        );
        return strtr((string) $dateFormat, $chars);
    }
}

function wp_date_format_php_to_vn($dateFormat)
{
    $chars = array(
        // Day
        'd' => 'Ngày',
        'j' => 'Ngày',
        'l' => 'Ngày',
        'D' => 'Ngày',
        // Month
        'm' => 'Tháng',
        'n' => 'Tháng',
        'F' => 'Tháng',
        'M' => 'Tháng',
        // Year
        'Y' => 'Năm',
        'y' => 'Năm',
    );
    return strtr((string) $dateFormat, $chars);
}

$jquery_date_format = wp_date_format_php_to_js(get_option('date_format'));
$date_picker_placeholder = wp_date_format_php_to_vn(get_option('date_format'));

?>
<script>
    jQuery(document).ready(function($) {
        if ($('#btn_add_fee').length > 0) {
            $("#btn_add_fee").click(function() {
                let divContainerNewIdx = parseInt($('#total_fee_qty').val()) + 1;
                let idx = getTimeTicks();
                let feeIndex = 'fee_' + idx;
                let $div = $("<div>", {
                    "class": "div_container"
                });
                let inHtml = $div.html();
                inHtml += `
                <div class="fee-modify-container">
                    <span class="error_content"></span>
                    <input type="hidden" class="fee_index_id" name="fee_modify[fees][id][]" value="${feeIndex}" />
                    <input type="hidden" class="fee_id" name="${feeIndex}[id]" value="" />
                    <input type="hidden" id="${feeIndex}_total_if_qty" class="total_if_qty" value="0" />
                    <div class="fee-modify-info">
                        <input type="checkbox" name="${feeIndex}[isActive]" class="active-fee" checked="checked" value="1" />
                        <div class="container-fluid">
                            <div class="row">

                                <div class="col-xl-1 col-lg-5 col-md-12 custom-col">
                                    <b class="header-inside">Độ ưu tiên</b>
                                    <div class="inc-dec-area">
                                        <span id="span_${feeIndex}_index" class="span_index">${divContainerNewIdx}</span>
                                        <div class="inc-dec-btn-area">
                                            <a href="javascript:;" class="sd-btn btn_inc_index">
                                                <?php esc_html_e('Tăng', 'ship-depot-translate') ?>
                                                <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_arrow_up.png') ?>" />
                                            </a>
                                            <a href="javascript:;" class="sd-btn btn_dec_index">
                                                <?php esc_html_e('Giảm', 'ship-depot-translate') ?>
                                                <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_arrow_down.png') ?>" />
                                            </a>
                                        </div>
                                    </div>    
                                </div>

                                <div class="col-xl-3 col-lg-7 col-md-12 custom-col">
                                    <b class="header-inside">Nếu như</b>
                                    <div class="condition_panel" id="container_${feeIndex}">
                                        
                                    </div>
                                    <div>
                                        <select id="sl_cond_for_${feeIndex}" class="sl_condition">
                                            <option value="total_amount"><?php esc_html_e('Giá trị đơn hàng', 'ship-depot-translate') ?></option>
                                            <option value="item_existed"><?php esc_html_e('Có sản phẩm trong đơn hàng', 'ship-depot-translate') ?></option>
                                            <option value="item_quantity"><?php esc_html_e('Số lượng mặt hàng trong đơn', 'ship-depot-translate') ?></option>
                                            <option value="payment_transfer"><?php esc_html_e('Thanh toán: Chuyển khoản', 'ship-depot-translate') ?></option>
                                            <option value="payment_cod"><?php esc_html_e('Thanh toán: COD', 'ship-depot-translate') ?></option>
                                        </select>
                                        <a href="javascript:;" class="btn_add_condition">
                                            <?php esc_html_e('[Thêm]', 'ship-depot-translate') ?>
                                        </a>
                                    </div>
                                </div>

                                <div class="col-xl-2 col-lg-5 col-md-12 custom-col div-fee-option">
                                    <b class="header-inside">Thì phí vận chuyển</b>
                                    <select name="${feeIndex}[feeOption][type]"  class="sl_fee_option_type">
                                        <option value="depend_shipdepot"><?php esc_html_e('Dựa trên ShipDepot', 'ship-depot-translate') ?></option>
                                        <option value="fixed_fee"><?php esc_html_e('Phí cố định (vnđ)', 'ship-depot-translate') ?></option>
                                    </select>
                                    <select name="${feeIndex}[feeOption][sub]" class="sl_fee_option_sub_type" style="display:block;">
                                        <option value="inc_percent"><?php esc_html_e('Tăng (%)', 'ship-depot-translate') ?></option>
                                        <option value="dec_percent"><?php esc_html_e('Giảm (%)', 'ship-depot-translate') ?></option>
                                        <option value="inc_amount"><?php esc_html_e('Tăng (vnđ)', 'ship-depot-translate') ?></option>
                                        <option value="dec_amount"><?php esc_html_e('Giảm (vnđ)', 'ship-depot-translate') ?></option>
                                    </select>
                                    <input name="${feeIndex}[feeOption][value]" pattern="^[0-9\.\/]+$" data-type="currency" type="text" class="fee_option_val" placeholder="<?php esc_attr_e('Ví dụ: 1,5', 'ship-depot-translate') ?>" value="" />
                                </div>

                                <div class="col-xl-2 col-lg-7 col-md-12 custom-col div-time-apply">
                                    <b class="header-inside">Thời gian áp dụng</b>
                                    <select name="${feeIndex}[timeApply][type]" class="sl_time_apply_fee">
                                        <option value="all_time"><?php esc_html_e('Tất cả', 'ship-depot-translate') ?></option>
                                        <option value="period"><?php esc_html_e('Khoảng thời gian', 'ship-depot-translate') ?></option>
                                        <input name="${feeIndex}[timeApply][from]" style="display:none;" type="text" class="from_time_val" placeholder="<?php esc_attr_e('Từ ngày (' . $date_picker_placeholder . ')', 'ship-depot-translate') ?>" value="" />
                                    <input name="${feeIndex}[timeApply][to]" style="display:none;" type="text" class="to_time_val" placeholder="<?php esc_attr_e('Đến ngày (' . $date_picker_placeholder . ')', 'ship-depot-translate') ?>" value="" />
                                    </select>
                                </div>

                                <div class="col-xl-4 col-lg-12 col-md-12 custom-col courier-col">
                                    <b class="header-inside">Đối với đơn vị vận chuyển</b>
                                    <select name="${feeIndex}[couriersApply][type]" class="sl_courier_apply">
                                        <option value="all"><?php esc_html_e('Tất cả', 'ship-depot-translate') ?></option>
                                        <option value="customize"><?php esc_html_e('Tự chọn', 'ship-depot-translate') ?></option>
                                    </select>
                                    <?php
                                    $listCouriers = json_decode(get_option('sd_list_couriers'));
                                    foreach ($listCouriers as $courier_obj) {
                                        $courier = new Ship_Depot_Courier($courier_obj);
                                        $listServices = $courier->ListServices;
                                    ?>
                                        <div class="courier-container" style="display:none;">
                                            <div class="courier-img">
                                                <label for="${feeIndex}_<?php echo esc_attr($courier->CourierID) ?>">
                                                    <input id="${feeIndex}_<?php echo esc_attr($courier->CourierID) ?>" name="${feeIndex}[couriersApply][courierSelected][<?php echo esc_attr($courier->CourierID) ?>]" class="cb_courier" type="checkbox" value="1"/>
                                                    <img <?php echo Ship_Depot_Helper::check_null_or_empty($courier->LogoURL) ? '' : 'src="' . esc_url($courier->LogoURL) . '"' ?> alt="<?php echo esc_attr($courier->CourierName) ?>" data-placement="bottom" title="<?php echo esc_attr($courier->CourierName) ?>">
                                                </label>
                                            </div>
                                            <div class="col container-fluid col-padding-0 container_service">
                                                <div class="row">
                                                    <?php
                                                    $count = 0;
                                                    foreach ($listServices as $service_obj) {
                                                        $service = new Ship_Depot_Courier_Service($service_obj);
                                                        if ($count > 0 && $count % 2 == 0) {
                                                            echo '<div class="w-100"></div>';
                                                        }
                                                        echo '<div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 checkbox_container disable-element">';
                                                    ?>
                                                            <label for="${feeIndex}_<?php echo esc_attr($courier->CourierID) ?>_<?php echo esc_attr($service->ServiceCode) ?>">
                                                                <input id="${feeIndex}_<?php echo esc_attr($courier->CourierID) ?>_<?php echo esc_attr($service->ServiceCode) ?>" name="${feeIndex}[couriersApply][<?php echo esc_attr($courier->CourierID) ?>][serviceSelected][<?php echo esc_attr($service->ServiceCode) ?>]" class="cb_service" type="checkbox" value="1"/>
                                                                <?php echo esc_attr($service->ServiceName) ?>
                                                            </label>
                                                        </div>
                                                        <?php
                                                        $count++;
                                                    }
                                                        ?>
                                                </div>
                                            </div>
                                        </div>

                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-xl-8 col-lg-12 col-md-12 col-padding-0 custom-col col-notes">
                                    <p class="notes-title"><?php esc_html_e('Ghi chú', 'ship-depot-translate') ?></p>
                                    <textarea name="${feeIndex}[notes]" class="notes-content" ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="div-btn-function">
                        <a href="javascript:;" class="sd-btn btn_delete_fee">
                            <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_delete.png') ?>" />
                            <?php esc_html_e('Xóa', 'ship-depot-translate') ?>
                        </a>
                        <a href="javascript:;" class="sd-btn btn_copy_fee">
                            <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_copy.png') ?>" />
                            <?php esc_html_e('Copy tạo mới', 'ship-depot-translate') ?>
                        </a>
                    </div>
                </div>
                    `
                $div.html(inHtml);
                $div.appendTo("#all_fee_container");
                $('#total_fee_qty').val(parseInt($('#total_fee_qty').val()) + 1);
            });
        };

        if ($('#all_fee_container').length > 0) {
            $("#all_fee_container").on("change", ".sl_fee_option_type", function() {
                if (this.value == 'fixed_fee') {
                    $(this).parent().children(".sl_fee_option_sub_type").hide();
                    $(this).parent().children(".fee_option_val").attr('placeholder', '<?php esc_attr_e('Ví dụ: 15000', 'ship-depot-translate'); ?>');
                } else {
                    $(this).parent().children(".sl_fee_option_sub_type").show();
                    $(this).parent().children(".fee_option_val").attr('placeholder', '<?php esc_attr_e('Ví dụ: 1,5', 'ship-depot-translate'); ?>');
                }
            });

            $("#all_fee_container").on("change", ".sl_fee_option_sub_type", function() {
                if (this.value == 'inc_percent' || this.value == 'dec_percent') {
                    $(this).parent().children(".fee_option_val").attr('placeholder', '<?php esc_attr_e('Ví dụ: 1,5', 'ship-depot-translate'); ?>');
                } else {
                    $(this).parent().children(".fee_option_val").attr('placeholder', '<?php esc_attr_e('Ví dụ: 25000', 'ship-depot-translate'); ?>');
                }
            });

            $("#all_fee_container").on("change", ".sl_time_apply_fee", function() {
                if (this.value == 'period') {
                    $(this).parent().children(".from_time_val").show();
                    $(this).parent().children(".to_time_val").show();
                    assignDatePicker($(this).parent().children(".from_time_val"));
                    assignDatePicker($(this).parent().children(".to_time_val"));
                } else {
                    $(this).parent().children(".from_time_val").hide();
                    $(this).parent().children(".to_time_val").hide();
                }
            });

            $("#all_fee_container").on("change", ".sl_courier_apply", function() {
                if (this.value == 'customize') {
                    $(this).parent().children(".courier-container").show();
                } else {
                    $(this).parent().children(".courier-container").hide();
                }
            });

            $("#all_fee_container").on("change", ".cb_courier", function() {
                let courierContainer = $(this).parents('.courier-container');
                let listDivContainer = $(courierContainer).find(".checkbox_container");
                let listService = $(courierContainer).find(".cb_service");
                if (this.checked) {
                    //I am checked
                    //listService.prop('disabled', '');
                    listDivContainer.removeClass("disable-element");
                } else {
                    //I'm not checked
                    listService.prop('checked', false);
                    //listService.prop('disabled', 'disabled');
                    listDivContainer.addClass("disable-element");
                }
            });

            function assignDatePicker(element) {
                if (element.length > 0 && !element.hasClass("hasDatepicker")) {
                    let value = $('#current_date_format').val();
                    element.datepicker({
                        dateFormat: value
                    });
                }
            };

            $(".from_time_val").each(function() {
                assignDatePicker($(this));
            });

            $(".to_time_val").each(function() {
                assignDatePicker($(this));
            });

            $("#all_fee_container").on("click", ".btn_remove_condition", function() {
                let divContent = $(this).parents('.fee-modify-container');
                let hdIfQty = divContent.children('.total_if_qty');
                let divCondition = $(this).parents('.condition_table');
                divCondition.remove();
                hdIfQty.val(parseInt(hdIfQty.val()) - 1);
            });

            $("#all_fee_container").on("click", ".btn_add_condition", function() {
                let divConditionPanel = $(this).parent().parent().children('.condition_panel');
                let divContent = $(this).parents('.fee-modify-container');
                let hdIfQty = divContent.children('.total_if_qty');
                let select = divContent.find('.sl_condition');
                let selectedValue = select.val();
                let selectedText = select.children(':selected').text();
                let feeID = divContent.children('.fee_index_id').val();
                let ticks = getTimeTicks();
                let if_name = feeID + '[if' + ticks + ']';
                let if_id = feeID + '_if' + ticks;
                let $div = $("<div>", {
                    "class": "condition_table",
                    id: "condition_table_" + ticks

                });

                if (selectedValue == 'total_amount') {
                    let inHTML = $div.html() + selectedText + `
                    <div>
                        <input type="hidden" name="${feeID}[if][]" value="if${ticks}" />
                        <input type="hidden" name="${if_name}[type]" value="total_amount" />
                        <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_close_16px.png') ?>" class="sd-btn btn_remove_condition"/>
                        <div class="container-fluid">
                                <div class="row">
                                    <div class="col-6">
                                        <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="${if_name}[from]" id="${if_id}_from" placeholder="<?php esc_attr_e('Từ (vnđ)', 'ship-depot-translate'); ?>"  />
                                    </div>
                                    <div class="col-6">
                                        <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="${if_name}[to]" id="${if_id}_to" placeholder="<?php esc_attr_e('Đến (vnđ)', 'ship-depot-translate'); ?>"  />
                                    </div>
                                </div>
                        </div>
                    </div>
                    <?php esc_html_e('và', 'ship-depot-translate') ?>`;
                    $div.html(inHTML);
                } else if (selectedValue == 'item_existed') {
                    let inHTML = $div.html() + selectedText + `
                    <div>
                        <input type="hidden" name="${feeID}[if][]" value="if${ticks}" />
                        <input type="hidden" name="${if_name}[type]" value="item_existed" />
                        <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_close_16px.png') ?>" class="sd-btn btn_remove_condition"/>
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-12">
                                    <input type="text" name="${if_name}[value]" placeholder="<?php esc_attr_e('Mã SP cách bởi dấu phẩy', 'ship-depot-translate'); ?>" />
                                </div>                               
                            </div>                              
                        </div>                              
                    </div>                               
                    <?php esc_html_e('và', 'ship-depot-translate') ?>`;
                    $div.html(inHTML);
                } else if (selectedValue == 'item_quantity') {
                    let inHTML = $div.html() + selectedText + `
                    <div>
                        <input type="hidden" name="${feeID}[if][]" value="if${ticks}" />
                        <input type="hidden" name="${if_name}[type]" value="item_quantity" />
                        <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_close_16px.png') ?>" class="sd-btn btn_remove_condition"/>
                        <div class="container-fluid">
                                <div class="row">
                                    <div class="col-6">
                                        <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="${if_name}[from]" id="${if_id}_from" placeholder="<?php esc_attr_e('Từ (SL)', 'ship-depot-translate'); ?>" />
                                    </div>
                                    <div class="col-6">
                                        <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="${if_name}[to]" id="${if_id}_to" placeholder="<?php esc_attr_e('Đến (SL)', 'ship-depot-translate'); ?>" />
                                    </div>
                                </div>
                        </div>
                    </div>
                    <?php esc_html_e('và', 'ship-depot-translate') ?>`;
                    $div.html(inHTML);
                } else if (selectedValue == 'payment_transfer') {
                    let inHTML = $div.html() + `
                    <div>
                        <input type="hidden" name="${feeID}[if][]" value="if${ticks}" />
                        <input type="hidden" name="${if_name}[type]" value="payment_transfer" />
                        <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_close_16px.png') ?>" class="sd-btn btn_remove_condition"/>
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-12">
                                <p>${selectedText}</p>
                                </div>                               
                            </div>                              
                        </div>                              
                    </div>                               
                    <?php esc_html_e('và', 'ship-depot-translate') ?>`;
                    $div.html(inHTML);
                } else if (selectedValue == 'payment_cod') {
                    let inHTML = $div.html() + `
                    <div>
                        <input type="hidden" name="${feeID}[if][]" value="if${ticks}" />
                        <input type="hidden" name="${if_name}[type]" value="payment_cod" />
                        <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_close_16px.png') ?>" class="sd-btn btn_remove_condition"/>
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-12">
                                <p>${selectedText}</p>
                                </div>                               
                            </div>                              
                        </div>                              
                    </div>                               
                    <?php esc_html_e('và', 'ship-depot-translate') ?>`;
                    $div.html(inHTML);
                }

                $(divConditionPanel).append($div);
            });

            $("#all_fee_container").on("click", ".btn_inc_index", function() {
                let divContent = $(this).parents('.fee-modify-container');
                let divContainer = divContent.parent();
                let divContainerIdx = $('#all_fee_container').find('.div_container').index(divContainer);

                if (divContainerIdx > 0) {
                    let previousDivContainerIdx = divContainerIdx - 1;
                    let previousDivContainer = $($('#all_fee_container').find('.div_container')[previousDivContainerIdx]);
                    let previousDivContent = previousDivContainer.children().first();
                    divContainer.append(previousDivContent);
                    previousDivContainer.append(divContent);

                    divContent.find('.span_index').text(previousDivContainerIdx + 1);
                    previousDivContent.find('.span_index').text(divContainerIdx + 1);
                }
            });

            $("#all_fee_container").on("click", ".btn_dec_index", function() {
                let divContent = $(this).parents('.fee-modify-container');
                let divContainer = divContent.parent();
                let divContainerIdx = $('#all_fee_container').find('.div_container').index(divContainer);

                if (divContainerIdx < parseInt($('#total_fee_qty').val()) - 1) {
                    let nextDivContainerIdx = divContainerIdx + 1;
                    let nextDivContainer = $($('#all_fee_container').find('.div_container')[nextDivContainerIdx]);
                    let nextDivContent = nextDivContainer.children().first();
                    divContainer.append(nextDivContent);
                    nextDivContainer.append(divContent);

                    divContent.find('.span_index').text(nextDivContainerIdx + 1);
                    nextDivContent.find('.span_index').text(divContainerIdx + 1);
                }
            });

            $("#all_fee_container").on("click", ".btn_delete_fee", function() {
                let divContent = $(this).parents('.fee-modify-container');
                let divContainer = divContent.parent();
                let divSpanIndex = divContainer.find(".span_index");
                let divContainerIdx = parseInt(divSpanIndex.text());
                $('#all_fee_container').find('.div_container').each(function() {
                    let spanIndex = $(this).find(".span_index");
                    let index = parseInt(spanIndex.text());
                    if (index > divContainerIdx) {
                        index = index - 1;
                        spanIndex.text(index);
                    }
                });
                divContainer.remove();
                $('#total_fee_qty').val(parseInt($('#total_fee_qty').val()) - 1)
            });

            $("#all_fee_container").on("click", ".btn_copy_fee", function() {
                let divContent = $(this).parents('.fee-modify-container');
                let divContainer = divContent.parent();
                let divContainerID_Idx = divContent.children('.fee_index_id').val();
                let divClone = divContainer.clone();
                let divContainerCloneIdx = parseInt($('#total_fee_qty').val()) + 1;
                let divContainerCloneID_Idx = getTimeTicks(); //parseInt($('#total_fee_qty').val()) + 1;
                divClone.find('.span_index').text(divContainerCloneIdx);
                divClone.find('.fee_id').val('');
                divClone.find('*').each(function() {
                    let fromString = divContainerID_Idx;
                    let toString = "fee_" + divContainerCloneID_Idx;
                    let value = $(this).val();
                    let id = $(this).attr('id');
                    let name = $(this).attr('name');
                    let cl = $(this).attr('class');
                    let classList = [];
                    if (value != null) {
                        $(this).val(value.replace(fromString, toString));
                    }

                    if (cl != null) {
                        classList = cl.split(/\s+/);
                        $.each(classList, function(index, className) {
                            if (className.indexOf(fromString) != -1) {
                                let newClassName = className.replace(fromString, toString)
                                let ele = $('.' + className);
                                ele.removeClass(className).addClass(newClassName);
                            }
                        });
                    }

                    if (id != null) {
                        $(this).attr('id', id.replace(fromString, toString));
                    }

                    if (name != null) {
                        $(this).attr('name', name.replace(fromString, toString));
                    }

                    if ($(this).is('select')) {
                        $(this).val(divContainer.find('.' + classList[0]).val());
                    }
                });
                divClone.appendTo("#all_fee_container");
                $('#total_fee_qty').val(parseInt($('#total_fee_qty').val()) + 1);
            });
        }
    });
</script>

<div class="sd-div sd-setting" id="sd-fee-modify">
    <?php
    wp_nonce_field('sd_fee_modify', 'sd_fee_modify_nonce');
    ?>
    <input type="hidden" id="current_date_format" value="<?php echo esc_attr($jquery_date_format); ?>" />
    <p class="description">
        <?php esc_html_e('Tính năng này cho phép bạn định nghĩa lại phí vận chuyển hiển thị khi tạo đơn hàng dựa trên một số tiền đề và điều kiện đã biết trước. Các kịch bản dưới đây sẽ giúp bạn hiểu rõ về tính năng đặc biệt này.', 'ship-depot-translate') ?>
    </p>
    <p class="description description-content"><?php esc_html_e('Kịch bản 1:', 'ship-depot-translate') ?></p>
    <p class="description">
        <?php esc_html_e('Nếu phí giao hàng cho đơn hàng A từ bạn đến khách hàng là 10.000đ (phí từ shipdepot) và bạn có kế hoạch giảm phí giao hàng còn 0đ cho tất cả đơn hàng có tổng giá trị dưới 100.000đ thì bạn sẽ dùng tính năng này để thiết lập lại biểu phí giao hàng.', 'ship-depot-translate') ?>
    </p>
    <p class="description description-content"><?php esc_html_e('Kịch bản 2:', 'ship-depot-translate') ?></p>
    <p class="description">
        <?php esc_html_e('Bạn bán sản phẩm có giá trị cao hơn 1.000.000đ và muốn tăng phí giao hàng với mục đích nào đó, bạn vẫn có thể dùng tính năng này để thay đổi.', 'ship-depot-translate') ?>
    </p>
    <p class="description description-content"><?php esc_html_e('Kịch bản 3:', 'ship-depot-translate') ?></p>
    <p class="description">
        <?php esc_html_e('Trường hợp bạn muốn phí giao hàng thay đổi dựa trên combo sản phẩm hoặc số lượng sản phẩm thì cũng dùng tính năng này.', 'ship-depot-translate') ?>
    </p>
    <p class="description">
        <?php esc_html_e('Tuy nhiên, cho dù là thay đổi tăng hay giảm biểu phí thì bạn vẫn phải thanh toán số tiền phí cho đơn vị vận chuyển đúng với giá trị từ ShipDepot gửi về vì việc thay đổi biểu phí này là chiến lược của bạn mà thôi. ', 'ship-depot-translate') ?>
    </p>
    <input type="checkbox" name="fee_modify[isActive]" value="1" <?php echo checked($fee_setting->IsActive, true, false) ?> />Bật tính năng
    <p class="description-black-text">
        <?php esc_html_e('Nếu không bật tính năng này thì phí vận chuyển luôn lấy từ ShipDepot, ngược lại nếu tìm thấy một biểu phí vận chuyển từ bảng bên dưới thì bạn vẫn phải trả phí vận chuyển tương ứng từ ShipDepot cho đơn vị vận chuyển khi đến lấy hàng.', 'ship-depot-translate') ?>
    </p>
    <a href="javascript:;" class="sd-btn" id="btn_add_fee"><?php esc_html_e('[Thêm biểu phí]', 'ship-depot-translate') ?></a>
    <div class="fee-modify-list">
        <div class="fee-modify-list__header">
            <input type="checkbox" value="1" />
            <div class="container-fluid col-padding-0">
                <div class="row">
                    <div class="col-xl-1 col-lg-6 col-md-12">
                        <b><?php esc_html_e('Độ ưu tiên', 'ship-depot-translate') ?></b>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-12">
                        <b><?php esc_html_e('Nếu như', 'ship-depot-translate') ?></b>
                    </div>
                    <div class="col-xl-2 col-lg-6 col-md-12">
                        <b><?php esc_html_e('Thì phí vận chuyển', 'ship-depot-translate') ?></b>
                    </div>
                    <div class="col-xl-2 col-lg-6 col-md-12">
                        <b><?php esc_html_e('Thời gian áp dụng', 'ship-depot-translate') ?></b>
                    </div>
                    <div class="col-xl-4 col-lg-12 col-md-1">
                        <b><?php esc_html_e('Đối với đơn vị vận chuyển', 'ship-depot-translate') ?></b>
                    </div>
                </div>
            </div>
        </div>

        <div id="all_fee_container">
            <input type="hidden" id="total_fee_qty" value="<?php echo esc_attr($qty_fee) ?>" />
            <input type="hidden" name="validate_error" id="validate_error" value="false" />
            <?php
            foreach ($listFeeOptions as $idx => $fee_obj) {
                $fee = new Ship_Depot_Fee_Markup($fee_obj);
                $checkedAttr = checked($fee->IsActive, true, false);
                $fee_index = 'fee_' . ($idx + 1);
            ?>
                <div class="div_container">
                    <div class="fee-modify-container">
                        <span class="error_content"></span>
                        <input type="hidden" class="fee_index_id" name="fee_modify[fees][id][]" value="<?php echo esc_attr($fee_index) ?>" />
                        <input type="hidden" class="fee_id" name="<?php echo esc_attr($fee_index) . '[id]' ?>" value="<?php echo esc_attr($fee->ID) ?>" />
                        <input type="hidden" id="<?php echo esc_attr($fee_index) . '_total_if_qty' ?>" class="total_if_qty" value="<?php echo esc_attr(count($fee->ListOrderConditions)) ?>" />
                        <div class="fee-modify-info">
                            <input type="checkbox" name="<?php echo esc_attr($fee_index) . '[isActive]' ?>" class="active-fee" <?php echo $checkedAttr ?> value="1" />
                            <div class="container-fluid">
                                <div class="row" class="all-conditions-group">
                                    <!-- Độ ưu tiên -->
                                    <div class="col-xl-1 col-lg-5 col-md-12 custom-col">
                                        <b class="header-inside"><?php esc_html_e('Độ ưu tiên', 'ship-depot-translate') ?></b>
                                        <div class="inc-dec-area">
                                            <span id="<?php echo 'span_' . esc_attr($fee_index) . '_index' ?>" class="span_index"><?php echo esc_html(($idx + 1)) ?></span>
                                            <div class="inc-dec-btn-area">
                                                <a href="javascript:;" class="sd-btn btn_inc_index">
                                                    <?php esc_html_e('Tăng', 'ship-depot-translate') ?>
                                                    <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_arrow_up.png') ?>" />
                                                </a>
                                                <a href="javascript:;" class="sd-btn btn_dec_index">
                                                    <?php esc_html_e('Giảm', 'ship-depot-translate') ?>
                                                    <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_arrow_down.png') ?>" />
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Nếu như -->
                                    <div class="col-xl-3 col-lg-7 col-md-12 custom-col">
                                        <b class="header-inside"><?php esc_html_e('Nếu như', 'ship-depot-translate') ?></b>
                                        <div class="condition_panel" id="container_<?php echo esc_attr($fee_index) ?>">
                                            <?php
                                            foreach ($fee->ListOrderConditions as $id => $order_condition_obj) {
                                                $order_condition = new Ship_Depot_Fee_Markup_Order_Condition($order_condition_obj);
                                                $if_name = $fee_index . '[if' . $id . ']';
                                                $if_id = $fee_index . '_if' . $id;
                                                switch ($order_condition->Type) {
                                                    case "total_amount":
                                            ?>
                                                        <div class="condition_table" id="condition_table_<?php echo esc_attr($id) ?>">
                                                            <?php esc_html_e('Giá trị đơn hàng', 'ship-depot-translate') ?>
                                                            <div>
                                                                <input type="hidden" name="<?php echo esc_attr($fee_index) . '[if][]' ?>" value="<?php echo 'if' . esc_attr($id) ?>" />
                                                                <input type="hidden" name="<?php echo esc_attr($if_name) . '[type]' ?>" value="total_amount" />
                                                                <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_close_16px.png') ?>" class="sd-btn btn_remove_condition" />
                                                                <div class="container-fluid">
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="<?php echo esc_attr($if_name) . '[from]' ?>" id="<?php echo esc_attr($if_id) . '_from' ?>" placeholder="<?php esc_attr_e('Từ (vnđ)', 'ship-depot-translate') ?>" value="<?php echo $order_condition->FromValue == -9999 ? "" : esc_attr(Ship_Depot_Helper::currency_format($order_condition->FromValue, "")) ?>" />
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="<?php echo esc_attr($if_name) . '[to]' ?>" id="<?php echo esc_attr($if_id) . '_to' ?>" placeholder="<?php esc_attr_e('Đến (vnđ)', 'ship-depot-translate') ?>" value="<?php echo $order_condition->ToValue == -9999 ? "" : esc_attr(Ship_Depot_Helper::currency_format($order_condition->ToValue, "")) ?>" />
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php esc_html_e('và', 'ship-depot-translate') ?>
                                                        </div>
                                                    <?php
                                                        break;
                                                    case "item_existed":
                                                    ?>
                                                        <div class="condition_table" id="condition_table_<?php echo esc_attr($id) ?>">
                                                            <?php esc_html_e('Có sản phẩm trong đơn hàng', 'ship-depot-translate') ?>
                                                            <div>
                                                                <input type="hidden" name="<?php echo esc_attr($fee_index) . '[if][]' ?>" value="<?php echo 'if' . esc_attr($id) ?>" />
                                                                <input type="hidden" name="<?php echo esc_attr($if_name) . '[type]' ?>" value="item_existed" />
                                                                <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_close_16px.png') ?>" class="sd-btn btn_remove_condition" />
                                                                <div class="container-fluid">
                                                                    <div class="row">
                                                                        <div class="col-12">
                                                                            <input type="text" name="<?php echo esc_attr($if_name) . '[value]' ?>" placeholder="Mã SP cách bởi dấu phẩy" value="<?php echo esc_attr($order_condition->FixedValue) ?>" />
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php esc_html_e('và', 'ship-depot-translate') ?>
                                                        </div>
                                                    <?php
                                                        break;
                                                    case "item_quantity":
                                                    ?>
                                                        <div class="condition_table" id="condition_table_<?php echo esc_attr($id) ?>">
                                                            <?php esc_html_e('Số lượng mặt hàng trong đơn', 'ship-depot-translate') ?>
                                                            <div>
                                                                <input type="hidden" name="<?php echo esc_attr($fee_index) . '[if][]' ?>" value="<?php echo 'if' . esc_attr($id) ?>" />
                                                                <input type="hidden" name="<?php echo esc_attr($if_name) . '[type]' ?>" value="item_quantity" />
                                                                <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_close_16px.png') ?>" class="sd-btn btn_remove_condition" />
                                                                <div class="container-fluid">
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="<?php echo esc_attr($if_name) . '[from]' ?>" id="<?php echo esc_attr($if_id) . '_from' ?>" placeholder="<?php esc_attr_e('Từ (SL)', 'ship-depot-translate') ?>" value="<?php echo $order_condition->FromValue == -9999 ? "" : esc_attr(Ship_Depot_Helper::currency_format($order_condition->FromValue, "")) ?>" />
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <input pattern="^[0-9\.\/]+$" data-type="currency" type="text" name="<?php echo esc_attr($if_name) . '[to]' ?>" id="<?php echo esc_attr($if_id) . '_to' ?>" placeholder="<?php esc_attr_e('Đến (SL)', 'ship-depot-translate') ?>" value="<?php echo $order_condition->ToValue == -9999 ? "" : esc_attr(Ship_Depot_Helper::currency_format($order_condition->ToValue, "")) ?>" />
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php esc_html_e('và', 'ship-depot-translate') ?>
                                                        </div>
                                                    <?php
                                                        break;
                                                    case "payment_transfer":
                                                    ?>
                                                        <div class="condition_table" id="condition_table_<?php echo esc_attr($id) ?>">
                                                            <div>
                                                                <input type="hidden" name="<?php echo esc_attr($fee_index) . '[if][]' ?>" value="<?php echo 'if' . esc_attr($id) ?>" />
                                                                <input type="hidden" name="<?php echo esc_attr($if_name) . '[type]' ?>" value="payment_transfer" />
                                                                <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_close_16px.png') ?>" class="sd-btn btn_remove_condition" />
                                                                <div class="container-fluid">
                                                                    <div class="row">
                                                                        <div class="col-12">
                                                                            <p><?php esc_html_e('Thanh toán: Chuyển khoản', 'ship-depot-translate') ?></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php esc_html_e('và', 'ship-depot-translate') ?>
                                                        </div>
                                                    <?php
                                                        break;
                                                    case "payment_cod":
                                                    ?>
                                                        <div class="condition_table" id="condition_table_<?php echo esc_attr($id) ?>">
                                                            <div>
                                                                <input type="hidden" name="<?php echo esc_attr($fee_index) . '[if][]' ?>" value="<?php echo 'if' . esc_attr($id) ?>" />
                                                                <input type="hidden" name="<?php echo esc_attr($if_name) . '[type]' ?>" value="payment_cod" />
                                                                <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_close_16px.png') ?>" class="sd-btn btn_remove_condition" />
                                                                <div class="container-fluid">
                                                                    <div class="row">
                                                                        <div class="col-12">
                                                                            <p><?php esc_html_e('Thanh toán: COD', 'ship-depot-translate') ?></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php esc_html_e('và', 'ship-depot-translate') ?>
                                                        </div>

                                            <?php
                                                        break;
                                                }
                                            }
                                            ?>
                                        </div>
                                        <div>
                                            <select id="sl_cond_for_<?php echo esc_attr($fee_index) ?>" class="sl_condition">
                                                <option value="total_amount"><?php esc_html_e('Giá trị đơn hàng', 'ship-depot-translate') ?></option>
                                                <option value="item_existed"><?php esc_html_e('Có sản phẩm trong đơn hàng', 'ship-depot-translate') ?></option>
                                                <option value="item_quantity"><?php esc_html_e('Số lượng mặt hàng trong đơn', 'ship-depot-translate') ?></option>
                                                <option value="payment_transfer"><?php esc_html_e('Thanh toán: Chuyển khoản', 'ship-depot-translate') ?></option>
                                                <option value="payment_cod"><?php esc_html_e('Thanh toán: COD', 'ship-depot-translate') ?></option>
                                            </select>
                                            <a href="javascript:;" class="sd-btn btn_add_condition">
                                                <?php esc_html_e('[Thêm]', 'ship-depot-translate') ?>
                                            </a>
                                        </div>
                                    </div>
                                    <!-- Thì phí -->
                                    <div class="col-xl-2 col-lg-5 col-md-12 custom-col div-fee-option">
                                        <b class="header-inside"><?php esc_html_e('Thì phí vận chuyển', 'ship-depot-translate') ?></b>
                                        <select name="<?php echo esc_attr($fee_index) . '[feeOption][type]' ?>" class="sl_fee_option_type">
                                            <option value="depend_shipdepot" <?php selected('depend_shipdepot', $fee->Option->Type) ?>><?php esc_html_e('Dựa trên ShipDepot', 'ship-depot-translate') ?></option>
                                            <option value="fixed_fee" <?php selected('fixed_fee', $fee->Option->Type) ?>><?php esc_html_e('Phí cố định (vnđ)', 'ship-depot-translate') ?></option>
                                        </select>
                                        <select name="<?php echo esc_attr($fee_index) . '[feeOption][sub]' ?>" class="sl_fee_option_sub_type" style="<?php echo $fee->Option->Type == 'depend_shipdepot' ? 'display:block;' : 'display:none;'  ?>">
                                            <option value="inc_percent" <?php selected('inc_percent', $fee->Option->SubType) ?>><?php esc_html_e('Tăng (%)', 'ship-depot-translate') ?></option>
                                            <option value="dec_percent" <?php selected('dec_percent', $fee->Option->SubType) ?>><?php esc_html_e('Giảm (%)', 'ship-depot-translate') ?></option>
                                            <option value="inc_amount" <?php selected('inc_amount', $fee->Option->SubType) ?>><?php esc_html_e('Tăng (vnđ)', 'ship-depot-translate') ?></option>
                                            <option value="dec_amount" <?php selected('dec_amount', $fee->Option->SubType) ?>><?php esc_html_e('Giảm (vnđ)', 'ship-depot-translate') ?></option>
                                        </select>
                                        <input name="<?php echo esc_attr($fee_index) . '[feeOption][value]' ?>" class="fee_option_val" placeholder="<?php esc_html_e('Ví dụ: 1,5', 'ship-depot-translate') ?>" pattern="^[0-9\.\/]+$" data-type="currency" type="text" value="<?php echo esc_attr(Ship_Depot_Helper::currency_format($fee->Option->Value, "")) ?>" />
                                    </div>
                                    <!-- Khoảng thời gian -->
                                    <div class="col-xl-2 col-lg-7 col-md-12 custom-col div-time-apply">
                                        <b class="header-inside"><?php esc_html_e('Thời gian áp dụng', 'ship-depot-translate') ?></b>
                                        <select name="<?php echo esc_attr($fee_index) . '[timeApply][type]' ?>" class="sl_time_apply_fee">
                                            <option value="all_time" <?php selected('all_time', $fee->TimeApply->Type) ?>><?php esc_html_e('Tất cả', 'ship-depot-translate') ?></option>
                                            <option value="period" <?php selected('period', $fee->TimeApply->Type) ?>><?php esc_html_e('Khoảng thời gian', 'ship-depot-translate') ?></option>
                                        </select>
                                        <?php
                                        $date_to_format = '';
                                        $date_from_format = '';
                                        if (isset($fee->TimeApply->FromDate) && $fee->TimeApply->FromDate != '') {
                                            Ship_Depot_Logger::wrlog('$fee->TimeApply->FromDate: ' . $fee->TimeApply->FromDate);
                                            $date_from_format = date(get_option('date_format'), $fee->TimeApply->FromDate);
                                        }

                                        if (isset($fee->TimeApply->ToDate) && $fee->TimeApply->ToDate != '') {
                                            $date_to_format = date(get_option('date_format'), $fee->TimeApply->ToDate); //$date_to->format(get_option('date_format'));
                                        }

                                        ?>
                                        <input name="<?php echo esc_attr($fee_index) . '[timeApply][from]' ?>" <?php echo $fee->TimeApply->Type == 'period' ? '' : 'style="display:none;"'  ?>;" type="text" class="from_time_val" placeholder="<?php esc_attr_e('Từ ngày (' . $date_picker_placeholder . ')', 'ship-depot-translate') ?>" value="<?php echo isset($date_from_format) ? esc_attr($date_from_format) : ''; ?>" />
                                        <input name="<?php echo esc_attr($fee_index) . '[timeApply][to]' ?>" <?php echo $fee->TimeApply->Type == 'period' ? '' : 'style="display:none;"' ?> type="text" class="to_time_val" placeholder="<?php esc_attr_e('Đến ngày (' . $date_picker_placeholder . ')', 'ship-depot-translate') ?>" value="<?php echo isset($date_to_format) ? esc_attr($date_to_format) : ''; ?>" />
                                    </div>
                                    <!-- Đơn vị vận chuyển -->
                                    <div class="col-xl-4 col-lg-12 col-md-12 custom-col courier-col">
                                        <b class="header-inside"><?php esc_html_e('Đối với đơn vị vận chuyển', 'ship-depot-translate') ?></b>
                                        <select name="<?php echo esc_attr($fee_index) . '[couriersApply][type]' ?>" class="sl_courier_apply">
                                            <option value="all" <?php selected('all', $fee->CourierApply->Type) ?>><?php esc_html_e('Tất cả', 'ship-depot-translate') ?></option>
                                            <option value="customize" <?php selected('customize', $fee->CourierApply->Type) ?>><?php esc_html_e('Tự chọn', 'ship-depot-translate') ?></option>
                                        </select>
                                        <?php
                                        $listCouriers = json_decode(get_option('sd_list_couriers'));
                                        foreach ($listCouriers as $courier_obj) {
                                            $courier = new Ship_Depot_Courier($courier_obj);
                                            // Ship_Depot_Logger::wrlog('$courier->cod: ' . $courier->cod);
                                            $listServices = $courier->ListServices;
                                        ?>

                                            <div class="courier-container" style="<?php echo $fee->CourierApply->Type == 'customize' ? 'display:block;' : 'display:none;'  ?>;">
                                                <div class="courier-img">
                                                    <?php
                                                    $checkedAttr = '';
                                                    if ($fee->CourierApply->Type == 'customize') {
                                                        foreach ($fee->CourierApply->ListCouriers as $selectedCouriers_obj) {
                                                            $selectedCouriers = new Ship_Depot_Fee_Markup_Courier_Service($selectedCouriers_obj);
                                                            if ($selectedCouriers->CourierID == $courier->CourierID) {
                                                                $checkedAttr = checked($selectedCouriers->IsActive, true, false);
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                    <label for="<?php echo esc_attr($fee_index) ?>_<?php echo esc_attr($courier->CourierID) ?>">
                                                        <input id="<?php echo esc_attr($fee_index) ?>_<?php echo esc_attr($courier->CourierID) ?>" name="<?php echo esc_attr($fee_index) ?>[couriersApply][courierSelected][<?php echo esc_attr($courier->CourierID) ?>]" class="cb_courier" type="checkbox" value="1" <?php echo $checkedAttr ?> />
                                                        <img <?php echo Ship_Depot_Helper::check_null_or_empty($courier->LogoURL) ? '' : 'src="' . esc_url($courier->LogoURL) . '"' ?> alt="<?php echo esc_attr($courier->CourierName) ?>" data-placement="bottom" title="<?php echo esc_attr($courier->CourierName) ?>">
                                                    </label>
                                                </div>
                                                <div class="col container-fluid col-padding-0 container_service">
                                                    <div class="row">
                                                        <?php
                                                        $count = 0;
                                                        foreach ($listServices as $service_obj) {
                                                            $service = new Ship_Depot_Courier_Service($service_obj);
                                                            if ($count > 0 && $count % 2 == 0) {
                                                                echo '<div class="w-100"></div>';
                                                            }

                                                            $checkedAttr = '';
                                                            $disableDiv = true;
                                                            if ($fee->CourierApply->Type == 'customize') {
                                                                foreach ($fee->CourierApply->ListCouriers as $selectedCouriers_obj) {
                                                                    $selectedCouriers = new Ship_Depot_Fee_Markup_Courier_Service($selectedCouriers_obj);
                                                                    if ($selectedCouriers->CourierID == $courier->CourierID) {
                                                                        if ($selectedCouriers->IsActive) {
                                                                            foreach ($selectedCouriers->ListServicesSelected as $serv) {
                                                                                if ($serv == $service->ServiceCode) {
                                                                                    $checkedAttr = checked(true, true, false);
                                                                                }
                                                                            }
                                                                        }
                                                                        $disableDiv = !$selectedCouriers->IsActive;
                                                                    }
                                                                }
                                                            } else {
                                                                $disableDiv = true;
                                                            }
                                                            $html_disable = $disableDiv ? ' disable-element' :  '';
                                                            echo '<div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 checkbox_container' . $html_disable . '">';
                                                            echo '<label for="' . esc_attr($fee_index) . '_' . esc_attr($courier->CourierID) . '_' . esc_attr($service->ServiceCode) . '">
                                                                <input id="' . esc_attr($fee_index) . '_' . esc_attr($courier->CourierID) . '_' . esc_attr($service->ServiceCode) . '" name="' . esc_attr($fee_index) . '[couriersApply][' . esc_attr($courier->CourierID) . '][serviceSelected][' . esc_attr($service->ServiceCode) . ']" class="cb_service" type="checkbox" value="1" ' . $checkedAttr . '/>'
                                                                . esc_attr($service->ServiceName);
                                                            echo '</label></div>';
                                                            $count++;
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>

                                        <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xl-8 col-lg-12 col-md-12 col-padding-0 custom-col col-notes">
                                        <p class="notes-title"><?php esc_html_e('Ghi chú', 'ship-depot-translate') ?></p>
                                        <textarea name="<?php echo esc_attr($fee_index) . '[notes]' ?>" class="notes-content"><?php echo esc_textarea($fee->Notes) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="div-btn-function">
                            <a href="javascript:;" class="sd-btn btn_delete_fee">
                                <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_delete.png') ?>" />
                                <?php esc_html_e('Xóa', 'ship-depot-translate') ?>
                            </a>
                            <a href="javascript:;" class="sd-btn btn_copy_fee">
                                <img src="<?php echo esc_url(SHIP_DEPOT_DIR_URL . 'assets/images/ic_copy.png') ?>" />
                                <?php esc_html_e('Copy tạo mới', 'ship-depot-translate') ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</div>