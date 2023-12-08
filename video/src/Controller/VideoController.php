<?php

/**
 * @file
 */

namespace Drupal\video\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\Controller\TagList;
use Drupal\video\Common\VideoDatatable;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;


class VideoController extends ControllerBase {
    
    public function __construct() {
        //$Paging = new Paging();
        //$DefaultPageLength = $Paging->getDefaultPageLength();

        $this->module = 'video';
        $this->pagesize = 5;
        $this->app_path = CommonUtil::getSysValue('app_path');
        $this->app_path_url = CommonUtil::getSysValue('app_path_url');
        $this->domain_name = CommonUtil::getSysValue('domain_name');
        
    }
    
    public function content() {

        $EventListAry = VideoDatatable::getVideoEventList($this->pagesize); // get most updated events
        $EventListRight = VideoDatatable::getVideoEventList($limit="", $start=($this->pagesize+1));

        return [
            '#theme' => 'video-home',
            '#items' => $EventListAry,
            '#items_right' => $EventListRight,
            '#empty' => t('No entries available.'),
        ];        


    }
}