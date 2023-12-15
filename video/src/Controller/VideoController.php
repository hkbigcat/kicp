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
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Database\Query\PagerSelectExtender;
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

    public function VideoContent($media_event_id="") {
        $EventInfo = VideoDatatable::getVideoEventInfo($media_event_id);
        $VideoList = VideoDatatable::getVideoListByEventId($media_event_id);
        $TagList = new TagList();
        $taglist = $TagList->getTagsForModule('video', $media_event_id);        
    
        return [
            '#theme' => 'video-list',
            '#media_event_id' => $media_event_id,
            '#items' => $VideoList,
            '#event_info' => $EventInfo,
            '#tags' => $taglist,
            '#empty' => t('No entries available.'),
        ];         

    }

    public function VideoTagContent() {

        $tags = array();
        $tagsUrl = \Drupal::request()->query->get('tags');
    
        if ($tagsUrl) {
          $tags = json_decode($tagsUrl);
          if ($tags && count($tags) > 0 ) {
            $tmp = $tags;
          }
        }
        $table_rows_file = VideoDatatable::getVideoTags($tags);
            
        return [
            '#theme' => 'video-tags',
            '#items' => $table_rows_file,
            '#tags' => $tags,
            '#tagsUrl' => $tmp,
            '#empty' => t('No entries available.'),
            '#tagsUrl' => $tmp,
            '#pager' => ['#type' => 'pager',
                        ],
        ];    

    }
}