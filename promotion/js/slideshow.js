function plusSlides(n, no) {
  showSlides(slideIndex[no] += n, no, this, 1);
}

function showSlides(n, no, t, s=0) {
  var i;
  var x = document.getElementsByClassName(slideId[no]);
  var no1 = no+1;
  var y = document.getElementById("slide-"+no1);
  var z = document.getElementById("newsimg-"+no);
  
  if (n > x.length) {slideIndex[no] = 1}    
  if (n < 1) {slideIndex[no] = x.length}
  for (i = 0; i < x.length; i++) {
     x[i].style.display = "none";  
  }
  
  
  if (s==0) {
	  if (y.style.display == "none") {
		y.style.display = "block";
		t.title=t.title.replace("Open", "Close");
		z.src="/kicp/modules/custom/promotion/images/newsletter2.png";
		/*_paq.push(['trackEvent', 'Promotion', 'Open', t.title.substr(-19)]);*/
	  } else {
		y.style.display = "none";
		t.title=t.title.replace("Close","Open");
		z.src="/kicp/modules/custom/promotion/images/newsletter-30.png";		
	  }   
  }
  x[slideIndex[no]-1].style.display = "block";  
}