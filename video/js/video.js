function UpdateVideoEvent(media_event_id) {
    jQuery("#media_event_form_" + media_event_id).submit();
}

function CheckIEBrowser()
{
    var ua = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE ");

    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))  // If Internet Explorer, return version number
    {
        //alert(parseInt(ua.substring(msie + 5, ua.indexOf(".", msie))));
        return true;
    } else  // If another browser, return 0
    {
        //alert('Please use Internet Explorer (IE) to browse this page.');
        return false;
    }

    
}

function CheckIsWmv(IsWmv) {
    /*
    if(IsWmv) {
        if(!CheckIEBrowser()) {
            alert('Please use Internet Explorer (IE) to browse this page.');
        }
    }
    */
}

function getEventItem(val) {

    jQuery.ajax({
        type: "POST",
        url: 'activities_get_event_select',
        data: {
            evt_type_id: val
        },
        success: function (option_data)
        {
            //jQuery("#div_evt_cop_id").html(data);
            var select = jQuery('#edit-eid');
            select.empty().append(option_data);
        }
    });
}

function getAllEventItem(val) {

    jQuery.ajax({
        type: "POST",
        url: app_path+'video_get_event_select',
        data: {
            evt_type: val
        },
        success: function (option_data)
        {
            var select = jQuery('#edit-eid');
            select.empty().append(option_data);
        },
        error: function () 
        {
            var select = jQuery('#edit-eid');
            select.empty();
        }
    });
}

function goChangeUrl() {
    var this_media_id = jQuery('#this_media_id').val();
    
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    var replaced = false;
    
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split("=");
        if (pair[0] == 'media_id') {
            vars[i] = pair[0] + "="+ this_media_id;
            replaced = true;
        }
    }
    if (!replaced) {
        vars.push('media_id' + "=" + this_media_id);
    }
    var this_var = vars.join("&");
    var newUrl = document.location.origin + document.location.pathname + '?' + this_var;
    
    window.history.pushState('', '', newUrl);
    
}
