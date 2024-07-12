jQuery(document).ready(function(){
    var userName = drupalSettings.userName;
      jQuery("#edit-name").html('<label for="edit-name">Your name</label>\n' + userName);
});
