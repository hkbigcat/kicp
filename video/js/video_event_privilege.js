/**
 * 
 */
jQuery(document).ready(function ($) {
	$('input#edit-privilege-group-name').focus();
	
	// load name list
	var ModulePath = document.getElementById("module_path").textContent;
	//var type = document.getElementById("type").textContent;
	var type='';
	
	var name_list = document.getElementById('edit-privilege-group-name');
	var userid_list = document.getElementById('edit-privilege-group-id');
	

	if (typeof name_list !== undefined && name_list !== null) {
		var names = [];
		if (name_list.value != '') {
			names = name_list.value.split(';');
			for (i = 0; i < names.length; i++) {
				names[i] = names[i].trim();
			}
		}
		var userids = [];
		if (userid_list.value != '') {
			userids = userid_list.value.split(',');
			for (i = 0; i < userids.length; i++) {
				userids[i] = userids[i].trim();
			}
		}

		var memberID_prefix = "membername";
		var delbtnID_prefix = "del_btn";
		var separator_prefix = "separator";
		for (i = 0; i < names.length; i++) {
			field_id_next = i;
			thisName = names[i].trim();
			thisUserId = userids[i].trim();
			var memberName =
				"<div id='member" + field_id_next + "' style='float:left; padding:5px 0 5px 0'>" +
				"<span id='" + memberID_prefix + field_id_next + "' title='" + thisUserId + "' memberID='" + thisUserId + "' >" + thisName + "</span>&nbsp;" +
				"<span id='" + delbtnID_prefix + field_id_next + "' class='button' onclick=\"delName('" + field_id_next + "', '" + thisUserId +
				"')\"  title='Delete'><img src='" + ModulePath + "/core/misc/icons/000000/ex.svg' /></span>" +
				"<span id='" + separator_prefix + field_id_next + "' >&emsp;</span>" +
				"</div>";
			jQuery("#dummy").before(memberName);
		}
	}

})

field_id_next = 1;

function addName(ele, type) {
// called by GroupMemberPublicMaint.php
	var ModulePath = document.getElementById("module_path").textContent;
	var thisName = ele.innerHTML;
	var thisUserId = ele.title;

	// save names to textbox
	var memberID_prefix = "membername";
	var delbtnID_prefix = "del_btn";
	var separator_prefix = "separator";

	var memberName =
		"<div id='member" + field_id_next + "' style='float:left; padding:5px 0 5px 0'>" +
		"<span id='" + memberID_prefix + field_id_next + "' title='" + thisUserId + "' memberID='" + thisUserId + "' >" + thisName + "</span>&nbsp;" +
		"<span id='" + delbtnID_prefix + field_id_next + "' class='button' onclick=\"delName('" + field_id_next + "', '" + thisUserId +
		"')\"  title='Delete'><img src='" + ModulePath + "/core/misc/icons/000000/ex.svg' /></span>" +
		"<span id='" + separator_prefix + field_id_next + "' >&emsp;</span>" +
		"</div>";
	jQuery("#dummy").before(memberName);
	field_id_next++;

	// load names into array
	var name_list = document.getElementById('edit-privilege-group-name');
	
	var names = [];
	if (name_list.value != '') {
		names = name_list.value.split(';');
		for (i = 0; i < names.length; i++) {
			names[i] = names[i].trim();
		}
	}
	var x = names.indexOf(thisName);

	// load user id. into array
	var userid_list = document.getElementById('edit-privilege-group-id');
	
	var userids = [];
	if (userid_list.value != '') {
		userids = userid_list.value.split(',');
		for (i = 0; i < userids.length; i++) {
			userids[i] = userids[i].trim();
		}
	}

	if (x == -1) {
		// If name is not listed, add it
		names[names.length] = thisName;
		ele.className = 'selected';
		userids[userids.length] = thisUserId;
	}

	// save names to textbox
	name_list = names.join('; ');
	document.getElementById('edit-privilege-group-name').value = name_list;
	document.getElementById('edit-privilege-group-name').focus();
	document.getElementById('edit-privilege-group-id').value = userids;
	
}

function delName(id, user_id) {
	jQuery("#member" + id).remove();

	var names = [];
	var userids = [];
	i = 0;
	jQuery("span[id^='membername']").each(function () {
		names[i] = jQuery(this).text();
		userids[i] = jQuery(this).attr("memberID");
		i++;
	});

	user_id = 'a_' + user_id;
	ele = document.getElementById(user_id);
	if (typeof ele !== undefined && ele !== null) {
		ele.className = 'unselected';
	}

	// save names to textbox
	name_list = names.join('; ');
	document.getElementById('edit-privilege-group-name').value = name_list;
	document.getElementById('edit-privilege-group-name').focus();
	document.getElementById('edit-privilege-group-id').value = userids;
	

}
