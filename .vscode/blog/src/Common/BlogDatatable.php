<?php

namespace Drupal\blog\Common;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\common\CommonUtil;
use Drupal\common\TagList;

class BlogDatatable {

    public static function getHomepageBlogList($blogType, $limit) {

        $sql = "SELECT a.entry_id, a.entry_title, c.blog_name, c.counter, a.entry_create_datetime, a.entry_modify_datetime, c.image_name, c.blog_type, a.blog_id, d.user_name AS user_displayname, c.user_id FROM kicp_blog_entry a INNER JOIN ( SELECT max(entry_id) as Maxid FROM kicp_blog_entry WHERE is_deleted=0 group by blog_id ) b ON (a.entry_id=b.Maxid) INNER JOIN kicp_blog c ON (a.blog_id=c.blog_id) INNER JOIN xoops_users d ON (c.user_id=d.user_id) INNER JOIN kicp_blog_thematic e ON (e.blog_id=c.blog_id) WHERE c.is_deleted=0 and a.is_deleted=0 AND c.blog_type="T" and d.user_is_inactive=0 ORDER BY e.weight DESC limit ".$limit;
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);




    }


}