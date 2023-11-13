var loadingImg = '<img src="modules/custom/common/images/loader.gif" border="0" width="40">';

function getAddGroupMemberUI(module, record_id) {
    jQuery("ul.menu").css("display","none");
    jQuery("#modal-body").html("");
    jQuery.ajax({
        type: "POST",
        url: 'get_add_group_member_ui',
        data: JSON.stringify({"module": module, "record_id": record_id}),
        cache: false,
        success: function (data)
        {
           jQuery("#add-record").html(data);
           ChangeDisplayGroupList(module,record_id,'P');
           
        }, error: function (error) {
            return "Fail to retrieve content, please try again later.";
        }
    }); 
}

function ChangeDisplayGroupList(module, record_id, group_type) {
    
    jQuery("#group_list").html(loadingImg);
    
    jQuery.ajax({
        type: "POST",
        url: 'get_add_group_member_group_type',
        data: JSON.stringify({"module":module,"record_id":record_id, "group_type": group_type}),
        cache: false,
        success: function (data)
        {
           jQuery("#group_list").html(data);
           
        }, error: function (error) {
            //alert("fail");
            return "Fail to retrieve content, please try again later.";
        }
    });
}

function reloadCurrentAccessControlGroup(module, record_id) {
    
    jQuery.ajax({
        type: 'POST',
        url: 'get_current_access_control_group',
        data: JSON.stringify({"this_module": module, "record_id": record_id}),
        cache: false,
        success: function (data) {
            //alert('data loaded');
            jQuery('#modal-body-left').html(data);
        },
        error: function (error) {
            alert('Error: ' + error);
        }
    });
}

function update_access_control_allow_edit(module, record_id, group_type, group_id, to_status) {
    jQuery.ajax({
        type: 'POST',
        url: 'update_access_control_allow_edit',
        data: JSON.stringify({"this_module": module, "record_id": record_id, "group_type": group_type, "group_id":  group_id, "to_status":to_status}),
        cache: false,
        success: function (data) {
            reloadCurrentAccessControlGroup(module, record_id);
        },
        error: function (error) {
            alert('Error: ' + error);
        }
    });
}

function get_search_public_group(search_str, module, record_id) {
    
    jQuery("#div_public_group").html(loadingImg);
    
    jQuery.ajax({
        type: 'POST',
        url: 'get_search_public_group',
        data: JSON.stringify({"search_str": search_str, "module": module, "record_id": record_id}),
        cache: false,
        success: function (data) {
            jQuery('#div_public_group').html(data);
           console.log(data);
        },
        error: function (error) {
            alert('Error: ' + error);
        }
    });
    
}

function key_press_search_public_group(evt){
    if (evt.keyCode == 13){
            jQuery('#loadBtn').click();
    }
}


function disableEditorFormat(){
    var editorList = jQuery('select.editor');
    for (var i = 0; i < editorList.length; i++) {
        var editorOptions = jQuery(editorList[i]).find('option');
        for(var j =0; j < editorOptions.length; j++){
            if (editorOptions[j].value != "full_html") {
               jQuery(editorOptions[j]).attr('disabled', 'disabled');
           }           
        }
    }
}