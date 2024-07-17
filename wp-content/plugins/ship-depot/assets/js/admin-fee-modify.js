jQuery(document).ready(function ($) {
    if ($('#sd-fee-modify').length > 0) {
        function validateData() {
            let error = false;
            $('#all_fee_container').find('.div_container').each(function () {
                let feeModifyContainer = $(this).children('.fee-modify-container')[0];
                let errorMessage = "";
                let errorMessageElement = $(feeModifyContainer).children('.error_content')[0];
                if (errorMessageElement != null) {
                    $(errorMessageElement).text('');
                }
                //Condition
                let conditionPanel = $(feeModifyContainer).find('.condition_panel')[0];
                let conditionTable = $(conditionPanel).find('.condition_table');
                if (conditionTable.length > 0) {
                    $(conditionTable).find('input[data-type=currency]').each(function () {
                        let id = this.id;
                        if (id.includes('_from')) {
                            let toId = id.replace("_from", "_to");
                            let toElement = $('#' + toId);
                            //Remove error before check
                            ClearError(this);

                            if (toElement != null) {
                                ClearError(toElement[0]);
                                //
                                if (isNullorEmpty($(this).val()) && isNullorEmpty(toElement.val())) {
                                    SetError(this);
                                    SetError(toElement[0]);
                                    errorMessage = arrangeMessage(errorMessage, sd_fee_modify_params.error_messages.error_required);
                                } else if (!isNullorEmpty($(this).val()) && !isNullorEmpty(toElement.val())) {
                                    //Compare
                                    if (parseFloat(toElement.val().replace(/\./g, "")) < parseFloat($(this).val().replace(/\./g, ""))) {
                                        SetError(this);
                                        SetError(toElement[0]);
                                        errorMessage = arrangeMessage(errorMessage, sd_fee_modify_params.error_messages.error_compare);
                                    }
                                }
                            }
                        }
                    });
                }
                //Option
                let feeOptionPanel = $(feeModifyContainer).find('.div-fee-option')[0];
                $(feeOptionPanel).find('input[data-type=currency]').each(function () {
                    //Remove error before check
                    ClearError(this);

                    if (isNullorEmpty($(this).val())) {
                        SetError(this);
                        errorMessage = arrangeMessage(errorMessage, sd_fee_modify_params.error_messages.error_required);
                    }
                });
                //Time apply
                let timePanel = $(feeModifyContainer).find('.div-time-apply')[0];
                let selectTime = $(timePanel).find('.sl_time_apply_fee')[0];
                let fromTimeElement = $(timePanel).find('.from_time_val')[0];
                let toTimeElement = $(timePanel).find('.to_time_val')[0];
                //
                ClearError(fromTimeElement);
                ClearError(toTimeElement);
                //
                if ($(selectTime).val() == 'period') {
                    if (isNullorEmpty($(fromTimeElement).val()) && isNullorEmpty($(toTimeElement).val())) {
                        SetError(fromTimeElement);
                        SetError(toTimeElement);
                        errorMessage = arrangeMessage(errorMessage, sd_fee_modify_params.error_messages.error_required);
                    } else if (!isNullorEmpty($(fromTimeElement).val()) && !isNullorEmpty($(toTimeElement).val())) {
                        //Compare
                        let date_format = isNullorEmpty($('#current_date_format').val()) ? 'dd/mm/yy' : $('#current_date_format').val();
                        if ($.datepicker.parseDate(date_format, $(fromTimeElement).val()) > $.datepicker.parseDate(date_format, $(toTimeElement).val())) {
                            SetError(fromTimeElement);
                            SetError(toTimeElement);
                            errorMessage = arrangeMessage(errorMessage, sd_fee_modify_params.error_messages.error_compare);
                        }
                    }
                }

                if (errorMessageElement != null && !isNullorEmpty(errorMessage)) {
                    error = true;
                    $(errorMessageElement).html(errorMessage);
                }
            });
            return error;
        }


        if ($('button[type=submit][name=save]').length > 0) {
            $('button[type=submit][name=save]').click(function (event) {
                console.log('Click save');
                event.preventDefault();
                if (validateData()) {
                    $('#validate_error').val('true');
                    unblock($('#mainform'));
                } else {
                    $('#validate_error').val('false');
                    $('#mainform').submit();
                }
            });
        }
    }

    if ($('#mainform').length > 0) {
        $('#mainform').on('keyup', 'input[data-type=currency]', function () {
            formatCurrency($(this));
        });

        $('#mainform').on('blur', 'input[data-type=currency]', function () {
            formatCurrency($(this));
        });
    }
});