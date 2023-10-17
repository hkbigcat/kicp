function addTag(ele) {
    var thisTag = ele.innerHTML;
    thisTag = thisTag.replace('&amp;', '&');
    var tag_list = document.getElementById('edit-tags');
    var tags = [];
    
    console.log('tag list: ('+tag_list.value+')');        

    if (tag_list.value != '') {
        tag_list.value = tag_list.value.replace('&amp;', '&');
        tags = tag_list.value.split(';');
        for (i=0; i<tags.length; i++) {
            tags[i] = tags[i].trim();
        }
    }
    

    var x = tags.indexOf(thisTag);

    if (x != -1) {
        // If tag is already listed, remove it
        for( var k = tags.length-1;  k>=0; k--){
            if ( tags[k] === thisTag) {
                tags.splice(k, 1);
            }
        }
        ele.className = 'unselected';
    } else {
            // Otherwise add it
            tags[tags.length] = thisTag;
            ele.className = 'selected';
    }

    document.getElementById('edit-tags').value = tags.join(';');
    document.getElementById('edit-tags').focus();
}
