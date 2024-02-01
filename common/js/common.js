function module_item_delete(module, item_id) {
  var page_url = "/"+module + "_delete/"+item_id;

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
    case 'blog_elegate':
        msgtitle = 'Blog delegrate users';
      break;          
    case 'activities_item':
        msgtitle = 'KM Activities';
      break;          
    case 'video_event':
        msgtitle = 'Video Event';
      break;          
    case 'video':
        msgtitle = 'Video';
      break;       
    case 'video_event_privilege':
       msgtitle = 'Group';
       page_url = "/"+module + "_delete/"+item_id.replace(/,/g, "/");
      break;     
    case 'profile_group':
       msgtitle = 'Profile Group';
       page_url = "/"+module + "_delete/"+item_id.replace(/,/g, "/");
    break;     
    case 'profile_group_member':
       msgtitle = 'Profile Group Member';
       page_url = "/"+module + "_delete/"+item_id.replace(/,/g, "/");
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
  var selected_tag = document.getElementById('selected_tag').value;
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


if (jQuery) {
  console.log("jquery is loaded");
} else {
  console.log("Not loaded");
}

