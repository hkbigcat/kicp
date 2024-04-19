<?php

/**
 * @file
 */

namespace Drupal\activities\Common;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\Core\Database\Database;
use Drupal\common\Controller\TagList;
use Drupal\common\LikeItem;
use Drupal\video\Common\VideoDatatable;


class ActivitiesDatatable {

    public static function getAllActivityType() {

        $search_str = \Drupal::request()->query->get('search_str');
        $cond = "";
        if ($search_str && $search_str !="") {
            $cond = " and a.evt_type_name like '%$search_str%' ";
        }                    

        $sql = "SELECT a.evt_type_id, a.evt_type_name, a.description, a.display_order, IF(COUNT(b.evt_id)>0, false, true) AS allow_delete 
                FROM kicp_km_event_type a LEFT JOIN kicp_km_event b on (b.evt_type_id = a.evt_type_id AND b.is_deleted = 0) 
                WHERE a.is_deleted = 0 $cond GROUP BY a.evt_type_id, a.evt_type_name,  a.description, a.display_order ORDER BY a.display_order";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
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
                $query->condition('a.group_name', '%' . $search_str . '%', 'LIKE');
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

    public static function getActivityTypeInfo($type_id=1) {
        $sql = "SELECT evt_type_name, description, display_order FROM kicp_km_event_type WHERE is_deleted = 0 AND evt_type_id=" . $type_id;
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAssoc();

        return $result;
        
    }

    public static function getCOPbyGroupID($group_id="") {
        $search_str = \Drupal::request()->query->get('search_str');
        $database = \Drupal::database();
        $query = $database-> select('kicp_km_cop', 'a'); 
        $query -> leftjoin('kicp_km_event', 'b', 'b.cop_id=a.cop_id AND b.evt_type_id=:evt_type_id AND b.is_deleted=:is_deleted' , [':evt_type_id' => 1, ':is_deleted' => 0]);
        $query-> fields('a', ['cop_id', 'cop_name',' cop_info', 'img_name', 'cop_group_id', 'display_order']);
        $query->addExpression('COUNT(b.evt_id)', 'evt_total');
        $query-> condition('a.cop_group_id', $group_id);
        $query-> condition('a.is_deleted', '0');
        if ($search_str && $search_str !="") {
            $query->condition('a.cop_name', '%' . $search_str . '%', 'LIKE');
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

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();

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
            $query->condition('a.evt_name', '%' . $search_str . '%', 'LIKE');
        }                    
        $query-> orderBy('a.evt_start_date', 'DESC');

        
        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);        

        return $result;
    }


    public static function getEventDetail($evt_id) {

        $record = array();
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();

        if ($evt_id == "") {
            return $record;
        }

        $sql = "SELECT a.evt_id, a.evt_name, a.evt_type_id, a.evt_start_date, a.evt_end_date, a.evt_enroll_start, a.evt_enroll_end, a.evt_description, a.evt_is_cop_evt, a.cop_id, a.survey_id, a.venue, b.display, b.template, a.is_recent, a.is_visible, a.evt_recent_status, a.evt_capacity, a.enroll_URL, a.current_enroll_status, a.submit_reply, a.forum_topic_id, a.evt_logo_url, a.user_id, EXISTS (SELECT 1 FROM kicp_km_event_photo where evt_id = '$evt_id' and  is_deleted=0 ) AS has_photo,  EXISTS (SELECT 1 FROM kicp_km_event_deliverable where evt_id = '$evt_id' and  is_deleted=0 ) AS has_deliverable  FROM kicp_km_event a LEFT JOIN kicp_km_event_submitreply b ON (a.submit_reply = b.reply AND b.is_visible = 1) WHERE a.evt_id='$evt_id' AND a.is_archived = 0 AND a.is_deleted = 0 ORDER BY a.evt_enroll_end DESC";

        
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAssoc();

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
    
    public static function getEventSubmitReply() {
        $output = array();

        $sql = 'SELECT id, reply, display FROM kicp_km_event_submitreply WHERE is_visible = 1 ORDER BY reply';
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);  
        
        if (!$result) 
          return null;

        foreach($result as $record) {
            $output[$record['reply']] = $record['display'];
        }
        
        return $output;
    }

    public static function getEventDeliverableByEventId($evt_id) {
        $record = array();
        $output = array();
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();

        $search_sql = "";
        $search_str = \Drupal::request()->query->get('search_str');
        if ($search_str && $search_str !="") {
            $search_sql = " and ( evt_deliverable_url like '%$search_str%' or  evt_deliverable_name like '%$search_str%' ) ";
        }

        if($evt_id == "") {
            return $record;
        } else {
            $sql = "SELECT evt_deliverable_id, evt_deliverable_url, evt_deliverable_name FROM kicp_km_event_deliverable WHERE evt_id='$evt_id' AND is_deleted = 0 $search_sql ORDER BY evt_deliverable_url";
            $database = \Drupal::database();
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
    

    public static function getPhotosbyEvent($evt_id) {

        $database = \Drupal::database();
        $query = $database-> select('kicp_km_event_photo', 'a');
        $query->fields('a', ['evt_id', 'evt_photo_id', 'evt_photo_url', 'evt_photo_description']);
        $query->condition('a.evt_id', $evt_id);
        $query->condition('a.is_deleted', 0);

        $search_str = \Drupal::request()->query->get('search_str');
        if ($search_str && $search_str !="") {
            $orGroup = $query->orConditionGroup();
            $orGroup->condition('a.evt_photo_url', '%' . $search_str . '%', 'LIKE');
            $orGroup->condition('a.evt_photo_description', '%' . $search_str . '%', 'LIKE');
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

        $query = $database-> select('kicp_km_event', 'a');
        $query->addField('a', 'evt_id','id');
        $query->addField('a', 'evt_name','name');
        $query->fields('a', ['evt_id']);
        $query->addField('a', 'modify_datetime','record_time');
        $query->addField('a', 'evt_description','highlight');
        $query->fields('a', ['evt_start_date', 'evt_end_date']);
        $query->addExpression('1', 'is_activity');
        $query->condition('a.is_deleted', '0');

        if ($tags && count($tags) > 0 ) {
            $tags1 = $database-> select('kicp_tags', 't');
            $tags1-> condition('tag', $tags, 'IN');
            $tags1-> condition('t.module', 'activities');
            $tags1-> condition('t.is_deleted', '0');
            $tags1-> addField('t', 'fid');
            $tags1-> groupBy('t.fid');
            $tags1-> having('COUNT(fid) >= :matches', [':matches' => count($tags)]);        
            $query-> condition('evt_id', $tags1, 'IN');
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
        


        $query->union($table2);
        $query-> orderBy('record_time', 'DESC');          

        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(5);
        //$pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->union($table2)->limit(10);


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

}