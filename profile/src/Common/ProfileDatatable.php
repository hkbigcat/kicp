<?php

namespace Drupal\profile\Common;

use Drupal\common\CommonUtil;


class ProfileDatatable {

    public static function getUserJoinedCopInfo() {
            
            
            $AuthClass = CommonUtil::getSysValue('AuthClass');
            $authen = new $AuthClass();
            $user_id = $authen->getUserId();
                    
            $sql = " SELECT a.cop_id, a.cop_name, b.cop_id AS joined_cop FROM kicp_km_cop a LEFT JOIN kicp_km_cop_membership b ON (a.cop_id=b.cop_id AND b.user_id='".$user_id."') WHERE a.is_deleted = 0 AND a.cop_group_id != 4 ORDER BY cop_name ";
            $database = \Drupal::database();
            $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

            return $result;
            
    }

    public static function checkUserJoinedCopMember($cop_id, $user_id="") {
        
        if($cop_id == "" || $user_id == "") {
            return;
        }
        

        $sql = " SELECT COUNT(1) as total FROM kicp_km_cop_membership WHERE cop_id=".$cop_id." AND user_id='".$user_id."' " ;
        $database = \Drupal::database();
        $record = $database-> query($sql)->fetchObject();
        
        return ($record->total > 0) ? true : false; 
        
    }    

    public static function getGroups() {

        $cond = "";
        $type = \Drupal::request()->query->get('type');

        $AuthClass = CommonUtil::getSysValue('AuthClass');
        $authen = new $AuthClass();
        $author = CommonUtil::getSysValue('AuthorClass');
        $user_id = $authen->getUserId();

        
        $database = \Drupal::database();
        $search_str = \Drupal::request()->query->get('search_str');

        if ($type=="P") {
            $query = $database-> select('kicp_public_group', 'a'); 
            $query->addField('a', 'pub_group_id', 'group_id');
            $query->addField('a', 'pub_group_name', 'group_name');
            $query-> condition('a.is_deleted', '0');
            $query-> condition('a.source', 'U');
            if (!$author::isSiteAdmin($authen->getUserId())) {
                $query-> condition('pub_group_owner', $user_id);
            }
            if ($search_str && $search_str !="") {
                $query->condition('a.pub_group_name', '%' . $search_str . '%', 'LIKE');
            }      
            $query-> orderBy('pub_group_name');
        }
        else {
            $query = $database-> select('kicp_buddy_group', 'a'); 
            $query->addField('a', 'buddy_group_id', 'group_id');
            $query->addField('a', 'buddy_group_name', 'group_name');
            $query-> condition('a.is_deleted', '0');
            if (!$author::isSiteAdmin($authen->getUserId())) {
                $query-> condition('a.user_id', $user_id);
            }
            if ($search_str && $search_str !="") {
                $query->condition('a.buddy_group_name', '%' . $search_str . '%', 'LIKE');
            }      
            $query-> orderBy('buddy_group_name');
        }   
        
        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);

        return $result;

    }

    public static function getBuddyGroupByGroupId($group_id) {
         if ($group_id == "")
            return null;
        
        $sql = "SELECT buddy_group_id AS group_id, buddy_group_name AS group_name, user_id AS owner FROM kicp_buddy_group ";
        $sql .= " WHERE buddy_group_id ='" . $group_id ."' AND is_deleted = 0";
        $database = \Drupal::database();
        $record = $database-> query($sql)->fetchObject();
        return $record;
        
    }

    public static function getPublicGroupByGroupId($group_id) {
        $record = array();
        if ($group_id == "") {
            return $record;
        } else {
            $sql = "SELECT pub_group_id AS group_id, pub_group_name AS group_name, pub_group_owner AS owner FROM kicp_public_group ";
            $sql .= " WHERE pub_group_id ='" . $group_id  ."' AND is_deleted = 0";
            $database = \Drupal::database();
            $record = $database-> query($sql)->fetchObject();
            return $record;
        }
    }

    public static function getMembersGroupId($type="", $group_id="", $user_id="") {

        
        
        if ($type == 'P') {
            $and_user = ($user_id !="")?" and a.pub_user_id ='".$user_id."'":"";
            $sql = "SELECT a.pub_user_name AS user_name, a.pub_user_id AS user_id FROM kicp_public_user_list a LEFT JOIN kicp_public_group b ON (a.pub_group_id=b.pub_group_id AND b.is_deleted = 0) LEFT JOIN xoops_users c ON (a.pub_user_id=c.user_id) WHERE a.is_deleted=0 AND a.pub_group_id='" . $group_id . "' ".$and_user." ORDER BY a.pub_user_name" ;
        } else if ($type == 'B') {
            $and_user = ($user_id !="")?" and a.buddy_user_id ='".$user_id."'":"";
            $sql = "SELECT a.buddy_user_name AS user_name, a.buddy_user_id AS user_id FROM kicp_buddy_user_list a LEFT JOIN kicp_buddy_group b ON (a.buddy_group_id=b.buddy_group_id AND b.is_deleted = 0) LEFT JOIN xoops_users c ON (a.buddy_user_id=c.user_id) WHERE a.is_deleted=0 AND a.buddy_group_id='" . $group_id . "' ".$and_user." ORDER BY a.buddy_user_name";
        }

        $database = \Drupal::database();

        if ($and_user=="" )
           $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        else
            $result = $database-> query($sql)->fetchObject();
        return $result;

    }

    public static function getUsers() {

        $search_str = \Drupal::request()->query->get('search_str');
        if ($search_str !=null && $search_str !="") {
            $sql = "SELECT user_id, user_name FROM xoops_users WHERE user_name LIKE '%" . $search_str . "%' AND user_is_inactive=0 ORDER BY user_name";

            $database = \Drupal::database();
            $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } else 
            return null;

    }

    public static function checkUserInGroup($type, $group_id, $user_id) {
        
        $GroupUserAry = self::getMembersGroupId($type, $group_id);
        
        if (in_array($user_id, array_column($GroupUserAry,'user_id'))) {
            return true;
        } else {
            return false;
        }
        
    }
    
    public static function getUserInfoByUserId($user_id) {
        
        if($user_id == "") {
            return array();
        }
       
       $sql = "SELECT user_full_name from xoops_users WHERE user_id='".$user_id."' AND user_is_inactive=0";
       $database = \Drupal::database();
       $record = $database-> query($sql)->fetchObject();
       return $record ->user_full_name;

   }

}