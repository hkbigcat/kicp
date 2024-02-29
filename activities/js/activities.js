jQuery(document).ready(function () {
    console.log("activity jquery ready");
});


function getEventData(type, evt_id) {

    // re-assign the active button
    jQuery("a[id^='submenu_link']").each(function (index, item) {
        jQuery(item).removeClass("active");
        jQuery("#submenu_link_" + type).addClass("active");
    });

    jQuery.ajax({
        type: "POST",
        url: '/kmapp2/kicp/activities_event_data',
        data: {
            evt_id: evt_id,
            type: type
        },
        success: function (data)
        {
            
            var content = "";
            if (type == 'photo') {
                link = '/kmapp2/kicp/activities_event_data?evt_id=' + evt_id + '&type=' + type
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