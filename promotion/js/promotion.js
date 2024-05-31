function scrollhash(h) {
  hpo = document.getElementById(h);
	  hpo.scrollIntoView();
}


function resetbtn() {
  window.location.href="/kicp/collections_rank";

}

function formsubmit() {
  var x = document.getElementById("search_str").value;
  console("searchstr: "+x);
  if (x!="") return true; else return false;
}

function popupNewsletter(dateNum) {

let params = 'scrollbars=no,resizable=no,status=no,location=no,toolbar=no,menubar=no, width=0,height=0,left=-1000,top=-1000';

open('http://dp2.ogcio.ccgo.hksarg/home/hrm/html/newsletter/'+dateNum+'/'+dateNum+'.pdf', 'newsletter', params);

}


function countOccurences(string, word) {
   a = string+' ';
   return a.split(word).length - 1;
}


function rankChg(a) {

   // var x = (a.value || a.options[a.selectedIndex].value);  //crossbrowser solution =)

   var rank =0;
   switch (a.id) {
     case "rank1": 
	document.getElementById("rank2").selectedIndex = 0;
	document.getElementById("rank3").selectedIndex = 0;
        rank = document.getElementById("rank1").selectedIndex;
	break;

     case "rank2":
        document.getElementById("rank1").selectedIndex = 0;
        document.getElementById("rank3").selectedIndex = 0;
        rank = document.getElementById("rank2").selectedIndex;
	break;

     case "rank3":
        document.getElementById("rank1").selectedIndex = 0;
        document.getElementById("rank2").selectedIndex = 0;
        rank = document.getElementById("rank3").selectedIndex;
        break;

   }

   if (rank != 0 && document.getElementById("year").selectedIndex != 0) {
      document.getElementById("search_str").disabled = false;
   } else {
       document.getElementById("search_str").disabled = true;
   }
  

}

function yearChg() {
     syr = document.getElementById("year").selectedIndex;

     if (syr !=0) {
       rank1 = document.getElementById("rank1").selectedIndex;
       rank2 = document.getElementById("rank2").selectedIndex;
       rank3 = document.getElementById("rank3").selectedIndex;
       syr = document.getElementById("year").selectedIndex;
       if ((rank1 !=0 || rank2 !=0 || rank3 !=0)) {
           document.getElementById("search_str").disabled = false;
       }
     } else {
          document.getElementById("search_str").disabled = true;
     }
}


jQuery(document).ready(function(){
	var totalname=0;
    jQuery("#search_str").on("input", function(){
		var inputvalue=jQuery(this).val().trim();
		//console.log("inputvalue: "+inputvalue+" length: "+inputvalue.length);	
		totalname=0;		
		jQuery('.staff_one').each(function() {
			var currentElement = jQuery(this);
			var value = currentElement.text(); 
			//console.log("value: "+value);
			pattern = new RegExp(inputvalue.replace(/, |-| /g, ".*")+ ".*","i");
			if (value.search(pattern) <0) {
				currentElement.hide();
			} else {
				currentElement.show();
				totalname++;
			}
		});
		jQuery('#namefilter').text(totalname);		
	});
	
});

