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
        url: 'activities_event_data',
        data: {
            evt_id: evt_id,
            type: type
        },
        success: function (data)
        {
            var content = "";
            if (type == 'photo') {
                link = 'activities_event_data?evt_id=' + evt_id + '&type=' + type
                content = '<div id="lightgallery">{{lightgallery}}</div>';
            } else {
                content = data;
            }
            jQuery("#description_content").html(content);
            $("#lightgallery").load(link);
            
            if (type == 'photo' || type == 'deliverable') {
                $("#event_detail").hide()
            }
            else{
                $("#event_detail").show()
            }
            
        }
    });


    var Capitalize_type = 'Tab ' + type.charAt(0).toUpperCase() + type.slice(1);
    _paq.push(['trackEvent', 'Activities', Capitalize_type, 'Event ID: '+evt_id]);

}