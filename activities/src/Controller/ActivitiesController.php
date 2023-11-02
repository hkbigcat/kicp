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

        return [
            '#theme' => 'activities-admin-events',
            '#items' => $events,
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],            
        ];   

    }



    public function ActivityEventData($evt_id="") {

        $photos = ActivitiesDatatable::getEventPhotoByEventId($evt_id);

        return [
            '#theme' => 'activities-photo',
            '#items' => $photos,
            '#empty' => t('No entries available.'),
        ];   

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

    


}