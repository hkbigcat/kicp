<?php

/**
 * @file
 */

namespace Drupal\video\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\TagList;
use Drupal\common\RatingData;
use Drupal\video\Common\VideoDatatable;
use Drupal\Core\Database\Database;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\common\Follow;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;


class VideoController extends ControllerBase {
    
    public function __construct() {

        $this->module = 'video';
        $this->pagesize = 5;        

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->is_authen = $authen->isAuthenticated;
        $this->my_user_id = $authen->getUserId();              
    }
    
    public function content() {

        $url = Url::fromUri('base:/no_access');
        if (! $this->is_authen) {
            return new RedirectResponse($url->toString());
        }

        $EventListAry = VideoDatatable::getVideoEventList($this->pagesize); // get most updated events
        $EventListRight = VideoDatatable::getVideoEventList($limit="", $start=($this->pagesize+1));
        $following = Follow::getFollow('KMU.OGCIO', $this->my_user_id);    
        return [
            '#theme' => 'video-home',
            '#items' => $EventListAry,
            '#items_right' => $EventListRight,
            '#my_user_id' => $this->my_user_id,
            '#following' => $following,            
            '#empty' => t('No entries available.'),
        ];        


    }

    public function VideoContent($media_event_id="") {

        $url = Url::fromUri('base:/no_access');
        if (! $this->is_authen) {
            return new RedirectResponse($url->toString());
        }

        $EventInfo = VideoDatatable::getVideoEventInfo($media_event_id);
        $VideoList = VideoDatatable::getVideoListByEventId($media_event_id);
        $TagList = new TagList();
        $taglist = $TagList->getTagsForModule('video', $media_event_id);        
        $RatingData = new RatingData();
        $rating = $RatingData->getList('video', $media_event_id);
        $rsHadRate = $RatingData->checkUserHadRate('kicp_media_event_name', $media_event_id, $this->my_user_id);
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

        $url = Url::fromUri('base:/no_access');
        if (! $this->is_authen) {
            return new RedirectResponse($url->toString());
        }

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
                '#media_event_id' => $media_event_id,
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

        $database = \Drupal::database();
        $transaction =  $database->startTransaction();         
        try {
          $query = $database->update('kicp_media_event_name')->fields([
            'is_deleted'=>1 , 
            'modify_datetime' => date('Y-m-d H:i:s', $current_time),
          ])
          ->condition('media_event_id', $media_event_id)
          ->execute();
          \Drupal::logger('video')->info('deleted event video id: %id',   
          array(
              '%id' => $media_event_id,
          ));
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Event has been deleted'));
          $err_code = 1;
          $response = array('result' => $err_code);
          return new JsonResponse($response);
        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::logger('video')->error('Video event is not deleted '  . $variables);   
            \Drupal::messenger()->addStatus(
            t('Unable to delete event at this time due to datbase error. Please try again. ' )
            );
            $transaction->rollback();
        }	
        unset($transaction);
    }    

    public function VideoDelete($media_id="") {

        $err_code = 0;
        $database = \Drupal::database();
        $transaction =  $database->startTransaction();          
        try {
            $query = $database->update('kicp_media_info')->fields([
              'is_deleted'=>1 , 
              'modify_datetime' => date('Y-m-d H:i:s', $current_time),
            ])
            ->condition('media_id', $media_id)
            ->execute();
            \Drupal::logger('video')->info('deleted Video id: %id',   
            array(
                '%id' => $media_id,
            ));             
     
            
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Video has been deleted'));
            $err_code = 1;

            $response = array('result' => $err_code);
            return new JsonResponse($response);

          }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::logger('video')->error('Video is not deleted '  . $variables);   
              \Drupal::messenger()->addStatus(
                  t('Unable to delete video at this time due to datbase error. Please try again. ' )
                  );
            $transaction->rollback();
           }	        
        unset($transaction);
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

