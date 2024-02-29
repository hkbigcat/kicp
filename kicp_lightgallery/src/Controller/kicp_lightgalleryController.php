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


        $album_name = (isset($_REQUEST['spgmGal']) && $_REQUEST['spgmGal'] != "") ? $_REQUEST['spgmGal'] : "";

        $file_system = \Drupal::service('file_system');
        $filePath = $file_system->realpath('public://'.$this->module.'/'.$this->download_folder);


        

        if($album_name != "") {            
            $filePath .= '/'.$album_name;
        }
        
        $dirFile = scandir($filePath);
                
        if(isset($_REQUEST['div'])){
            $div = $_REQUEST['div'];
        } else {
            $div = 0;
        }

        if($album_name != "") {
            $renderable = [
                '#theme' => 'kicplightgallery-album',
                '#album' => $album_name,
                '#items' => $dirFile,
                '#div' => $div,
            ];
        } else {
            $renderable = [
                '#theme' => 'kicplightgallery-folder',
                '#album' => $album_name,
                '#items' => $dirFile,
            ];

        }

        $output = \Drupal::service('renderer')->renderPlain($renderable);
        $response = new Response();
        $response->setContent($output);
        return $response;

        
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
