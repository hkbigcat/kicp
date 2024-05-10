<?php

/**
 * @file
 */

namespace Drupal\ppcactivities\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\Controller\TagList;
use Drupal\common\Controller\TagStorage;
use Drupal\common\Follow;
use Drupal\ppcactivities\Common\PPCActivitiesDatatable;
use Drupal\Core\Database\Database;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;


class PPCActivitiesController extends ControllerBase {
    
    public function __construct() {
        $this->module = 'ppcactivities';

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->my_user_id = $authen->getUserId();    
    }
    
    public function content( $cop_id="1", $type_id="") {
        
        $activitiesType = PPCActivitiesDatatable::getAllActivityType();
        $activityInfo = PPCActivitiesDatatable::getActivityTypeInfo($type_id);
        $activityInfo['type_id'] = $type_id;
        $category = PpcActivitiesDatatable::getAllActivityCategory();
        $events = PPCActivitiesDatatable::getEventItemByTypeId($type_id, $cop_id);
        $following = Follow::getFollow('PPCIS.OGCIO', $this->my_user_id);    

        return [
            '#theme' => 'ppcactivities-main',
            '#items' => $activityInfo,
            '#cop_id' => $cop_id,
            '#categories' => $category,
            '#events' => $events,
            '#types' => $activitiesType,
            '#my_user_id' => $this->my_user_id,
            '#following' => $following,                        
            '#empty' => t('No entries available.'),
        ];                

    }


    public function ActivityDetail($evt_id="") {

        $EventDetail = PPCActivitiesDatatable::getEventDetail($evt_id);
        $activitiesType = PPCActivitiesDatatable::getAllActivityType();
        $category = PpcActivitiesDatatable::getAllActivityCategory();

        return [
            '#theme' => 'ppcactivities-details',
            '#items' => $EventDetail,
            '#types' => $activitiesType,
            '#cop_id' => $EventDetail['cop_id'],
            '#categories' => $category,
            '#empty' => t('No entries available.'),
        ];                


    }


