<?php

namespace Drupal\common;

use Drupal;

class RatingData {

    public function __construct() {
        $this->domain_name = $_SERVER['SERVER_NAME'];
    }

    
    public function getList($module="", $record_id="", $type="") {
        $output = array();

        if ($module == 'bookmark') {
            /*
            $sql = "select t1.bId, rating_count, total_rating/rating_count as overall_rating from kicp_bookmark t1 " .
                "left join (select rate_id, count(*) as rating_count, sum(rating) as total_rating  from kicp_rate " .
                " where module = '" . $module . "' and is_deleted = 0 group by rate_id ) as t2 on t1.bId = t2.rate_id";
            */
            $sql = "SELECT a.bId as rate_id, count(1) as rating_count, AVG(r.rating) as overall_rating  FROM `kicp_bookmark` a left join kicp_rate r on (a.bId = r.rate_id ) where r.module = 'bookmark' and r.is_deleted = 0 and rate_id = ".$record_id." group by a.bId order by a.bId";
        }
        if ($module == 'ppc') {
            $sql = "select t1.id, rating_count, total_rating/rating_count as overall_rating from kicp_ppc_publication t1 " .
                "left join (select rate_id, count(*) as rating_count, sum(rating) as total_rating  from kicp_rate " .
                " where module = '" . $module . "' and is_deleted = 0 group by rate_id ) as t2 on t1.id = t2.rate_id ";
            if (isset($type) && $type != '') {
                $sql .= " where type = '$type'";
            }
        }
        if ($module == 'video') {
            /*
            $sql = "select t1.media_event_id, rating_count, total_rating/rating_count as overall_rating from kicp_media_event_name t1 " .
                "left join (select rate_id, count(*) as rating_count, sum(rating) as total_rating  from kicp_rate " .
                " where module = '" . $module . "' and is_deleted = 0 group by rate_id ) as t2 on t1.media_event_id = t2.rate_id";
            */
            $sql = "SELECT a.media_event_id as rate_id, count(1) as rating_count, AVG(r.rating) as overall_rating  FROM `kicp_media_event_name` a left join kicp_rate r on (a.media_event_id = r.rate_id ) where r.module = 'video' and r.is_deleted = 0 and rate_id = ".$record_id." group by a.media_event_id order by a.media_event_id";
        }
        if ($module == 'fileshare') {
            /*
            $sql = "select t1.file_id, rating_count, total_rating/rating_count as overall_rating from kicp_file_share t1 " .
                " left join (select rate_id, count(*) as rating_count, sum(rating) as total_rating  from kicp_rate " .
                " where module = '" . $module . "' and is_deleted = 0 group by rate_id ) as t2 on t1.file_id = t2.rate_id";
            */
            $sql = "SELECT a.file_id as rate_id, count(1) as rating_count, AVG(r.rating) as overall_rating  FROM `kicp_file_share` a left join kicp_rate r on (a.file_id = r.rate_id ) where r.module = 'fileshare' and r.is_deleted = 0 and rate_id = ".$record_id." group by a.file_id order by a.file_id";
        }


        $database = \Drupal::database();
        $result = $database-> query($sql)->fetch(\PDO::FETCH_ASSOC);

        if (!$result) {
            $result = ['rate_id'=> $record_id, 'rating_count' => 0, 'overall_rating' => 0 ];
            
        }
    
        return $result;
    }

    function checkUserHadRate($module, $rateId, $user_id) {
        $sql = "select count(1) as userHadRate from kicp_rate where " .
            "module = '" . $module . "' and rate_id = " . $rateId . " and user_id = '" . $user_id . "' and is_deleted = 0";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
    
        $rsHadRate = intval($result->userHadRate);
    
        return $rsHadRate;
    }    
  
    
}
