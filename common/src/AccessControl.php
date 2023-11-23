<?php

namespace Drupal\common;

use Drupal\Core\Database\Database;

class AccessControl {

    public static function getMyAccessControl($module, $record_id) {
             
        // get authorized group(s)
        $sql = "SELECT a.id, a.group_type, a.group_id, a.allow_edit, IF(a.group_type='P', b.pub_group_name, c.buddy_group_name) AS group_name
                    FROM kicp_access_control a
                    LEFT JOIN kicp_public_group b ON (a.group_type='P' AND b.pub_group_id=a.group_id AND b.is_deleted=0)
                    LEFT JOIN kicp_buddy_group c ON (a.group_type='B' AND c.buddy_group_id=a.group_id AND c.is_deleted=0)
                    WHERE a.module='".$module."' AND a.record_id=".$record_id." AND a.is_deleted = 0
                    ORDER BY group_name";

        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);        
        
        return $result;
    }    

    public static function accessControlInfo($id="", $entryData=array()) {
        
        if($id != "") {
            $sql = "SELECT id, module, record_id, group_type, group_id, user_id, allow_edit FROM kicp_access_control WHERE is_deleted=0 AND id=".$id;
        } else if (count($entryData)>0) {
            $cond = "";
            foreach($entryData as $key=>$value) {
                $cond .= " AND ".$key."='".$value."' ";
            }
            
            $sql = "SELECT id, module, record_id, group_type, group_id, user_id, allow_edit FROM kicp_access_control WHERE is_deleted=0 ".$cond;
        }
        
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();        
        
        return $result;
    }


}