        $database = \Drupal::database();
        $transaction =  $database->startTransaction(); 
        try {
          $query = $database->insert('kicp_media_event_privilege')->fields([
            'media_event_id' => $media_event_id,
            'pub_group_id' => $pub_group_id,
            'is_deleted'=>0 , 
          ])
          ->execute();
          \Drupal::logger('video')->info('added video preivilege id: %id',   
          array(
              '%id' => $media__event_id,
          ));
          $search_str = \Drupal::request()->query->get('search_str');
          $url = Url::fromUserInput('/video_event_privilege/'.$media_event_id.'?search_str='.$search_str);
          return new RedirectResponse($url->toString());

        } 
        catch (\Exception $e) {
            \Drupal::messenger()->addStatus(
                t('Unable to add privilege at this time due to datbase error. Please try again. ' )
                );
                $transaction->rollback();    
            }	
        unset($transaction);
    }

    public function EventPrivilegeDelete($media_event_id="",$pub_group_id="") {

        $err_code = 0;
        $database = \Drupal::database();
        $transaction =  $database->startTransaction();         
        try {
            $query = $database->update('kicp_media_event_privilege')->fields([
                'is_deleted' => 1,
                ])
                ->condition('media_event_id', $media_event_id)
                ->condition('pub_group_id', $pub_group_id)
                ->execute();
            \Drupal::logger('video')->info('deleted video preivilege id: %id',   
            array(
                '%id' => $media__event_id,
            ));                
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Event Privilege has been deleted'));
            $err_code = 1;
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addStatus(
                t('Unable to delete privilege at this time due to datbase error. Please try again. ' )
                );
                $transaction->rollback(); 
            }
        unset($transaction);
        $response = array('result' => $err_code);
        return new JsonResponse($response);  

    }

    public static function getEventSelection($evt_type="") {
        
    
        if($evt_type == "") {
            if(isset($_REQUEST['evt_type']) && $_REQUEST['evt_type'] != "") {
                $evt_type = $_REQUEST['evt_type'];
            }
        }

    
        $evtItemAry = VideoDatatable::getAllActivityByEventType($evt_type);

        $renderable = [
            '#theme' => 'video-event.selection',
            '#items' => $evtItemAry,
          ];

        $output = \Drupal::service('renderer')->renderPlain($renderable);

        $response = new Response();
        $response->setContent($output);

    
        return $response;

    }

    public static function Breadcrumb() {

        $base_url = Url::fromRoute('video.video_entrylist');
        $admin_url = Url::fromRoute('video.admin_content');
        $base_path = [
            'name' => 'Video', 
            'url' => $base_url,
        ];
        $admin_path = [
            'name' => 'Admin - Events List', 
            'url' => $admin_url,
        ];
        $breads = array();
        $route_match = \Drupal::routeMatch();
        $routeName = $route_match->getRouteName();
        $media_event_id = $route_match->getParameter('media_event_id');
        if ($routeName=="video.video_entrylist") {
            $breads[] = [
                'name' => 'Video', 
            ];
        } else if  ($routeName=="video.video_tag") {
            $breads[] = $base_path;
            $breads[] = [
                'name' => 'Tags', 
            ];            
        }
        else if ($routeName=="video.video_list") {
            $breads[] = $base_path;
            if ($media_event_id) {
                $media_event_name = VideoDatatable::getEventName($media_event_id);
                if ($media_event_name) {
                    $breads[] = [
                        'name' => $media_event_name, 
                    ];        
                }
            }
        }  else if  ($routeName=="video.admin_content") {
            $breads[] = $base_path;
            $breads[] = [
                'name' => 'Admin - Events List', 
            ];            
        } else if  ($routeName=="video.admin_video_content") {
            $breads[] = $base_path;
            $breads[] = $admin_path;
            $breads[] = [
                'name' => 'Videos List', 
            ];            
        } else if ($routeName=="video.change_event_data") {
            $breads[] = $base_path;
            $breads[] = $admin_path;
            $breads[] = [
                'name' => 'Edit', 
            ];            
        } else if ($routeName=="video.add_event_data") {
            $breads[] = $base_path;
            $breads[] = $admin_path;
            $breads[] = [
                'name' => 'Add', 
            ];            
        } else if ($routeName=="video.add_video_data") {
            $breads[] = $base_path;
            $breads[] = $admin_path;
            if ($media_event_id) {
                $admin_event_url = Url::fromRoute('video.admin_video_content', ['media_event_id' => $media_event_id]);
            }
            $breads[] = [
                'name' => 'Videos List', 
                'url'  => $admin_event_url,           
            ];
            $breads[] = [
                'name' => 'Add', 
            ];            
        }  else if ($routeName=="video.change_video_data") {
            $breads[] = $base_path;
            $breads[] = $admin_path;
            $media_id = $route_match->getParameter('media_id');
            if ($media_id)  {
                $media = VideoDatatable::getVideoInfo($media_id);
                $media_event_id = $media['media_event_id'];
            }
            if ($media_event_id) {
                $admin_event_url = Url::fromRoute('video.admin_video_content', ['media_event_id' => $media_event_id]);
            }
            $breads[] = [
                'name' => 'Videos List', 
                'url'  => $admin_event_url,           
            ];
            $breads[] = [
                'name' => 'Edit', 
            ];            
        }
        
        
        return $breads;

    }

}