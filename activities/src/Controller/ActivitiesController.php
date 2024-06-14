<?php

/**
 * @file
 */

namespace Drupal\activities\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\TagList;
use Drupal\common\TagStorage;
use Drupal\common\Follow;
use Drupal\activities\Common\ActivitiesDatatable;
use Drupal\Core\Database\Database;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Utility\Error;

class ActivitiesController extends ControllerBase {
    
    public $is_authen;
    public $my_user_id;
    public $module;
    
    public function __construct() {
        //$Paging = new Paging();
        //$DefaultPageLength = $Paging->getDefaultPageLength();

        $this->module = 'activities';

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->is_authen = $authen->isAuthenticated;
        $this->my_user_id = $authen->getUserId();      
        
    }
    
    public function content($type_id=1, $cop_id="", $item_id="" ) {
        
        $url = Url::fromUri('base:/no_access');
        if (! $this->is_authen) {
            return new RedirectResponse($url->toString());
        }

        $activitiesType = ActivitiesDatatable::getAllActivityType();
        $GroupInfo = ActivitiesDatatable::getCOPGroupInfo();
        $COPitems = array();
        $events = array();

        if ($cop_id!="")   { 
            $COPitems = ActivitiesDatatable::getCOPbyGroupID($cop_id);
    
            if ($item_id!="")   { 
                $item_index = array_search($item_id, (array_column($COPitems, 'cop_id')));
                $activityInfo = ['evt_type_name' => $COPitems[$item_index]['cop_name'], 'description' =>  $COPitems[$item_index]['cop_info']];
            } else {
                $cop_info = ActivitiesDatatable::getCOPGroupInfo($cop_id);
                $activityInfo = ['evt_type_name' => $cop_info['group_name'], 'description' =>  $cop_info['group_description']];
            }
        } else {
            $activityInfo = ActivitiesDatatable::getActivityTypeInfo($type_id);
        }
        $activityInfo['type_id'] = $type_id;

        if (($type_id==1 && $item_id!="" ) || $type_id!=1 )
            $events = ActivitiesDatatable::getEventItemByTypeId($type_id, $item_id);

        $following = Follow::getFollow('KMU.OGCIO', $this->my_user_id);    

        return [
            '#theme' => 'activities-main',
            '#items' => $activityInfo,
            '#groups' => $GroupInfo,
            '#events' => $events,
            '#types' => $activitiesType,
            '#copitems' => $COPitems,
            '#my_user_id' => $this->my_user_id,
            '#following' => $following,
            '#empty' => t('No entries available.'),
        ];                

    }


    public function ActivityDetail($evt_id="") {

        $url = Url::fromUri('base:/no_access');
        if (! $this->is_authen) {
            return new RedirectResponse($url->toString());
        }

        $EventDetail = ActivitiesDatatable::getEventDetail($evt_id);
        $activitiesType = ActivitiesDatatable::getAllActivityType();
        $GroupInfo = ActivitiesDatatable::getCOPGroupInfo();

        return [
            '#theme' => 'activities-details',
            '#items' => $EventDetail,
            '#types' => $activitiesType,
            '#groups' => $GroupInfo,
            '#empty' => t('No entries available.'),
        ];                


    }

