
function block(element)
{
    element.block({
        message: null,
        overlayCSS: {
            background: '#fff',
            opacity: 0.6
        }
    });

}

function unblock(element)
{
    element.unblock();
}

function hideElement(e)
{
    e.style.display = "none";
}

function showElement(e)
{
    e.style.display = "block";
}

function insertAfter(newNode, existingNode)
{
    existingNode.parentNode.insertBefore(newNode, existingNode.nextSibling);
}

function getTimeTicks()
{
    let yourDate = new Date(); // for example

    // the number of .net ticks at the unix epoch
    let epochTicks = 621355968000000000;

    // there are 10000 .net ticks per millisecond
    let ticksPerMillisecond = 10000;

    // calculate the total number of .net ticks for your date
    let yourTicks = epochTicks + (yourDate.getTime() * ticksPerMillisecond);
    return yourTicks;
}

function setVal(e)
{
    e.setAttribute('value', e.value);
}

function b64toBlob(b64Data, contentType = '', sliceSize = 512)
{
    const byteCharacters = atob(b64Data);
    const byteArrays = [];

    for (let offset = 0; offset < byteCharacters.length; offset += sliceSize)
    {
        const slice = byteCharacters.slice(offset, offset + sliceSize);

        const byteNumbers = new Array(slice.length);
        for (let i = 0; i < slice.length; i++)
        {
            byteNumbers[i] = slice.charCodeAt(i);
        }

        const byteArray = new Uint8Array(byteNumbers);
        byteArrays.push(byteArray);
    }

    const blob = new Blob(byteArrays, {
        type: contentType
    });
    return blob;
}

function formatVNCurrency(money)
{
    const config = {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 9
    };
    return new Intl.NumberFormat(sd_general_params.sd_locale, config).format(parseFloat(money));
}

function isNullorEmpty(value)
{
    if (value == null || value == '')
    {
        return true;
    }
    return false;
}

function arrangeMessage(parentMessages, messageAdd)
{
    if (!isNullorEmpty(parentMessages))
    {
        if (!parentMessages.includes(messageAdd))
        {
            parentMessages += "<br>";
            parentMessages += messageAdd;
        }
    } else
    {
        parentMessages += messageAdd;
    }
    return parentMessages;
}

function formatNumber(n)
{
    // format number 1000000 to 1.234.567
    return n.replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".")
}

// Jquery Dependency
jQuery(document).ready(function ($)
{
    $("input[data-type='currency']").on({
        keyup: function ()
        {
            formatCurrency($(this));
        },
        blur: function ()
        {
            formatCurrency($(this));
        }
    });
});

var formatCurrency = function (input)
{
    // and puts cursor back in right position.

    // get input value
    let input_val = input.val();

    // don't validate empty input
    if (input_val === "") { return; }

    // original length
    let original_len = input_val.length;

    // initial caret position 
    let caret_pos = input.prop("selectionStart");

    input_val = formatNumber(input_val);
    // send updated string to input
    input.val(input_val);

    // put caret back in the right position
    let updated_len = input_val.length;
    caret_pos = updated_len - original_len + caret_pos;
    input[0].setSelectionRange(caret_pos, caret_pos);
}

function formatPhone(phone)
{
    if (!checkNullorEmpty(phone))
    {
        phone_length = phone.length;
        format = '';
        if (phone_length > 3 && phone_length <= 6)
        {
            format = phone.substring(0, 3) + '-' + phone.substring(3);
        } else if (phone_length > 6)
        {
            format = phone.substring(0, 3) + '-' + phone.substring(3, 6) + '-' + phone.substring(6);
        }

        return format;
    }
    return phone;
}

function copyTextToClipboard(text)
{
    // Copy the text inside the text field
    if (window.isSecureContext && navigator.clipboard)
    {
        //Chrome
        navigator.clipboard.writeText(text);
    } else if (window.clipboardData)
    {
        // Internet Explorer
        window.clipboardData.setData("Text", text);
    } else
    {
        unsecuredCopyToClipboard(text);
    }
}

function unsecuredCopyToClipboard(text)
{
    const textArea = document.createElement("textarea");
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try
    {
        document.execCommand('copy');
    } catch (err)
    {
        console.error("Unable to copy to clipboard", err);
    }
    document.body.removeChild(textArea);
}

function SetError(element)
{
    element.classList.add("error-class");
}

function ClearError(element)
{
    if (element.classList.contains("error-class"))
    {
        element.classList.remove("error-class");
    }
}

function SetDisable(element)
{
    element.classList.add("disable-element");
}

function SetEnable(element)
{
    if (element.classList.contains("disable-element"))
    {
        element.classList.remove("disable-element");
    }
}

function ParseHTMLJson(htmlData)
{
    if (htmlData)
    {
        const re = /'/gi;
        let jsonCompl = htmlData.replace(re, `"`);
        return JSON.parse(jsonCompl);
    }
    return null;
}

function ParseObjToHTMLJson(obj)
{
    const re = /"/gi;
    return JSON.stringify(obj).replace(re, `'`);
}

function checkNullorEmpty(value)
{
    if (value == null || value == '' || value.replace(/^\s+|\s+$/gm, '') == '')
    {
        return true;
    }
    return false;
}