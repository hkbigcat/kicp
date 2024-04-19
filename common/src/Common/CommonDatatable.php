<?php

namespace Drupal\common\Common;

use Drupal\Core\Database\Database;
use Drupal\common\Controller\TagList;

class CommonDatatable {

    public static function getAllPublicGroup($search_str="") {
        
        $cond = ($search_str != "") ? " AND pub_group_name LIKE '%".$search_str."%' " : "";        
        $sql = "SELECT pub_group_id AS group_id, pub_group_name AS group_name FROM kicp_public_group WHERE is_deleted=0 ".$cond." ORDER BY pub_group_name";
        
        $database = \Drupal::database();
		$groups = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);  
        return $groups;
    }


    public static function getBuddyGroupByUId($uid) {
        $record = array();
        if ($uid == "") {
            return $record;
        } else {
            $sql = "SELECT a.buddy_group_id, a.buddy_group_name, b.user_id FROM kicp_buddy_group a ";
            $sql .= " JOIN xoops_users b ON (b.user_id = a.user_id) ";
            $sql .= " WHERE b.uid =" . $uid . " AND a.is_deleted = 0 ORDER BY buddy_group_name";
            $database = \Drupal::database();
            $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);  
            return $result;
        }
    }

    public static function getPublicGroupByGroupId($group_id) {
        $record = array();
        if ($group_id == "") {
            return $record;
        } else {
            $sql = "SELECT pub_group_id AS group_id, pub_group_name AS group_name, pub_group_owner AS owner FROM kicp_public_group ";
            $sql .= " WHERE pub_group_id ='" . $group_id  ."' AND is_deleted = 0";
            $database = \Drupal::database();
            $result = $database-> query($sql)->fetchObject();
            return $result;            
        }
    }

    public static function getBuddyGroupByGroupId($group_id) {
        $record = array();
        if ($group_id == "") {
            return $record;
        } else {
            $sql = "SELECT buddy_group_id AS group_id, buddy_group_name AS group_name, user_id AS owner FROM kicp_buddy_group ";
            $sql .= " WHERE buddy_group_id ='" . $group_id ."' AND is_deleted = 0";
            $database = \Drupal::database();
            $result = $database-> query($sql)->fetchObject();
            return $result;   
        }
    }    

    public static function getUserListByGroupId($type, $group_id, $user_id="") {       
        
        if($type == "P") {
            $cond = ($user_id != "") ? ' AND pub_user_id=\''.$user_id.'\'' : '';
            
            $sql ='SELECT pub_user_id as user_id, pub_user_name as user_name FROM kicp_public_user_list WHERE pub_group_id='.$group_id.' AND is_deleted=0 '.$cond.' ORDER BY user_name';
        } else if($type == "B") {
            $cond = ($user_id != "") ? ' AND buddy_user_id=\''.$user_id.'\'' : '';
            
            $sql ='SELECT buddy_user_id as user_id, buddy_user_name as user_name FROM kicp_buddy_user_list WHERE buddy_group_id='.$group_id.' AND is_deleted=0 '.$cond.' ORDER BY user_name';
        }
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);  
        
        return $result;
        
    }


    public static function getWikiTags()  {

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId(); 

        $output=array();
        $TagList = new TagList();
        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
        
        $tagsUrl = \Drupal::request()->query->get('tags');

        if ($tagsUrl) {
            $tags = json_decode($tagsUrl);
          }    
      
//          dump($tags);

/*
          if ($tags == null) {
            return $output;
        }

*/

        $database = \Drupal::database();
        $query = $database-> select('wikipage', 'a'); 
        if (!$isSiteAdmin) {          
            $query -> leftjoin('kicp_access_control', 'b', 'b.record_id = a.page_id AND b.module = :module AND b.is_deleted = :is_deleted', [':module' => 'wiki', ':is_deleted' => '0']);
            $query -> leftjoin('kicp_public_user_list', 'e', 'b.group_id = e.pub_group_id AND b.module = :module AND b.is_deleted = :is_deleted AND b.group_type= :typeP AND e.is_deleted = :is_deleted AND e.pub_user_id = :user_id', [':module' => 'wiki', ':is_deleted' => '0', ':typeP' => 'P', ':user_id' => $my_user_id]);
            $query -> leftjoin('kicp_buddy_user_list', 'f', 'b.group_id = f.buddy_group_id AND b.module = :module AND b.is_deleted = :is_deleted AND b.group_type= :typeB AND f.is_deleted = :is_deleted AND f.buddy_user_id = :user_id', [':module' => 'wiki', ':is_deleted' => '0', ':typeB' => 'B', ':user_id' => $my_user_id]);
            $query -> leftjoin('kicp_public_group', 'g', 'b.group_id = g.pub_group_id AND b.module = :module AND b.is_deleted = :is_deleted AND b.group_type= :typeP AND g.is_deleted = :is_deleted AND g.pub_group_owner = :user_id', [':module' => 'wiki', ':is_deleted' => '0', ':typeP' => 'P', ':user_id' => $my_user_id]);
            $query -> leftjoin('kicp_buddy_group', 'h', 'b.group_id = h.buddy_group_id AND b.module = :module AND b.is_deleted = :is_deleted AND b.group_type= :typeB AND h.is_deleted = :is_deleted AND h.user_id = :user_id', [':module' => 'wiki', ':is_deleted' => '0', ':typeP' => 'P', ':user_id' => $my_user_id]);
          }
          if ($tags && count($tags) > 0 ) {
            $tags1 = $database-> select('kicp_tags', 't');
            $tags1-> condition('tag', $tags, 'IN');
            $tags1-> condition('t.module', 'wiki');
            $tags1-> condition('t.is_deleted', '0');
            $tags1-> addField('t', 'fid');
            $tags1-> groupBy('t.fid');
            $tags1-> having('COUNT(fid) >= :matches', [':matches' => count($tags)]);        
            $query-> condition('page_id', $tags1, 'IN');
          }          
          $query-> fields('a', ['page_id', 'page_title']);
      
          if (!$isSiteAdmin) {          
            $query-> having('COUNT(b.id)=0 OR COUNT(e.pub_user_id)> 0 OR COUNT(f.buddy_user_id)> 0 OR COUNT(g.pub_group_id)> 0 OR COUNT(h.user_id)> 0', [':user_id' => $my_user_id]);
          }
    
          $query-> groupBy('a.page_id');
          $query-> orderBy('a.page_touched', 'DESC');
          $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
          $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);
    
          if (!$result)
            return null;
        
          foreach ($result as $record) {
            $record["tags"] = $TagList->getTagsForModule('wiki', $record["page_id"]);   
            $output[] = $record;
          }
          
         return  $output;

    }


}