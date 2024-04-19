<?php

/**
 * @file
 */

namespace Drupal\activities\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\Controller\TagList;
use Drupal\common\Controller\TagStorage;
use Drupal\common\Follow;
use Drupal\activities\Common\ActivitiesDatatable;
use Drupal\Core\Database\Database;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;


class ActivitiesController extends ControllerBase {
    
    public function __construct() {
        //$Paging = new Paging();
        //$DefaultPageLength = $Paging->getDefaultPageLength();

        $this->module = 'activities';

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->my_user_id = $authen->getUserId();      
        
    }
    
    public function content($type_id=1, $cop_id="", $item_id="" ) {
        
        $activitiesType = ActivitiesDatatable::getAllActivityType();
        $GroupInfo = ActivitiesDatatable::getCOPGroupInfo();
        $COPitems = array();
        $events = array();

        if ($cop_id!="" )   { 
            $COPitems = ActivitiesDatatable::getCOPbyGroupID($cop_id);

            if ($item_id!="" )   { 
                $item_index = array_search($item_id, (array_column($COPitems, 'cop_id')));
                $activityInfo = ['evt_type_name' => $COPitems[$item_index]['cop_name'], 'description' =>  $COPitems[$item_index]['cop_info']];
            } else {
                $activityInfo = ['evt_type_name' => $GroupInfo[$cop_id]['group_name'], 'description' =>  $GroupInfo[$cop_id]['group_description']];
            }
        } else {
            $activityInfo = ActivitiesDatatable::getActivityTypeInfo($type_id);
        }
        $activityInfo['type_id'] = $type_id;

        if ($type_id==1 & $item_id!="" || $type_id!=1 )
            $events = ActivitiesDatatable::getEventItemByTypeId($type_id);

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
        try {
          $database = \Drupal::database();
          $query = $database->update('kicp_km_event_type')->fields([
            'is_deleted'=>1 , 
          ])
          ->condition('evt_type_id', $evt_type_id)
          ->execute();

          $err_code = '1';
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('KM Event Type has been deleted'));
      
  
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addError(
                t('Unable to delete KM Event Type at this time due to datbase error. Please try again. ' )
                );

            }	        
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
        try {
          $database = \Drupal::database();
          $query = $database->update('kicp_km_cop_group')->fields([
            'is_deleted'=>1 , 
          ])
          ->condition('group_id', $group_id)
          ->execute();

          $err_code = '1';
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('KM COP Category has been deleted'));
      
  
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addError(
                t('Unable to delete KM COP Category at this time due to datbase error. Please try again. ' )
                );

            }	        
        $response = array('result' => $err_code);
        return new JsonResponse($response);

    }

    public function AdminActivityCOP($group_id="") {

        $COPitems = ActivitiesDatatable::getCOPbyGroupID($group_id);
        $copGroupInfo = ActivitiesDatatable::getCOPGroupInfo($group_id);
        $search_str = \Drupal::request()->query->get('search_str');
        $COPitems['search_str'] =  $search_str;        

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
        try {
          $database = \Drupal::database();
          $query = $database->update('kicp_km_cop')->fields([
            'is_deleted'=>1 , 
          ])
          ->condition('cop_id', $cop_id)
          ->execute();

          $err_code = '1';
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('KM COP Event Category has been deleted'));
      
  
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addError(
                t('Unable to delete KM COP  Event Category at this time due to datbase error. Please try again. ' )
                );

            }	        
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
    
        try {
          $database = \Drupal::database();
          $query = $database->update('kicp_km_event')->fields([
            'is_deleted'=>1 , 
            'modify_datetime' => date('Y-m-d H:i:s', $current_time),
          ])
          ->condition('evt_id', $evt_id)
          ->execute();

          // delete tags
          $return2 = TagStorage::markDelete($this->module, $evt_id);

          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Event has been deleted'));
  
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addError(
                t('Unable to delete event at this time due to datbase error. Please try again. ' )
                );

            }	
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
        try {
          $database = \Drupal::database();
          $query = $database->update('kicp_km_event_member_list')->fields([
            'is_deleted'=>1 , 
          ])
          ->condition('evt_id', $evt_id)
          ->condition('user_id', $user_id)
          ->execute();

          $err_code = '1';
          
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Enrollment has been deleted'));

          $response = array('result' => $err_code);
          return new JsonResponse($response);
  
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addError(
                t('Unable to delete enrollment at this time due to datbase error. Please try again. ' )
                );

            }	        
        $response = array('result' => $err_code);
        return new JsonResponse($response);
    

    }


    public static function changeEnrollStatus($evt_id, $user_id) {
        
        $err_code = '0';
        $is_enrol_successful = $_REQUEST['is_enrol_successful'];
        $is_showup = $_REQUEST['is_showup'];

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
            }

        }
        catch (Exception $e) {
            \Drupal::messenger()->addError(
                t('Unable to update enrollment at this time due to datbase error. Please try again. '.$e )
                );
        }
        
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
        if(file_exists($file_location)) {
            unlink($file_location);
        }   

        // delete record
        try {
          $database = \Drupal::database();
          $query = $database->update('kicp_km_event_photo')->fields([
            'is_deleted'=>1 , 
          ])
          ->condition('evt_photo_id', $evt_photo_id)
          ->execute();

          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Photo has been deleted'));
      
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addError(
                t('Unable to delete photo at this time due to datbase error. Please try again. ' )
                );

            }	
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
        if(file_exists($file_location)) {
            unlink($file_location);
        }        

        // delete record
    
        try {
          $database = \Drupal::database();
          $query = $database->update('kicp_km_event_deliverable')->fields([
            'is_deleted'=>1 , 
            'modify_datetime' => date('Y-m-d H:i:s', $current_time),
          ])
          ->condition('evt_deliverable_id', $evt_deliverable_id)
          ->execute();

          // delete tags
          $return2 = TagStorage::markDelete('activities_deliverable',$evt_deliverable_id);
      
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Deliverable has been deleted'));
  
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addError(
                t('Unable to delete deliverable at this time due to datbase error. Please try again. ' )
                );

            }	        
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
            'pager' => ['#type' => 'pager',
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

}