<?php

namespace Drupal\common\Common;

use Drupal\Core\Database\Database;

class CommonDatatable {

    public static function getAllPublicGroup($search_str="") {
        
        $cond = ($search_str != "") ? " AND pub_group_name LIKE '%".$search_str."%' " : "";        
        $sql = "SELECT pub_group_id AS group_id, pub_group_name AS group_name FROM kicp_public_group WHERE is_deleted=0 ".$cond." ORDER BY pub_group_name";
        
        $database = \Drupal::database();
		$group = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);  
        return $group;
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

}