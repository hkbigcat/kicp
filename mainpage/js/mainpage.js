console.log("mainpage js");
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
