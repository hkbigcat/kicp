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

        $this->$user_id = $authen->getUserId();
        
    }
    
    public function content($type_id=1, $cop_id="", $item_id="" ) {
        
        $activitiesType = ActivitiesDatatable::getAllActivityType();
        
        $GroupInfo = ActivitiesDatatable::getCOPGroupInfo();

        if ($cop_id!="" )   { 
            $COPitems = ActivitiesDatatable::getCOPItem($cop_id);

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

        return [
            '#theme' => 'activities-main',
            '#items' => $activityInfo,
            '#groups' => $GroupInfo,
            '#events' => $events,
            '#types' => $activitiesType,
            '#copitems' => $COPitems,
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

        return [
            '#theme' => 'activities-admin',
            '#items' => $activitiesType,
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],
        ];   
        

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
            $PhotoPerPage = 15;
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

          return new RedirectResponse("/activities_admin_event/2");
  
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Event has been deleted'));
  
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addStatus(
                t('Unable to delete event at this time due to datbase error. Please try again. ' )
                );

            }	


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