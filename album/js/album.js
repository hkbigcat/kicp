jQuery(document).ready(function ($) {
    // We only want these styles applied when javascript is enabled
    $('div#container').css({'width': '600px'});
    $('div.navigation').css({'width': '190px', 'float': 'left'});
    $('div.content').css({'display': 'block', 'width': '410px'});

    // Initially set opacity on thumbs and add
    // additional styling for hover effect on thumbs
    var onMouseOutOpacity = 0.67;
    $('#thumbs ul.thumbs li').opacityrollover({
        mouseOutOpacity: onMouseOutOpacity,
        mouseOverOpacity: 1.0,
        fadeSpeed: 'fast',
        exemptionSelector: '.selected'
    });

    // Initialize Advanced Galleriffic Gallery
    var gallery = $('#thumbs').galleriffic({
        delay: 2500,
        numThumbs: 10,
        preloadAhead: 10,
        enableTopPager: true,
        enableBottomPager: true,
        maxPagesToShow: 4,
        imageContainerSel: '#slideshow',
        controlsContainerSel: '#controls',
        captionContainerSel: '#caption',
        loadingContainerSel: '#loading',
        renderSSControls: true,
        renderNavControls: true,
        playLinkText: 'Play Slideshow',
        pauseLinkText: 'Pause Slideshow',
        prevLinkText: '&lsaquo; Previous ',
        nextLinkText: 'Next  &rsaquo;',
        nextPageLinkText: '&rsaquo;',
        prevPageLinkText: '&lsaquo;',
        enableHistory: false,
        autoStart: false,
        syncTransitions: true,
        defaultTransitionDuration: 900,
        onSlideChange: function (prevIndex, nextIndex) {
            // 'this' refers to the gallery, which is an extension of $('#thumbs')
            this.find('ul.thumbs').children()
                    .eq(prevIndex).fadeTo('fast', onMouseOutOpacity).end()
                    .eq(nextIndex).fadeTo('fast', 1.0);
        },
        onPageTransitionOut: function (callback) {
            this.fadeTo('fast', 0.0, callback);
        },
        onPageTransitionIn: function () {
            this.fadeTo('fast', 1.0);
        }
    });
});