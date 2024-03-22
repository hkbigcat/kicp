<?php

namespace Drupal\mainpage\Common;

use Drupal\common\Controller\TagList;
use Drupal\common\LikeItem;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\common\RatingData;
use Drupal\blog\Common\BlogDatatable;

class MainpageDatatable {
    
    public function __construct() {
        $this->module = 'mainpage';
    }
    
    public static function getEditorChoiceRecord() {
        
        $sql ='SELECT `description` FROM `kicp_editor_choice` where `id` = 9 ';
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result->description;
            
    }

    public static function getLatest($my_user_id="", $tags=null) {

        if ($tags==null)
            $tags = array();

        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
        $output=array();
        $database = \Drupal::database();

        $query = $database-> select('kicp_bookmark', 'a');
        $query -> leftjoin('xoops_users', 'u', 'a.user_id = u.user_id');
        $query ->addField('a', 'bTitle','Title');
        $query ->addField('a', 'bid','record_id');
        $query ->addField('a', 'bAddress','link');
        $query ->addField('a', 'user_id');
        $query ->addField('u', 'user_displayname');
        $query ->addField('a', 'bModified','record_time');
        $query ->addExpression('null', 'image_name');
        $query ->addExpression(":module", 'module', array(":module"=>"bookmark" ));
        $query ->condition('a.is_deleted', '0');
        
        if (!$isSiteAdmin) {
          $orGroup1 = $query->orConditionGroup()
          ->condition('a.user_id', $my_user_id)
          ->condition('a.bStatus', 0);
          $query->condition($orGroup1);
        }        

 

        $query_blog = $database-> select('kicp_blog_entry', 'a1');
        $query_blog -> leftjoin('kicp_blog', 'b1', 'a1.blog_id = b1.blog_id');
        $query_blog -> leftjoin('xoops_users', 'u1', 'b1.user_id = u1.user_id');
        $query_blog ->addField('a1', 'entry_title','Title');
        $query_blog ->addField('a1', 'entry_id','record_id');
        $query_blog ->addField('a1', 'entry_id','link');
        $query_blog ->addField('b1', 'user_id');
        $query_blog ->addField('u1', 'user_displayname');
        $query_blog ->addField('a1', 'entry_modify_datetime','record_time');
        $query_blog ->addExpression("IF(b1.image_name = 'shadow.gif', 'shadow.gif' , CONCAT(LPAD(u1.uid, 6, '0'),  '/', b1.image_name))", 'image_name');
        $query_blog ->addExpression(":module2", 'module', array(":module2"=>"blog" ));
        $query_blog ->condition('a1.is_deleted', '0');
        if (!$isSiteAdmin) {
            $blog_unaccess = BlogDatatable::myUnAccessibleBlog($my_user_id);
            $query_blog ->condition('b1.blog_id', $blog_unaccess, 'NOT IN');
        }
        
        

        $query_file = $database-> select('kicp_file_share', 'a2');
        $query_file -> leftjoin('xoops_users', 'u2', 'a2.user_id = u2.user_id');
        
        if (!$isSiteAdmin) {     

            $query_access = $database-> select('kicp_file_share', 's2');
            $query_access -> join('kicp_file_share_folder', 'j2', 's2.folder_id = j2.folder_id');
            $query_access -> leftjoin('kicp_access_control', 'b2', 'b2.record_id = j2.folder_id AND b2.module = :module3 AND b2.is_deleted = :is_deleted', [':module3' => 'fileshare', ':is_deleted' => '0']);
            $query_access -> leftjoin('kicp_public_user_list', 'e2', 'b2.group_id = e2.pub_group_id AND b2.group_type= :typeP AND e2.is_deleted = :is_deleted AND e2.pub_user_id = :user_id', [':is_deleted' => '0', ':typeP' => 'P', ':user_id' => $my_user_id]);
            $query_access -> leftjoin('kicp_buddy_user_list', 'f2', 'b2.group_id = f2.buddy_group_id AND b2.group_type= :typeB AND f2.is_deleted = :is_deleted AND f2.buddy_user_id = :user_id', [':is_deleted' => '0', ':typeB' => 'B', ':user_id' => $my_user_id]);
            $query_access -> leftjoin('kicp_public_group', 'g2', 'b2.group_id = g2.pub_group_id AND b2.group_type= :typeP AND g2.is_deleted = :is_deleted AND g2.pub_group_owner = :user_id', [':is_deleted' => '0', ':typeP' => 'P', ':user_id' => $my_user_id]);
            $query_access -> leftjoin('kicp_buddy_group', 'h2', 'b2.group_id = h2.buddy_group_id AND b2.group_type= :typeB AND h2.is_deleted = :is_deleted AND h2.user_id = :user_id', [ ':is_deleted' => '0', ':typeP' => 'P', ':user_id' => $my_user_id]);
            $query_access-> addField('s2', 'file_id');
            $query_access-> addField('s2', 'user_id');
            $query_access-> having('s2.user_id = :user_id OR COUNT(b2.id)=0 OR COUNT(e2.pub_user_id)> 0 OR COUNT(f2.buddy_user_id)> 0 OR COUNT(g2.pub_group_id)> 0 OR COUNT(h2.user_id)> 0', [':user_id' => $my_user_id]);
            $query_access-> groupBy('s2.file_id');
            $result1 =  $query_access->execute()->fetchCol();
            $query_file-> condition('a2.file_id', $result1, 'IN');
        } 
               
        $query_file ->addField('a2', 'title','Title');
        $query_file ->addField('a2', 'file_id','record_id');
        $query_file ->addField('a2', 'file_id','link');
        $query_file ->addField('a2', 'user_id');
        $query_file ->addField('u2', 'user_displayname');
        $query_file ->addField('a2', 'modify_datetime','record_time');
        $query_file ->addExpression("CONCAT(LPAD(a2.file_id, 6, '0'),  '/', a2.image_name)", 'image_name');
        $query_file ->addExpression(":module3", 'module', array(":module3"=>"fileshare" ));
        $query_file ->condition('a2.is_deleted', '0');

        $query_forum = $database-> select('kicp_forum_topic', 'a3');
        $query_forum -> leftjoin('xoops_users', 'u3', 'a3.user_id = u3.user_id');

        if (!$isSiteAdmin) {     
            $query_access3 = $database-> select('kicp_forum_forum', 's3');
            $query_access3 -> leftjoin('kicp_access_control', 'b3', 'b3.record_id = s3.forum_id AND b3.module = :module4 AND b3.is_deleted = :is_deleted', [':module4' => 'forum', ':is_deleted' => '0']);
            $query_access3 -> leftjoin('kicp_public_group', 'g3', 'b3.group_id = g3.pub_group_id AND b3.group_type= :typeP AND g3.is_deleted = :is_deleted', [':is_deleted' => '0', ':typeP' => 'P']);
            $query_access3 -> leftjoin('kicp_buddy_group', 'h3', 'b3.group_id = h3.buddy_group_id AND b3.group_type= :typeB AND h3.is_deleted = :is_deleted', [ ':is_deleted' => '0', ':typeP' => 'P']);
            $query_access3 -> leftjoin('kicp_public_user_list', 'e3', 'b3.group_id = e3.pub_group_id AND b3.group_type= :typeP AND e3.is_deleted = :is_deleted AND e3.pub_user_id = :user_id', [':is_deleted' => '0', ':typeP' => 'P', ':user_id' => $my_user_id]);
            $query_access3 -> leftjoin('kicp_buddy_user_list', 'f3', 'b3.group_id = f3.buddy_group_id AND b3.group_type= :typeB AND f3.is_deleted = :is_deleted AND f3.buddy_user_id = :user_id', [':is_deleted' => '0', ':typeB' => 'B', ':user_id' => $my_user_id]);
            $query_access3 -> groupBy('s3.forum_id');
            $query_access3 -> addField('s3', 'forum_id');
            $query_access3 -> having(' COUNT(b3.id)=0 OR COUNT(e3.pub_user_id)> 0 OR COUNT(f3.buddy_user_id)> 0 ');
            $result3 =  $query_access3->execute()->fetchCol();
            $query_forum-> condition('a3.forum_id', $result3, 'IN');
        } 


        $query_forum ->addField('a3', 'title','Title');
        $query_forum ->addField('a3', 'topic_id','record_id');
        $query_forum ->addField('a3', 'topic_id','link');
        $query_forum ->addField('a3', 'user_id');
        $query_forum ->addField('u3', 'user_displayname');
        $query_forum ->addField('a3', 'create_datetime','record_time');
        $query_forum ->addExpression('null', 'image_name');
        $query_forum ->addExpression(":module4", 'module', array(":module4"=>"forum" ));
        $query_forum ->condition('a3.is_deleted', '0');


        if ($tags && count($tags) > 0 ) {    

            $tagscount = count($tags);

            $tags1 = $database-> select('kicp_tags', 't1');
            $tags1-> condition('tag', $tags, 'IN');
            $tags1-> condition('t1.is_deleted', '0');
            $tags1-> condition('t1.module', 'bookmark');
            $tags1-> addField('t1', 'fid');
            $tags1-> groupBy('t1.fid');
            $tags1-> having('COUNT(fid) >= :matches', [':matches' => $tagscount]);        
            $query-> condition('bid', $tags1, 'IN');

            $tags2 = $database-> select('kicp_tags', 't2');
            $tags2-> condition('tag', $tags, 'IN');
            $tags2-> condition('t2.is_deleted', '0');
            $tags2-> condition('t2.module', 'blog');
            $tags2-> addField('t2', 'fid');
            $tags2-> groupBy('t2.fid');
            $tags2-> having('COUNT(fid) >= :matches', [':matches' => $tagscount]);        
            $query_blog-> condition('entry_id', $tags2, 'IN');

            $tags3 = $database-> select('kicp_tags', 't3');
            $tags3-> condition('tag', $tags, 'IN');
            $tags3-> condition('t3.is_deleted', '0');
            $tags3-> condition('t3.module', 'fileshare');
            $tags3-> addField('t3', 'fid');
            $tags3-> groupBy('t3.fid');
            $tags3-> having('COUNT(fid) >= :matches', [':matches' => $tagscount]);        
            $query_file-> condition('file_id', $tags3, 'IN');

            $tags4 = $database-> select('kicp_tags', 't4');
            $tags4-> condition('tag', $tags, 'IN');
            $tags4-> condition('t4.is_deleted', '0');
            $tags4-> condition('t4.module', 'forum');
            $tags4-> addField('t4', 'fid');
            $tags4-> groupBy('t4.fid');
            $tags4-> having('COUNT(fid) >= :matches', [':matches' => $tagscount]);        
            $query_forum-> condition('topic_id', $tags4, 'IN');
        }        

        $query->union($query_blog);
        $query->union($query_forum);
        $query->union($query_file);
        
        


        $query-> orderBy('record_time', 'DESC');
        
        if ($tags && count($tags) > 0 ) {
            $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
            $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);

        } else {
            $query->range(0, 12);
            $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        }

