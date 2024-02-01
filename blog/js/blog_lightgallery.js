/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var hrefAry = new Array();
var data_sub_htmlAry = new Array();
var srcAry = new Array();

var PhotoPerPageConst = 12;
var totalPages = new Array();
var currentPage = new Array();

var modulePath = 'modules/custom/kicp_lightgallery/';
var srcPath = modulePath + 'src/';


function getiframe() {
    
    iframe_id = 1;
    
    $("iframe").each(function() { 
        
        hrefAry[iframe_id] = new Array();
        data_sub_htmlAry[iframe_id] = new Array();
        srcAry[iframe_id] = new Array();
        
        var iframeSrc= $(this).attr('src');
        //$(this).attr('src',src); 
        //console.log(iframeSrc); // /kmapp/kicp/album?spgmGal=Commendation_Letter_Presentation_Ceremony_2011&spgmPic=0
        
        res = iframeSrc.split("spgmGal=");
        res2 = res[1].split("&");
        AlbumName= res2[0];
        
        iframe = $(this);
        
        iframeSrc = iframeSrc.replace(" ","%20");
        
        replaceToDiv(iframe_id, iframe, iframeSrc, AlbumName);
        
        iframe_id++;
    });
    
}

function replaceToDiv(iframe_id, iframe, iframeSrc, AlbumName){
        iframe.replaceWith("<div id='iframe" + iframe_id +"' class='lightgallery'>" + AlbumName + "</div>");
        
        $.get(iframeSrc.replace("album", "kicp_lightgallery") + "&div=1", function(data) {
            htmldata = data;
        }, 'html').done(function(){
            
            $("#iframe" + iframe_id).html(htmldata);
            
            var page_selector = $("#page_selector");
            page_selector.attr("id","page_selector" + iframe_id);
            page_selector.html("{{page_selector"+ iframe_id  +"}}");
            
            var gallery = $("#animated-thumbnials");
            //gallery.html(htmldata);
            gallery.attr("id","animated-thumbnials" + iframe_id);
            
            var gallery_page = $("#gallery_page");
            gallery_page.attr("id","gallery_page" + iframe_id);
            
            $("#animated-thumbnials" + iframe_id +" .jg-entry").each(function() {
                hrefAry[iframe_id].push($(this).attr("href"));
                data_sub_htmlAry[iframe_id].push($(this).attr("data-sub-html"));
            });

            $("#animated-thumbnials" + iframe_id +" .img-responsive").each(function() {
                srcAry[iframe_id].push($(this).attr("src"));
            });
            
            //console.log("frame" + iframe_id + " Total href:" + hrefAry[iframe_id].length);
            
            totalPages[iframe_id] = Math.ceil(hrefAry[iframe_id].length/PhotoPerPageConst);
            currentPage = 1;
            
            BlogLightGalleryChangePage(iframe_id, currentPage, PhotoPerPageConst, totalPages[iframe_id]);
            
        });
}

