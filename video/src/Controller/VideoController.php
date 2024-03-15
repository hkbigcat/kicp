<?php

/**
 * @file
 */

namespace Drupal\video\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\Controller\TagList;
use Drupal\common\RatingData;
use Drupal\video\Common\VideoDatatable;
use Drupal\Core\Database\Database;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;


class VideoController extends ControllerBase {
    
    public function __construct() {
        //$Paging = new Paging();
        //$DefaultPageLength = $Paging->getDefaultPageLength();

        $this->module = 'video';
        $this->pagesize = 5;        
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

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();

        $EventInfo = VideoDatatable::getVideoEventInfo($media_event_id);
        $VideoList = VideoDatatable::getVideoListByEventId($media_event_id);
        $TagList = new TagList();
        $taglist = $TagList->getTagsForModule('video', $media_event_id);        
        $RatingData = new RatingData();
        $rating = $RatingData->getList('video', $media_event_id);
        $rsHadRate = $RatingData->checkUserHadRate('kicp_media_event_name', $media_event_id, $my_user_id);
        $rating['rsHadRate'] = $rsHadRate;
        $rating['module'] = "video";        

    
        return [
            '#theme' => 'video-list',
            '#media_event_id' => $media_event_id,
            '#items' => $VideoList,
            '#event_info' => $EventInfo,
            '#tags' => $taglist,
            '#rating' => $rating,
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],
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
            '#pager' => ['#type' => 'pager',
                        ],
        ];    

    }


    public function AdminContent() {

        $video_events = VideoDatatable::getVideoEventAdminList();

        $search_str = \Drupal::request()->query->get('search_str');
        $video_events['search_str'] =  $search_str;
        
        return [
                '#theme' => 'video-admin',
                '#items' => $video_events,
                '#empty' => t('No entries available.'),
                '#pager' => ['#type' => 'pager',
                            ],
        ];    
    }

    public function AdminVideoContent($media_event_id="") {

        $videolist = VideoDatatable::getVideoListByEventId($media_event_id, 1); //Admin has different fields
        $EventInfo = VideoDatatable::getVideoEventInfo($media_event_id);

        $search_str = \Drupal::request()->query->get('search_str');
        $videolist['search_str'] =  $search_str;
     

        return [
                '#theme' => 'video-admin-video',
                '#items' => $videolist,
                '#eventname' => $EventInfo->media_event_name,
                '#empty' => t('No entries available.'),
                '#pager' => ['#type' => 'pager',
                            ],
        ];       


    }

    public function VideoEventDelete($media_event_id="") {

        $current_time =  \Drupal::time()->getRequestTime();

        // delete record
        $err_code = 0;

        try {
          $database = \Drupal::database();
          $query = $database->update('kicp_media_event_name')->fields([
            'is_deleted'=>1 , 
            'modify_datetime' => date('Y-m-d H:i:s', $current_time),
          ])
          ->condition('media_event_id', $media_event_id)
          ->execute();

          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Event has been deleted'));

          $err_code = 1;

          $response = array('result' => $err_code);
          return new JsonResponse($response);
  
  
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addStatus(
                t('Unable to delete event at this time due to datbase error. Please try again. ' )
                );

            }	
    }    

    public function VideoDelete($media_id="") {

        $err_code = 0;

        try {
            $database = \Drupal::database();
            $query = $database->update('kicp_media_info')->fields([
              'is_deleted'=>1 , 
              'modify_datetime' => date('Y-m-d H:i:s', $current_time),
            ])
            ->condition('media_id', $media_id)
            ->execute();
     
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Video has been deleted'));
    
            $err_code = 1;

            $response = array('result' => $err_code);
            return new JsonResponse($response);

          }
          catch (\Exception $e) {
              \Drupal::messenger()->addStatus(
                  t('Unable to delete video at this time due to datbase error. Please try again. ' )
                  );
  
              }	        

    }
    

    public function AdminVideoEventPrivilege($media_event_id="") {

        $video_privilege = VideoDatatable::getVideoPrivilege($media_event_id);
        $EventInfo = VideoDatatable::getVideoEventInfo($media_event_id);
        $video_privilege['media_event_id'] = $media_event_id;
        $video_privilege['media_event_name'] = $EventInfo->media_event_name;
        $search_str = \Drupal::request()->query->get('search_str');
        $video_privilege['search_str'] =  $search_str;
        if ($search_str && $search_str!="") {
            $privilege_group = VideoDatatable::getVideoPrivilegeGroup($search_str);
        }


        return [
                '#theme' => 'video-admin-privilege',
                '#items' => $video_privilege,
                '#privilege_group' => $privilege_group,
                '#empty' => t('No entries available.'),
                '#pager' => ['#type' => 'pager',
                            ],
        ];         


    }
    
    public function AdminVideoEventPrivilegeAddAction($media_event_id="", $pub_group_id="") {

        try {
          $database = \Drupal::database();
          $query = $database->insert('kicp_media_event_privilege')->fields([
            'media_event_id' => $media_event_id,
            'pub_group_id' => $pub_group_id,
            'is_deleted'=>0 , 
          ])
          ->execute();

          $search_str = \Drupal::request()->query->get('search_str');

          $url = Url::fromUserInput('/video_event_privilege/'.$media_event_id.'?search_str='.$search_str);
          return new RedirectResponse($url->toString());

        } 
        catch (\Exception $e) {
            \Drupal::messenger()->addStatus(
                t('Unable to add privilege at this time due to datbase error. Please try again. ' )
                );
            }	

    }

    public function EventPrivilegeDelete($media_event_id="",$pub_group_id="") {

        $err_code = 0;
        try {
            $database = \Drupal::database();
            $query = $database->update('kicp_media_event_privilege')->fields([
                'is_deleted' => 1,
                ])
                ->condition('media_event_id', $media_event_id)
                ->condition('pub_group_id', $pub_group_id)
                ->execute();

                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('Event Privilege has been deleted'));
        
                $err_code = 1;
    

    
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addStatus(
                t('Unable to delete privilege at this time due to datbase error. Please try again. ' )
                );
            }

        $response = array('result' => $err_code);
        return new JsonResponse($response);  

    }

}