        $TagList = new TagList();
        $RatingData = new RatingData();
        foreach ($result as $record) {

            $record["tags"] = $TagList->getTagsForModule($record["module"], $record["record_id"]);

            switch($record["module"]) {
                case 'bookmark':
                    $record["img_path"] = "";
                break;

                case 'blog':
                    $record["img_path"] = ($record["image_name"] == "shadow.gif") ? "sites/default/files/public/default/shadow.gif" : 'system/files/blog/icon/' . $record["image_name"];
                    $record["countlike"] = LikeItem::countLike($record["module"], $record["record_id"]);
                    $record["liked"] = LikeItem::countLike($record["module"], $record["record_id"],$my_user_id);
                    $record["link"] = "blog_entry/".$record["link"];
                break;

                case 'fileshare':
                    $record["img_path"] = "system/files/fileshare/image/".$record["image_name"];
                    $record["link"] = "fileshare_view/".$record["link"];
                    $record["rating"] = $RatingData->getList('fileshare', $record["record_id"]);
                    $rsHadRate = $RatingData->checkUserHadRate('fileshare', $record["record_id"], $my_user_id);
                    $record["rating"]['rsHadRate'] = $rsHadRate;
                    $record["rating"]['module'] = 'fileshare';          
        
                break;

                case 'forum':
                    $record["img_path"] = "sites/default/files/public/default/img_forum.gif";
                    $record["countlike"] = LikeItem::countLike($record["module"], $record["record_id"]);
                    $record["liked"] = LikeItem::countLike($record["module"], $record["record_id"],$my_user_id);    
                    $record["link"] = "forum_view_topic/".$record["link"];
                break;

                default:
                break;
            }
            
            $output[] = $record;

        }

        
        return $output;

    }

    
    
}