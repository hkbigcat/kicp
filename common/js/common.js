function module_item_delete(module, item_id) {

  const currentUrl = window.location.href;
  console.log("currentUrl: "+currentUrl);
  var page_url = app_path + module + "_delete/"+item_id;

  var msgtitle = '';  

  switch (module) {
    case 'fileshare':
      msgtitle = 'File Share';
      break;
    case 'fileshare_folder':
        msgtitle = 'File Share Folder';
      break;
    case 'blog':
        msgtitle = 'Blog';
      break;          
    case 'blog_delegate':
        msgtitle = 'Blog delegrate users';
      break;          
    case 'activities_item':
        msgtitle = 'KM Activities';
      break;          
    case 'activities_list':
        msgtitle = 'KM Activities';
      break;          
    case 'activities_category':
        msgtitle = 'KM Activities';
      break;          
    case 'activities_cop':
        msgtitle = 'KM Activities';
      break;          
    case 'activities_enroll':
        msgtitle = 'KM Activities';
        page_url = "../"+module + "_delete/"+item_id.replace(/,/g, "/");
      break;          
    case 'activities_photo':
        msgtitle = 'KM Activities Photo';
      break;
    case 'activities_deliverable':
        msgtitle = 'KM Activities Deliverable';
      break;
    case 'ppcactivities_item':
        msgtitle = 'PPC Activities';
      break;          
    case 'ppcactivities_category':
        msgtitle = 'PPC Activities';
      break;          
    case 'ppcactivities_list':
        msgtitle = 'PPC Activities';
      break;          
    case 'ppcactivities_enroll':
        msgtitle = 'PPC Activities';
        page_url = "../"+module + "_delete/"+item_id.replace(/,/g, "/");
      break;          
    case 'ppcactivities_photo':
        msgtitle = 'PPC Activities Photo';
      break;
    case 'ppcactivities_deliverable':
        msgtitle = 'PPC Activities Deliverable';
      break;             
    case 'video_event':
        msgtitle = 'Video Event';
      break;          
    case 'video':
        msgtitle = 'Video';
      break;       
    case 'video_event_privilege':
       msgtitle = 'Group';
       page_url = "../"+module + "_delete/"+item_id.replace(/,/g, "/");
      break;     
    case 'survey':
        msgtitle = 'Survey';
      break;       
    case 'profile_group':
       msgtitle = 'Profile Group';
       page_url = module + "_delete/"+item_id.replace(/,/g, "/");
    break;     
    case 'profile_group_member':
       msgtitle = 'Profile Group Member';
       page_url = "../../"+module + "_delete/"+item_id.replace(/,/g, "/");
    break;     
    default:
      msgtitle = module;        
  }

  console.log('msgtitle: '+msgtitle);
  console.log('module: '+module);
  console.log('page_url: '+page_url);


  jQuery("#delete-confirm").dialog({
    title: 'Delete '+ msgtitle,
    width: 400,
    height: 225,
    modal: true,
    buttons: {
      "OK": function () {

        if (module=="blog") {
          jQuery.post(page_url, {function(result){
              jQuery('#delete-confirm').dialog('close');
              console.log("close dialog: "+result);
              window.location.reload();      
            }});
          } else {
        jQuery.post({url:page_url, success: function(result){
            jQuery('#delete-confirm').dialog('close');
            console.log("close dialog: "+result);
            window.location.reload();      
          }});

          } 
      },

      Cancel: function () {
          console.log( "You canceled!");
          jQuery(this).dialog("close");
      }
    }
  });

}


function AddLike(module, record_id) {

  jQuery.ajax({
      url: '/add_like',
      data: {
          module: module,
          record_id: record_id
      },
      success: function (data)
      {
          jQuery("#like_div_" + record_id).html(data.data);
          console.log("data "+data);
      },
      error: function (data) {

      }
  });
}


function addTagUrl(thisTag) {

  var selected_tag = "";
  if (document.getElementById("selected_tag")) 
    selected_tag = document.getElementById('selected_tag').value;
      
  let tag_page = document.getElementById('tag_page').value;
  

  tags = [];
  if (selected_tag != "") {
    selected_tag.replaceAll("&","%26");
    tags = selected_tag.split(';');
  } 
  if (thisTag != "" && !tags.includes(thisTag)) {
    tags.push(thisTag.replaceAll("&","%26"));
  } 
  
  console.log("Tags: "+ JSON.stringify(tags));

  self.location.href=tag_page+'?tags='+JSON.stringify(tags);

}


function RemovetagUrl(thisTag) {


  let tag_page = document.getElementById('tag_page').value;

  var selected_tag = document.getElementById('selected_tag').value;
  let  notag_page=(document.getElementById('notag_page') && document.getElementById('notag_page').value !="")?document.getElementById('notag_page').value:tag_page;
  console.log ("notag_page: "+notag_page);

  
  if (selected_tag == "") return;
  let tags = selected_tag.split(';');
  
  var newtag = [];
  j=0;
  for (let i = 0; i < tags.length; i++) {
    if (tags[i] != thisTag) {
      newtag[j++] = tags[i];
    }

  }
  console.log("Tags: "+ JSON.stringify(newtag));

  if (j==0) {
    self.location.href=notag_page;  
  } else {
    self.location.href=tag_page+'?tags='+JSON.stringify(newtag);
  }
}

