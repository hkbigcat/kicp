function ShowHideDiv(div_name) {

    if (jQuery("#" + div_name).css('display') === "none") {
        jQuery("#" + div_name).fadeIn();
    } else {
        jQuery("#" + div_name).fadeOut();
    }
  }
jQuery(document).ready(function ($) {
  jQuery("#block-thex-kicp-main-menu li.menu-item.menu-item-level-1:nth-child(2)").addClass("active");
})