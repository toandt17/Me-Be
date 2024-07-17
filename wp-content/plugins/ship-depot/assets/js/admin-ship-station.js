jQuery(document).ready(function ($) {
    //JS for ship from station
    if ($('#ship_from_station_yes').length > 0) {
        $('#ship_from_station_yes').change(function () {
            const divYesOption = $(this).parents('.pick-station-options').find('.pick-station-yes-option')
            if ($(this).is(':checked')) {
                divYesOption.show()
            }
            validateStationData()
        })
    }

    if ($('#ship_from_station_no').length > 0) {
        $('#ship_from_station_no').change(function () {
            const divYesOption = $(this).parents('.pick-station-options').find('.pick-station-yes-option')
            if ($(this).is(':checked')) {
                divYesOption.hide()
            }
            validateStationData()
        })
    }

    if ($('.sl_province').length > 0) {
        $('.sl_province').change(function () {
            const provinceCode = $(this).val()
            const divParent = $(this).parents('.div-pick-station')
            const selectDistrict = $(divParent).find('.sl_district')
            const selectStation = $(divParent).find('.sl_station')
            const selectedStationData = $(divParent).find('.selected_station_data')
            selectDistrict.html('<option value="-1">' + sd_admin_address_params.l10n.loading + '</option>').attr('disabled', true)
            selectStation.html('<option value="-1">' + sd_ship_station_params.l10n.select_station + '</option>')
            selectedStationData.val('')
            let html = '<option value="-1">' + sd_admin_address_params.l10n.select_district + '</option>'
            let allProvinces = JSON.parse(sd_admin_address_params.all_provinces)
            allProvinces.forEach(pro => {
                if (pro.Code == provinceCode) {
                    pro.ListDistricts.forEach(dis => {
                        html += '<option value="' + dis.Code + '">' + dis.Name + '</option>'
                    })
                }
            })
            selectDistrict.html(html).attr('disabled', false)
        })
    }

    if ($('.sl_district').length > 0) {
        $('.sl_district').change(function () {
            const selectedDistrict = $(this).val()
            const divParent = $(this).parents('.div-pick-station')
            const courierData = ParseHTMLJson($(divParent).find('.courier_data').val())
            const districtData = ParseHTMLJson($(divParent).find('.selected_district').val())
            const selectStation = $(divParent).find('.sl_station')
            const selectProvince = $(divParent).find('.sl_province')
            const selectedStationData = $(divParent).find('.selected_station_data')
            selectedStationData.val('')
            let html = '<option value="-1">' + sd_ship_station_params.l10n.select_station + '</option>'
            if (selectedDistrict && selectedDistrict > 0) {
                selectStation.html('<option value="-1">' + sd_ship_station_params.l10n.loading + '</option>').attr('disabled', true)
                console.log('courierData: ', courierData)
                console.log('districtData: ', districtData)
                const dataInput = {
                    City: { CityISN: selectProvince.find(':selected').data('id'), Code: selectProvince.val() },
                    District: { Code: selectedDistrict },
                    CourierISN: courierData.CourierISN
                }
                console.log('dataInput: ', dataInput)
                $.ajax({
                    url: sd_ship_station_params.ajax.ship_depot_host_api + '/Shipping/GetShipStations',
                    headers: {
                        'ShopAPIKey': sd_ship_station_params.sd_api_key
                    },
                    dataType: 'json',
                    contentType: 'application/json',
                    data: JSON.stringify(dataInput),
                    type: 'POST',
                    success: function (response) {
                        selectStation.html(html).attr('disabled', false)
                        if (response.Code >= 0) {
                            console.log('Response data: ', response.Data)
                            const options = response.Data.map((station) => `<option data-json="${ParseObjToHTMLJson(station)}" title="${station.Address}" value="${station.Id}">${station.Name}</option>`)
                            selectStation.html(html + options.join(' '))
                        } else {
                            alert(sd_ship_station_params.l10n.get_station_error)
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        selectStation.html(html).attr('disabled', false)
                        alert(sd_ship_station_params.l10n.get_station_error)
                    }
                })
            } else {
                selectStation.html(html).attr('disabled', false)
            }
        })
    }

    if ($('.sl_station').length > 0) {
        // $('.sl_station').change(function () {
        //     const divParent = $(this).parents('.pick-station-yes-option-row')
        //     const selectedStationData = $(divParent).find('.selected_station_data')
        //     selectedStationData.val($(this).find(':selected').data('json'))
        // })

        $('.sl_station').on({
            change: function () {
                const divParent = $(this).parents('.pick-station-yes-option-row')
                const selectedStationData = $(divParent).find('.selected_station_data')
                selectedStationData.val($(this).find(':selected').data('json'))
            },

            blur: function () {
                validateStationData()
            }
        })
    }
})

function validateStationData() {
    if (jQuery('#ship_from_station_yes').length > 0) {
        if (jQuery('#ship_from_station_yes').is(':checked')) {
            if (jQuery('.sl_station').length > 0) {
                jQuery('.sl_station').each(function () {
                    const divParent = jQuery(this).parents('.pick-station-yes-option')
                    const error_station_content = jQuery('#error_station_content')
                    if (this.value == null || this.value == '' || this.value < 0) {
                        SetError(this)
                        if (divParent.length > 0) {
                            const error_content = jQuery(divParent).find('.error_content')
                            if (error_content.length > 0) {
                                error_content.text(sd_ship_station_params.error_messages.station_required)
                            }
                        }

                        if (error_station_content.length > 0) {
                            error_station_content.text(sd_ship_station_params.error_messages.station_required)
                        }
                    } else {
                        ClearError(this)
                        if (divParent.length > 0) {
                            const error_content = jQuery(divParent).find('.error_content')
                            if (error_content.length > 0) {
                                error_content.empty()
                            }
                        }

                        if (error_station_content.length > 0) {
                            error_station_content.empty()
                        }
                    }
                })
            }
        } else {
            if (jQuery('.sl_station').length > 0) {
                jQuery('.sl_station').each(function () {
                    ClearError(this)
                    const divParent = jQuery(this).parents('.pick-station-yes-option')
                    const error_station_content = jQuery('#error_station_content')
                    if (divParent.length > 0) {
                        const error_content = jQuery(divParent).find('.error_content')
                        if (error_content.length > 0) {
                            error_content.empty()
                        }
                    }

                    if (error_station_content.length > 0) {
                        error_station_content.empty()
                    }
                })
            }
        }
    }

}

function clearValidate() {
    const error_station_content = jQuery('#error_station_content')
    if (error_station_content.length > 0) {
        error_station_content.empty()
    }

    if (jQuery('.sl_station').length > 0) {
        jQuery('.sl_station').each(function () {
            ClearError(this)
        })
    }
}