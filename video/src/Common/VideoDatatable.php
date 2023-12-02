<?php

namespace Drupal\video\Common;

use Drupal\Core\Database\Database;
use Drupal\common\CommonUtil;
use Drupal\common\TagList;

class VideoDatatable {

    public $module = 'video';

    public static function getVideoEventList($limit = "", $start="") {

        $outputAry = array();

        
        //$this_limit = (isset($limit) && $limit != "") ? ' LIMIT ' . $limit : '';
        if($start != "") {
            $start_cond = ' LIMIT '.$start.', 99999999';
        } else {
            $start_cond = '';
        }

        // Event list
        $sql = 'SELECT media_event_id, media_event_name, LEFT(media_event_date,10) as media_event_date, media_event_image FROM kicp_media_event_name WHERE is_visible=1 AND is_deleted=0 ORDER BY media_event_sequence DESC, media_event_name' . $start_cond;
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
        /*
        $hasAccessRight = "";
        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();
        $user_id = $authen->getUserId();

        $count = 0;

        foreach ($result as $record) {
            $hasAccessRight = self::hasEventAccessRight($record->media_event_id, $user_id);

            if (!$hasAccessRight) {
                continue;
            }

            if ($limit == "") {
                // return all events
                $outputAry[$record->media_event_id] = $record;
            }
            else {
                if ($count < $limit) {
                    $outputAry[$record->media_event_id] = $record;
                    $count++;
                }
                else {
                    break;
                }
            }
        }
        return $outputAry;
        */
        
    }


}