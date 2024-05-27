function LoadMoreDescription(this_id) {
    
    // hide the short description
    jQuery("#DivDescription_"+this_id).hide();
    // display full description
    jQuery("#DivFullDescription_"+this_id).show();
    
}

// in "common/common_jquery.js"
//reloadAggregatedTag();

function reloadAggregatedTag() {
    
    jQuery.ajax({
        type: "POST",
        url: 'aggregated_tag_reload',
        success: function (data)
        {
            jQuery('#DivAggregatedTagDetail').html(data);
            //alert(data);
        }
    });
}

function getFollow(choices) {
    var click_follow =  jQuery("#follow_list").css( "display" );
    var current = jQuery("#choices").text();
    if (click_follow == "none" || choices != current) {        
        jQuery.ajax({
            type: "POST",
            url: 'get_follow',
            data: JSON.stringify({"choices": choices}),
            cache: false,
            success: function (data)
            {
            jQuery("#follow_list").html(data);
            
            }, error: function (error) {
                return "Fail to retrieve content, please try again later.";
            }
        }); 
        jQuery("#follow_list").css("display","block");
    } else {
        closeFollowModal();
    }
}

function closeFollowModal() {
    var current = jQuery("#choices").text();
    jQuery.ajax({
        type: "POST",
        url: 'get_follow_no',
        cache: false,
        success: function (data)
        {
        jQuery("#followed").text(data);
        
        }, error: function (error) {
            return "Fail to retrieve content, please try again later.";
        }
    }); 
    jQuery("#follow_list").css("display","none");
    jQuery("#follow_list").html("");    
}


jQuery(document).ready(function ($) {
    jQuery("#block-thex-kicp-main-menu li.menu-item.menu-item-level-1:first-child").addClass("active");
  })