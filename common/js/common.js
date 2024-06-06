var KICPadvertisement1 = 'Simply click the link below.%0a';
var KICPadvertisement2 = 'Enjoy!%0a';
var app_path = drupalSettings.path.baseUrl;

function module_item_delete(module, item_id) {

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
  let position = page_url.search('blog_delete');
  let delete_redirect_page = (document.getElementById('delete_redirect_page') && document.getElementById('delete_redirect_page').value!="")?document.getElementById('delete_redirect_page').value:"";
  console.log('delete_redirect_page: '+delete_redirect_page);

  jQuery("#delete-confirm").dialog({
    title: 'Delete '+ msgtitle,
    width: 400,
    height: 225,
    modal: true,
    buttons: {
      "OK": function () {
        if (module=="blog") {
          //jQuery.post(page_url, {function(){
            jQuery.post(page_url, function(data, status){
              jQuery('#delete-confirm').dialog('close');
              if (delete_redirect_page!="") {
                console.log("close dialog 1 Status : "+status );
                window.location.href=delete_redirect_page;      
              } else {
                console.log("close dialog 2 Status : "+status );
                window.location.reload();        
              }
            });
          } else {
          jQuery.post({url:page_url, success: function(result){
              jQuery('#delete-confirm').dialog('close');
              console.log("close dialog 3: "+result);
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
      url: app_path + 'add_like',
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
    selected_tag = selected_tag.replaceAll("&","%26");
    tags = selected_tag.split(';');
  } 
  console.log("selected_tag: "+ selected_tag);
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
  selected_tag = selected_tag.replaceAll("&","%26");
  thisTag = thisTag.replaceAll("&","%26");
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


function copyTextToClipboard(link="") {
  var txt = link==""?self.location.href:link;
  // creating new textarea element and giveing it id 't'
  let t = document.createElement('textarea');
  t.id = 't';
  // Optional step to make less noise in the page, if any!
  t.style.border = 0;
  t.style.height = 1;
  t.style.margin = 0;
  t.style.padding = 0;

  // You have to append it to your page somewhere, I chose <body>
  document.body.appendChild(t);
  // Copy whatever is in your div to our new textarea
  t.value = txt;

  // Now copy whatever inside the textarea to clipboard
  let selector = document.querySelector('#t');
  selector.select();
  var copySuccess = document.execCommand('copy');

  // Remove the textarea
  t.style.height = 0;

  return false;
}

function mailto(sub, body) {
  var txt = self.location.href;

  switch (sub) {
      case 0: //kicppedia
          sub = 'Your friend would like to share with you a KICPedia page in KICP';
          break;
      case 1: //taxonomy
          sub = 'Your friend would like to share with you a document in KICP';
          break;
      case 2: //KM
          sub = 'Your friend would like to share with you a KM activity in KICP';
          break;
      case 3: //Blog
          sub = 'Your friend would like to share with you a blog in KICP';
          break;
      case 4: //Blog Entry
          sub = 'Your friend would like to share with you a blog entry in KICP';
          break;
      case 5: //Forum
          sub = 'Your friend would like to share with you a forum in KICP';
          break;
      case 6: //News
          sub = 'Your friend would like to share with you an internal news in KICP';
          break;
      case 7: //Video
          sub = 'Your friend would like to share with you a video in KICP';
          break;
      case 8: //Survey
          sub = 'Your friend would like to share with you a survey in KICP';
          break;
      case 9: //Vote
          sub = 'Your friend would like to share with you a vote in KICP';
          break;
      case 10: //Bookmark
          sub = 'Your friend would like to share with you a bookmark in KICP';
          break;
      case 11: //PPC Activity
          sub = 'Your friend would like to share with you a PPC Activity in KICP';
          break;
      default:
          sub = sub;
  }
  msg = "The corresponding URL has been copied into the clipboard\n\nDo you also want to send this URL through mail e.g. Microsoft Outlook?";
  if (confirm(msg)) {
      window.location.href = "mailto:?Subject=" + sub + "&Body=" + KICPadvertisement1 + "%0a" + escape(txt) + "%0a%0a" + KICPadvertisement2 + "%0a";
  }
}


jQuery(document).ready(function(){
    jQuery('li.menu-item.menu-item-level-1:nth-child(6) a').attr('target', '_blank');
    jQuery('.menu:last-child').addClass( 'columns' );  
    jQuery("[data-drupal-link-system-path='disclaimer']").attr('target', '_blank');
    jQuery("[data-drupal-link-system-path='contact']").text("Contact Us");

});


