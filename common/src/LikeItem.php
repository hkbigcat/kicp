<?php

namespace Drupal\common;

use Drupal\common\CommonUtil;
use Drupal\Core\Database\Database;

class LikeItem {

    public function __construct() {
        
    }


    public static function insertLike($entry) {
        
        $return_value = NULL;
        
        $myLikeTotal = self::countLike($entry['module'], $entry['fid'], $entry['user_id']);
        
        if($myLikeTotal > 0) {
            return $return_value;
        }
        
        try {
            $query = \Drupal::database()->insert('kicp_like')
                ->fields($entry);
            $return_value = $query->execute();    
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addStatus(
                t('Unable to update like at this time due to datbase error. Please try again. ' )
                );
        }
        return $return_value;
    }


    public static function countLike($module, $record_id, $user_id = "") {

        $cond = "";

        if ($user_id != "") {
            $cond = " AND user_id = '" . $user_id . "'";
        }
        $sql = "SELECT COUNT(1) as total FROM kicp_like WHERE module='" . $module . "' AND fid='" . $record_id . "' AND is_deleted=0" . $cond;

        $result = \Drupal::database() -> query($sql);
        foreach ($result as $record) {
            $output = $record->total;
            break;
        }

        return $output;
    }

}
