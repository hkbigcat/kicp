<?php

namespace Drupal\common\Common;

use Drupal\Core\Database\Database;

class CommonDatatable {

    public static function getAllPublicGroup($search_str="") {
        
        $cond = ($search_str != "") ? " AND pub_group_name LIKE '%".$search_str."%' " : "";
        
        $sql = "SELECT pub_group_id AS group_id, pub_group_name AS group_name FROM kicp_public_group WHERE is_deleted=0 ".$cond." ORDER BY pub_group_name";

		$group = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);  
        
        return $group;
    }

}