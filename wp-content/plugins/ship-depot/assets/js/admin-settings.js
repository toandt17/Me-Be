jQuery(document).ready(function ($) {
    if ($('#cb_auto_create_shipping').length > 0) {
        $("#cb_auto_create_shipping").change(function () {
            if (this.checked) {
                //I am checked
                $("#div_auto_shipping").removeClass("disable-element");
            } else {
                //I'm not checked
                $("#div_auto_shipping").addClass("disable-element");
                $("#div_auto_shipping").children().each(function () {
                    $(this).children('input').each(function () {
                        $(this).prop('checked', false);
                    });
                });
            }
        });
    };

    if ($('.cb_woo_status').length > 0) {
        $('.cb_woo_status').change(function () {
            let parent = this.parentElement;
            let select = $(parent).children('.select_order_status');
            if (this.checked) {
                //I am checked
                $(select).prop('disabled', '');
            } else {
                //I'm not checked
                $(select).prop('disabled', 'disabled');
            }
        });
    }

    if ($('#lb_advanced_settings').length > 0) {
        $("#lb_advanced_settings").click(function () {
            if ($("#div_advanced_settings").hasClass("collapse")) {
                $("#div_advanced_settings").removeClass("collapse");
            } else {
                $("#div_advanced_settings").addClass("collapse");
            }
        });
    };

    if ($('#btn_sync_settings').length > 0) {
        $("#btn_sync_settings").click(function () {
            if (confirm("Bạn muốn đồng bộ thiết lập từ Ship Depot admin?")) {
                block($('#sd-general-settings'));
                $('.woocommerce-save-button').prop('disabled', 'disabled');
                jQuery.ajax({
                    url: sd_admin_address_params.ajax.url,
                    data: {
                        action: 'sync_setting'
                    },
                    type: 'POST',
                    success: function (response) {
                        location.reload();
                        unblock($('#sd-general-settings'));
                        $('.woocommerce-save-button').prop('disabled', '');
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        location.reload();
                        unblock($('#sd-general-settings'));
                        console.log('The following error occured: ' + textStatus, errorThrown);
                        $('.woocommerce-save-button').prop('disabled', '');
                    }
                });
            }
        });
    };

    if ($('.woocommerce-save-button').length > 0) {
        $('.woocommerce-save-button').click(function () {
            block($('#mainform'));
        });
    };
});
