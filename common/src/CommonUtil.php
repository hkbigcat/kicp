<?php

// using by: 

namespace Drupal\common;

use Drupal\Core\Database\Database;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\file\Entity;
use Drupal\file\Entity\File;

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


    public static function udpateMsgImagePath($createDir, $content,  $newImagePathWebAccess , $oldImagePath='', $PublicUri = 'public://inline-images') {


        $oldImagePath = ($oldImagePath=="")?base_path() . 'sites/default/files/public/inline-images': $oldImagePath;

        $file_system = \Drupal::service('file_system');   
        if (!is_dir($file_system->realpath($createDir ))) {
            // Prepare the directory with proper permissions.
            if (!$file_system->prepareDirectory( $createDir , FileSystemInterface::CREATE_DIRECTORY)) {
              throw new \Exception('Could not create the directory.');
            }
        }

        // read all image tags into an array
        preg_match_all('/<img[^>]+>/i', $content, $imgTags);

        for ($i = 0; $i < count($imgTags[0]); $i++) {
            // get the source string
            preg_match('/src="([^"]+)/i', $imgTags[0][$i], $imgage);

            // remove opening 'src=' tag, can`t get the regex right
            $thisImgSrc = str_ireplace('src="', '', $imgage[0]);
            $origImageSrc[] = $thisImgSrc;  // store the img "src" to  array (full path)

            $_tempImgSrcAry = explode('/', $thisImgSrc);
            $thisImgName = end($_tempImgSrcAry);   // image filename
            $ImgNameAry[] = $thisImgName;

            // move file from temp location to destination
            $thisImgName = urldecode($thisImgName);                
            
            if (file_exists($file_system->realpath($PublicUri) . '/' . $thisImgName)) {    

                $sql = "select fid from `file_managed` WHERE uri = '".$PublicUri."/".$thisImgName."'";
                $database = \Drupal::database();
                $file_result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

               
                // Move all the files to the private file area
                foreach ($file_result as  $record) {
                    
                    if (!file_exists($PublicUri . '/' .$thisImgName)) {
                        break;
                    }
                    
                    $source = $file_system->realpath($PublicUri . '/'. $thisImgName);
                    $destination = $file_system->realpath( $createDir . '/'. $thisImgName);

                    if (!$file_system->move($source, $destination, FileSystemInterface::EXISTS_REPLACE)) {
                        throw new \Exception('Could not copy the generic placeholder image to the destination directory.');
                      }


                    // update the "uri" in table "file_managed" (from "public" to "private" folder)
                    $rs = CommonUtil::updateDrupalFileManagedUri("", $createDir . '/' . $thisImgName, $record['fid']);
                }
            

            } 

            $content = str_replace($oldImagePath, $newImagePathWebAccess, $content);

        }

        return $content;
    }

    public static function getUserInfoByUserId($user_id) {
           
        $sql = "SELECT uid, uname, email, user_id, user_full_name, user_displayname, user_dept, user_rank, user_post_unit, user_name FROM xoops_users WHERE user_id='".$user_id."' AND user_is_inactive=0";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result;   
    }
    
}