    public function AdminContent() {

        $activitiesType = ActivitiesDatatable::getAllActivityType();
        $search_str = \Drupal::request()->query->get('search_str');
        $activitiesType['search_str'] =  $search_str;

        return [
            '#theme' => 'activities-admin',
            '#items' => $activitiesType,
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],
        ];   
        
    }

    public function deleteActivityType($evt_type_id) {

        // delete record   
        $err_code = '0'; 
        $database = \Drupal::database();
        $transaction = $database->startTransaction();          
        try {
          $query = $database->update('kicp_km_event_type')->fields([
            'is_deleted'=>1 , 
          ])
          ->condition('evt_type_id', $evt_type_id)
          ->execute();

          \Drupal::logger('activities')->info('Type deleted id: %id',   
          array(
              '%id' => $evt_type_id,
          ));              
          $err_code = '1';
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('KM Event Type has been deleted'));
      
        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Unable to delete KM Event Type at this time due to datbase error. Please try again. ' )
                );
            \Drupal::logger('activities')->error('Activity Type is not deleted: ' . $variables);       
            $transaction->rollBack();    
            }
        unset($transaction);    	        
        $response = array('result' => $err_code);
        return new JsonResponse($response);
    
    }      

    public function AdminCategory() {

        $GroupInfo = ActivitiesDatatable::getCOPGroupInfo();
        $search_str = \Drupal::request()->query->get('search_str');
        $GroupInfo['search_str'] =  $search_str;

        return [
            '#theme' => 'activities-admin-category',
            '#items' =>  $GroupInfo,
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],
        ];   
        
    }
        
    public function deleteCOPCategory($group_id) {

        // delete record   
        $err_code = '0'; 
        $database = \Drupal::database();
        $transaction = $database->startTransaction();            
        try {
          $query = $database->update('kicp_km_cop_group')->fields([
            'is_deleted'=>1 , 
          ])
          ->condition('group_id', $group_id)
          ->execute();

          \Drupal::logger('activities')->info('COP Category deleted id: %id',   
          array(
              '%id' =>  $group_id,
          ));               
          $err_code = '1';
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('KM COP Category has been deleted'));
        
        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Unable to delete KM COP Category at this time due to datbase error. Please try again. ' )
                );
            \Drupal::logger('activities')->error('Activity COP Group is not deleted: '. $variables);
            $transaction->rollBack();
        }	        
        unset($transaction);            
        $response = array('result' => $err_code);
        return new JsonResponse($response);

    }

    public function AdminActivityCOP($group_id="") {

        $COPitems = ActivitiesDatatable::getCOPbyGroupID($group_id);
        if ($COPitems) {
            $copGroupInfo = ActivitiesDatatable::getCOPGroupInfo($group_id);
            $search_str = \Drupal::request()->query->get('search_str');
            $COPitems['search_str'] =  $search_str;        
        }

        return [
            '#theme' => 'activities-admin-cop',
            '#items' =>  $COPitems,
            '#group' =>  $copGroupInfo,
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],
        ];   
        
    }


    public function deleteCOPItem($cop_id) {

        // delete record   
        $err_code = '0'; 
        $database = \Drupal::database();
        $transaction = $database->startTransaction();           
        try {
          $query = $database->update('kicp_km_cop')->fields([
            'is_deleted'=>1 , 
          ])
          ->condition('cop_id', $cop_id)
          ->execute();

          \Drupal::logger('activities')->info('KM COP Event Category deleted id: %id',   
          array(
              '%id' =>  $cop_id,
          ));  
          $err_code = '1';
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('KM COP Event Categ ory has been deleted'));  
        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Unable to delete KM COP  Event Category at this time due to datbase error. Please try again. ' )
                );
            \Drupal::logger('activities')->error('KM COP  Event Category is not deleted: '. $variables);
            $transaction->rollBack();
        }
        unset($transaction);	        
        $response = array('result' => $err_code);
        return new JsonResponse($response);

    }

    public function AdminEvents($type_id="") {

        $events = ActivitiesDatatable::getAdminEvents($type_id);
        $events['type_id'] = $type_id;

        $search_str = \Drupal::request()->query->get('search_str');
        $events['search_str'] =  $search_str;

        return [
            '#theme' => 'activities-admin-events',
            '#items' => $events,
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],            
        ];   

    }



    public function ActivityEventData() {

        $evt_id = $_REQUEST['evt_id'];
        $type = $_REQUEST['type'];
        $output = '';

        if($type == 'info') {
            $EventDetail = ActivitiesDatatable::getEventDetail($evt_id);
            $output .= nl2br($EventDetail['evt_description']);   

        }  else if($type == 'photo') {
             
            $photos = ActivitiesDatatable::getEventPhotoByEventId($evt_id);
            $PhotoPerPage = 18;
            $renderable = [
                '#theme' => 'activities-photo',
                '#items' => $photos,
                '#PhotoPerPage' => $PhotoPerPage,
                '#evt_id' => $evt_id,
              ];
            $output .= \Drupal::service('renderer')->renderPlain($renderable);

        } else if ($type == 'deliverable') {

            $deliverable = ActivitiesDatatable::getEventDeliverableByEventId($evt_id);
            $renderable = [
                '#theme' => 'activities-deliverable',
                '#items' => $deliverable,
                '#evt_id' => $evt_id,
              ];
            $output .= \Drupal::service('renderer')->renderPlain($renderable);            
        }


        $response = new Response();
        $response->setContent($output);
        return $response;

    }

    public function deleteEventItem($evt_id="") {

        $eventInfo = ActivitiesDatatable::getEventDetail($evt_id);
        $type_id = $eventInfo['evt_type_id'];
        $current_time =  \Drupal::time()->getRequestTime();

        // delete record
        $database = \Drupal::database();
        $transaction = $database->startTransaction();   
        try {
          $query = $database->update('kicp_km_event')->fields([
            'is_deleted'=>1 , 
            'modify_datetime' => date('Y-m-d H:i:s', $current_time),
          ])
          ->condition('evt_id', $evt_id)
          ->execute();

          // delete tags
          $return2 = TagStorage::markDelete($this->module, $evt_id);

          \Drupal::logger('activities')->info('KM Event deleted id: %id',   
          array(
              '%id' =>  $evt_id,
          ));

          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Event has been deleted'));
  
        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Unable to delete event at this time due to datbase error. Please try again. ' )
                );
            \Drupal::logger('activities')->error('KM Event is not deleted: '. $variables);
            $transaction->rollBack();
        }	
        unset($transaction);            
        $response = array('result' => 1);
        return new JsonResponse($response);            
    

    }


    public function EventEnrollStatus($evt_id="") {

        $EnrollRecord = ActivitiesDatatable::getEnrollmemtRecord($evt_id);
        $EnrollRecord['time'] = \Drupal::time()->getCurrentTime();
        $EventDetail = ActivitiesDatatable::getEventDetail($evt_id);
                
        return [
            '#theme' => 'activities-enrollment-status',
            '#items' => $EnrollRecord,
            '#event' => $EventDetail,
            '#empty' => t('No entries available.'),
        ];   

    }


    public function EventEnrollList($evt_id="") {

        $EnrollRecord = ActivitiesDatatable::getEnrollmemtRecord($evt_id);
        $EnrollRecord['time'] = \Drupal::time()->getCurrentTime();
        $EventDetail = ActivitiesDatatable::getEventDetail($evt_id);
                
        return [
            '#theme' => 'activities-enrollment-list',
            '#items' => $EnrollRecord,
            '#event' => $EventDetail,
            '#empty' => t('No entries available.'),
        ];   

    }

    public function deleteEventEnroll($evt_id, $user_id) {


        // delete record   
        $err_code = '0'; 
        $database = \Drupal::database();
        $transaction = $database->startTransaction();           
        try {
          $query = $database->update('kicp_km_event_member_list')->fields([
            'is_deleted'=>1 , 
          ])
          ->condition('evt_id', $evt_id)
          ->condition('user_id', $user_id)
          ->execute();

          \Drupal::logger('activities')->info('KM Enrollment deleted id: %id. user: %user_id.',   
          array(
              '%id' =>  $evt_id,
              '%user_id' =>  $user_id,
          ));               
          $err_code = '1';
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Enrollment has been deleted'));

          $response = array('result' => $err_code);
          return new JsonResponse($response);
  
        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Unable to delete enrollment at this time due to datbase error. Please try again. ' )
                );
            \Drupal::logger('activities')->error('KM enrollment is not deleted: '. $variables);
            $transaction->rollBack();
        }	        
        unset($transaction);                  
        $response = array('result' => $err_code);
        return new JsonResponse($response);
    

    }


    public static function changeEnrollStatus($evt_id, $user_id) {
        
        $err_code = '0';
        $is_enrol_successful = $_REQUEST['is_enrol_successful'];
        $is_showup = $_REQUEST['is_showup'];
        $database = \Drupal::database();
        $transaction = $database->startTransaction();   
        try {
            $database = \Drupal::database();
            $query = $database->update('kicp_km_event_member_list')->fields([
              'is_enrol_successful' => $is_enrol_successful, 
              'is_showup'=>$is_showup,
            ])
            ->condition('evt_id', $evt_id)
            ->condition('user_id', $user_id);
            $orGroup = $query->orConditionGroup();
            $orGroup->condition('is_enrol_successful', $is_enrol_successful, '!=');
            $orGroup->condition('is_showup', $is_showup, '!=' );
            $query->condition($orGroup);
            $affected_rows = $query->execute();
          
            if ($affected_rows) {
                $err_code = '1';
                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('Enrollment has been updated: ').$user_id);
                \Drupal::logger('activities')->info('KM Enrollment updated id: %id. user: %user_id.',   
                array(
                    '%id' =>  $evt_id,
                    '%user_id' =>  $user_id,
                ));                  
            }

        }
        catch (Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Unable to update enrollment at this time due to datbase error. Please try again. ' )
                );
            \Drupal::logger('activities')->error('KM enrollment is not updated: '. $variables);
            $transaction->rollBack();
        }
        unset($transaction);            
        $err_code = '1';
        $response = array('result' => $err_code);
        return new JsonResponse($response);
        
    }


    public function ActivityEvtPhotos($evt_id) {

        $EventDetail = ActivitiesDatatable::getEventDetail($evt_id);
        $EventPhotos = ActivitiesDatatable::getPhotosbyEvent($evt_id);

        $search_str = \Drupal::request()->query->get('search_str');
        $EventPhotos['search_str'] =  $search_str;


        return [
            '#theme' => 'activities-admin-photos',
            '#items' => $EventPhotos,
            '#evt_id' => $evt_id,
            '#type_id' => $EventDetail['evt_type_id'],
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],
        ]; 
    }


    public function ActivityPhotoDelete($evt_photo_id) {

        $photoInfo = ActivitiesDatatable::getEventPhotoInfo($evt_photo_id);
        $evt_id = $photoInfo->evt_id;

        $photo_name = $photoInfo->evt_photo_url;
        $evt_id = $photoInfo->evt_id;
        $file_system = \Drupal::service('file_system');
        $this_evt_id = str_pad($evt_id, 6, "0", STR_PAD_LEFT);
        $ActivitiesPhotoUri = 'private://activities/photo/'.$this_evt_id.'/'.$photo_name;
        $file_location = $file_system->realpath($ActivitiesPhotoUri);

        // delete record
        $database = \Drupal::database();
        $transaction = $database->startTransaction();           
        try {
          $fid = CommonUtil::deleteFile( $ActivitiesPhotoUri);
          $query = $database->update('kicp_km_event_photo')->fields([
            'is_deleted'=>1 , 
          ])
          ->condition('evt_photo_id', $evt_photo_id)
          ->execute();

          \Drupal::logger('activities')->info('KM Event photo deleted id: %id',   
          array(
              '%id' =>  $evt_photo_id,
          ));

          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Photo has been deleted'));
      
        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Unable to delete photo at this time due to datbase error. Please try again. ' )
                );
            \Drupal::logger('activities')->error('KM Event Photo is not deleted: '. $variables);
            $transaction->rollBack();
        }	
        unset($transaction);                  
        $response = array('result' => 1);
        return new JsonResponse($response);            
    
    }
    
    public function ActivityDeliverable($evt_id) {

        $EventDetail = ActivitiesDatatable::getEventDetail($evt_id);
        $deliverable = ActivitiesDatatable::getEventDeliverableByEventId($evt_id);
        $search_str = \Drupal::request()->query->get('search_str');
        $deliverable['search_str'] =  $search_str;

        
        return [
            '#theme' => 'activities-admin-deliverable',
            '#items' => $deliverable,
            '#type_id' => $EventDetail['evt_type_id'],
            '#evt_id' => $evt_id,
            '#empty' => t('No entries available.'),
            'pager' => ['#type' => 'pager',
            ],                
          ];

    }

    public function ActivityDeliverableDelete($evt_deliverable_id="") {
     
        $deliverableInfo = ActivitiesDatatable::getEventDeliverableInfo($evt_deliverable_id);
        $deliverable_name = $deliverableInfo->evt_deliverable_url;
        $evt_id = $deliverableInfo->evt_id;
        $current_time =  \Drupal::time()->getRequestTime();
        $file_system = \Drupal::service('file_system');
        $this_evt_id = str_pad($evt_id, 6, "0", STR_PAD_LEFT);
        $ActivitiesDeliverableUri = 'private://activities/deliverable/'.$this_evt_id.'/'.$deliverable_name;
        $file_location = $file_system->realpath($ActivitiesDeliverableUri);
        $fid = CommonUtil::deleteFile( $ActivitiesDeliverableUri);

        // delete record
        $database = \Drupal::database();
        $transaction = $database->startTransaction();     
        try {
          $query = $database->update('kicp_km_event_deliverable')->fields([
            'is_deleted'=>1 , 
            'modify_datetime' => date('Y-m-d H:i:s', $current_time),
          ])
          ->condition('evt_deliverable_id', $evt_deliverable_id)
          ->execute();

          // delete tags
          $return2 = TagStorage::markDelete('activities_deliverable',$evt_deliverable_id);

          \Drupal::logger('activities')->info('KM Event deliverable deleted id: %id',   
          array(
              '%id' =>  $evt_deliverable_id,
          ));
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Deliverable has been deleted'));
  
        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('nable to delete deliverable at this time due to datbase error. Please try again. ' )
                );
            \Drupal::logger('activities')->error('KM Event deliverable is not deleted: '. $variables);
            $transaction->rollBack();
        }	        
        unset($transaction);
        $response = array('result' => 1);
        return new JsonResponse($response);            
        
    }

    public function getTagContent() {


        $tags = array();
        $tagsUrl = \Drupal::request()->query->get('tags');
    
        if ($tagsUrl) {
          $tags = json_decode($tagsUrl);
          if ($tags && count($tags) > 0 ) {
            $tmp = $tags;
          }
        }
        $activities = ActivitiesDatatable::getActivitiesTags($tags);

        return [
            '#theme' => 'activities-tags',
            '#act_items' => $activities,
            '#empty' => t('No entries available.'),
            '#tags' => $tags,
            '#tagsUrl' => $tmp,            
            '#pager' => ['#type' => 'pager',
            ],                      
        ];   


    }
    
    public function EventEnrollStatusExport($evt_id="") {

        $output= "";

        $EnrollRecord = ActivitiesDatatable::getEnrollmemtRecord($evt_id);
        $EventDetail = ActivitiesDatatable::getEventDetail($evt_id);
        $EnrollRecord['event'] = $EventDetail;

        $output .= '<html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\"><html><head><meta http-equiv=\"Content-type\" content=\"text/html;charset=utf-8\" /></head><body>';        
        $output .= '<h2>Event Name: '.$EventDetail['evt_name'].'</h2>';
        $output .= '<div>Total <strong>'.count($EventDetail).'</strong> enrollment(s) as at '.date('H:i:s').' on '.date('d.m.Y').'</div>';
        $output .= '<div>&nbsp;</div>';
        $output .= '<table border="1">';
        $output .= '<tr>';
        $output .= '<td>Full Name</td>';
        $output .= '<td>Dept</td>';
        $output .= '<td>Rank</td>';
        $output .= '<td>Post</td>';
        $output .= '<td>Tel</td>';
        $output .= '<td>Email</td>';
        $output .= '<td>Registration Time</td>';
        $output .= '</tr>';

        foreach($EnrollRecord as $record) {
            $output .= '<tr>';
            $output .= '<td>'.$record['user_full_name'].'</td>';
            $output .= '<td>'.$record['user_dept'].'</td>';
            $output .= '<td>'.$record['user_rank'].'</td>';
            $output .= '<td>'.$record['user_post_unit'].'</td>';
            $output .= '<td>'.$record['user_phone'].'</td>';
            $output .= '<td>'.$record['user_int_email'].'</td>';
            $output .= '<td>'.$record['evt_reg_datetime'].'</td>';
            $output .= '</tr>';
        }
        $output .= '</table>';
 
        $filename = "Event_".$evt_id."_[".mb_convert_encoding($EventDetail['evt_name'],'UTF-8')."]_Enrollment_Status_" . date('Ymd') . ".xls";
        
        $output .= header('Content-Type: application/vnd.ms-excel; charset=utf-8');       
        $output .= header("Content-Disposition: attachment; filename=\"$filename\"");
        $output .= header('Content-Transfer-Encoding: binary');
        $output .= header('Pragma: no-cache');
        $output .= header('Expires: 0');        
        
        $response = new Response();
        $response->setContent($output);
        return $response;

    }

    public function EventRegistration($action="", $evt_id="") {
        $msg = "";
        if($action === 'enroll') {
            $userInfo = ActivitiesDatatable::getUserInfoForRegistration($this->my_user_id);
            $eventEntry = array(
                'evt_id' => $evt_id,
                'user_id' => $this->my_user_id,
                'uid' => $userInfo->uid,
                'user_dept' => $userInfo->user_dept,
                'user_rank' => $userInfo->user_rank,
                'user_post_unit' => $userInfo->user_post_unit,
                'is_portal_user' => 1,
              );
              $register_member_id = ActivitiesDatatable::insertRegistration($eventEntry);
              if ($register_member_id) {
                $msg = ActivitiesDatatable::getEventReplyMsg($evt_id);
                \Drupal::logger('activities')->info('KM Event enroll id: %id , user_id: %user_id',   
                array(
                    '%id' =>  $evt_id,
                    '%user_id' =>  $this->my_user_id,
                ));
                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('We have received your enrollment.'));
              } 
        } else if($action === 'cancel_enroll') {
            $eventEntry = array(
                'evt_id' => $evt_id,
                'user_id' => $this->my_user_id,
                'cancel_enrol_datetime' => date('Y-m-d H:i:s'),
              );
              $register_member_id = ActivitiesDatatable::changeRegistration($eventEntry); 
              if ($register_member_id) {
                \Drupal::logger('activities')->info('KM Event cancel enroll id: %id , user_id: %user_id',   
                array(
                    '%id' =>  $evt_id,
                    '%user_id' =>  $this->my_user_id,
                ));
                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('We have received your cancellation of enrollment.'));
              }              
        } else if($action === 'reenroll') {
            $eventEntry = array(
                'evt_id' => $evt_id,
                'user_id' => $this->my_user_id,
                'is_reenrol' => 1,
            );
            $register_member_id = ActivitiesDatatable::changeRegistration($eventEntry); 
            if ($register_member_id) {
               $msg = ActivitiesDatatable::getEventReplyMsg($evt_id);
              \Drupal::logger('activities')->info('KM Activities Enrollment (Re-enrollment) id: %id , user_id: %user_id',   
              array(
                  '%id' =>  $evt_id,
                  '%user_id' =>  $this->my_user_id,
              ));
              $messenger = \Drupal::messenger(); 
              $messenger->addMessage( t('We have received your re-enrollment.'));
            }              
        }  else if($action === 'cancel_reenrol') {
           
            $eventEntry = array(
                'evt_id' => $evt_id,
                'user_id' => $this->my_user_id,
                'cancel_reenrol_datetime' => date('Y-m-d H:i:s'),
              );

              $register_member_id = ActivitiesDatatable::changeRegistration($eventEntry); 
              if ($register_member_id) {
                \Drupal::logger('activities')->info('KM Activities Cancel Re-enrollment id: %id , user_id: %user_id',   
                array(
                    '%id' =>  $evt_id,
                    '%user_id' =>  $this->my_user_id,
                ));
                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('We have received your cancellation of re-enrollment.'));
              }              
        }


        return [
            '#type' => 'markup',
            '#markup' => $this->t('<p>'.$msg.'</p><p>Go Back to <a href="../../activities_detail/'.$evt_id.'">Event Page</a></p>'),
          ];


    }

    public static function Breadcrumb() {

        $base_url = Url::fromRoute('activities.content');
        $admin_url = Url::fromRoute('activities.admin_content');
        $admin_cop_url = Url::fromRoute('activities.admin_category');
        $base_path = [
            'name' => 'KM Activities', 
            'url' => $base_url,
        ];
        $admin_path = [
            'name' => 'Admin - Events Type' ,
            'url' =>  $admin_url,
        ];
        $admin_cop_path = [
            'name' => 'COP Category' ,
            'url' =>  $admin_cop_url,
        ];        
        $breads = array();
        $route_match = \Drupal::routeMatch();
        $routeName = $route_match->getRouteName();
        $type_id = $route_match->getParameter('type_id');
        if ($type_id) {
            $type_name = ActivitiesDatatable::getTypeName($type_id);
            if ($type_name) 
               $type_url = Url::fromRoute('activities.content.type', ['type_id' => $type_id]);
        }

        if ($routeName=="activities.content") {
            $breads[] = [
                'name' => 'KM Activities', 
            ];
        } else if ($routeName=="activities.content.type") {
            $breads[] = $base_path;
            $breads[] = [
             'name' => $type_name??'No Activities Type' ,
           ];
        } else if ($routeName=="activities.content.cop") {
            $group_id = $route_match->getParameter('cop_id');
            $group_name = ActivitiesDatatable::getCOPGroupName($group_id);
            $breads[] = $base_path;
            $breads[] = [
                'name' => $type_name??'No Activities Type' ,
                'url' => $type_url??null,
            ];
            if ($type_name) {
            $breads[] = [
                'name' => $group_name??'No COP Group' ,
              ];
            }
        }  else if ($routeName=="activities.content.item") {
            $group_id = $route_match->getParameter('cop_id');
            $item_id = $route_match->getParameter('item_id');
            $group_name = ActivitiesDatatable::getCOPGroupName($group_id);
            $breads[] = $base_path;
            $breads[] = [
                'name' => $type_name??'No Activities Type' ,
                'url' => $type_url??null,
            ];
            if ($group_name) {
                $group_url = Url::fromRoute('activities.content.cop', ['type_id' => $type_id, 'cop_id' => $group_id]);
            }
            $breads[] = [
                'name' => $group_name??'No COP Group' ,
                'url' => $group_url??null,
            ];
            if ($group_name) {
                $item = ActivitiesDatatable::getCOPItem($item_id);
                $breads[] = [
                    'name' => $item['cop_name']??'No COP' ,
                ];
    
            }
        } else if ($routeName=="activities.activities_detail") {
            $evt_id = $route_match->getParameter('evt_id');
            $evt  = ActivitiesDatatable::getEventInfo($evt_id);
            $breads[] = $base_path;
            if ($evt) {
                $type_id = $evt->evt_type_id;
                $evt_name = $evt->evt_name;
                $type_name = ActivitiesDatatable::getTypeName($type_id);
                if ($type_name) {
                    $type_url = Url::fromRoute('activities.content.type', ['type_id' => $type_id]);
                }                
            }
            $breads[] = [
                'name' => $type_name??'No Activitiy' ,
                'url' => $type_url??null,
            ]; 
            if ($evt && $type_id==1) {
                $group_name = $evt->group_name;
                $group_id = $evt->group_id;
                $group_url = Url::fromRoute('activities.content.cop', ['type_id' => $type_id, 'cop_id' => $group_id ]);
                $breads[] = [
                    'name' => $group_name??'No Group' ,
                    'url' => $group_url??null,
                ];         
                $cop_id = $evt->cop_id;
                $cop_name = $evt->cop_name;
                $cop_url = Url::fromRoute('activities.content.item', ['type_id' => $type_id, 'cop_id' => $group_id, 'item_id' => $cop_id ]);
                $breads[] = [
                    'name' => $cop_name??'No Group' ,
                    'url' => $cop_url??null,
                ];         
            }
            if ($type_name) {
                $breads[] = [
                    'name' => $evt_name??'No Event' ,
                ];               
            }        

        } else if ($routeName=="activities.admin_content") {
            $breads[] = $base_path;
            $breads[] = [
                'name' => 'Admin Event Type' ,
            ];
        }  else if ($routeName=="activities.admin_category") {
            $breads[] = $base_path;
            $breads[] = $admin_path;
            $breads[] = [
                'name' => 'COP Category Management' ,
            ];
        } else if ($routeName=="activities.admin_list_add") {
            $breads[] = $base_path;
            $breads[] = $admin_path;
            $breads[] = [
                'name' => 'Add' ,
            ];
        } else if ($routeName=="activities.admin_list_change") {
            $breads[] = $base_path;
            $breads[] = $admin_path;
            $breads[] = [
                'name' => 'Edit' ,
            ];
        } else if ($routeName=="activities.admin_cop_category_change") {
            $breads[] = $base_path;
            $breads[] = $admin_path;
            $breads[] = $admin_cop_path;
            $breads[] = [
                'name' => 'Edit' ,
            ];
        } else if ($routeName=="activities.admin_cop_category_add") {
            $breads[] = $base_path;
            $breads[] = $admin_path;
            $breads[] = $admin_cop_path;
            $breads[] = [
                'name' => 'Add' ,
            ];
        } else if ($routeName=="activities.admin_event") {
            $breads[] = $base_path;
            $breads[] = $admin_path;
            if ($type_name) {
                $breads[] = [
                    'name' => $type_name??null,
                ];
            }
        }  else if ($routeName=="activities.admin_item_add") {
            $breads[] = $base_path;
            $breads[] = $admin_path;
            if ($type_name) {
                $admin_event_url = Url::fromRoute('activities.admin_event', ['type_id' => $type_id]);
                $breads[] = [
                    'name' => $type_name??null,
                    'url' => $admin_event_url??null,
                ];
                $breads[] = [
                    'name' => 'Add',
                ];                
            }
        }  else if ($routeName=="activities.admin_item_change") {
            $evt_id = $route_match->getParameter('evt_id');
            $breads[] = $base_path;
            $breads[] = $admin_path;
            $evt  = ActivitiesDatatable::getEventInfo($evt_id);
            if ($evt) {
                $type_id = $evt->evt_type_id;
                $type_name = ActivitiesDatatable::getTypeName($type_id);
                $evt_name = $evt->evt_name;
            }            
            if ($type_name) {
                $admin_event_url = Url::fromRoute('activities.admin_event', ['type_id' => $type_id]);
                $breads[] = [
                    'name' => $type_name??null,
                    'url' => $admin_event_url??null,
                ];
            }
            $breads[] = [
                'name' => 'Edit',
            ];                

        } else if ($routeName=="activities.admin.enroll_list") {
            $evt_id = $route_match->getParameter('evt_id');
            $breads[] = $base_path;
            $breads[] = $admin_path;
            $evt  = ActivitiesDatatable::getEventInfo($evt_id);
            if ($evt) {
                $type_id = $evt->evt_type_id;
                $type_name = ActivitiesDatatable::getTypeName($type_id);
                $evt_name = $evt->evt_name;
            }            
            if ($type_name) {
                $admin_event_url = Url::fromRoute('activities.admin_event', ['type_id' => $type_id]);
                $breads[] = [
                    'name' => $type_name??null,
                    'url' => $admin_event_url??null,
                ];
            }
            $breads[] = [
                'name' => $evt_name.' - Enrollment List',
            ];                

        } else if ($routeName=="activities.photo") {
            $evt_id = $route_match->getParameter('evt_id');
            $breads[] = $base_path;
            $breads[] = $admin_path;
            $evt  = ActivitiesDatatable::getEventInfo($evt_id);
            if ($evt) {
                $type_id = $evt->evt_type_id;
                $type_name = ActivitiesDatatable::getTypeName($type_id);
                $evt_name = $evt->evt_name;
            }            
            if ($type_name) {
                $admin_event_url = Url::fromRoute('activities.admin_event', ['type_id' => $type_id]);
                $breads[] = [
                    'name' => $type_name??null,
                    'url' => $admin_event_url??null,
                ];
            }
            $breads[] = [
                'name' => $evt_name.' - Photos',
            ];                

        }  else if ($routeName=="activities.photo_add") {
            $evt_id = $route_match->getParameter('evt_id');
            $breads[] = $base_path;
            $breads[] = $admin_path;
            $evt  = ActivitiesDatatable::getEventInfo($evt_id);
            if ($evt) {
                $type_id = $evt->evt_type_id;
                $type_name = ActivitiesDatatable::getTypeName($type_id);
                $evt_name = $evt->evt_name;
            }            
            if ($type_name) {
                $admin_event_url = Url::fromRoute('activities.admin_event', ['type_id' => $type_id]);
                $breads[] = [
                    'name' => $type_name??null,
                    'url' => $admin_event_url??null,
                ];
            }
            $admin_photo_url = Url::fromRoute('activities.photo', ['evt_id' => $evt_id]);
            $breads[] = [
                'name' => $evt_name.' - Photos',
                'url' => $admin_photo_url,
            ];                
            $breads[] = [
                'name' => 'Add',
            ];                


        } else if ($routeName=="activities.deliverable") {
            $evt_id = $route_match->getParameter('evt_id');
            $breads[] = $base_path;
            $breads[] = $admin_path;
            $evt  = ActivitiesDatatable::getEventInfo($evt_id);
            if ($evt) {
                $type_id = $evt->evt_type_id;
                $type_name = ActivitiesDatatable::getTypeName($type_id);
                $evt_name = $evt->evt_name;
            }            
            if ($type_name) {
                $admin_event_url = Url::fromRoute('activities.admin_event', ['type_id' => $type_id]);
                $breads[] = [
                    'name' => $type_name??null,
                    'url' => $admin_event_url??null,
                ];
            }
            $breads[] = [
                'name' => $evt_name.' - Deliverable',
            ];                

        } else if ($routeName=="activities.deliverable_add") {
            $evt_id = $route_match->getParameter('evt_id');
            $breads[] = $base_path;
            $breads[] = $admin_path;
            $evt  = ActivitiesDatatable::getEventInfo($evt_id);
            if ($evt) {
                $type_id = $evt->evt_type_id;
                $type_name = ActivitiesDatatable::getTypeName($type_id);
                $evt_name = $evt->evt_name;
            }            
            if ($type_name) {
                $admin_event_url = Url::fromRoute('activities.admin_event', ['type_id' => $type_id]);
                $breads[] = [
                    'name' => $type_name??null,
                    'url' => $admin_event_url??null,
                ];
            }
            $admin_photo_url = Url::fromRoute('activities.deliverable', ['evt_id' => $evt_id]);
            $breads[] = [
                'name' => $evt_name.' - Deliverable',
                'url' => $admin_photo_url,
            ];                
            $breads[] = [
                'name' => 'Add',
            ];                
        } else if ($routeName=="activities.activities_tag") {
            $breads[] = $base_path;
            $breads[] = [
                'name' => 'Tags' ,
            ];
        } 

        return $breads;
    }


}