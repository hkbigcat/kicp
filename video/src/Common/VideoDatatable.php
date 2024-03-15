<?php

namespace Drupal\video\Common;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use Drupal\common\CommonUtil;
use Drupal\common\Controller\TagList;
use Drupal\common\RatingData;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Database\Query\PagerSelectExtender;


class VideoDatatable {

    public $module = 'video';

    public static function getVideoEventList($limit = "", $start="") {

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();
        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 

        $access_sql = "";
        $access_sql2 = "";
        if (!$isSiteAdmin) {

            $access_sql = " LEFT JOIN kicp_media_event_privilege k on (a.media_event_id = k.media_event_id and k.is_deleted = 0)
                            LEFT JOIN kicp_public_user_list g ON ( g.is_deleted=0 AND g.pub_group_id=k.pub_group_id AND g.pub_user_id='$my_user_id') ";

            $access_sql2 = " group by a.media_event_id having count(k.id) = 0 or count(g.id)>0 ";

        }

        
        $this_limit = (isset($limit) && $limit != "") ? ' LIMIT ' . $limit : '';
        if($start != "") {
            $start_cond = ' LIMIT '.$start.', 99999999';
        } else {
            $start_cond = '';
        }

        // Event list
        $sql =  "SELECT a.media_event_id, a.media_event_name, LEFT(a.media_event_date,10) as media_event_date, a.media_event_image 
        FROM kicp_media_event_name a $access_sql WHERE a.is_visible=1 AND a.is_deleted=0 $access_sql2 ORDER BY a.media_event_sequence DESC, a.media_event_name $start_cond $this_limit ";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
        
    }

    public static function getVideoListByEventId($media_event_id, $admin="") {

        $database = \Drupal::database();
        $query = $database-> select('kicp_media_info', 'a'); 
        $query-> fields('a', ['media_id', 'media_title', 'media_duration', 'media_postdate']);
        if ($admin==1)  {
            $search_str = \Drupal::request()->query->get('search_str');
            if ($search_str && $search_str !="") {
                $orGroup = $query->orConditionGroup();
                $orGroup->condition('a.media_title', '%' . $search_str . '%', 'LIKE');
                $orGroup->condition('a.media_description', '%' . $search_str . '%', 'LIKE');
                $query->condition($orGroup);
            }  

            $query->addField('a', 'sort_field');
            $query->addField('a','is_visible');
            $query->addField('a', 'is_banned');
        } else {
            $query->addField('a', 'media_description');
            $query->addField('a','media_file_path');
            $query->addField('a', 'media_img');
        }
        $query-> condition('a.media_event_id', $media_event_id);
        $query-> condition('a.is_deleted', '0');
        $query-> orderBy('sort_field', 'DESC');
        $query-> orderBy('media_title');

        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);

        if ($admin==1)  return $result;

        $entries=array();
        $TagList = new TagList();
        foreach ($result as $record) {
          $record["tags"] = $TagList->getTagsForModule('video_video', $record["media_id"]);   
          $entries[] = $record;
        }

