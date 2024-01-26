<?php

namespace Drupal\profile\Common;

use Drupal\common\CommonUtil;


class ProfileDatatable {

    public static function getUserJoinedCopInfo() {
            
            
            $AuthClass = CommonUtil::getSysValue('AuthClass');
            $authen = new $AuthClass();
            $user_id = $authen->getUserId();
                    
            $sql = " SELECT a.cop_id, a.cop_name, b.cop_id AS joined_cop FROM kicp_km_cop a LEFT JOIN kicp_km_cop_membership b ON (a.cop_id=b.cop_id AND b.user_id='".$user_id."') WHERE a.cop_group_id != 4 ORDER BY cop_name ";
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

}