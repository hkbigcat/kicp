<?php

namespace Drupal\blog\Common;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\common\Controller\TagList;
use Drupal\common\LikeItem;

class BlogDatatable {

    public static function getHomepageBlogList($blogType, $limit) {


        if ($blogType=="ALL") {
            $sql="SELECT
            a.entry_id, a.entry_title, b.blog_name, b.counter, a.entry_create_datetime, a.entry_modify_datetime,
            b.image_name, b.blog_type, a.blog_id, c.uid, c.user_name AS user_displayname, b.user_id
            FROM kicp_blog_entry a
            INNER JOIN kicp_blog b ON (a.blog_id=b.blog_id)
            INNER JOIN xoops_users c ON (b.user_id=c.user_id AND c.user_is_inactive=0)
            WHERE a.is_deleted = 0 AND b.is_deleted=0 
            ORDER BY a.entry_modify_datetime DESC limit 10";
        } else {
            $sql2="";
            if ($blogType=="T") {
                $sql2 =  "INNER JOIN kicp_blog_thematic e ON (e.blog_id=c.blog_id) ";
                $order_by = " ORDER BY e.weight DESC";
            } else {
                $order_by = " ORDER BY c.counter DESC";
            }

            $sql = "SELECT a.entry_id, a.entry_title, c.blog_name, c.counter, a.entry_create_datetime, a.entry_modify_datetime, c.image_name, c.blog_type, a.blog_id, d.uid, d.user_name AS user_displayname, c.user_id FROM kicp_blog_entry a INNER JOIN ( SELECT max(entry_id) as Maxid FROM kicp_blog_entry WHERE is_deleted=0 group by blog_id ) b ON (a.entry_id=b.Maxid) INNER JOIN kicp_blog c ON (a.blog_id=c.blog_id) INNER JOIN xoops_users d ON (c.user_id=d.user_id) 
            $sql2 WHERE c.is_deleted=0 and a.is_deleted=0 AND c.blog_type='$blogType' and d.user_is_inactive=0 $order_by limit $limit";
        }
        //if ($blogType="P") dump ($sql);

        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;


    }

    
    public static function getBlogEntryContent($entry_id) {

        # return "false" if the entry_id is EMPTY
        if ($entry_id == "") {
            return false;
        }
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();
            
        //$isSiteAdmin = CommonUtil::isSiteAdmin();
        
        //$cond = $isSiteAdmin ? "" : " AND a.is_deleted = 0 ";
        $cond = "";

        $sql = '
                SELECT b.blog_name, b.user_id, a.entry_id, a.entry_title, a.entry_content, a.created_by, a.blog_id, a.is_pub_comment, a.entry_create_datetime, a.entry_modify_datetime, c.user_name AS user_full_name, a.has_attachment, b.user_id
                FROM kicp_blog_entry a
                LEFT JOIN kicp_blog b ON (a.blog_id=b.blog_ID)
                LEFT JOIN xoops_users c ON (c.user_id=b.user_id)
                WHERE a.entry_id = ' . $entry_id . '
                AND a.is_archived = 0 AND a.is_banned = 0 '.$cond.' AND a.is_visible = 1
                AND b.is_archived = 0 AND b.is_banned = 0 AND b.is_deleted = 0 AND b.is_visible = 1';

        
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $record) {
            $record["attachment"] = self::getAttachments($record["blog_id"], $entry_id);
            $record["countlike"] = LikeItem::countLike('blog', $record["entry_id"]);
            $record["liked"] = LikeItem::countLike('blog', $record["entry_id"],$my_user_id);
            return $record;
        }

    }    

    public static function getBlogInfo($blog_id) {

        $sql="SELECT b.blog_name, b.user_id, c.user_name AS user_full_name FROM kicp_blog b LEFT JOIN xoops_users c ON c.user_id=b.user_id WHERE b.blog_id=$blog_id and b.is_deleted = 0 ";

        try {
            $database = \Drupal::database();
            $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        }
        catch (\Exception $e) {
          \Drupal::messenger()->addStatus(
             t('Unable to load blogs infomation at this time due to datbase error. Please try again.')
           );
           return  NULL;
        }

        foreach ($result as $record) {
            return $record;
        }
    }

    public static function getBlogIDByUserID($user_id = "") {

        if ($user_id == "") {
            # return user_id of current user
            $AuthClass = "\Drupal\common\Authentication";
            $authen = new $AuthClass();
            $user_id = $authen->getUserId();
        }        

        $sql="SELECT blog_id FROM kicp_blog WHERE user_id ='" . $user_id . "' AND is_deleted=0";

        try {
            $database = \Drupal::database();
            $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        }
        catch (\Exception $e) {
          \Drupal::messenger()->addStatus(
             t('Unable to load blog id at this time due to datbase error. Please try again.')
           );
           return  NULL;
        }

        foreach ($result as $record) {
            return $record['blog_id'];
        }        

    }

    public static function getBlogIDByEntryID($entry_id = "") {

        $sql="SELECT blog_id FROM kicp_blog_entry WHERE entry_id ='" . $entry_id . "' AND is_deleted=0";

        try {
            $database = \Drupal::database();
            $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        }
        catch (\Exception $e) {
          \Drupal::messenger()->addStatus(
             t('Unable to load blog id at this time due to datbase error. Please try again.')
           );
           return  NULL;
        }

           foreach ($result as $record) {
            return $record['blog_id'];
        }    

    }    

    public static function getBlogListContent($blog_id) {

        $output=array();
        $TagList = new TagList();

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();

        try {
            $database = \Drupal::database();
            $selected_query = $database-> select('kicp_blog_entry', 'a'); 
            $selected_query -> join('kicp_blog', 'b', 'a.blog_id = b.blog_id');
            $selected_query-> fields('a', ['entry_id', 'entry_title','entry_content','created_by', 'is_pub_comment', 'entry_create_datetime', 'entry_modify_datetime', 'has_attachment']);
            $selected_query-> fields('b', ['blog_name', 'user_id']);
            $selected_query-> condition('a.blog_id ', $blog_id, '=');
            $selected_query-> condition('a.is_deleted', '0', '=');
            $selected_query-> condition('b.is_deleted', '0', '=');
            $selected_query-> orderBy('entry_modify_datetime', 'DESC');
            $pager = $selected_query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(5);
            $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($result as $record) {
                $record["tags"] = $TagList->getTagsForModule('blog', $record["entry_id"]);   
                $record["attachments"] = self::getAttachments($blog_id, $record["entry_id"]);
                $record["countlike"] = LikeItem::countLike('blog', $record["entry_id"]);
                $record["liked"] = LikeItem::countLike('blog', $record["entry_id"],$my_user_id);
                $output[] = $record;
            }
            return $output;

        }   catch (\Exception $e) {
            \Drupal::messenger()->addStatus(
               t('Unable to load blogs at this time due to datbase error. Please try again.')
             );
           return  NULL;
         }

    }


    public static function getAttachments($blog_id, $entry_id="")  {
        
        
        $BlogFileUri = 'private://blog/file';
        $file_system = \Drupal::service('file_system');
        $blog_owner_id = str_pad($blog_id, 6, "0", STR_PAD_LEFT);
        $dirFile = array();
        $output = array();
        
        if ($entry_id != "") {
            $this_entry_id_path = str_pad($entry_id, 6, "0", STR_PAD_LEFT);
            $entryDir = $file_system->realpath($BlogFileUri . '/' . $blog_owner_id . '/' . $this_entry_id_path);
            if (is_dir($entryDir)) {
                $dirFile = scandir($entryDir);
                if (count($dirFile) > 0) {
            
                    foreach ($dirFile as $attach) {
                        if ($attach != "." && $attach != "..") {
                            $output[] = $attach;
                        }
                    }
                }        
            }
    
        } 
              
        return $output;
    }


    public static function getBlogEntryByTags() {

    
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();

        $output=array();
        $tagsUrl = \Drupal::request()->query->get('tags');
        if ($tagsUrl) {
          $tags = json_decode($tagsUrl);
        } 

        
        try {         
          $database = \Drupal::database();
          $query = $database-> select('kicp_blog_entry', 'a'); 
          $query -> join('kicp_blog', 'b', 'a.blog_id = b.blog_id');
          if ($tags && count($tags) > 0 ) {
            $query -> join('kicp_tags', 't', 'a.entry_id = t.fid');
            $query-> condition('t.module', 'blog');
            $orGroup = $query->orConditionGroup();
            foreach($tags as $tmp) {
              $orGroup->condition('t.tag', $tmp);
            }
            $query->condition($orGroup);
            $query-> condition('t.is_deleted', '0');
            $query-> groupBy('a.entry_id', '0');
            $query->addExpression('COUNT(a.entry_id)>='.count($tags) , 'occ');
            $query->havingCondition('occ', 1);
          } 
          $query-> fields('a', ['entry_id', 'entry_title','entry_content','created_by', 'is_pub_comment', 'entry_create_datetime', 'entry_modify_datetime', 'has_attachment']);
          $query-> fields('b', ['blog_name', 'user_id']);
//          $query-> condition('t.tag', '%'.$tags.'%', 'LIKE');
//          $query-> condition('t.is_deleted', '0');
          $query-> condition('a.is_deleted', '0', '=');
          $query-> condition('b.is_deleted', '0', '=');
          $query-> orderBy('entry_modify_datetime', 'DESC');
          $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
          $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);
          
          $TagList = new TagList();
          foreach ($result as $record) {
            $record["tags"] = $TagList->getTagsForModule('blog', $record["entry_id"]);
            $record["attachment"] = self::getAttachments($record["blog_id"], $entry_id);
            $record["countlike"] = LikeItem::countLike('blog', $record["entry_id"]);
            $record["liked"] = LikeItem::countLike('blog', $record["entry_id"],$my_user_id);               
            $output[] = $record;

          }
    
          return $output;
    
        }
        catch (\Exception $e) {
           \Drupal::messenger()->addStatus(
              t('Unable to load blog by tags at this time due to datbase error. Please try again.')
            );
          return  NULL;
        }
        
      }

    public static function getBlogArchiveTree($blog_id = "") {

        if ($blog_id == "") {
            $blog_id = self::getBlogIDByUserID();
        }

        $output = array();

        if ($blog_id == "") {
            return $output;
        }

        $sql = 'SELECT
                    entry_id, entry_title , /*entry_content, entry_create_datetime,*/
                    substr(entry_modify_datetime,1,4) as thisYear,
                    substr(entry_modify_datetime,6,2) as thisMonth,
                    substr(entry_modify_datetime,9,2) as thisDay
                    FROM kicp_blog_entry
                    where blog_id=' . $blog_id . ' AND is_deleted=0
                    ORDER BY thisYear DESC, thisMonth DESC, entry_modify_datetime DESC';
       
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($result as $record) {
            $output[$record['thisYear']][$record['thisMonth']][] = $record;
        }

        return $output;
    }      

    public static function DeleteBlogEntryAttachment($delete_attachment_list, $this_blog_id = "", $this_entry_id = "") {
        
        $BlogFileUri = 'private://blog/file';
        $output=0;
        $file_system = \Drupal::service('file_system');
        $filePath = $file_system->realpath($BlogFileUri . '/' .   $this_blog_id . '/' .  $this_entry_id);
        $deleteFileAry = explode(',', $delete_attachment_list);
        if (is_dir($filePath)) {
            $dirFile = scandir($filePath);
            if (count($dirFile) > 0) {
                $i=1;
                foreach ($dirFile as $attach) {
                    if ($attach == "." || $attach == "..") {
                        continue;
                    }
                    if (in_array($i, $deleteFileAry) && file_exists($filePath . '/' . $attach)) {
                        unlink($filePath . '/' . $attach);
                        $output++;
                    }
                    $i++;
                }
            }
        }

        return $output;
            
    }

    public static function getEntryComment($entry_id) {

        $sql = "SELECT
                    a.comment_id, a.comment_content, IF(a.is_guest=0, IF(b.user_name IS NULL,'Guest',b.user_name), IF(a.comment_name='','Guest', a.comment_name)) as display_name, a.comment_create_datetime
                    FROM kicp_blog_entry_comment a
                    LEFT JOIN xoops_users b ON (a.user_id=b.user_id)
                    WHERE a.entry_id='" . $entry_id . "' AND a.is_deleted = 0 ORDER BY a.comment_id DESC";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }
    
    
    public static function getAllEntry() {

        $search_str = \Drupal::request()->query->get('search_str');

        $database = \Drupal::database();
        $query = $database-> select('kicp_blog', 'a'); 
        $query -> join('xoops_users', 'b', 'a.user_id = b.user_id');
        $query -> join('kicp_blog_entry', 'c', 'a.blog_id = c.blog_id');
        $query-> fields('a', ['blog_id', 'user_id','blog_type']);
        $query-> fields('b', ['user_name']);
        $query-> condition('a.is_deleted', '0');
        $query-> condition('b.user_is_inactive', '0');
        $query-> condition('c.is_deleted', '0');
        if ($search_str && $search_str !="") {
            $orGroup = $query->orConditionGroup()
            ->condition('a.blog_name', '%' . $search_str . '%', 'LIKE')
            ->condition('a.blog_name', '%' . $search_str . '%', 'LIKE');          
            $query->condition($orGroup);
        }
        $query->groupBy("a.user_id");
        $query->groupBy("a.blog_id");
        $query->groupBy("a.blog_name");
        $query->groupBy("b.user_name");
        $query->groupBy("a.blog_type");
        $query-> orderBy('b.user_name');

        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);

        return $result;

    }


    public static function getBlogDelegate($my_blog_id) {


        $database = \Drupal::database();
        $query = $database-> select('kicp_blog_delegated', 'a'); 
        $query -> join('kicp_blog', 'b', 'a.blog_id = b.blog_id');
        $query -> join('xoops_users', 'c', 'a.user_id = c.user_id');
        $query->addField('a', 'user_id', 'member_id');
        $query-> fields('a', ['blog_id']);
        $query-> fields('b', ['user_id']);
        $query->addField('c', 'user_name', 'member_name');
        $query-> condition('b.is_deleted', '0');
        $query-> condition('c.user_is_inactive', '0');
        $query-> condition('a.blog_id', $my_blog_id);
        $query-> orderBy('c.user_name');
        $result =  $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
       
        return $result;

    }

    public static function blog_delegate_add_search($search_str="") {
        
        //$sql = 'SELECT  user_id, user_name FROM xoops_users WHERE user_name LIKE "%'.$search_str.'%" OR user_id LIKE "%'.$search_str.'%" ORDER BY user_name;';
        $database = \Drupal::database();
        $query = $database-> select('xoops_users', 'a'); 
        $query-> fields('a', ['user_id', 'user_name']);
        $orGroup = $query->orConditionGroup()
            -> condition('a.user_name', '%' . $search_str . '%', 'LIKE')
            -> condition('a.user_id', '%' . $search_str . '%', 'LIKE');
        $query->condition($orGroup);
        $query-> orderBy('a.user_name');

        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);

        return $result;

    }

    public static function myAccessibleBlog($user_id) {
        // return the blog account that user has access right (delegated by others)
        $sql = "SELECT a.blog_id, b.blog_name, c.user_name AS uname FROM kicp_blog_delegated a INNER JOIN kicp_blog b ON (a.blog_id=b.blog_id AND b.is_deleted=0) INNER JOIN xoops_users c ON (b.user_id=c.user_Id) WHERE a.user_id='" . $user_id . "' ORDER BY c.user_name";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }
    

}