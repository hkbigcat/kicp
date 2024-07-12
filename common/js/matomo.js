var _paq = window._paq = window._paq || [];
/* tracker methods like "setCustomDimension" should be called before "trackPageView" */
var section=window.location.pathname.substring(12);
if (section.indexOf("_")!=-1) {
    section = section.substring(0,section.indexOf("_"));
}
if (section=="") section = "home";    
_paq.push(['trackPageView', document.title, {dimension1: section}]);
var user_id = drupalSettings.user_id;
if (user_id!="")
    _paq.push(['setUserId', user_id]);
_paq.push(['trackPageView']);
_paq.push(['enableLinkTracking']);
(function() {
let h = location.host;
var u="//"+h+app_path+"matomo/";
_paq.push(['setTrackerUrl', u+'matomo.php']);
_paq.push(['setSiteId', '1']);
var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
})();
