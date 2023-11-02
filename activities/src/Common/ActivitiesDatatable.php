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


class ActivitiesDatatable {

    public static function getAllActivityType() {

        $sql = "SELECT a.evt_type_id, a.evt_type_name, a.description, a.display_order, IF(COUNT(b.evt_id)>0, false, true) AS allow_delete FROM kicp_km_event_type a LEFT JOIN kicp_km_event b on (b.evt_type_id = a.evt_type_id AND b.is_deleted = 0) WHERE a.is_deleted = 0 GROUP BY a.evt_type_id, a.evt_type_name,  a.description, a.display_order ORDER BY a.display_order";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getCOPGroupInfo() {
        //$cond = ($cop_id != "") ? ' AND group_id=' . $cop_id : '';
        $sql = 'SELECT group_id, group_name, group_description, img_name FROM kicp_km_cop_group WHERE  is_deleted = 0  '.$cond.' ORDER BY group_id ';
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);        
        return $result;
    }    


    public static function getActivityTypeInfo($type_id=1) {
        $sql = "SELECT evt_type_name, description, display_order FROM kicp_km_event_type WHERE is_deleted = 0 AND evt_type_id=" . $type_id;
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        foreach($result as $record) {
            return $record;
        }
        
    }

    public static function getCOPItem($cop_id="") {
        $cond = ($cop_id != "") ? ' AND cop_group_id=' . $cop_id : '';
        $sql = 'SELECT cop_id, cop_name, cop_info, img_name, cop_group_id, display_order FROM kicp_km_cop WHERE is_deleted = 0 ' . $cond . ' ORDER BY cop_group_id, display_order ';    // in specific display order
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

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
        $database = \Drupal::database();

        $query = $database-> select(' kicp_km_event', 'a'); 
        $query -> join('kicp_km_event_type', 'b', 'a.evt_type_id = b.evt_type_id');
        if ($type_id==1) {
            $query -> join('kicp_km_cop', 'c', 'a.cop_id = c.cop_id');
            $query-> fields('c', ['cop_name']);
            $query-> condition('c.is_deleted', '0');
        }
        $query-> fields('a', ['evt_id', 'evt_name','evt_id', 'evt_start_date', 'evt_end_date', 'is_recent', 'is_visible' ]);
        $query-> fields('b', ['evt_type_name']);
        $query-> condition('a.evt_type_id', $type_id);
        $query-> condition('a.is_archived', '0');
        $query-> condition('a.is_deleted', '0');
        $query-> condition('b.is_deleted', '0');
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

        $sql = 'SELECT a.evt_id, a.evt_name, a.evt_type_id, a.evt_start_date, a.evt_end_date, a.evt_enroll_start, a.evt_enroll_end, a.evt_description, a.evt_is_cop_evt, a.cop_id, a.survey_id, a.venue, b.display, b.template, a.is_recent, a.is_visible, a.evt_recent_status, a.evt_capacity, a.enroll_URL, a.current_enroll_status, a.submit_reply, a.forum_topic_id, a.evt_logo_url, a.user_id, EXISTS (SELECT 1 FROM kicp_km_event_photo where evt_id = \'' . $evt_id . '\' and  is_deleted=0 ) AS has_photo,  EXISTS (SELECT 1 FROM kicp_km_event_deliverable where evt_id = \'' . $evt_id . '\' and  is_deleted=0 ) AS has_deliverable  FROM kicp_km_event a LEFT JOIN kicp_km_event_submitreply b ON (a.submit_reply = b.reply AND b.is_visible = 1) WHERE a.evt_id=\'' . $evt_id . '\' AND a.is_archived = 0 AND a.is_deleted = 0 ORDER BY a.evt_enroll_end DESC';

        
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);        

        $TagList = new TagList();
        foreach ($result as $record) {
            $record["user"] = $authen->getKICPUserInfo($record["user_id"]);
            $record["tags"] = $TagList->getTagsForModule('activities', $record["evt_id"]);
            $record["countlike"] = LikeItem::countLike('activities', $record["evt_id"]);
            $record["liked"] = LikeItem::countLike('activities', $record["evt_id"],$my_user_id);    
            return $record;   
        }
        
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
        
        foreach($result as $record) {
            $output[$record['reply']] = $record['display'];
        }
        
        return $output;
    }

}