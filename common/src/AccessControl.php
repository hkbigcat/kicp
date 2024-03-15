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
    
    
    public static function hasRecordAccessRight($module, $record_id, $user_id) {


        $database = \Drupal::database();
        $query = $database-> select('kicp_access_controo', 'a');
        $query->fields('a', ['a_id']);
        $query->condition('a.module', $module);
        $query->condition('a.record_id', $record_id);
        $num_rows = $query->countQuery()->execute()->fetchField();

        if ($num_rows==0) return true;

        $sql = "SELECT a.id, a.user_id, IF(b.pub_group_id,1,0) AS is_pub_owner, IF(c.buddy_group_id,1,0) AS is_buddy_owner, IF(d.id,1,0) AS is_pub_user, IF(e.id,1,0) AS is_buddy_user
        FROM kicp_access_control a 
        LEFT JOIN kicp_public_group b ON (a.group_type='P' AND a.group_id=b.pub_group_id AND b.is_deleted=0 AND b.pub_group_owner='$user_id')
        LEFT JOIN kicp_buddy_group c ON (a.group_type='B' AND a.group_id=c.buddy_group_id AND c.is_deleted=0 AND c.user_id='$user_id')
        LEFT JOIN kicp_public_user_list d ON (a.group_type='P' AND a.group_id=d.pub_group_id AND d.is_deleted=0 AND d.pub_user_id='$user_id')
        LEFT JOIN kicp_buddy_user_list e ON (a.group_type='B' AND a.group_id=e.buddy_group_id AND e.is_deleted=0 AND e.buddy_user_id='$user_id')
        WHERE a.is_deleted=0 AND a.module='$module' AND a.record_id='$record_id' 
        HAVING is_pub_owner=1 OR is_buddy_owner=1 OR is_pub_user=1 OR is_buddy_user=1";

        $result = $database-> query($sql)->fetchObject;        

        if ($record_id->id !=null)
            return true;
        else
            return false;
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


    public static function delAccessControl($module, $record_id) {

        try {
            $database = \Drupal::database();
            $query = $database->update('kicp_access_control')->fields([
              'is_deleted'=>1 , 
              'modify_datetime' => date('Y-m-d H:i:s', $current_time),
            ])
            ->condition('module', $module)
            ->condition('record_id', $record_id)
            ->execute();    
             
            return true;
          } 
          catch (\Exception $e ) {
            \Drupal::messenger()->addError(
              t('Unable to delete acces control at this time due to datbase error. Please try again.')
            ); 

            return false;
         }    


    }


    public static function canAccess($module, $record_id, $user_id) {

        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
        if ($isSiteAdmin)
            return true;

        $sql = "SELECT a.record_id, count(b.id) as hasPublic , count(c.id) as hasPersonal FROM `kicp_access_control` a 
        left join kicp_public_user_list b on (b.pub_group_id = a.group_id and a.group_type = 'P' and b.pub_user_id = '$user_id' and b.is_deleted = 0) 
        left join kicp_buddy_user_list c on ( c.buddy_group_id = a.group_id and a.group_type = 'B' and c.buddy_user_id = '$user_id' and c.is_deleted = 0) 
        where a.module = '$module' and a.record_id = $record_id and a.is_deleted = 0 group by a.record_id";

        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();       
        
        if ($result->record_id && $result->record_id != null && $result->hasPublic=0 && $result->hasPersonal=0  )
            return false;
        else
            return true;

    }

}