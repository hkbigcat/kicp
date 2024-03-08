function module_item_delete(module, item_id) {
  var page_url = module + "_delete/"+item_id;

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
        page_url = "../"+module + "_delete/"+item_id;
      break;          
    case 'blog_elegate':
        msgtitle = 'Blog delegrate users';
      break;          
    case 'activities_item':
        msgtitle = 'KM Activities';
        page_url = "../"+module + "_delete/"+item_id;
      break;          
    case 'activities_enroll':
        msgtitle = 'KM Activities';
        page_url = "../"+module + "_delete/"+item_id.replace(/,/g, "/");
      break;          
    case 'activities_photo':
        msgtitle = 'KM Activities Photo';
        page_url = "../"+module + "_delete/"+item_id;
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
    case 'profile_group':
       msgtitle = 'Profile Group';
       page_url = "../"+module + "_delete/"+item_id.replace(/,/g, "/");
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
        jQuery.post({url:page_url, success: function(result){
              console.log(page_url);      
              jQuery('#delete-confirm').dialog('close');
              console.log("close dialog");
              window.location.reload();      
            }});
            
            
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


if (jQuery) {
  console.log("common.js jquery is loaded");
} else {
  console.log("common.js  Not loaded");
}