        return $entries;
    }

    public static function getVideoEventInfo($media_event_id) {
            $sql = "SELECT media_event_name, media_event_sequence, media_event_date, is_visible, is_wmv, media_event_image, evt_id, evt_type, user_id FROM kicp_media_event_name WHERE is_deleted=0 AND media_event_id=" . $media_event_id;
            $database = \Drupal::database();
            $result = $database-> query($sql)->fetchObject();
    
            return $result;

    }


    public static function getVideoTags($tags) {
        $output=array();
        $TagList = new TagList();
        $database = \Drupal::database();

        $table2 = $database-> select('kicp_media_info', 'b'); 
        if ($tags && count($tags) > 0 ) {
            $tags1 = $database-> select('kicp_tags', 't2');
            $tags1-> condition('tag', $tags, 'IN');
            $tags1-> condition('t2.module', 'video_video');
            $tags1-> condition('t2.is_deleted', '0');
            $tags1-> addField('t2', 'fid');
            $tags1-> groupBy('t2.fid');
            $tags1-> having('COUNT(fid) >= :matches', [':matches' => count($tags)]);        

            $table2-> condition('media_id', $tags1, 'IN');
        }
        //$table2->addExpression(':this_module2', 'this_module', array(':this_module2' => 'video_video'));
        $table2->addField('b', 'media_id','id');
        $table2->addField('b', 'media_event_id');
        $table2->addField('b', 'media_title');
        $table2->addField('b', 'media_img');
        $table2->addField('b', 'media_postdate', 'evt_date');
        $table2->addExpression(':is_event2', 'is_event', array(':is_event2' => 0));
        $table2->addField('b', 'modify_datetime', 'record_time');            
        
        $query = $database-> select('kicp_media_event_name', 'a'); 
        if ($tags && count($tags) > 0 ) {
            $tags2 = $database-> select('kicp_tags', 't');
            $tags2-> condition('tag', $tags, 'IN');
            $tags2-> condition('t.module', 'video');
            $tags2-> condition('t.is_deleted', '0');
            $tags2-> addField('t', 'fid');
            $tags2-> groupBy('t.fid');
            $tags2-> having('COUNT(fid) >= :matches', [':matches' => count($tags)]);        

            $query-> condition('media_event_id', $tags2, 'IN');
        }
          
          //$query->addExpression(':this_module', 'this_module', array(':this_module' => 'video'));
          $query->addField('a', 'media_event_id','id');
          $query->addField('a', 'media_event_id');
          $query->addField('a', 'media_event_name', 'media_title');
          $query->addField('a', 'media_event_image', 'media_img');
          $query->addField('a', 'media_event_date', 'evt_date');
          $query->addExpression(':is_event', 'is_event', array(':is_event' => 1));
          $query->addField('a', 'modify_datetime', 'record_time');

          $query->union($table2, 'UNION');

          $query-> orderBy('record_time', 'DESC');          

          $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);

          $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);

          //$result =  $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

          foreach ($result as $record) {
            if ($record["is_event"]) {
                $record["tags"] = $TagList->getTagsForModule('video', $record["id"]);
            } else {
                $record["tags"] = $TagList->getTagsForModule('video_video', $record["id"]);   
            }


            $output[] = $record;
          }

          return $output;

    }

    public static function getVideoEventAdminList() {

        $database = \Drupal::database();
        $query = $database-> select('kicp_media_event_name', 'a'); 
        $query -> leftjoin('kicp_media_event_privilege', 'c', 'a.media_event_id = c.media_event_id AND c.is_deleted= :is_deleted', [':is_deleted' => 0] );

        $search_str = \Drupal::request()->query->get('search_str');
        if ($search_str && $search_str !="") {
            $query->condition('a.media_event_name', '%' . $search_str . '%', 'LIKE');
        }  

        $query-> condition('a.is_deleted', '0');
        $query-> fields('a', ['media_event_id', 'media_event_name', 'media_event_sequence', 'media_event_date', 'is_visible']);
        $query-> addExpression('count(c.id)', 'eventprivilege');
        $query-> groupBy('a.media_event_id');
        $query-> orderBy('media_event_sequence', 'DESC');
        $query-> orderBy('media_event_name');
        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);

        return $result;

    }

    public static function getVideoPrivilege($media_event_id="") {

        $sql = "SELECT a.media_event_id, a.pub_group_id, b.pub_group_name FROM kicp_media_event_privilege a INNER JOIN kicp_public_group b ON (a.pub_group_id=b.pub_group_id)  WHERE a.media_event_id=" . $media_event_id . " AND b.is_deleted = 0 AND a.is_deleted=0 ";

        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
        
    }    

    public static function getVideoPrivilegeGroup($search_str="") {

        $sql="SELECT pub_group_id, pub_group_name FROM kicp_public_group WHERE is_deleted = 0 AND pub_group_name LIKE '%".$search_str."%'";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;


    }

    public static function hasEventAccessRight($media_event_id, $user_id = "") {

         $sql= "SELECT a.media_event_id 
                FROM kicp_media_event_name a 
                LEFT JOIN kicp_media_event_privilege b ON (a.media_event_id=b.media_event_id and b.is_deleted = 0) 
                LEFT JOIN kicp_public_user_list c on (b.pub_group_id = c.pub_group_id and c.is_deleted = 0 and c.pub_user_id = '$user_id') 
                WHERE a.media_event_id = $media_event_id
                group by a.media_event_id
                having count(b.pub_group_id) = 0 or count(c.id)>0 ";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();

        if ($result->media_event_id) {
            return true;
        } else {
            return false;
        }

    }


    public static function getMediaEventbyEvtID($evt_id="", $type="", $user_id = "") {

        $accessql = "";
        $accessql2 = ""; 
        $isKMAdmin = \Drupal::currentUser()->hasPermission('activities_admin'); 
        if (!$isKMAdmin ) {
            $accessql = " LEFT JOIN kicp_media_event_privilege b ON (a.media_event_id=b.media_event_id and b.is_deleted = 0) 
                          LEFT JOIN kicp_public_user_list c on (b.pub_group_id = c.pub_group_id and c.is_deleted = 0 and c.pub_user_id = '$user_id') ";
            $accessql2 = " group by a.media_event_id
                          having count(b.pub_group_id) = 0 or count(c.id)>0 ";
        }

        $sql = "SELECT a.media_event_id FROM kicp_media_event_name a $accessql 
                WHERE evt_type = '$type' AND evt_id = '$evt_id' $accessql2";

        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();

        return $result->media_event_id;

    }

    public static function getAllActivityByEventType($evt_type="") {
        
        $database = \Drupal::database();
        
        if($evt_type == 'KM') {
            $sql = 'SELECT evt_id, evt_name FROM kicp_km_event WHERE is_visible = 1 AND is_deleted = 0 AND is_archived = 0 ORDER BY evt_name';
            $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } else if ($evt_type == 'PPC') {
            $sql = 'SELECT evt_id, evt_name FROM kicp_ppc_event WHERE is_visible = 1 AND is_deleted = 0 AND is_archived = 0 ORDER BY evt_name';
            $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $result = array();
        }
        
        return $result;
    }    

}