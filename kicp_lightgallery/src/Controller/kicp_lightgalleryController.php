<?php

/**
 * @file
 */

namespace Drupal\kicp_lightgallery\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\common\CommonUtil;

class kicp_lightgalleryController extends ControllerBase {

    public function __construct() {
        $this->module = 'album';
        $this->thumnail_folder = 'gal';
        $this->download_folder = 'download';
    }

    public function content() {

        return [
            '#theme' => 'kicplightgallery-album',
            '#items' => array(),
            '#empty' => t('No entries available.'),
        ];  

        /*
        $output = '';
        $lineBreak = '<br>';

        $album_name = (isset($_REQUEST['spgmGal']) && $_REQUEST['spgmGal'] != "") ? $_REQUEST['spgmGal'] : "";

        $output .= '<style media="all">@import url("modules/custom/kicp_lightgallery/css/album.css");</style>';               
        $output .= '<link rel="stylesheet" href="modules/custom/kicp_lightgallery/css/galleriffic-basic.css" type="text/css" />';
        $output .= '<link rel="stylesheet" href="modules/custom/kicp_lightgallery/css/galleriffic.css" type="text/css" />';
        $output .= '<script src="/core/assets/vendor/jquery/jquery.min.js?v=2.2.3"></script>';
        $output .= '<script type="text/javascript" src="modules/custom/kicp_lightgallery/js/jquery.galleriffic.js"></script>';
        $output .= '<script type="text/javascript" src="modules/custom/kicp_lightgallery/js/jquery.opacityrollover.js"></script>';
        
        $AbsoluteServerPath = CommonUtil::getSysValue('server_absolute_path');
        $app_path = CommonUtil::getSysValue('app_path');
        $filePath = $app_path.'/sites/default/files/public/'.$this->module.'/'.$this->download_folder;
        
        if($album_name != "") {
            
            $filePath .= '/'.$album_name;
            
        }
        
        $dirFile = CommonUtil::getDirectoryList($AbsoluteServerPath . $filePath, true);
        
        if(isset($_REQUEST['div'])){
            $div = $_REQUEST['div'];
        } else {
            $div = 0;
        }
        
        if($album_name != "") {
            $output = self::getGalleryDisplay($album_name, $dirFile, $div);
        } else {
            $output .= self::getGalleryFolderDisplay($dirFile);
        }
        
        
        $response = new Response();
        $response->setContent($output);
        return $response;
        */
        
    }
    
    public function getGalleryDisplay($album_name, $dirFileAry, $div) {


        $output = '';
        $kicp_lightgallery_display = '';
        
        $lineBreak = '<br>';
        
        $modulePath = 'modules/custom/kicp_lightgallery/';
        $srcPath = $modulePath . 'src/';
        
        $html = '';
        if($div!=1){
            $html = file_get_contents($srcPath . 'header.html');
        }
        
        $output .= str_replace("../",$modulePath,$html);
        
        $mainhtml = file_get_contents($srcPath . 'main.html');
        
        $thumbnail_path = 'sites/default/files/public/'.$this->module.'/gal/'.$album_name.'/_thb_';
        $original_path = 'sites/default/files/public/'.$this->module.'/gal/'.$album_name.'/';
        $download_path = 'sites/default/files/public/'.$this->module.'/download/'.$album_name.'/';
        
        $photoshtml = '';
        
        for($i=0; $i<count($dirFileAry); $i++) {
            // as thumbnail & original photos are stored in same directory, only get those original file name here
            $pos = strpos($dirFileAry[$i], '_thb_');
            if($pos === 0) {
                continue;
            } 
        
            $photoshtmlTemp = file_get_contents($srcPath . 'photos.html');
            $photoshtmlTemp = str_replace("{{downloadPhotoPath}}",$download_path . $dirFileAry[$i],$photoshtmlTemp);
            $photoshtmlTemp = str_replace("{{downloadPhotoName}}",$dirFileAry[$i],$photoshtmlTemp);
            $photoshtmlTemp = str_replace("{{galPhotoPath}}",$original_path . $dirFileAry[$i],$photoshtmlTemp);
            $photoshtml .= $photoshtmlTemp;
            
        }

    
        
        $html = str_replace("{{photos.html}}",$photoshtml, $mainhtml);
        $output .= $html;
        
        $output .= $lineBreak;
        
        $jshtml = file_get_contents($srcPath . 'js.html');
        
        if($div!=1){
            $footerhtml = file_get_contents($srcPath . 'footer.html');
            $html = str_replace("{{js.html}}",$jshtml,$footerhtml);
            $output .= str_replace("../",$modulePath,$html);
            $output .= "<script>loadLightGallery()</script>";
        }
        else{
            $output .= $jshtml;
        }        
        
        return $output;


    }
    
    public static function getGalleryFolderDisplay($folderAry) {
        
        $output = '';
        //$output .= 'folderAry=' . (string)implode(" ",$folderAry);
        for($i=0; $i<count($folderAry); $i++) {
            if($i%2==0) {
                $output .= '<div class="inline_row">';
            }
            
            $output .= '<div class="homepage_div"><a href="kicp_lightgallery?spgmGal='.$folderAry[$i].'"><img src="modules/custom/common/images/icon_album.jpg" border="0" style="vertical-align:middle"><div style="margin-top: -30px; margin-left: 50px;">'.$folderAry[$i].'</div></a></div>';
            
            if($i%2==1 || $i == count($folderAry)-1) {
                $output .= '</div>';
            }
        }
        
        return $output;
    }
    
    public function photoDownload() {
        
        $kicp_lightgallery = $_REQUEST['kicp_lightgallery'];
        $filename = $_REQUEST['name'];
        
        $file = 'sites/default/files/public/'.$this->module.'/download/'.$kicp_lightgallery.'/'.$filename;
            
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        header("Content-Description: File Transfer");

        header("application/force-download");

        header("Content-Disposition: attachment; filename=".basename($file).";");

        header("Content-Transfer-Encoding: binary");
        //header("Content-Length: ".filesize($filename));

        @readfile($file);

    }

}