    public function AdminCategory() {

        $COPInfo = PPCActivitiesDatatable::getCOPInfo();
        $search_str = \Drupal::request()->query->get('search_str');
        $COPInfo['search_str'] =  $search_str;

        return [
            '#theme' => 'ppcactivities-admin-category',
            '#items' =>  $COPInfo,
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],
        ];   
        
    }

    public function deletePpcCategory($cop_id) {

        // delete record   
        $err_code = '0'; 
        $database = \Drupal::database();
        $transaction = $database->startTransaction();           
        try {
            $query = $database->update('kicp_ppc_cop')->fields([
            'is_deleted'=>1 , 
            ])
            ->condition('cop_id', $cop_id)
            ->execute();
            \Drupal::logger('ppcactivities')->info('Type deleted id: %id',   
            array(
                '%id' => $evt_type_id,
            )); 
            $err_code = '1';
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('PPC Category has been deleted'));
        
    
        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Unable to delete PPC Category at this time due to datbase error. Please try again. ' )
                );
            \Drupal::logger('ppcactivities')->error('PPC Category is not deleted: ' . $variables);       
            $transaction->rollBack(); 
        }	        
        unset($transaction);      
        $response = array('result' => $err_code);
        return new JsonResponse($response);

    }

    public function AdminType() {

        $activitiesType = PPCActivitiesDatatable::getAllActivityType();
        $search_str = \Drupal::request()->query->get('search_str');
        $COPInfo['search_str'] =  $search_str;

        return [
            '#theme' => 'ppcactivities-admin-type',
            '#items' =>  $activitiesType,
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
          $query = $database->update('kicp_ppc_event_type')->fields([
            'is_deleted'=>1 , 
          ])
          ->condition('evt_type_id', $evt_type_id)
          ->execute();
          \Drupal::logger('ppcactivities')->info('Type deleted id: %id',   
          array(
              '%id' => $evt_type_id,
          ));              

          $err_code = '1';
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('PPC Event Type has been deleted'));
        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Unable to delete PPC  Event Type at this time due to datbase error. Please try again. ' )
                );
            \Drupal::logger('ppcactivities')->error('PPC Activity Type is not deleted: ' . $variables);       
            $transaction->rollBack();    
        }	
        unset($transaction);            
        $response = array('result' => $err_code);
        return new JsonResponse($response);
    
    }    


    public function AdminEvents() {

        $events = PPCActivitiesDatatable::getAdminEvents();

        $search_str = \Drupal::request()->query->get('search_str');
        $events['search_str'] =  $search_str;

        return [
            '#theme' => 'ppcactivities-admin-events',
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
            $EventDetail = PPCActivitiesDatatable::getEventDetail($evt_id);
            $output .= nl2br($EventDetail['evt_description']);   

        }  else if($type == 'photo') {
             
            $photos = PPCActivitiesDatatable::getEventPhotoByEventId($evt_id);
            $PhotoPerPage = 15;
            $renderable = [
                '#theme' => 'ppcactivities-photo',
                '#items' => $photos,
                '#PhotoPerPage' => $PhotoPerPage,
                '#evt_id' => $evt_id,
              ];
            $output .= \Drupal::service('renderer')->renderPlain($renderable);

        } else if ($type == 'deliverable') {

            $deliverable = PPCActivitiesDatatable::getEventDeliverableByEventId($evt_id);
            $renderable = [
                '#theme' => 'ppcactivities-deliverable',
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
        
        $current_time =  \Drupal::time()->getRequestTime();
        // delete record
        $database = \Drupal::database();
        $transaction = $database->startTransaction();   
        try {
          $database = \Drupal::database();
          $query = $database->update('kicp_ppc_event')->fields([
            'is_deleted'=>1 , 
            'modify_datetime' => date('Y-m-d H:i:s', $current_time),
          ])
          ->condition('evt_id', $evt_id)
          ->execute();

          // delete tags
          $return2 = TagStorage::markDelete($this->module, $evt_id);
          \Drupal::logger('ppcactivities')->info('PPC Event deleted id: %id',   
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
            \Drupal::logger('ppcactivities')->error('KM Event is not deleted: '. $variables);
            $transaction->rollBack();
        }	
        unset($transaction);  
        $response = array('result' => 1);
        return new JsonResponse($response);
  
    }


    public function EventEnrollStatus($evt_id="") {

        $EnrollRecord = PPCActivitiesDatatable::getEnrollmemtRecord($evt_id);
        $EnrollRecord['time'] = \Drupal::time()->getCurrentTime();
        $EventDetail = PPCActivitiesDatatable::getEventDetail($evt_id);
                
        return [
            '#theme' => 'ppcactivities-enrollment-status',
            '#items' => $EnrollRecord,
            '#event' => $EventDetail,
            '#empty' => t('No entries available.'),
        ];   

    }


    public function EventEnrollList($evt_id="") {

        $EnrollRecord = PPCActivitiesDatatable::getEnrollmemtRecord($evt_id);
        $EnrollRecord['time'] = \Drupal::time()->getCurrentTime();
        $EventDetail = PPCActivitiesDatatable::getEventDetail($evt_id);
                
        return [
            '#theme' => 'ppcactivities-enrollment-list',
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
          $database = \Drupal::database();
          $query = $database->update('kicp_ppc_event_member_list')->fields([
            'is_deleted'=>1 , 
          ])
          ->condition('evt_id', $evt_id)
          ->condition('user_id', $user_id)
          ->execute();

          \Drupal::logger('ppcactivities')->info('PPC Enrollment deleted id: %id. user: %user_id.',   
          array(
              '%id' =>  $evt_id,
              '%user_id' =>  $user_id,
          ));          
          $err_code = '1';
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Enrollment has been deleted'));
      
  
        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Unable to delete enrollment at this time due to datbase error. Please try again. ' )
                );
            \Drupal::logger('ppcactivities')->error('PPC enrollment is not deleted: '. $variables);
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
            $query = $database->update('kicp_ppc_event_member_list')->fields([
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
                \Drupal::logger('ppcactivities')->info('PPC Enrollment updated id: %id. user: %user_id.',   
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
            \Drupal::logger('ppcactivities')->error('PPC enrollment is not updated: '. $variables);
            $transaction->rollBack();
        }
        unset($transaction); 
        
        $err_code = '1';
        $response = array('result' => $err_code);
        return new JsonResponse($response);
        
    }


    public function ActivityEvtPhotos($evt_id) {

        $EventDetail = PPCActivitiesDatatable::getEventDetail($evt_id);
        $EventPhotos = PPCActivitiesDatatable::getPhotosbyEvent($evt_id);

        $search_str = \Drupal::request()->query->get('search_str');
        $EventPhotos['search_str'] =  $search_str;


        return [
            '#theme' => 'ppcactivities-admin-photos',
            '#items' => $EventPhotos,
            '#evt_id' => $evt_id,
            '#type_id' => $EventDetail['evt_type_id'],
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],
        ]; 
    }


    public function ActivityPhotoDelete($evt_photo_id) {


        $photoInfo = PPCActivitiesDatatable::getEventPhotoInfo($evt_photo_id);
        $evt_id = $photoInfo->evt_id;

        $photo_name = $photoInfo->evt_photo_url;
        $evt_id = $photoInfo->evt_id;
        $file_system = \Drupal::service('file_system');
        $this_evt_id = str_pad($evt_id, 6, "0", STR_PAD_LEFT);
        $ActivitiesPhotoUri = 'private://ppcactivities/photo/'.$this_evt_id.'/'.$photo_name;
        $file_location = $file_system->realpath($ActivitiesPhotoUri);
        $fid = CommonUtil::deleteFile( $ActivitiesPhotoUri);        

        // delete record
        $database = \Drupal::database();
        $transaction = $database->startTransaction();          
        try {
          $query = $database->update('kicp_ppc_event_photo')->fields([
            'is_deleted'=>1 , 
          ])
          ->condition('evt_photo_id', $evt_photo_id)
          ->execute();
          \Drupal::logger('ppcactivities')->info('PPC Event photo deleted id: %id',   
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
            \Drupal::logger('ppcactivities')->error('KM Event Photo is not deleted: '. $variables);
            $transaction->rollBack();
        }	
        unset($transaction);         
        $response = array('result' => 1);
        return new JsonResponse($response);     

    }
    
    public function ActivityDeliverable($evt_id) {

        $EventDetail = PPCActivitiesDatatable::getEventDetail($evt_id);
        $deliverable = PPCActivitiesDatatable::getEventDeliverableByEventId($evt_id);
        $search_str = \Drupal::request()->query->get('search_str');
        $deliverable['search_str'] =  $search_str;

        
        return [
            '#theme' => 'ppcactivities-admin-deliverable',
            '#items' => $deliverable,
            '#type_id' => $EventDetail['evt_type_id'],
            '#evt_id' => $evt_id,
            '#empty' => t('No entries available.'),
            'pager' => ['#type' => 'pager',
            ],                
          ];

    }

    public function ActivityDeliverableDelete($evt_deliverable_id="") {
     
        $deliverableInfo = PPCActivitiesDatatable::getEventDeliverableInfo($evt_deliverable_id);
        $deliverable_name = $deliverableInfo->evt_deliverable_url;
        $evt_id = $deliverableInfo->evt_id;
        $current_time =  \Drupal::time()->getRequestTime();
        $file_system = \Drupal::service('file_system');
        $this_evt_id = str_pad($evt_id, 6, "0", STR_PAD_LEFT);
        $ActivitiesDeliverableUri = 'private://ppcactivities/deliverable/'.$this_evt_id.'/'.$deliverable_name;
        $file_location = $file_system->realpath($ActivitiesDeliverableUri);
        $fid = CommonUtil::deleteFile( $ActivitiesDeliverableUri);

        // delete record
        $database = \Drupal::database();
        $transaction = $database->startTransaction();  
        try {
          $query = $database->update('kicp_ppc_event_deliverable')->fields([
            'is_deleted'=>1 , 
            'modify_datetime' => date('Y-m-d H:i:s', $current_time),
          ])
          ->condition('evt_deliverable_id', $evt_deliverable_id)
          ->execute();

          // delete tags
          $return2 = TagStorage::markDelete('ppcactivities_deliverable',$evt_deliverable_id);

          \Drupal::logger('ppcactivities')->info('PPC Event deliverable deleted id: %id',   
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
            \Drupal::logger('ppcactivities')->error('PPC Event deliverable is not deleted: '. $variables);
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
        $activities = PPCActivitiesDatatable::getActivitiesTags($tags);

        return [
            '#theme' => 'ppcactivities-tags',
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

        $EnrollRecord = PPCActivitiesDatatable::getEnrollmemtRecord($evt_id);
        $EventDetail = PPCActivitiesDatatable::getEventDetail($evt_id);
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