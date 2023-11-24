<?php

// using by: 

namespace Drupal\common;


use Drupal\Core\Database\Database;

class CommonUtil {

   public static function getSysValue($sys_property) {


    
        try {  
            $sql = "SELECT sys_value from kicp_system_config WHERE sys_property = '".$sys_property."' AND is_deleted=0";
            $database = \Drupal::database();
            $result = $database-> query($sql); 
            

            foreach ($result as $record)    {
                return $record->sys_value;  
            }
            

            //return $record->sys_value;
            
        }
        catch (\Exception $e) {
   
            \Drupal::messenger()->addStatus(
               t('Unable to load sysvalue at this time due to datbase error. Please try again.')
             );
     
             return NULL;
        }
    

    }



    public static function updateDrupalFileManagedUri($uuid = "", $newPath = "", $fid = "") {

        if (($uuid == "" && $fid == "") || $newPath == "") {
            return;
        }

        $cond = "";

        if ($uuid != "") {
            $cond = ' uuid=\'' . $uuid . '\'';
        }
        else if ($fid != "") {
            $cond = '  fid=\'' . $fid . '\'';
        }
        
        $sql = 'UPDATE file_managed SET status=1, uri=\'' . $newPath . '\' WHERE ' . $cond;
        $database = \Drupal::database();
        $result = $database-> query($sql); 

        return $result;
    }


    public static function isSiteAdmin() {

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $author = "Drupal\common\Authorisation";

        $isSiteAdmin = $author::isSiteAdmin($authen->getUserId());  // store "isSiteAdmin" checking in variable

        return $isSiteAdmin;
    }


    public static function isRecordDeleted($table_name, $fieldAry = array()) {

        if ($table_name == "" || count($fieldAry) == 0) {
            // if no table name or parameter provided, return empty array
            return array();
        }
        else {

            $and = ' AND ';
            $cond = '';

            foreach ($fieldAry as $key => $value) {
                $cond .= $and . " " . $key . " = '" . $value . "' ";
            }

            $sql = 'SELECT 1 FROM ' . $table_name . ' WHERE is_deleted = 0 ' . $cond;
            $database = \Drupal::database();
            $result = $database-> query($sql)->fetchObject();
            
            
            $j = 0;
            foreach ($result as $record) {
                $j++;
                break;
            }

            if ($j == 0) {
                \Drupal::messenger()->addStatus(
                    t('Record is already deleted.'.$sql )
                    );

                return true;
            }
            else {
                return false;
            }
        }
    }    


}