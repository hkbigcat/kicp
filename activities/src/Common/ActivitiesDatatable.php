<?php

/**
 * @file
 */

namespace Drupal\activities\Common;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\Core\Database\Database;
use Drupal\common\TagList;
use Drupal\common\LikeItem;
use Drupal\video\Common\VideoDatatable;
use Drupal\Core\Utility\Error;

class ActivitiesDatatable {

    public static function getAllActivityType() {

        $search_str = \Drupal::request()->query->get('search_str');
        $cond = "";
        $database = \Drupal::database();
        if ($search_str && $search_str !="") {
            $escaped = $database->escapeLike($search_str);
            $cond = " and a.evt_type_name like '%$escaped%' ";
        }                    
        $sql = "SELECT a.evt_type_id, a.evt_type_name, a.description, a.display_order, IF(COUNT(b.evt_id)>0, false, true) AS allow_delete 
                FROM kicp_km_event_type a LEFT JOIN kicp_km_event b on (b.evt_type_id = a.evt_type_id AND b.is_deleted = 0) 
                WHERE a.is_deleted = 0 $cond GROUP BY a.evt_type_id, a.evt_type_name,  a.description, a.display_order ORDER BY a.display_order";
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getTypeName($type_id="") {

        if ($type_id=="")
            return null;

        $sql = "SELECT evt_type_name FROM kicp_km_event_type WHERE evt_type_id = '$type_id'";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        if ($result)
            return $result->evt_type_name;
        else return null;
    }

    public static function getCOPGroupInfo($group_id="") {

        $search_str = \Drupal::request()->query->get('search_str');       

        $database = \Drupal::database();
        $query = $database-> select('kicp_km_cop_group', 'a'); 
        $query-> fields('a', ['group_id', 'group_name',' group_description', 'img_name']);
        $query-> condition('a.is_deleted', '0');

        if ($group_id != "") {
            $query-> condition('a.group_id', $group_id);
            $result = $query->execute()->fetchAssoc();
        } else {
            if ($search_str && $search_str !="") {
                $escaped = $database->escapeLike($search_str);
                $query->condition('a.group_name', '%' . $escaped . '%', 'LIKE');
            }                 
            $query -> leftjoin('kicp_km_cop', 'b', 'a.group_id = b.cop_group_id AND b.is_deleted=:is_deleted' , [':is_deleted' => 0]);
            $query->addExpression('COUNT(b.cop_id)', 'cop_total');
            $query->groupBy('a.group_id');
            $query-> orderBy('a. group_id');
            $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
            $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);        
        }

        return $result;        

    }    

    public static function getCOPGroupName($cop_group_id) {
        $sql = "SELECT group_name FROM kicp_km_cop_group WHERE group_id = '$cop_group_id'";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        if ($result)
            return $result->group_name;
        else return null;
    }    

    public static function getActivityTypeInfo($type_id=1) {

        $sql = "SELECT evt_type_name, description, display_order FROM kicp_km_event_type WHERE is_deleted = 0 AND evt_type_id=" . $type_id;
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAssoc();

        return $result;
        
    }

    public static function getCOPbyGroupID($group_id="") {

        if ($group_id=="")
          return null;

        $search_str = \Drupal::request()->query->get('search_str');
        $database = \Drupal::database();
        $query = $database-> select('kicp_km_cop', 'a'); 
        $query -> leftjoin('kicp_km_event', 'b', 'b.cop_id=a.cop_id AND b.evt_type_id=:evt_type_id AND b.is_deleted=:is_deleted' , [':evt_type_id' => 1, ':is_deleted' => 0]);
        $query-> fields('a', ['cop_id', 'cop_name',' cop_info', 'img_name', 'cop_group_id', 'display_order']);
        $query->addExpression('COUNT(b.evt_id)', 'evt_total');
        $query-> condition('a.cop_group_id', $group_id);
        $query-> condition('a.is_deleted', '0');
        if ($search_str && $search_str !="") {
            $escaped = $database->escapeLike($search_str);
            $query->condition('a.cop_name', '%' . $escaped . '%', 'LIKE');
        }          
        $query->groupBy('a.cop_id');
        $query-> orderBy('a.display_order');
        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);        

