<?php

namespace Drupal\common\Common;

use Drupal\Core\Database\Database;

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

}