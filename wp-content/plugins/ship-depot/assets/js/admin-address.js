jQuery(document).ready(function ($) {
    let xhr_sd = null;
    // Load select2 address
    try {
        jQuery(".__fsw_city, .__fsw_district, .__fsw_ward").attr('data-value', function (e) { return $(this).val() }).select2();
    } catch (e) {
        console.log('Select2 library not loading');
    }

    // Update District when selected Province/City
    if (jQuery('.__sd_city').length > 0) {
        jQuery(document).on('change', '.__sd_city', function () {
            //Reset district and ward select
            // Order Details
            let parent = jQuery(this).closest('.edit_address');

            // Your Profile
            if (parent.length === 0) {
                parent = jQuery(this).closest('.form-table');
            }

            // Your Profile - AddressBook
            if (parent.length === 0) {
                parent = jQuery(this).closest('.sd-address-form');
            }
            let html = '<option value="">' + sd_admin_address_params.l10n.select_district + '</option>'
            parent.find('.__sd_district').html(html);
            parent.find('.__sd_ward').html('<option value="">' + sd_admin_address_params.l10n.select_ward + '</option>')
            //
            if (jQuery(this).val() && parseInt(jQuery(this).data('value')) !== parseInt(jQuery(this).val())) {
                let allProvinces = JSON.parse(sd_admin_address_params.all_provinces);
                let province_code = jQuery(this).val();
                allProvinces.forEach(pro => {
                    if (pro.Code == province_code) {
                        pro.ListDistricts.forEach(dis => {
                            html += '<option value="' + dis.Code + '">' + dis.Name + '</option>';
                        });
                    }
                });
                parent.find('.__sd_district').html(html).select2('close').attr('data-value', '').prop("disabled", false);
                jQuery(this).attr('data-value', province_code);
            }
        });
    }

    // Update Commune/Ward when selected District
    if (jQuery('.__sd_district').length > 0) {
        jQuery(document).on('change', '.__sd_district', function () {
            let parent = jQuery(this).closest('.edit_address');
            let html = '<option value="">' + sd_admin_address_params.l10n.select_ward + '</option>';
            parent.find('.__sd_ward').html(html);

            if (jQuery(this).val() && parseInt(jQuery(this).data('value')) !== parseInt(jQuery(this).val())) {
                if (jQuery(this).hasClass('loading')) return false;
                let id = jQuery(this).prop('id');
                let type = id.replace("_district", "");
                let province_select = $('#' + type + '_city');
                let province_code = province_select.val();
                let district_code = jQuery(this).val();
                // Order Details


                // Your Profile
                if (parent.length === 0) {
                    parent = jQuery(this).closest('.form-table');
                }

                // Your Profile - AddressBook
                if (parent.length === 0) {
                    parent = jQuery(this).closest('.sd-address-form');
                }

                let allProvinces = JSON.parse(sd_admin_address_params.all_provinces);
                parent.find('.__sd_ward').html('<option></option>').prop("disabled", false);

                allProvinces.forEach(pro => {
                    if (pro.Code == province_code) {

                        pro.ListDistricts.forEach(dis => {
                            if (dis.Code == district_code) {
                                dis.ListWards.forEach(ward => {
                                    html += '<option value="' + ward.Code + '">' + ward.Name + '</option>';
                                });
                            }
                        });

                    }
                });
                parent.find('.__sd_ward').html(html).select2('close').attr('data-value', '');
                jQuery(this).attr('data-value', district_code);
            }
        });
    }

    // Update data-value when selected ward
    if (jQuery('.__sd_ward').length > 0) {
        jQuery(document).on('change', '.__sd_ward', function () {
            if (jQuery(this).val() && parseInt(jQuery(this).data('value')) !== parseInt(jQuery(this).val())) {
                jQuery(this).attr('data-value', jQuery(this).val());
            }
        });
    }

    // Copy address billing to shipping in Your Profile
    jQuery(document).on('click', '.js_copy-billing', function () {
        console.log('copy billing click');
        let shipping_city = jQuery('[name="shipping_city"]');
        let billing_city = jQuery('[name="billing_city"]');
        let billing_district = jQuery('[name="billing_district"]');
        let billing_ward = jQuery('[name="billing_ward"]');
        let province_code = billing_city.val();
        let district_code = billing_district.val();
        let ward_code = billing_ward.val();
        let allProvinces = JSON.parse(sd_admin_address_params.all_provinces);
        //Check city
        if (shipping_city.attr('data-value') != shipping_city.val()) {
            //Load and update district
            jQuery('[name="shipping_district"]').html('<option></option>')
            html = '<option value="">' + sd_admin_address_params.l10n.select_district + '</option>';
            allProvinces.forEach(pro => {
                if (pro.Code == province_code) {
                    pro.ListDistricts.forEach(dis => {
                        html += '<option value="' + dis.Code + '">' + dis.Name + '</option>';
                    });
                }
            });
            jQuery('[name="shipping_district"]').html(html).val(district_code).trigger('change');
            jQuery('[name="shipping_district"]').attr('data-value', district_code);
            //Load and update ward
            jQuery('[name="shipping_ward"]').html('<option></option>').prop("disabled", false);
            html = '<option value="">' + sd_admin_address_params.l10n.select_ward + '</option>';
            allProvinces.forEach(pro => {
                if (pro.Code == province_code) {
                    pro.ListDistricts.forEach(dis => {
                        if (dis.Code == district_code) {
                            dis.ListWards.forEach(ward => {
                                html += '<option value="' + ward.Code + '">' + ward.Name + '</option>';
                            });
                        }
                    });
                }
            });
            jQuery('[name="shipping_ward"]').html(html).val(ward_code).trigger('change');
            jQuery('[name="shipping_ward"]').attr('data-value', ward_code);

            shipping_city.attr('data-value', province_code);
        } else {
            //Check district
            if (jQuery('[name="shipping_district"] option[value="' + district_code + '"]').length > 0) {
                //Update district
                jQuery('[name="shipping_district"]').val(district_code).trigger('change');
            } else {
                //Load and update district
                jQuery('[name="shipping_district"]').html('<option></option>')
                html = '<option value="">' + sd_admin_address_params.l10n.select_district + '</option>';
                allProvinces.forEach(pro => {
                    if (pro.Code == province_code) {
                        pro.ListDistricts.forEach(dis => {
                            html += '<option value="' + dis.Code + '">' + dis.Name + '</option>';
                        });
                    }
                });
                jQuery('[name="shipping_district"]').html(html).val(district_code).trigger('change');
                jQuery('[name="shipping_district"]').attr('data-value', district_code);
            }
            //Check ward
            if (jQuery('[name="shipping_ward"] option[value="' + ward_code + '"]').length > 0) {
                //Update ward
                jQuery('[name="shipping_ward"]').val(ward_code).trigger('change');
            } else {
                //Load and update ward
                jQuery('[name="shipping_ward"]').html('<option></option>').prop("disabled", false);
                html = '<option value="">' + sd_admin_address_params.l10n.select_ward + '</option>';
                allProvinces.forEach(pro => {
                    if (pro.Code == province_code) {
                        pro.ListDistricts.forEach(dis => {
                            if (dis.Code == district_code) {
                                dis.ListWards.forEach(ward => {
                                    html += '<option value="' + ward.Code + '">' + ward.Name + '</option>';
                                });
                            }
                        });
                    }
                });
                jQuery('[name="shipping_ward"]').html(html).val(ward_code).trigger('change');
                jQuery('[name="shipping_ward"]').attr('data-value', ward_code);
            }
        }
    });

    // Copy address billing to shipping in Order details
    jQuery("a.billing-same-as-shipping").on("click", function () {
        let billing_city = jQuery('[name="_billing_city"]');
        let billing_district = jQuery('[name="_billing_district"]');
        let billing_ward = jQuery('[name="_billing_ward"');
        let province_code = billing_city.val();
        let district_code = billing_district.val();
        let ward_code = billing_ward.val();
        let allProvinces = JSON.parse(sd_admin_address_params.all_provinces);
        if (jQuery('[name="_shipping_district"] option[value="' + district_code + '"]').length > 0) {
            //Update district
            jQuery('[name="_shipping_district"]').val(district_code).trigger('change');
        } else {
            //Load and update district 
            jQuery('[name="_shipping_district"]').html('<option></option>')
            html = '<option value="">' + sd_admin_address_params.l10n.select_district + '</option>';
            allProvinces.forEach(pro => {
                if (pro.Code == province_code) {
                    pro.ListDistricts.forEach(dis => {
                        html += '<option value="' + dis.Code + '">' + dis.Name + '</option>';
                    });
                }
            });
            jQuery('[name="_shipping_district"]').html(html).val(district_code).trigger('change');
            jQuery('[name="_shipping_district"]').attr('data-value', district_code);
        }

        if (jQuery('[name="_shipping_ward"] option[value="' + ward_code + '"]').length > 0) {
            //Update ward
            jQuery('[name="_shipping_ward"]').val(ward_code).trigger('change');
        } else {
            //Load and update ward
            jQuery('[name="_shipping_ward"]').html('<option></option>').prop( "disabled", false );
            html = '<option value="">' + sd_admin_address_params.l10n.select_ward + '</option>';
            allProvinces.forEach(pro => {
                if (pro.Code == province_code) {
                    pro.ListDistricts.forEach(dis => {
                        if (dis.Code == district_code) {
                            dis.ListWards.forEach(ward => {
                                html += '<option value="' + ward.Code + '">' + ward.Name + '</option>';
                            });
                        }
                    });
                }
            });
            jQuery('[name="_shipping_ward"]').html(html).val(ward_code).trigger('change');
            jQuery('[name="_shipping_ward"]').attr('data-value', ward_code);
        }
    });

    // Copy address billing from Your Profile
    jQuery("a.load_customer_billing").on("click", function () {
        let user_id = jQuery('#customer_user').val();
        let div_parent = jQuery(this).parent().parent().parent();
        if (!user_id) return false;
        block(div_parent);

        let data = {
            user_id: user_id,
            action: 'load_customer_address',
            security: woocommerce_admin_meta_boxes.get_customer_details_nonce
        };

        jQuery.ajax({
            url: sd_admin_address_params.ajax.url,
            data: data,
            type: 'POST',
            success: function (response) {
                if (response && response.data.billing) {
                    // Remove all XHR before
                    if (xhr_sd) xhr_sd.abort();

                    if (parseInt(response.data.billing.district) === 'NaN') {
                        response.data.billing.district = '';
                    }
                    if (parseInt(response.data.billing.ward) === 'NaN') {
                        response.data.billing.ward = '';
                    }

                    let delayInMilliseconds = 1000; //1 second

                    setTimeout(function () {
                        let province_code = response.data.billing.city;
                        let district_code = response.data.billing.district;
                        let ward_code = response.data.billing.ward;
                        let allProvinces = JSON.parse(sd_admin_address_params.all_provinces);

                        //Load and update district
                        jQuery(':input#_billing_district').html('<option></option>')
                        html = '<option value="">' + sd_admin_address_params.l10n.select_district + '</option>';
                        allProvinces.some(pro => {
                            if (pro.Code == province_code) {
                                pro.ListDistricts.forEach(dis => {
                                    html += '<option value="' + dis.Code + '">' + dis.Name + '</option>';
                                });
                            }
                            return pro.Code == province_code;
                        });
                        jQuery(':input#_billing_district').html(html).val(district_code).trigger('change');

                        //Load and update ward
                        jQuery(':input#_billing_ward').html('<option></option>').prop("disabled", false);
                        html = '<option value="">' + sd_admin_address_params.l10n.select_ward + '</option>';
                        allProvinces.some(pro => {
                            if (pro.Code == province_code) {
                                pro.ListDistricts.some(dis => {
                                    if (dis.Code == district_code) {
                                        dis.ListWards.forEach(ward => {
                                            html += '<option value="' + ward.Code + '">' + ward.Name + '</option>';
                                        });
                                    }
                                    return dis.Code == district_code;
                                });
                            }
                            return pro.Code == province_code;
                        });
                        jQuery(':input#_billing_ward').html(html).val(ward_code).trigger('change');
                        //
                        jQuery('[name="_billing_district"]').attr('data-value', district_code);
                        jQuery('[name="_billing_ward"]').attr('data-value', ward_code);
                        unblock(div_parent);
                    }, delayInMilliseconds);
                }
            }
        });
    });

    // Copy address shipping from Your Profile
    jQuery("a.load_customer_shipping").on("click", function () {
        let user_id = jQuery('#customer_user').val();
        let div_parent = jQuery(this).parent().parent().parent();
        if (!user_id) return false;
        block(div_parent);
        let data = {
            user_id: user_id,
            action: 'load_customer_address',
            security: woocommerce_admin_meta_boxes.get_customer_details_nonce
        };

        jQuery.ajax({
            url: sd_admin_address_params.ajax.url,
            data: data,
            type: 'POST',
            success: function (response) {
                if (response && response.data.shipping) {
                    // Remove all XHR before
                    if (xhr_sd) xhr_sd.abort();

                    if (parseInt(response.data.shipping.district) === 'NaN') {
                        response.data.shipping.district = '';
                    }
                    if (parseInt(response.data.shipping.ward) === 'NaN') {
                        response.data.shipping.ward = '';
                    }

                    let delayInMilliseconds = 1000; //1 second

                    setTimeout(function () {
                        //your code to be executed after 1 second
                        let province_code = response.data.shipping.city;
                        let district_code = response.data.shipping.district;
                        let ward_code = response.data.shipping.ward;
                        let allProvinces = JSON.parse(sd_admin_address_params.all_provinces);

                        //Load and update district
                        jQuery(':input#_shipping_district').html('<option></option>')
                        html = '<option value="">' + sd_admin_address_params.l10n.select_district + '</option>';
                        allProvinces.some(pro => {
                            if (pro.Code == province_code) {
                                pro.ListDistricts.forEach(dis => {
                                    html += '<option value="' + dis.Code + '">' + dis.Name + '</option>';
                                });
                            }
                            return pro.Code == province_code;
                        });
                        jQuery(':input#_shipping_district').html(html).val(district_code).trigger('change');

                        //Load and update ward
                        jQuery(':input#_shipping_ward').html('<option></option>').prop("disabled", false);
                        html = '<option value="">' + sd_admin_address_params.l10n.select_ward + '</option>';
                        allProvinces.some(pro => {
                            if (pro.Code == province_code) {
                                pro.ListDistricts.some(dis => {
                                    if (dis.Code == district_code) {
                                        dis.ListWards.forEach(ward => {
                                            html += '<option value="' + ward.Code + '">' + ward.Name + '</option>';
                                        });
                                    }
                                    return dis.Code == district_code;
                                });
                            }
                            return pro.Code == province_code;
                        });
                        jQuery(':input#_shipping_ward').html(html).val(ward_code).trigger('change');
                        //
                        jQuery('[name="_shipping_district"]').attr('data-value', district_code);
                        jQuery('[name="_shipping_ward"]').attr('data-value', ward_code);
                        unblock(div_parent);
                    }, delayInMilliseconds);
                }
            }
        });
    });

    // Copy address billing/shipping when change customer in Order details
    jQuery("#customer_user").bind("change", function () {
        let user_id = jQuery(this).val();
        if (!user_id) return false;

        let data = {
            user_id: user_id,
            action: 'load_customer_address',
            security: woocommerce_admin_meta_boxes.get_customer_details_nonce
        };

        jQuery.ajax({
            url: sd_admin_address_params.ajax.url,
            data: data,
            type: 'POST',
            success: function (response) {
                if (response && response.data.billing) {
                    if (parseInt(response.data.billing.district) === 'NaN') {
                        response.data.billing.district = '';
                    }
                    if (parseInt(response.data.billing.ward) === 'NaN') {
                        response.data.billing.ward = '';
                    }

                    let div_parent_billing = jQuery(':input#_billing_district').parent().parent().parent();
                    if (div_parent_billing.find('div.edit_address').css('display') != 'none') {
                        block(div_parent_billing);
                        let delayInMilliseconds = 1000; //1 second
                        setTimeout(function () {
                            let province_code = response.data.billing.city;
                            let district_code = response.data.billing.district;
                            let ward_code = response.data.billing.ward;
                            let allProvinces = JSON.parse(sd_admin_address_params.all_provinces);

                            //Load and update district
                            jQuery(':input#_billing_district').html('<option></option>')
                            html = '<option value="">' + sd_admin_address_params.l10n.select_district + '</option>';
                            allProvinces.some(pro => {
                                if (pro.Code == province_code) {
                                    pro.ListDistricts.forEach(dis => {
                                        html += '<option value="' + dis.Code + '">' + dis.Name + '</option>';
                                    });
                                }
                                return pro.Code == province_code;
                            });
                            jQuery(':input#_billing_district').html(html).val(district_code).trigger('change');

                            //Load and update ward
                            jQuery(':input#_billing_ward').html('<option></option>').prop("disabled", false);
                            html = '<option value="">' + sd_admin_address_params.l10n.select_ward + '</option>';
                            allProvinces.some(pro => {
                                if (pro.Code == province_code) {
                                    pro.ListDistricts.some(dis => {
                                        if (dis.Code == district_code) {
                                            dis.ListWards.forEach(ward => {
                                                html += '<option value="' + ward.Code + '">' + ward.Name + '</option>';
                                            });
                                        }
                                        return dis.Code == district_code;
                                    });
                                }
                                return pro.Code == province_code;
                            });
                            jQuery(':input#_billing_ward').html(html).val(ward_code).trigger('change');
                            //
                            jQuery('[name="_billing_district"]').attr('data-value', district_code);
                            jQuery('[name="_billing_ward"]').attr('data-value', ward_code);
                            unblock(div_parent_billing);
                        }, delayInMilliseconds);
                    }
                }

                if (response && response.data.shipping) {
                    // Remove all XHR before
                    if (xhr_sd) xhr_sd.abort();

                    if (parseInt(response.data.shipping.district) === 'NaN') {
                        response.data.shipping.district = '';
                    }
                    if (parseInt(response.data.shipping.ward) === 'NaN') {
                        response.data.shipping.ward = '';
                    }

                    let div_parent_shipping = jQuery(':input#_shipping_district').parent().parent().parent();
                    if (div_parent_shipping.find('div.edit_address').css('display') != 'none') {
                        block(div_parent_shipping);
                        let delayInMilliseconds = 1000; //1 second
                        setTimeout(function () {
                            let province_code = response.data.shipping.city;
                            let district_code = response.data.shipping.district;
                            let ward_code = response.data.shipping.ward;
                            let allProvinces = JSON.parse(sd_admin_address_params.all_provinces);

                            //Load and update district
                            jQuery(':input#_shipping_district').html('<option></option>')
                            html = '<option value="">' + sd_admin_address_params.l10n.select_district + '</option>';
                            allProvinces.some(pro => {
                                if (pro.Code == province_code) {
                                    pro.ListDistricts.forEach(dis => {
                                        html += '<option value="' + dis.Code + '">' + dis.Name + '</option>';
                                    });
                                }
                                return pro.Code == province_code;
                            });
                            jQuery(':input#_shipping_district').html(html).val(district_code).trigger('change');

                            //Load and update ward
                            jQuery(':input#_shipping_ward').html('<option></option>').prop("disabled", false);
                            html = '<option value="">' + sd_admin_address_params.l10n.select_ward + '</option>';
                            allProvinces.some(pro => {
                                if (pro.Code == province_code) {
                                    pro.ListDistricts.some(dis => {
                                        if (dis.Code == district_code) {
                                            dis.ListWards.forEach(ward => {
                                                html += '<option value="' + ward.Code + '">' + ward.Name + '</option>';
                                            });
                                        }
                                        return dis.Code == district_code;
                                    });
                                }
                                return pro.Code == province_code;
                            });
                            jQuery(':input#_shipping_ward').html(html).val(ward_code).trigger('change');
                            //
                            jQuery('[name="_shipping_district"]').attr('data-value', district_code);
                            jQuery('[name="_shipping_ward"]').attr('data-value', ward_code);
                            unblock(div_parent_shipping);
                        }, delayInMilliseconds);
                    }
                }
            }
        });
    });
});