        return $result; 

        
    }
    public static function getCOPItem($cop_id="") {

        $database = \Drupal::database();
        $query = $database-> select('kicp_km_cop', 'a'); 
        $query-> fields('a', ['cop_id', 'cop_name',' cop_info', 'img_name', 'cop_group_id', 'display_order']);
        $query-> condition('a.is_deleted', '0');
        if ($cop_id != "") {
            $query-> condition('a.cop_id', $cop_id);
            $result = $query->execute()->fetchAssoc();
        } else {
            $query-> orderBy('a.cop_group_id');
            $query-> orderBy('a.display_order');
            $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
            $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);        
        }

        return $result; 


    }


    public static function getEventItemByTypeId($type_id, $item_id = "", $currentEventOnly=false) {

        $output=array();
        $cond = '';

        $current_user = \Drupal::currentUser();
        $my_user_id = $current_user->getAccountName();    

        if ($type_id == 1 && $item_id != "") {
            $cond .= ' AND cop_id=' . $item_id;
        }
        
        if($currentEventOnly) {
            $cond .= ' AND evt_start_date > NOW() ';
        }

        $sql = 'SELECT evt_id, evt_name, evt_start_date, evt_end_date, evt_logo_url, allow_likes, num_likes FROM kicp_km_event WHERE evt_type_id = \'' . $type_id . '\' AND is_deleted = 0 AND is_visible = 1 AND is_archived = 0 ' . $cond . ' ORDER BY evt_start_date DESC, evt_end_date DESC, evt_name';       
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        
        if (!$result) 
          return null;

        foreach ($result as $record) {
            $record["countlike"] = LikeItem::countLike('activities', $record["evt_id"]);
            $record["liked"] = LikeItem::countLike('activities', $record["evt_id"],$my_user_id);
            $output[] = $record;
        }
        return $output;
 
    }

    public static function getAdminEvents($type_id) {

        //$sql = 'SELECT a.evt_id, a.evt_id, a.evt_id, a.evt_end_date, IF(a.is_recent,\'Y\', \'N\') as is_recent, IF(a.is_visible, \'Y\', \'N\') as is_visible, b.evt_type_name 
        //FROM kicp_km_event a LEFT JOIN kicp_km_event_type b ON (b.evt_type_id=a.evt_type_id AND b.is_deleted=0) WHERE a.evt_type_id ='.$type_id.' AND a.is_archived = 0 AND a.is_deleted = 0 ORDER BY a.evt_start_date DESC';

        $search_str = \Drupal::request()->query->get('search_str');
        
        $database = \Drupal::database();
        $query = $database-> select(' kicp_km_event', 'a'); 
        $query -> join('kicp_km_event_type', 'b', 'a.evt_type_id = b.evt_type_id');
        if ($type_id==1) {
            $query -> join('kicp_km_cop', 'c', 'a.cop_id = c.cop_id');
            $query-> fields('c', ['cop_name']);
            $query-> condition('c.is_deleted', '0');
        }
        $query-> fields('a', ['evt_id', 'evt_name', 'evt_start_date', 'evt_end_date', 'is_recent', 'is_visible' ]);
        $query-> fields('b', ['evt_type_name']);
        $query-> condition('a.evt_type_id', $type_id);
        $query-> condition('a.is_archived', '0');
        $query-> condition('a.is_deleted', '0');
        $query-> condition('b.is_deleted', '0');
        if ($search_str && $search_str !="") {
            $escaped = $database->escapeLike($search_str);
            $query->condition('a.evt_name', '%' . $escaped . '%', 'LIKE');
        }                    
        $query-> orderBy('a.evt_start_date', 'DESC');

        
        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);        

        return $result;
    }


    public static function getEventInfo($evt_id) {

        $sql = "SELECT evt_name, evt_type_id, cop_id FROM  kicp_km_event wHERE evt_id = '$evt_id'";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();

        if ($result) {
            if ($result->evt_type_id == 1) {
                $sql = "SELECT a.cop_name, b.group_id, b.group_name FROM  kicp_km_cop a left join kicp_km_cop_group b on a.cop_group_id = b.group_id wHERE a.cop_id = '$result->cop_id'";
                $group = $database-> query($sql)->fetchObject();        
                if ($group) {
                    $result->cop_name =  $group->cop_name;
                    $result->group_id =  $group->group_id;
                    $result->group_name =  $group->group_name;
                }
            }
        }


        return $result;        
    }

    public static function getEventDetail($evt_id="") {

        $record = array();
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $current_user = \Drupal::currentUser();
        $my_user_id = $current_user->getAccountName();    

        if ($evt_id == "") {
            return $record;
        }

        $sql = "SELECT a.evt_id, a.evt_name, a.evt_type_id, a.evt_start_date, a.evt_end_date, a.evt_enroll_start, a.evt_enroll_end, a.evt_description, a.evt_is_cop_evt, a.cop_id, a.survey_id, a.venue, b.display, b.template, a.is_recent, a.is_visible, a.evt_recent_status, a.evt_capacity, a.enroll_URL, a.current_enroll_status, a.submit_reply, a.forum_topic_id, a.evt_logo_url, a.user_id, EXISTS (SELECT 1 FROM kicp_km_event_photo where evt_id = '$evt_id' and  is_deleted=0 ) AS has_photo,  EXISTS (SELECT 1 FROM kicp_km_event_deliverable where evt_id = '$evt_id' and  is_deleted=0 ) AS has_deliverable  FROM kicp_km_event a LEFT JOIN kicp_km_event_submitreply b ON (a.submit_reply = b.reply AND b.is_visible = 1) WHERE a.evt_id='$evt_id' AND a.is_archived = 0 AND a.is_deleted = 0 ORDER BY a.evt_enroll_end DESC";       
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAssoc();

        if (!$result) return null;

        $NoOfEnrolled = self::numOfEventEnrollment($evt_id);
        $NoOfEnrolled??0;
        $CurrentTimestamp = time();
        if($CurrentTimestamp > strtotime($result['evt_enroll_start']) && $CurrentTimestamp < strtotime($result['evt_enroll_end'])) {
            $result["enroll_period"] = true;
            if ( !$result['evt_capacity'] || $result['evt_capacity'] =="" || $result['evt_capacity'] == 0 || $result['evt_capacity'] > $NoOfEnrolled ) {
                $RegistrationInfo = self::getEventRegistrationInfo($evt_id, $my_user_id);
                if (!$RegistrationInfo) {
                    $result["enroll_action"] = 'enroll';
                    $result["enroll_text"] = 'Enroll';
                } else if ($RegistrationInfo->evt_reg_datetime != "" && $RegistrationInfo->cancel_enrol_datetime == "" && $RegistrationInfo->cancel_reenrol_datetime == "") {
                    $result["enroll_action"] = 'cancel_enroll';
                    $result["enroll_text"] = 'Cancel Enroll';
                } else if($RegistrationInfo->cancel_enrol_datetime != "" && $RegistrationInfo->is_reenrol == 0) { 
                    $result["enroll_action"] = 'reenroll';
                    $result["enroll_text"] = 'Re-enroll';
                } else if($RegistrationInfo->is_reenrol == 1 && $RegistrationInfo->cancel_reenrol_datetime == "") {
                    $result["enroll_action"] = 'cancel_reenrol';
                    $result["enroll_text"] = 'Cancel Re-enroll';
                } else {
                    $result["enroll_text"] = 'Please contact KICP Adminitrator if you would like to re-enroll this event.';
                }
            }
        } else {
            $result["enroll_period"] = false;
        }
        $result["has_video"] = VideoDatatable::getMediaEventbyEvtID($evt_id, 'KM', $my_user_id);
        $TagList = new TagList();      
        $result["user"] = $authen->getKICPUserInfo($result["user_id"]);
        $result["tags"] = $TagList->getTagsForModule('activities', $result["evt_id"]);
        $result["countlike"] = LikeItem::countLike('activities', $result["evt_id"]);
        $result["liked"] = LikeItem::countLike('activities', $result["evt_id"],$my_user_id);    

        
        return $result;
        
        
    }
    
    public static function getEventPhotoByEventId($evt_id) {
        $record = array();
        if($evt_id == "") {
            return $record;
        } else {
            $sql = 'SELECT evt_photo_id, evt_photo_url, evt_photo_description FROM kicp_km_event_photo WHERE evt_id='.$evt_id.' AND is_deleted = 0 ORDER BY evt_photo_url';
            $database = \Drupal::database();
            $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);  

            return $result;
        }
    } 
    
    public static function getEventReplyMsg($evt_id="") {
        $sql = "SELECT display FROM kicp_km_event_submitreply WHERE is_visible = 1 AND reply = (select submit_reply from kicp_km_event where evt_id='$evt_id')  ORDER BY id desc limit 1";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();  
        return $result->display;

    }

    public static function getEventSubmitReply() {
        $output = array();

        $sql = "SELECT id, reply, display FROM kicp_km_event_submitreply WHERE is_visible = 1 ORDER BY reply";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);  
        foreach($result as $record) {
            $output[$record['reply']] = $record['display'];
        }
        
        return $output;
    }

    public static function getEventDeliverableByEventId($evt_id) {
        $record = array();
        $output = array();
        $current_user = \Drupal::currentUser();
        $my_user_id = $current_user->getAccountName();    

        $search_sql = "";
        $search_str = \Drupal::request()->query->get('search_str');

        $database = \Drupal::database();
        if ($search_str && $search_str !="") {
            $escaped = $database->escapeLike($search_str);
            $search_sql = " and ( evt_deliverable_url like '%$escaped%' or  evt_deliverable_name like '%$escaped%' ) ";
        }

        if($evt_id == "") {
            return $record;
        } else {
            $sql = "SELECT evt_deliverable_id, evt_deliverable_url, evt_deliverable_name FROM kicp_km_event_deliverable WHERE evt_id='$evt_id' AND is_deleted = 0 $search_sql ORDER BY evt_deliverable_url";
            
            $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);  
            if (!$result) 
              return null;
  
            $TagList = new TagList();
            foreach ($result as $record) {
                $record["tags"] = $TagList->getTagsForModule('activities_deliverable', $record["evt_deliverable_id"]);
                $record["countlike"] = LikeItem::countLike('activities_deliverable', $record["evt_deliverable_id"]);
                $record["liked"] = LikeItem::countLike('activities_deliverable', $record["evt_deliverable_id"],$my_user_id); 
                $output[] = $record;
            }
            return $output;
        }
    }

    public static function getEventDeliverableInfo($evt_deliverable_id) {

        $sql = "SELECT * FROM kicp_km_event_deliverable WHERE is_deleted = 0 and evt_deliverable_id = '$evt_deliverable_id' ";        
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result;
            
    }

    public static function getEnrollmemtRecord($evt_id) {
        
        $record = array();
        if(empty($evt_id)) {
            return $record;
        } 
        
        $sql = "SELECT  b.user_full_name, b.user_rank, b.user_post_unit, b.user_dept, b.user_phone, b.user_lotus_email, a.evt_reg_datetime, b.user_id, a.is_enrol_successful, a.is_showup, b.user_int_email 
                         FROM kicp_km_event_member_list a INNER JOIN xoops_users b ON (a.user_id=b.user_id)
                         WHERE a.is_portal_user=1 AND a.evt_id=".$evt_id." AND a.evt_reg_datetime IS NOT NULL AND a.is_deleted = 0 AND (a.cancel_enrol_datetime IS NULL OR (a.is_reenrol = 1 AND a.cancel_reenrol_datetime IS NULL))
                    
                        UNION
                    
                    SELECT CONCAT (d.user_surname,' ', d.user_givenname) AS user_full_name, d.user_rank AS user_rank, d.user_post AS user_post_unit, d.user_dept, d.user_office_tel AS user_phone, d.user_lotus_email AS user_lotus_email, c.evt_reg_datetime, c.user_id, c.is_enrol_successful, c.is_showup, d.user_int_email 
                    FROM kicp_nonportal_users d 
                    INNER JOIN kicp_km_event_member_list c ON (d.uid=c.uid) 
                    WHERE c.is_deleted = 0 AND c.is_portal_user=0 AND d.uid IN (SELECT uid FROM kicp_km_event_member_list WHERE evt_id=".$evt_id." AND evt_reg_datetime IS NOT NULL)
                        AND c.evt_id=".$evt_id."                            
                    ORDER BY evt_reg_datetime DESC";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }
    
    public static function numOfEventEnrollment($evt_id) {
        
        $sql = "SELECT COUNT(1) as total_enrol FROM kicp_km_event_member_list WHERE evt_id='$evt_id' AND evt_reg_datetime IS NOT NULL AND is_deleted = 0 AND (cancel_enrol_datetime IS NULL OR (is_reenrol = 1 AND cancel_reenrol_datetime IS NULL))" ;
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result->total_enrol;
        
    }

    public static function getEventRegistrationInfo($evt_id, $user_id) {
        
        $sql = "SELECT evt_reg_datetime, is_enrol_successful, is_showup, is_reenrol, cancel_enrol_datetime, cancel_reenrol_datetime, is_deleted, is_enrol_successful, is_showup FROM kicp_km_event_member_list WHERE evt_id='$evt_id' AND user_id = '$user_id' ORDER BY evt_reg_datetime DESC LIMIT 1";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result;
        
    }    

    public static function getUserInfoForRegistration($user_id) {
        $sql = "SELECT uid, user_dept, user_rank, user_post_unit FROM xoops_users WHERE user_id= '$user_id'";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result;
    }

    public static function getPhotosbyEvent($evt_id) {

        $database = \Drupal::database();
        $query = $database-> select('kicp_km_event_photo', 'a');
        $query->fields('a', ['evt_id', 'evt_photo_id', 'evt_photo_url', 'evt_photo_description']);
        $query->condition('a.evt_id', $evt_id);
        $query->condition('a.is_deleted', 0);

        $search_str = \Drupal::request()->query->get('search_str');
        if ($search_str && $search_str !="") {
            $escaped = $database->escapeLike($search_str);
            $orGroup = $query->orConditionGroup();
            $orGroup->condition('a.evt_photo_url', '%' . $escaped . '%', 'LIKE');
            $orGroup->condition('a.evt_photo_description', '%' . $escaped . '%', 'LIKE');
            $query->condition($orGroup);
        }                   

        $query-> orderBy('a.evt_photo_url');

        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);        

        return $result;

    }
    

    public static function getEventPhotoInfo($evt_photo_id) {
        
        $record = array();
        
        if($evt_photo_id == "") {
            return $record;
        } else {
            $sql = 'SELECT evt_id, evt_photo_url, evt_photo_description FROM kicp_km_event_photo WHERE is_deleted = 0 AND evt_photo_id='.$evt_photo_id;
            $database = \Drupal::database();
            $result = $database-> query($sql)->fetchObject();
            return $result;
        }
        
    }

    public static function getActivitiesTags($tags) {

        $output=array();
        $TagList = new TagList();
        $database = \Drupal::database();

        $query_event = $database-> select('kicp_km_event', 'a');
        $query_event->addField('a', 'evt_id','id');
        $query_event->addField('a', 'evt_name','name');
        $query_event->fields('a', ['evt_id']);
        $query_event->addField('a', 'modify_datetime','record_time');
        $query_event->addField('a', 'evt_description','highlight');
        $query_event->fields('a', ['evt_start_date', 'evt_end_date']);
        $query_event->addExpression('1', 'is_activity');
        $query_event->condition('a.is_deleted', '0');

        if ($tags && count($tags) > 0 ) {
            $tags1 = $database-> select('kicp_tags', 't');
            $tags1-> condition('tag', $tags, 'IN');
            $tags1-> condition('t.module', 'activities');
            $tags1-> condition('t.is_deleted', '0');
            $tags1-> addField('t', 'fid');
            $tags1-> groupBy('t.fid');
            $tags1-> having('COUNT(fid) >= :matches', [':matches' => count($tags)]);        
            $query_event-> condition('evt_id', $tags1, 'IN');
      }
      
        $table2 = $database-> select('kicp_km_event_deliverable', 'b'); 
        $table2->addField('b', 'evt_deliverable_id','id');
        $table2->addField('b', 'evt_deliverable_name','name');
        $table2->fields('b', ['evt_id']);
        $table2->addField('b', 'modify_datetime','record_time');
        $table2->addExpression("CONCAT('File name: ', evt_deliverable_url)",'highlight');
        $table2->addExpression('null', 'evt_start_date');
        $table2->addExpression('null', 'evt_end_date');
        $table2->addExpression('0', 'is_activity');
        $table2->condition('b.is_deleted', '0');
        
        if ($tags && count($tags) > 0 ) {
            $tags2 = $database-> select('kicp_tags', 't2');
            $tags2-> condition('tag', $tags, 'IN');
            $tags2-> condition('t2.module', 'activities_deliverable');
            $tags2-> condition('t2.is_deleted', '0');
            $tags2-> addField('t2', 'fid');
            $tags2-> groupBy('t2.fid');
            $tags2-> having('COUNT(fid) >= :matches', [':matches' => count($tags)]);        
            $table2-> condition('evt_deliverable_id', $tags2, 'IN');
        }
        
        $query = $database-> select($query_event->union($table2))
        ->fields(NULL, ['id','name', 'evt_id', 'record_time', 'highlight', 'evt_start_date','evt_end_date', 'is_activity' ]);
        $query-> orderBy('record_time', 'DESC');          
        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);


        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);
        
        if (!$result) 
          return null;        
        
        foreach ($result as $record) {
            if ($record["is_activity"]) {
                $record["tags"] = $TagList->getTagsForModule('activities', $record["id"]);
            } else {
                $record["tags"] = $TagList->getTagsForModule('activities_deliverable', $record["id"]);   
            }


            $output[] = $record;
        }

        return $output;

    }

    public static function getActivityTypeMaxDisplayOrder() {
        $sql = "SELECT MAX(display_order) AS max_value FROM kicp_km_event_type WHERE is_deleted = 0";
        $database = \Drupal::database();
        $record = $database-> query($sql)->fetchObject();
    
        return ($record->max_value == "" ? 0 : $record->max_value);
    }

    public static function getMaxCopItemDispplayOrder($cop_group_id) {
        $sql = "SELECT max(display_order) as maxOrder FROM kicp_km_cop WHERE cop_group_id=".$cop_group_id.' AND is_deleted=0';
        $database = \Drupal::database();
        $record = $database-> query($sql)->fetchObject();
        
        return ($record->maxOrder == "" ? 0 : $record->maxOrder);

    }

    public static function insertRegistration($entry) {
        $return_value = NULL;
        $database = \Drupal::database();
        $transaction = $database->startTransaction();   
        try {
            $query = $database->insert('kicp_km_event_member_list') ->fields($entry);
            $return_value = $query->execute();
        } 
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Enrollment is not created.' )
                );
            \Drupal::logger('activities')->error('KM Event Enrollment is not created: '. $variables);
            $transaction->rollBack();
        }	        
        unset($transaction);    
        return $return_value;        
    }


    public static function changeRegistration($entry) {
        $return_value = NULL;
        $database = \Drupal::database();
        $transaction = $database->startTransaction();   
        try {
            $query = $database->update('kicp_km_event_member_list') ->fields($entry)
            ->condition('evt_id', $entry['evt_id'])
            ->condition('user_id', $entry['user_id']);
            $return_value = $query->execute();
        } 
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Enrollment is not updated.' )
                );
            \Drupal::logger('activities')->error('KM Event Enrollment is not updated: '. $variables);
            $transaction->rollBack();
        }	        
        unset($transaction);    
        return $return_value;
    }

}