// remove existing attachment
function RemoveAttachment(this_id) {
  var div_id = 'DivEntryAttach' + this_id;
  var delimitor = "";

  var elem = document.getElementById(div_id);
  elem.parentNode.removeChild(elem);

  if (document.getElementsByName('delete_doc_id')[0].value != "") {
      delimitor = ",";
  }

  document.getElementsByName('upload_files')[0].value --;
  console.log(document.getElementsByName('upload_files')[0].value);
  document.getElementsByName('delete_doc_id')[0].value += delimitor + this_id;
  console.log ("delete_doc_id: "+document.getElementsByName('delete_doc_id')[0].value);
  return document.getElementsByName('upload_files')[0].value;

}


function loadMoreTag(module) {

  var current_no_of_loaded_tag = parseInt(jQuery("#current_no_of_loaded_tag").val());
  var total_tag = parseInt(jQuery("#total_tag").val());

  if (current_no_of_loaded_tag != 'undefined' && current_no_of_loaded_tag >= 90) {
      var interval = 9999999999;
  } else {
      var interval = parseInt(jQuery("#default_interval").val());
  }

  // get more tag
  jQuery.ajax({
      type: "POST",
      url: 'load_more_tag',
      data: {
          module: module,
          current_no_of_loaded_tag: current_no_of_loaded_tag,
          interval: interval
      },
      success: function (data)
      {
          // load 20 more tags
          current_no_of_loaded_tag += parseInt(interval);
          // assign the no. of loaded tag to hidden field
          jQuery("#current_no_of_loaded_tag").val(current_no_of_loaded_tag);
          //load data
          jQuery("#DivLoadMore").before(data);
          // hide the "more" icon when reach the total no. of records
          if (current_no_of_loaded_tag >= total_tag) {
              jQuery("#IconLoadMore").hide();
          }
          if (current_no_of_loaded_tag >= 90) {
              jQuery('#textMore').html('all');
          }
      },
      error: function (data) {
          alert("No more tag");
      }
  });

}

function checkAll(cb, which) {
  var cb_list = document.getElementsByName(which);
  for (i = 0; i < cb_list.length; i++) {
      cb_list[i].checked = cb.checked;
  }
}


var customCalendar = null;
var outputTxt = null;
var imageObject = null;

function getCalendarDate(c_year, c_month, c_day) {
    document.getElementById(outputTxt).value = c_year + '-' + LZ(c_month) + '-' + LZ(c_day);
}

function createCalendar(imgObj) {
    
  console.log("customCalendar");
    customCalendar = new CalendarPopup('calendarDiv');
    customCalendar.showNavigationDropdowns();
    customCalendar.setReturnFunction('getCalendarDate');
    customCalendar.setCssPrefix("TEST");
    console.log("customCalendar "+customCalendar);

    imageObject = imgObj;
    txtObj = null;
    if (imgObj.id == "CalExpiry")
        txtObj = document.getElementById("edit-expirydate");
    else if (imgObj.id == "CalStart")
        txtObj = document.getElementById("edit-startdate");
    if (txtObj.value != '') {
        var dateParts = txtObj.value.split(".");
        customCalendar.select(document.getElementById(outputTxt), 'calendarLink', 'MM/dd/yyyy', dateParts[1] + '/' + dateParts[0] + '/' + dateParts[2]);
    }
}


function showCalendar() {
    customCalendar.showCalendar('calendarLink');
}

function changeOutput(str) {
    outputTxt = str;
}


function checkDate() {
    var startDate = new Date();
    var expiryDate = new Date();
    var now = new Date();

    var strStartDate = document.getElementById('startdate').value;
    var strExpiryDate = document.getElementById('expirydate').value;
    var startParts = strStartDate.split(".");
    var expiryParts = strExpiryDate.split(".");
    if (!startParts[0] || !startParts[1] || !startParts[2]) {
        alert("Please enter the start date before submission.");
        return false;
    }
    if (!expiryParts[0] || !expiryParts[1] || !expiryParts[2]) {
        alert("Please enter the expiry date before submission.");
        return false;
    }

    startDate.setFullYear(startParts[2], (startParts[1] - 1), startParts[0]);
    expiryDate.setFullYear(expiryParts[2], (expiryParts[1] - 1), expiryParts[0]);

    var eleDate = document.getElementById("sDate");
    if (eleDate == null) {
        if (now > startDate) {
            alert("Start Date should not be earlier than today.");
            return false;
        }
    } else {
        if (now > startDate && strStartDate != eleDate.value) {
            alert("Start Date should not be earlier than today.");
            return false;
        }
    }

    if (now > expiryDate) {
        alert("End Date should not be earlier than today.");
        return false;
    }
    if (startDate > expiryDate) {
        alert("The Start Date should't be later than the End Date.");
        return false;
    }
    return true;
}



jQuery(document).ready(function(){
    jQuery('li.menu-item.menu-item-level-1:nth-child(7) a').attr('target', '_blank');
});