<?php

namespace Drupal\common;
use Drupal\common\CommonUtil;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Follow {
    public function __construct() {
        
    }
	
    public static function getFollow($contributor_id, $my_user_id) {
               
        // if "contributor_id" is current user, no need to display "follow" icon
        if($contributor_id == "" || $my_user_id == "" || $contributor_id == $my_user_id) 
            return false;
        
        $sql = "SELECT id FROM kicp_follow WHERE user_id='$my_user_id' AND contributor_id='$contributor_id' AND is_deleted = 0";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();  
        return $result?true:false;                
    }

    public static function addFollow($entry) {
        $return_value = NULL;
        try {
            $query = \Drupal::database()->insert('kicp_follow')
                ->fields($entry);
            $return_value = $query->execute();
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addError(
                t('Unable to add follow at this time due to datbase error.  ' )
                );
        }

        return $return_value;
    }

    public static function updateFollow($entry) {
        $err_code = '0'; 
        try {
            $query = \Drupal::database()->update('kicp_follow')
                ->fields(['is_deleted'=>1])
                ->condition('user_id', $entry['user_id'])
                ->condition('contributor_id', $entry['contributor_id'])
                ->execute();
            return 1;    
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addError(
                t('Unable to update follow at this time due to datbase error.  ' )
                );
            return 0;
        }
    } 
    

    public static function getFolloweringList($user_id="") {
        
        $sql = "SELECT a.contributor_id, b.user_displayname FROM kicp_follow a left join xoops_users b on a.contributor_id = b.user_id  WHERE a.user_id='".$user_id."' AND a.is_deleted = 0 and a.contributor_id <> ''";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $output = array();
        if ($result != null ) {
            foreach ($result as $record) {
                $record["follow"] = self::getFollow($record["contributor_id"], $user_id);
                $output[] = $record;
            }
        }        

        return $output;

    }


    public static function getMyFollowerList($user_id="") {
                       
        $sql = "SELECT a.user_id, b.user_displayname FROM kicp_follow a left join xoops_users b on a.user_id = b.user_id WHERE a.contributor_id='".$user_id."' AND a.is_deleted = 0";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $output = array();
        if ($result != null ) {
            foreach ($result as $record) {
                $record["follow"] = self::getFollow($record["user_id"], $user_id);
                $output[] = $record;
            }
        }        

        return $output;
    }


    public static function getMyFollower($user_id="") {
                       
        $sql = "SELECT count(1) as followers FROM kicp_follow WHERE contributor_id='$user_id' AND is_deleted = 0";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result->followers;

    }

    public static function getMyFollowering($user_id="") {
                       
        $sql = "SELECT count(1) as following FROM kicp_follow WHERE user_id='$user_id' AND is_deleted = 0 AND contributor_id <> ''";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result->following;

    }    
        
}