function BlogLightGalleryChangePage(iframe_id, page, PhotoPerPage, totalPage){
    $('#gallery_page' + iframe_id).val(page);

    $('#animated-thumbnials' + iframe_id).html('');

    PhotoIndexMin=PhotoPerPage*(page-1);
    PhotoIndexMax=PhotoPerPage*page-1;

    var photohtml;
    photohtmlAry = new Array();
    
    $.get(srcPath + "photos.html", function(data) {
        htmldata = data;
    }, 'html').done(function(){
        photohtml = '';
        for(var i=0; i<hrefAry[iframe_id].length; i++){
            if(i>=PhotoIndexMin && i<=PhotoIndexMax){
                photohtmlAry[i] = htmldata;
                photohtmlAry[i] = photohtmlAry[i].replace("{{downloadPhotoPath}}", hrefAry[iframe_id][i]);
                photohtmlAry[i] = photohtmlAry[i].replace("{{downloadPhotoName}}", data_sub_htmlAry[iframe_id][i]);
                photohtmlAry[i] = photohtmlAry[i].replace("{{galPhotoPath}}", srcAry[iframe_id][i]);
                
                photohtml = photohtml + photohtmlAry[i];
                
            }
        }
        
        var gallery = $("#animated-thumbnials" + iframe_id);
        gallery.lightGallery();
        gallery.data('lightGallery').destroy(true);
        gallery.html(photohtml);

        gallery.justifiedGallery({
                border: 6
        }).on('jg.complete', function() {
                gallery.lightGallery({
                        thumbnail: true,
                        animateThumb: true
                });
        });

        PreviousPage = page-1;
        NextPage = page+1;
        FirstPage = 1;
        LastPage =  totalPage;

        if(PreviousPage<FirstPage){
            PreviousPage = FirstPage;
        }

        if(NextPage>LastPage){
            NextPage = LastPage;
        }

        page_selector_html = '<a href="#' + iframe_id + '" id="album_pageFirst' + iframe_id + '" class="btn_page_selector' + iframe_id + '" onClick="BlogLightGalleryChangePage(' + iframe_id + ',1,' + PhotoPerPage + ',' + totalPage + ')">&lt;&lt; First</a>';
        page_selector_html += '&nbsp;&nbsp;&nbsp;&nbsp;';

        page_selector_html += '<a href="#' + iframe_id + '" id="album_pagePrevious' + iframe_id + '" class="btn_page_selector' + iframe_id + '" onClick="BlogLightGalleryChangePage(' + iframe_id + ',' + PreviousPage + ',' + PhotoPerPage + ',' + totalPage + ')">&lt; Previous</a>';
        page_selector_html += '&nbsp;&nbsp;&nbsp;&nbsp;';

        for (page_index=1;page_index<=totalPage;page_index++){        
            page_selector_html += '<a href="#' + iframe_id + '" id="album_page' + page_index + '_' + iframe_id + '" class="btn_page_selector' + iframe_id + '" onClick="BlogLightGalleryChangePage(' + iframe_id + ',' + page_index + ',' + PhotoPerPage + ',' + totalPage + ')">' + page_index + '</a>';
            page_selector_html += '&nbsp;&nbsp;&nbsp;&nbsp;';
        }

        page_selector_html += '<a href="#' + iframe_id + '" id="album_pageNext' + iframe_id + '" class="btn_page_selector' + iframe_id + '" onClick="BlogLightGalleryChangePage(' + iframe_id + ',' + NextPage + ',' + PhotoPerPage + ',' + totalPage + ')">Next &gt;</a>';
        page_selector_html += '&nbsp;&nbsp;&nbsp;&nbsp;';

        page_selector_html += '<a href="#' + iframe_id + '" id="album_pageLast' + iframe_id + '" class="btn_page_selector' + iframe_id + '" onClick="BlogLightGalleryChangePage(' + iframe_id + ',' + totalPage + ',' + PhotoPerPage + ',' + totalPage + ')">Last &gt;&gt;</a>';

        var page_selector = $("#page_selector" + iframe_id);
        page_selector.html(page_selector_html);

        if(PreviousPage == page){
            $('#album_pagePrevious' + iframe_id).css('display','none');
        }
        else{
            $('#album_pagePrevious' + iframe_id).css('display','');
        }

        if(NextPage == page){
            $('#album_pageNext' + iframe_id).css('display','none');
        }
        else{
            $('#album_pageNext' + iframe_id).css('display','');
        }

        if(FirstPage == page){
            $('#album_pageFirst' + iframe_id).css('display','none');
        }
        else{
            $('#album_pageFirst' + iframe_id).css('display','');
        }

        if(LastPage == page){
            $('#album_pageLast' + iframe_id).css('display','none');
        }
        else{
            $('#album_pageLast'+ iframe_id).css('display','');
        }

        for(var j=1; j<=totalPage; j++){
            $('#album_page' + j + '_' + iframe_id).attr("href","#" + iframe_id);
            $('#album_page' + j + '_' + iframe_id).attr("onclick","BlogLightGalleryChangePage(" + iframe_id + "," + j.toString() + "," + PhotoPerPage.toString() + "," + totalPage.toString() + ")");
        }

        $('#album_page' + page + '_' + iframe_id).removeAttr('href');
        $('#album_page' + page + '_' + iframe_id).removeAttr('onclick');

        $('.btn_page_selector'+ iframe_id).css("font-weight", "");
        $('.btn_page_selector'+ iframe_id).css("color", "");

        $('#album_page' + page + '_' + iframe_id).css('font-weight', 'bold');
        $('#album_page' + page + '_' + iframe_id).css("color", "black");

        
        
    });
    
}
