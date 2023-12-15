<?php

/**
 * @file
 */

namespace Drupal\forum\Common;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\LikeItem;


class ForumDatatable {

    public static function getLatest5Topic() {

        $sql = " SELECT MAX(a.post_id) AS post_id, MAX(a.post_id) AS this_post_id, b.title, COUNT(a.post_id) AS total_post, b.topic_id, b.forum_id, c.forum_name, x.user_name, b.counter, IF(COUNT(a.post_id)>0,(COUNT(a.post_id)-1),0) AS total_reply, b.create_datetime, IF(d.is_guest=1,d.poster_name,x.user_name) AS poster_name
        FROM kicp_forum_post a 
        LEFT JOIN kicp_forum_topic b ON (a.topic_id=b.topic_id AND b.is_deleted=0) 
        LEFT JOIN kicp_forum_forum c ON (b.forum_id=c.forum_id)
        LEFT JOIN kicp_forum_topic d ON (a.topic_id=d.topic_id AND d.is_deleted=0)
        LEFT JOIN xoops_users x ON (x.user_id=b.user_id)
        WHERE a.is_deleted=0 
        GROUP BY a.topic_id 
        HAVING post_id=this_post_id 
        ORDER BY a.topic_id desc LIMIT 5 ";

        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);        

        return $result;

    }


    public static function getForumList() {

        $sql = "SELECT a.forum_id, a.forum_name, COUNT(b.topic_id) AS total_topic, IF(COUNT(c.post_id)>0,(COUNT(c.post_id)-1),0) AS total_post, max(b.create_datetime) AS topic_datetime, IFNULL(MAX(c.create_datetime), NULL) AS last_post, '' AS poster_name, COUNT(d.id) AS forum_access FROM kicp_forum_forum a LEFT JOIN kicp_forum_topic b ON (b.forum_id=a.forum_id AND b.is_deleted=0) LEFT JOIN kicp_forum_post c ON (c.topic_id=b.topic_id AND c.is_deleted=0) LEFT JOIN kicp_access_control d ON (d.is_deleted=0 AND d.module='forum' AND d.record_id=a.forum_id) WHERE 1 GROUP BY a.forum_id ORDER BY a.forum_name";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);        

        return $result;

    }


    public static function getForumPostList($forum_id="") {

        $output=array();
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();

        $database = \Drupal::database();
        $query = $database-> select('kicp_forum_topic', 'a');
        $query -> leftjoin('kicp_forum_forum', 'b', 'a.forum_id = b.forum_id');
        $query -> leftjoin('kicp_forum_post', 'c', 'a.topic_id = a.topic_id AND c.is_deleted= :is_deleted1', [':is_deleted1' => 0]);
        $query -> leftjoin('kicp_access_control', 'd', 'd.record_id = a.forum_id AND d.is_deleted= :is_deleted2 AND d.module= :module', [':is_deleted2' => 0, ':module' => 'forum']);
        $query -> leftjoin('xoops_users', 'x', 'x.user_id=a.user_id');
        $query -> leftjoin('xoops_users', 'x2', 'x2.user_id=c.user_id');
        $query-> condition('a.is_deleted', '0');
        if ($forum_id!="")  {
            $query-> condition('a.forum_id', $forum_id);
        }
        $query-> fields('a', ['topic_id', 'title', 'counter', 'user_id', 'is_guest', 'create_datetime', 'poster_name', 'forum_id']);
        $query-> fields('b', ['forum_name']);
        $query-> fields('x', ['user_name']);
        //$query-> fields('c', ['topic_id']);
        $query->addExpression('count(c.post_id)', 'total_reply');
        $query->addExpression('count(d.id)', 'topic_access');

        $query-> groupBy('a.topic_id');
        $query-> orderBy('a.create_datetime', 'DESC');            

        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($result as $record) {
            $record["countlike"] = LikeItem::countLike('forum', $record["topic_id"]);
            $record["liked"] = LikeItem::countLike('forum', $record["topic_id"],$my_user_id);
            $output[] = $record;
        }
        return $output;
        

    }


    public static function getForumThreads($topic_id="") {

        $cond = $topic_id!=""?" AND topic_id = ".$topic_id:"";
        $sql = "SELECT a.post_id, a.subject, a.content, a.parent_id, a.create_datetime, if(a.is_guest=1,a.poster_name, x.user_name) as poster_name, a.topic_id, a.is_guest, a.user_id
            FROM kicp_forum_post a
            LEFT JOIN xoops_users x ON (x.user_id=a.user_id)
            WHERE is_deleted=0 ".$cond."
            ORDER BY post_id DESC";

        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);        

        return $result;    
    }

    public static function getForumName($forum_id) {
        $sql = " SELECT forum_name FROM kicp_forum_forum WHERE forum_id=".$forum_id;
        $database = \Drupal::database();
        $record = $database-> query($sql)->fetchObject();
        
        return $record->forum_name;
    }

    public static function getForumByTopic($topic_id) {
        $sql = " SELECT forum_id, forum_name FROM kicp_forum_forum WHERE forum_id=( select forum_id from kicp_forum_topic where topic_id = ".$topic_id." )";
        $database = \Drupal::database();
        $record = $database-> query($sql)->fetchObject();
        
        return $record;
    }


}

