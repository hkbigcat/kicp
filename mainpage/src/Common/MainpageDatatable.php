<?php

namespace Drupal\mainpage\Common;

use Drupal\common\Controller\TagList;
use Drupal\common\LikeItem;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Database\Query\PagerSelectExtender;


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

    public static function getLatest($tags="") {

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
        
        $query_file = $database-> select('kicp_file_share', 'a2');
        $query_file -> leftjoin('xoops_users', 'u2', 'a2.user_id = u2.user_id');
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
            $tags1-> addField('t1', 'fid');
            $tags1-> groupBy('t1.fid');
            $tags1-> having('COUNT(fid) >= :matches', [':matches' => $tagscount]);        
            $query-> condition('bid', $tags1, 'IN');
            
            $tags2 = $database-> select('kicp_tags', 't2');
            $tags2-> condition('tag', $tags, 'IN');
            $tags2-> condition('t2.is_deleted', '0');
            $tags2-> addField('t2', 'fid');
            $tags2-> groupBy('t2.fid');
            $tags2-> having('COUNT(fid) >= :matches', [':matches' => $tagscount]);        
            $query_blog-> condition('entry_id', $tags2, 'IN');

            $tags3 = $database-> select('kicp_tags', 't3');
            $tags3-> condition('tag', $tags, 'IN');
            $tags3-> condition('t3.is_deleted', '0');
            $tags3-> addField('t3', 'fid');
            $tags3-> groupBy('t3.fid');
            $tags3-> having('COUNT(fid) >= :matches', [':matches' => $tagscount]);        
            $query_file-> condition('file_id', $tags3, 'IN');

            $tags4 = $database-> select('kicp_tags', 't4');
            $tags4-> condition('tag', $tags, 'IN');
            $tags4-> condition('t4.is_deleted', '0');
            $tags4-> addField('t4', 'fid');
            $tags4-> groupBy('t4.fid');
            $tags4-> having('COUNT(fid) >= :matches', [':matches' => $tagscount]);        
            $query_forum-> condition('topic_id', $tags4, 'IN');
        }        

        $query->union($query_blog);
        $query->union($query_file);
        $query->union($query_forum);

        $query-> orderBy('record_time', 'DESC');
        
        if ($tags && count($tags) > 0 ) {
            $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
            $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $query->range(0, 12);
            $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        }


        $TagList = new TagList();
        $img_no_image_num = 1;
        foreach ($result as $record) {

            $img_no_image_num = ($img_no_image_num>5)?1:$img_no_image_num;

            $record["tags"] = $TagList->getTagsForModule($record["module"], $record["record_id"]);

            switch($record["module"]) {
                case 'bookmark':
                    $record["img_path"] = "sites/default/files/public/default/img_no_image_".$img_no_image_num++.".gif";
                break;

                case 'blog':
                    $blog_user_id = str_pad($image_record->uid, 6, "0", STR_PAD_LEFT);
                    $record["img_path"] = ($record["image_name"] == "shadow.gif") ? "sites/default/files/public/default/shadow.gif" : 'system/files/blog/icon/' . $record["image_name"];
                    $record["countlike"] = LikeItem::countLike($record["module"], $record["record_id"]);
                    $record["liked"] = LikeItem::countLike($record["module"], $record["record_id"],$my_user_id);
                    $record["link"] = "blog_entry/".$record["link"];
                break;

                case 'fileshare':
                    $record["img_path"] = "system/files/fileshare/image/".$record["image_name"];
                    $record["link"] = "fileshare_view/".$record["link"];
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