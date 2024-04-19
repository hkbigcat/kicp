function getAddGroupMemberUI(module, record_id) {
    console.log("module: "+module);
    jQuery("ul.menu").css("display","none");
    jQuery("#modal-body").html("");
    jQuery.ajax({
        type: "POST",
        url: app_path + 'get_add_group_member_ui',
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
    console.log("get search - search_str: "+search_str+" module: "+module+" record_id: "+record_id);
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


function disableEditormat(){
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

                console.log("add access control - this module: "+module+" record_id: "+record_id + " group_type: "+group_type + " group_id: "+group_id );

                jQuery.ajax({
                    type: 'POST',
                    url: 'access_control_add_action',
                    data: JSON.stringify({"this_module": module, "record_id": record_id, "group_type": group_type, "group_id": group_id}),
                    cache: false,
                    success: function (data) {
                        // reload group member UI (member list)
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

function delete_access_control(module, record_id, group_type, group_id) {
    
    jQuery("#delete-access-control-confirm").dialog({
        title: 'Access Control',
        width: 400,
        height: 225,
        modal: true,
        buttons: {
            "OK": function () {
                jQuery.ajax({
                    type: 'POST',
                    url: 'access_control_delete_action',
                    data: JSON.stringify({"this_module": module, "record_id": record_id, "group_type": group_type, "group_id": group_id}),
                    cache: false,
                    success: function (data) {
                        // reload group member UI (member list)
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
    
    console.log("reload current group - module: "+module+" record_id: "+record_id );

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




function cpRateShow(x,y, rateID) {

    const Wording = ['N/A', 'Poor', 'Nothing special', 'Worth watching', 'Pretty cool', 'Excellent'];

    for (i = 1; i <= 5; i++) {
        
        if (i <= x)
            document.getElementById('star_'+i+'_'+rateID).style.backgroundImage = "url("+app_path+"modules/custom/common/images/star-solid.svg)";
        else
        if (x + 1 - i >= 0.75)
            document.getElementById('star_'+i+'_'+rateID).style.backgroundImage = "url("+app_path+"modules/custom/common/images/star-solid.svg)";
        else
        if (x + 1 - i >= 0.25)
            document.getElementById('star_'+i+'_'+rateID).style.backgroundImage = "url("+app_path+"modules/custom/common/images/star-half-solid.svg)";
        else
            document.getElementById('star_'+i+'_'+rateID).style.backgroundImage = "url("+app_path+"modules/custom/common/images/star-regular.svg)";
    }

    if (y!=1) {
        document.getElementById('cpRateWording_'+rateID).innerHTML = Wording[x];
    } else {
        document.getElementById('cpRateWording_'+rateID).innerHTML = "";
    }

        //return 1;
}

function cpRateShowBox(rateId, userId, rating, divName, module, type) {
    console.log("cpRateShowBox");
    var newRateDiv = newDisplayBox('newRateDiv', divName.parentNode);
    try {
        xmlHttp = getXmlHttpObject(newRateDiv, 1);
        var url = app_path + 'cpProcess';
        

        jQuery.ajax({
            type: 'POST',
            url: url,
            contentType: "application/json; charset=utf-8",
            data: JSON.stringify({"module": module, "type": type, "rateId": rateId, "userId": userId, "rating": rating}),
            dataType: "html",
            cache: false,
            success: function (data, status, xhr) {
                document.getElementById('newRateDiv').innerHTML = data;
            },
            error: function (error) {
                alert('error, ' + error.status + ':' + error.statusText);
            }
        });

    } catch (e) {
        alert(getMessage(10109));
    }
}

function newDisplayBox(boxName, pNode) {
    if (pNode == undefined) {
        pNode = document.body;
    } else {
        pNode = pNode.parentNode;
    }
    var layerBox = document.getElementById(boxName);
    if (layerBox != undefined) {
        layerBox.parentNode.removeChild(layerBox);
    }
    layerBox = createEle(
            'div',
            {'id': boxName, 'class': 'acBox'},
            {margin: '0 auto', border: '3px solid #808080', padding: '3px 3px 3px 3px', backgroundColor: '#FFFFFF'},
            ''
            );
    layerBox.style.zIndex = 10000;
    layerBox.style.position = 'absolute';
    pNode.appendChild(layerBox);
    layerBox.style.visibility = 'visible';

    return layerBox;
}

createEle = function (t, a, y, x) {
    var e = document.createElement(t);
    if (a) {
        for (var k in a) {
            if (k == 'class')
                e.className = a[k];
            else if (k == 'id')
                e.id = a[k];
            else
                e.setAttribute(k, a[k]);
        }
    }
    if (y) {
        for (var k in y)
            e.style[k] = y[k];
    }
    if (x) {
        e.appendChild(document.createTextNode(x));
    }
    return e;
}

function getXmlHttpObject(handler, type) {
    var objXmlHttp = null;
    if (navigator.userAgent.indexOf('Opera') >= 0) {
        alert(getMessage(10106));
        return null;
    }
    if (navigator.userAgent.indexOf('MSIE') >= 0) {
        var strName = 'Msxml2.XMLHTTP';

        if (navigator.appVersion.indexOf('MSIE 5.5') >= 0) {
            strName = 'Microsoft.XMLHTTP';
        }
        try {
            objXmlHttp = new ActiveXObject(strName);
        } catch (e) {
            alert(getMessage(10108));
            return null;
        }
    } else {
        if (navigator.userAgent.indexOf('Mozilla') >= 0)
        {
            objXmlHttp = new XMLHttpRequest();
        }
    }

    if (objXmlHttp != null) {
        if (type) {
            objXmlHttp.onreadystatechange = function () {
                ajaxDivHandler(objXmlHttp, handler)
            };
        } else {
            objXmlHttp.onreadystatechange = handler;
        }
        return objXmlHttp;
    } else {
        alert(getMessage(10105));
        return n
    }
}

function ajaxDivHandler(xmlHttp, divName) {
    if (xmlHttp.readyState == 4 || xmlHttp.readyState == 'complete') {
        if (!checkXmlHttpStatus(xmlHttp.status)) {
            document.getElementById('loading').style.visibility = 'hidden';
            return;
        }
        var reptext = xmlHttp.responseText;
        if (reptext.match('error:')) {
            alert(reptext);
        } else if (reptext.match('redirect::')) {
            ary = reptext.split("::");
            window.location = ary[1];
        } else if (reptext.match('alert::')) {
            ary = reptext.split("::");
            alert(ary[1]);
        } else if (reptext.match('js::')) {
            ary = reptext.split("::");
            eval(ary[1]);
        } else {
            if (typeof (divName) == "object")
                divName.innerHTML = reptext;
            else
                document.getElementById(divName).innerHTML = reptext;
        }
    }
}

function cpRating(rateId, userId, rating, divName, module, type) {

    if (module != null && rateId != null) {
        //var ModulePath = document.getElementById("module_path").textContent;
        var url =  app_path + 'updateCpRate';
        console.log*(url);
        jQuery.ajax({
            type: 'POST',
            url: url,
            contentType: "application/json; charset=utf-8",
            data: JSON.stringify({"module": module, "type": type, "id": rateId, "user": userId, "rating": rating}),
            dataType: "json",
            cache: false,
            success: function (data, status, xhr) {
                showResult(module, data.result);
                var x = 'cpRate_' + module + '_' + rateId;
                console.log(x);
                console.log(data.ratingpic);
                document.getElementById(x).innerHTML = data.ratingpic;
            },
            error: function (error) {
                jQuery("<div id='addrating-error'>Error, status:" + error.status + ", text:" + error.statusText + " </div>").appendTo("body");
                jQuery("#addrating-error").dialog({
                    title: 'Rating Error',
                    width: 400,
                    height: 225,
                    modal: true,
                    buttons: {
                        "OK": function () {
                            page_url = app_path + "/" + module;
                            if (type != '') {
                                page_url = page_url + "?type=" + type;
                            }
                            window.open(page_url, "_self");
                        }
                    }
                });
            }
        });
    }
}


function showResult(module, result) {
    var x = result.toString();
    var msg = null;
    switch (x) {
        case '0':
            msg = 'Error, rating is not added.';
            break;
        case '1':
            msg = 'Rating is submitted.';
            break;
        case '2':
            msg = 'Rating was already added.';
            break;
        default:
    }

    var msgtitle = '';
    
    switch (module) {
        case 'bookmark':
            msgtitle = 'Bookmark';
            break;
        case 'ppc':
            msgtitle = 'PPC publication';
            break;
        case 'blog':
            msgtitle = 'Blog';
            break;
        case 'video':
            msgtitle = 'Video';
            break;
        case 'fileshare':
            msgtitle = 'File Share';
            break;
        default:
            msgtitle = module;
    }

    var message = jQuery('<div id="dialog">' + msg + "</div>").appendTo('body');
    Drupal.dialog(message, {
        title: msgtitle,
        width: 400,
        height: 225,
        buttons: [
            {
                text: "Close",
                click: function () {
                    jQuery(this).dialog("close");
                }
            }
        ]
    }).showModal();
}

function updateFollowUserStatus(this_user_id, status, this_elem) {
    let tmp = this_elem.parentElement;
    //console.log("tmp : "+tmp.innerHTML);
    jQuery.ajax({
        type: "POST",
        url: app_path+'update_follow_status',
        data: {
            contributor_id: this_user_id,
            status: status
        },
        success: function (data)
        {
           console.log("update follow status : "+data.following);
           tmp.innerHTML = data.following;
        }
    });
} 



