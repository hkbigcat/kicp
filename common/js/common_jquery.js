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
           //ChangeDisplayGroupList(module,record_id,'P');
           
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
    console.log("search_str: "+search_str+" module: "+module+" record_id: "+record_id);
    jQuery.ajax({
        type: 'POST',
        url: 'get_search_public_group',
        data: JSON.stringify({"search_str": search_str, "module": module, "record_id": record_id}),
        cache: false,
        success: function (data) {
            jQuery('#div_public_group').html(data);
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

function displayGroupMember(elmt, group_type, group_id) {
    // hide all elements of group member div
    jQuery('[id^="div_member_"]').css('display', 'none');
    jQuery.ajax({
        contentType: "application/json; charset=utf-8",
        type: "POST",
        url: 'get_group_member_div',
        data: JSON.stringify({"elmt_name": elmt, "group_type": group_type, "group_id": group_id}),
        cache: false,
        success: function (data)
        {
            var group_name = data['group_name'];
            var group_member = data['group_member'];
            
            // display the specific element
            jQuery( "<div id='div_member_"+ elmt +"' style='position:relative;' onClick='ShowHideDiv(\"div_member_"+elmt+"\")'><div class='group_member_list'><span class='group_close' title='Close' alt='Close'>x</span><div class='group_name' onClick='ShowHideDiv(\"div_member_"+elmt+"\")'>"+ group_name +"</div><div class='group_member'> "+ group_member + "</div></div></div>" ).insertAfter( jQuery('#'+elmt).closest('#group_member_link') );
        }
    });
        
}


function add_access_control(module, record_id, group_type, group_id) {
    
    jQuery("#add-access-control-confirm").dialog({
        title: 'Access Control',
        width: 400,
        height: 225,
        modal: true,
        buttons: {
            "OK": function () {
                jQuery.ajax({
                    type: 'POST',
                    url: 'access_control_add_action',
                    data: JSON.stringify({"this_module": module, "record_id": record_id, "group_type": group_type, "group_id": group_id}),
                    cache: false,
                    success: function (data) {
                        // reload group member UI (member list)
                        console.log(data);
                        reloadCurrentAccessControlGroup(module, record_id);

                    },
                    error: function (error) {
                        alert('Error: ' + error);
                    }
                });
                jQuery(this).dialog("close");
            },

            Cancel: function () {
                jQuery(this).dialog("close");
            }
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
            jQuery('#modal-body-left').html(data);
        },
        error: function (error) {
            alert('Error: ' + error);
        }
    });
}