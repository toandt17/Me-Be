jQuery(document).ready(function ($)
{
    if ($('#sd-courier').length > 0)
    {
        //sd-couriers
        if ($('.cb_courier_isUsed').length > 0)
        {
            $('.cb_courier_isUsed').change(function ()
            {
                let div_cod = $(this).parents('.courier-container').find('.div-cod');
                let div_cod_failed = $(this).parents('.courier-container').find('.div-cod-failed');
                let div_serv = $(this).parents('.courier-container').find('.div_service');
                let div_pick_station = $(this).parents('.courier-container').find('.div-pick-station');
                let div_extra_info = $(this).parents('.courier-container').find('.div-extra-info');
                if ($(this).is(':checked') == true)
                {
                    div_cod.removeClass('disable-element');
                    div_cod_failed.removeClass('disable-element');
                    div_serv.removeClass('disable-element');
                    div_pick_station.removeClass('disable-element');
                    div_extra_info.removeClass('disable-element');
                } else
                {
                    div_cod.addClass('disable-element');
                    div_cod_failed.addClass('disable-element');
                    div_serv.addClass('disable-element');
                    div_pick_station.addClass('disable-element');
                    div_extra_info.addClass('disable-element');
                }
            });
        }

        if ($('.rb-cod-failed-use-no').length > 0)
        {
            $('.rb-cod-failed-use-no').change(function ()
            {
                if ($(this).is(':checked'))
                {
                    let div_cod_faild = $(this).parents('.div-cod-failed').length == 0 ? null : $(this).parents('.div-cod-failed')[0];
                    if (div_cod_faild != null)
                    {
                        let grid_cod_failed_settings = $(div_cod_faild).find('.grid-cod-failed').length == 0 ? null : $(div_cod_faild).find('.grid-cod-failed')[0];
                        if (grid_cod_failed_settings != null)
                        {
                            SetDisable(grid_cod_failed_settings);
                        }
                    }
                }
            })
        }

        if ($('.rb-cod-failed-use-yes').length > 0)
        {
            $('.rb-cod-failed-use-yes').change(function ()
            {
                if ($(this).is(':checked'))
                {
                    let div_cod_faild = $(this).parents('.div-cod-failed').length == 0 ? null : $(this).parents('.div-cod-failed')[0];
                    if (div_cod_faild != null)
                    {
                        let grid_cod_failed_settings = $(div_cod_faild).find('.grid-cod-failed').length == 0 ? null : $(div_cod_faild).find('.grid-cod-failed')[0];
                        if (grid_cod_failed_settings != null)
                        {
                            SetEnable(grid_cod_failed_settings);
                        }
                    }
                }
            })
        }

        if ($('.cod-failed-value').length > 0)
        {
            $('.cod-failed-value').on({
                // keypress: function (event) {
                //     if (event.key === '-') {
                //         event.preventDefault();
                //     } else {
                //         if (this.value.includes('.')) {
                //             let split = this.value.split('.');
                //             if (split.length > 1 && split[1].length >= 2) {
                //                 event.preventDefault();
                //             }
                //         }
                //     }
                // },

                blur: function ()
                {
                    if (this.value == null || this.value == '' || this.value < 0)
                    {
                        SetError(this)
                    } else
                    {
                        ClearError(this)
                    }
                }
            });
        }

        const defaultCheckoutText = "Nếu bạn không nhận hàng, bạn cần thanh toán phí ship là";
        const defaultLabelText = "Nếu khách hàng không nhận hàng thì phí ship là";
        HandleContentLogic("checkout", defaultCheckoutText);
        HandleContentLogic("label", defaultLabelText);

        function HandleContentLogic(contentType, defaultText)
        {
            if ($(`.cf-content-${contentType}`).length > 0)
            {
                $(`.cf-content-${contentType}`).blur(function ()
                {
                    let div_content = $(this).parents(`.div-${contentType}-content`).length == 0 ? null : $(this).parents(`.div-${contentType}-content`)[0];
                    if (div_content != null)
                    {
                        let radio_yes = $(div_content).find(`.rb-${contentType}-yes`).length == 0 ? null : $(div_content).find(`.rb-${contentType}-yes`)[0];
                        validateContent(radio_yes, this);
                    }
                })
            }

            if ($(`.rb-${contentType}-no`).length > 0)
            {
                $(`.rb-${contentType}-no`).change(function ()
                {
                    if ($(this).is(':checked'))
                    {
                        let div_content = $(this).parents(`.div-${contentType}-content`).length == 0 ? null : $(this).parents(`.div-${contentType}-content`)[0];
                        if (div_content != null)
                        {
                            let input_content = $(div_content).find(`.cf-content-${contentType}`).length == 0 ? null : $(div_content).find(`.cf-content-${contentType}`)[0];
                            if (input_content != null)
                            {
                                $(input_content).val('');
                                SetDisable(input_content);
                                ClearError(input_content)
                            }
                        }
                    }
                })
            }

            if ($(`.rb-${contentType}-yes`).length > 0)
            {
                $(`.rb-${contentType}-yes`).change(function ()
                {
                    if ($(this).is(':checked'))
                    {
                        let div_content = $(this).parents(`.div-${contentType}-content`).length == 0 ? null : $(this).parents(`.div-${contentType}-content`)[0];
                        if (div_content != null)
                        {
                            let input_content = $(div_content).find(`.cf-content-${contentType}`).length == 0 ? null : $(div_content).find(`.cf-content-${contentType}`)[0];
                            if (input_content != null)
                            {
                                $(input_content).val(defaultText);
                                SetEnable(input_content);
                            }
                        }
                    }
                })
            }
        }

        function validateContent(radioYes, inputContent)
        {
            if (radioYes != null && $(radioYes).is(':checked') && $(inputContent).val() == '')
            {
                SetError(inputContent)
            } else
            {
                ClearError(inputContent)
            }
        }

        //

        if ($('button[type=submit][name=save]').length > 0)
        {
            $('button[type=submit][name=save]').click(function (event)
            {
                event.preventDefault();
                validateStationData();
                if ($('.error-class').length > 0)
                {
                    $('#validate_error').val('true');
                    unblock($('#mainform'));
                } else
                {
                    $('#validate_error').val('false');
                    $('#mainform').submit();
                }
            });
        }
    }
});
