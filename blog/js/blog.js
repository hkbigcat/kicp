function ShowHideDiv(div_name) {

    if (jQuery("#" + div_name).css('display') === "none") {
        jQuery("#" + div_name).fadeIn();
    } else {
        jQuery("#" + div_name).fadeOut();
    }
  }