function ShowHideDiv(divname) {
    if (document.getElementById(divname).style.display == 'none') {
        document.getElementById(divname).style.display = 'block';
        document.getElementById("submenu_link_add_member").style.display = 'none';
        document.getElementById("submenu_link_rename").style.display = 'none';
        document.getElementById("submenu_link_delete").style.display = 'none';
    } else {
        document.getElementById(divname).style.display = 'none';
        document.getElementById("submenu_link_add_member").style.display = 'none';
        document.getElementById("submenu_link_rename").style.display = 'none';
        document.getElementById("submenu_link_delete").style.display = 'none';
    }
}

function ShowHideDivForSubgroup(divname, groupID, groupType) {
    if (document.getElementById(divname).style.display == 'none') {
        document.getElementById(divname).style.display = 'block';
        document.getElementById("submenu_link_add_member").style.display = 'block';
        document.getElementById("submenu_link_rename").style.display = 'block';
        document.getElementById("submenu_link_delete").style.display = 'block';
    } else {
        document.getElementById(divname).style.display = 'none';
    }

    document.getElementById("level_storage").value = "G";
    document.getElementById("group_id_storage").value = groupID;
    document.getElementById("group_type_storage").value = groupType;
}

function ShowHideDivForUser(userName, userID) {
    document.getElementById("submenu_link_add_group").style.display = 'none';
    document.getElementById("submenu_link_add_member").style.display = 'none';
    document.getElementById("submenu_link_rename").style.display = 'block';
    document.getElementById("submenu_link_delete").style.display = 'block';

    document.getElementById("level_storage").value = "M";
    document.getElementById("userName_storage").value = userName;
    document.getElementById("userID_storage").value = userID;
}

function getForm(type, uid) {
    // re-assign the active button
    jQuery("a[id^='submenu_link']").each(function (index, item) {
        jQuery(item).removeClass("active");
        jQuery("#submenu_link_" + type).addClass("active");
    });

    var level = document.getElementById("level_storage").value;
    var groupID = document.getElementById("group_id_storage").value;
    var groupType = document.getElementById("group_type_storage").value;
    var userName = document.getElementById("userName_storage").value;
    var userID = document.getElementById("userID_storage").value;

    jQuery.ajax({
        type: "POST",
        url: 'profile_group_maintenance_form_data',
        data: {
            uid: uid,
            type: type,
            level: level,
            groupID: groupID,
            groupType: groupType,
            userName: userName,
            userID: userID
        },
        success: function (data)
        {
            var content = "";
            content = data;
            jQuery("#subform").html(content);
        }
    });

}

function SubmitAddGroupForm() {
    var name_of_group = document.getElementById("name_of_group").value;
    var type_of_group = document.getElementsByName("type_of_group");
    var checkedTypeValue = "";
    for (var i = 0; i < type_of_group.length; i++) {
        if (type_of_group[i].checked) {
            checkedTypeValue = type_of_group[i].value;
        }
    }

    jQuery.ajax({
        type: "POST",
        url: 'profile_group_maintenance_add_group_submit',
        data: {
            name_of_group: name_of_group,
            type_of_group: checkedTypeValue
        },
        success: function ()
        {
            window.location.reload();
        }
    });
}

function SubmitRenameGroupForm() {
    var name_of_group = document.getElementById("name_of_group").value;
    var group_id = document.getElementById("group_id").value;
    var group_type = document.getElementById("group_type").value;

    jQuery.ajax({
        type: "POST",
        url: 'profile_group_maintenance_rename_submit',
        data: {
            name_of_group: name_of_group,
            group_id: group_id,
            group_type: group_type
        },
        success: function ()
        {
            window.location.reload();
        }
    });
}

function SubmitAddMemberForm() {

}

function SubmitRenameMemberForm() {
    var name_of_member = document.getElementById("name_of_member").value;
    var group_id = document.getElementById("group_id").value;
    var group_type = document.getElementById("group_type").value;
    var user_name = document.getElementById("user_name").value;
    var user_id = document.getElementById("user_id").value;

    jQuery.ajax({
        type: "POST",
        url: 'profile_group_maintenance_rename_submit',
        data: {
            name_of_member: name_of_member,
            group_id: group_id,
            group_type: group_type,
            user_name: user_name,
            user_id: user_id
        },
        success: function ()
        {
            window.location.reload();
        }
    });
}
    
function joinCopMember(cop_id, cop_name) {
    if(confirm("Are you sure to join \"" + cop_name + "\" COP?")) {
        jQuery.ajax({
            type: "POST",
            url: 'profile_join_cop_membership',
            data: {
                cop_id: cop_id,
                action: 'check'
            },
            success: function (data)
            {
                if(data == 'Y') {
                    // joined already, no further action
                    alert("You have already joined this COP.");
                } else {
                    // continue to join in
                    jQuery.ajax({
                        type: "POST",
                        url: 'profile_join_cop_membership',
                        data: {
                            cop_id: cop_id,
                            action: 'add'
                        },
                        success: function (data)
                        {
                            //alert('added');
                            reloadCopJoinMemberTable();
                        },
                        error: function (error) {
                            alert('error, ' + error.status + ':' + error.statusText);
                        }
                    });
                }
            },
            error: function (error) {
                alert('error, ' + error.status + ':' + error.statusText);
            }
        });
    }
}

function reloadCopJoinMemberTable() {
    jQuery.ajax({
        type: "POST",
        url: 'profile_reload_cop_membership',
        success: function (data)
        {
            jQuery(".CopJoinMemberTable").html(data);
        },
        error: function (error) {
            alert('error, ' + error.status + ':' + error.statusText);
        }
    });
}

//reloadCopJoinMemberTable();

function ProfileUpdateEmailNotify() {
    var forum_id_str = "";
    var delimitor = "";
    
    jQuery('input[id="forum_checkbox"]:checked').each(function() {
        forum_id_str += delimitor + this.value;
        delimitor = ',';
    });
    
    // update DB
    jQuery.ajax({
        type: "POST",
        url: 'profile_submit_email_notify',
        data:{'forum_id_str': forum_id_str},
        success: function (data)
        {
            //alert('Information updated.'+'|'+data);
            jQuery("#update-complete").dialog({
                            title: 'Email notification Service',
                            width: 400,
                            height: 225,
                            modal: true,
                            buttons: {
                                "OK": function () {
                                    jQuery(this).dialog("close");
                                }
                            }
                        });
        },
        error: function (error) {
            alert('error, ' + error.status + ':' + error.statusText);
        }
    });
}