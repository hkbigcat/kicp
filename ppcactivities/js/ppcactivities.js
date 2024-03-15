jQuery(document).ready(function () {
    console.log("ppc activity jquery ready");
});


function updateEnrollStatus() {

    for (i = 0; i < document.getElementsByName('chk_success').length; i++) {
        var isSuccess = 0;
        var isShowup = 0;
        var user_id = document.getElementsByName('chk_success')[i].value;
        var evt_id = jQuery("#evt_id").val();

        if (document.getElementsByName('chk_success')[i].checked) {
            isSuccess = 1;
        }

        if (document.getElementsByName('chk_showup')[i].checked) {
            isShowup = 1;
        }

//        console.log("user_id: " + user_id+" evt_id: "+evt_id+" isSuccess: "+isSuccess);
        jQuery.ajax({
            type: "POST",
            url: '../ppcactivities_enroll_status_update/'+evt_id+'/'+user_id,
            data: {
                is_enrol_successful: isSuccess,
                is_showup: isShowup,
            },
            success: function (data)
            {
            }
        });
    }


    showDialogueBox('update');
    location.reload();

}

function showDialogueBox(action) {
    if (action == 'update') {
        jQuery("#update-confirm").dialog({
            title: "KICP",
            width: 400,
            height: 225,
            modal: true,
            buttons: {
                "OK": function () {
                    jQuery(this).dialog("close");
                }
            }
        });
    }
}




function getEventData(type, evt_id) {

    // re-assign the active button
    jQuery("a[id^='submenu_link']").each(function (index, item) {
        jQuery(item).removeClass("active");
        jQuery("#submenu_link_" + type).addClass("active");
    });

    jQuery.ajax({
        type: "POST",
        url: '/kmapp2/kicp/ppcactivities_event_data',
        data: {
            evt_id: evt_id,
            type: type
        },
        success: function (data)
        {
            
            var content = "";
            if (type == 'photo') {
                link = '/kmapp2/kicp/ppcactivities_event_data?evt_id=' + evt_id + '&type=' + type
                content = '<div id="lightgallery">{{lightgallery}}</div>';
            } else {
                content = data;
            }
            jQuery("#description_content").html(content);

            if (type == 'photo')
                jQuery("#lightgallery").load(link);
            
            if (type == 'photo' || type == 'deliverable') {
                jQuery("#event_detail").hide()
            }
            else{
                jQuery("#event_detail").show()
            }
            
        }
    });

    
}


