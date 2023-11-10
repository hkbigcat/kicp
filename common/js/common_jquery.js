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