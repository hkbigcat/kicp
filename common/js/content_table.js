jQuery(document).ready(function() {
    
    var emt = document.getElementsByName('search_str')[0];
    
   
        
    jQuery('#search_str').keydown(function(event) {
        if (event.keyCode == 13) {
            this.form.page.value = 0;
            this.form.submit();            
            return false;
         }
    });
    
    jQuery('#blog_search_str').keydown(function(event) {
        if (event.keyCode == 13) {
            this.form.submit();            
            return false;
         }
    });

    jQuery("#add_search_form").submit(function(){
        alert("user ID: "+ $("#delegate_user_id").val());
      });        

    
});

  
function addDelegate(form,userID)
{
    let search_str =  jQuery('#search_str').val();
    if (userID && userID != "") {
        console.log ("user ID: "+userID);  

        jQuery("#delegate_user_id").val(userID);

        console.log( "hidden value: "+jQuery("#delegate_user_id").val());

        form.submit(function(){console.log("OK")});

    } else {
      alert("NO USERID" + document.getElementsByID('delegate_user_id').valueD);

    }
    

}


function updateQueryStringParameter(uri, key, value) {
    var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
    var separator = uri.indexOf('?') !== -1 ? "&" : "?";

    if (uri.match(re)) {
        return uri.replace(re, '$1' + key + "=" + value + '$2');
    } else {
        return uri + separator + key + "=" + value;
    }
}

function dateSort(module_page) {
    var url = document.getElementById("domain_path").textContent;	// e.g. "https://www.abc.com"
    var path_location = document.getElementById("path_location").textContent;	// e.g. "/index?param1=AB&pararm2=BB"
    var selected_sortorder = document.getElementById("selected_sortorder").value;

    var current_sortkey = jQuery("#sortkey").val();
    
    url = path_location;

    var x = document.getElementById("sortDate").value;

    // replace parameter in query string
    var new_sortorder = (current_sortkey != "D") ? 'D' : (selected_sortorder == 'D' ? 'A' : 'D');

    url = updateQueryStringParameter(url, 'sortkey', 'D');
    url = updateQueryStringParameter(url, 'sortorder', new_sortorder);
   
   window.open(url, "_self");
}

function titleSort(module_page) {
    var url = document.getElementById("domain_path").textContent;	// e.g. "https://www.abc.com"
    var path_location = document.getElementById("path_location").textContent;	// e.g. "/index?param1=AB&pararm2=BB"
    var selected_sortorder = document.getElementById("selected_sortorder").value;
    
    var current_sortkey = jQuery("#sortkey").val();
    
    url = path_location;
    
    var x = document.getElementById("sortTitle").value;

    // replace parameter in query string
    var new_sortorder = (current_sortkey != "T") ? 'A' : (selected_sortorder == 'D' ? 'A' : 'D');
    
    url = updateQueryStringParameter(url, 'sortkey', 'T');
    url = updateQueryStringParameter(url, 'sortorder', new_sortorder);

    window.open(url, "_self");
}

function ratingSort(module_page) {
    var url = document.getElementById("domain_path").textContent;	// e.g. "https://www.abc.com"
    var path_location = document.getElementById("path_location").textContent;	// e.g. "/index?param1=AB&pararm2=BB"
    var selected_sortorder = document.getElementById("selected_sortorder").value;

    var current_sortkey = jQuery("#sortkey").val();

    url = path_location;

    var x = document.getElementById("sortRating").value;

    // replace parameter in query string
    var new_sortorder = (current_sortkey != "R") ? 'D' : (selected_sortorder == 'A' ? 'D' : 'A');
    url = updateQueryStringParameter(url, 'sortkey', 'R');
    url = updateQueryStringParameter(url, 'sortorder', new_sortorder);
   
    window.open(url, "_self");
}
function likeSort(module_page) {
    var url = document.getElementById("domain_path").textContent;	// e.g. "https://www.abc.com"
    var path_location = document.getElementById("path_location").textContent;	// e.g. "/index?param1=AB&pararm2=BB"
    var selected_sortorder = document.getElementById("selected_sortorder").value;

    var current_sortkey = jQuery("#sortkey").val();

    url = path_location;

    var x = document.getElementById("sortLike").value;

    // replace parameter in query string
    var new_sortorder = (current_sortkey != "L") ? 'D' : (selected_sortorder == 'A' ? 'D' : 'A');
    url = updateQueryStringParameter(url, 'sortkey', 'L');
    url = updateQueryStringParameter(url, 'sortorder', new_sortorder);
   
    window.open(url, "_self");
}
function reviewSort(module_page) {
    var url = document.getElementById("domain_path").textContent;	// e.g. "https://www.abc.com"
    var path_location = document.getElementById("path_location").textContent;	// e.g. "/index?param1=AB&pararm2=BB"
    var selected_sortorder = document.getElementById("selected_sortorder").value;
    
    var current_sortkey = jQuery("#sortkey").val();

    url = path_location;

    var x = document.getElementById("sortReview").value;

    // replace parameter in query string
    var new_sortorder = (current_sortkey != "Re") ? 'D' : (selected_sortorder == 'A' ? 'D' : 'A');
    url = updateQueryStringParameter(url, 'sortkey', 'Re');
    url = updateQueryStringParameter(url, 'sortorder', new_sortorder);

    window.open(url, "_self");
}