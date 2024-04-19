<?php

/**
 * @file
 */

namespace Drupal\forum\Common;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\LikeItem;
use Drupal\common\Controller\TagList;
use Drupal\common\Follow;


class ForumDatatable {

    public static function getLatest5Topic() {

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();
        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 

        $access_sql = "";
        $access_sql_group = "";
        $access_sql2 = "";
        if (!$isSiteAdmin) {
            $access_sql = " LEFT JOIN kicp_access_control dd ON (dd.is_deleted=0 AND dd.module='forum' AND dd.record_id=c.forum_id)
			LEFT JOIN kicp_public_group e ON ( e.is_deleted=0 AND dd.group_type='P' AND e.pub_group_id=dd.group_id)
            LEFT JOIN kicp_buddy_group f ON ( f.is_deleted=0 AND dd.group_type='B' AND f.buddy_group_id=dd.group_id)
            LEFT JOIN kicp_public_user_list g ON ( g.is_deleted=0 AND g.pub_group_id=e.pub_group_id AND g.pub_user_id='$my_user_id')
            LEFT JOIN kicp_buddy_user_list h ON ( h.is_deleted=0 AND h.buddy_group_id=f.buddy_group_id AND h.buddy_user_id='$my_user_id')   ";

            $access_sql_group = ", c.forum_id ";
            $access_sql2 = " and ( count(dd.id) = 0 or count(g.id) >0 or count(h.buddy_user_id) >0  ) ";
        }


        $sql = " SELECT MAX(a.post_id) AS post_id, MAX(a.post_id) AS this_post_id, b.title, COUNT(a.post_id) AS total_post, b.topic_id, b.forum_id, b.user_id, c.forum_name, x.user_name, b.counter, IF(COUNT(a.post_id)>0,(COUNT(a.post_id)-1),0) AS total_reply, b.create_datetime, IF(d.is_guest=1,d.poster_name,x.user_name) AS poster_name
        FROM kicp_forum_post a 
        LEFT JOIN kicp_forum_topic b ON (a.topic_id=b.topic_id AND b.is_deleted=0) 
        LEFT JOIN kicp_forum_forum c ON (b.forum_id=c.forum_id)
        LEFT JOIN kicp_forum_topic d ON (a.topic_id=d.topic_id AND d.is_deleted=0)
        LEFT JOIN xoops_users x ON (x.user_id=b.user_id) $access_sql 
        WHERE a.is_deleted=0 
        GROUP BY a.topic_id $access_sql_group
        HAVING post_id=this_post_id $access_sql2
        ORDER BY a.topic_id desc LIMIT 5 ";

        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);        

        if (!$result)
          return null;

        $output=array();
        foreach ($result as $record) {
            $record["follow"] = Follow::getFollow($record["user_id"], $my_user_id); 
            $output[] = $record;

        }
        return $output;

    }


    public static function getForumList() {

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();
        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 

        $access_sql = "";
        $access_sql2 = "";
        if (!$isSiteAdmin) {
            $access_sql = " LEFT JOIN kicp_public_group e ON ( e.is_deleted=0 AND d.group_type='P' AND e.pub_group_id=d.group_id)
            LEFT JOIN kicp_buddy_group f ON ( f.is_deleted=0 AND d.group_type='B' AND f.buddy_group_id=d.group_id)
            LEFT JOIN kicp_public_user_list g ON ( g.is_deleted=0 AND g.pub_group_id=e.pub_group_id AND g.pub_user_id='$my_user_id')
            LEFT JOIN kicp_buddy_user_list h ON ( h.is_deleted=0 AND h.buddy_group_id=f.buddy_group_id AND h.buddy_user_id='$my_user_id') ";

            $access_sql2 = " having count(d.id) = 0 or count(g.id) >0 or count(h.buddy_user_id) >0 ";
        }


        $sql = "SELECT a.forum_id, a.forum_name, COUNT(b.topic_id) AS total_topic, IF(COUNT(c.post_id)>0,(COUNT(c.post_id)-1),0) AS total_post, max(b.create_datetime) AS topic_datetime, IFNULL(MAX(c.create_datetime), NULL) AS last_post, '' AS poster_name, COUNT(d.id) AS forum_access 
        FROM kicp_forum_forum a LEFT JOIN kicp_forum_topic b ON (b.forum_id=a.forum_id AND b.is_deleted=0) 
        LEFT JOIN kicp_forum_post c ON (c.topic_id=b.topic_id AND c.is_deleted=0) 
        LEFT JOIN kicp_access_control d ON (d.is_deleted=0 AND d.module='forum' AND d.record_id=a.forum_id) $access_sql
        WHERE 1 GROUP BY a.forum_id $access_sql2 ORDER BY a.forum_name";
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
        $query -> leftjoin('kicp_forum_post', 'c', 'c.topic_id = a.topic_id AND c.is_deleted= :is_deleted1', [':is_deleted1' => 0]);
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
            $record["liked"] = LikeItem::countLike('forum', $record["topic_id"],$my_user_id);
            $record["follow"] = Follow::getFollow($record["user_id"], $my_user_id); 
            $output[] = $record;
        }
        return $output;
        

    }


    public static function getForumListByTag($tags) {

        $output=array();
        $TagList = new TagList();
        $database = \Drupal::database();

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();        

        $query = $database-> select('kicp_forum_topic', 'a'); 
        
        if ($tags && count($tags) > 0 ) {
            $tags1 = $database-> select('kicp_tags', 't');
            $tags1-> condition('tag', $tags, 'IN');
            $tags1-> condition('t.module', 'forum');
            $tags1-> condition('t.is_deleted', '0');
            $tags1-> addField('t', 'fid');
            $tags1-> groupBy('t.fid');
            $tags1-> having('COUNT(fid) >= :matches', [':matches' => count($tags)]);        

            $query-> condition('a.topic_id', $tags1, 'IN');
        }
                
        $query -> leftjoin('kicp_forum_forum', 'b', 'a.forum_id = b.forum_id');
        $query -> leftjoin('kicp_forum_post', 'c', 'c.topic_id = a.topic_id AND c.is_deleted= :is_deleted1', [':is_deleted1' => 0]);
        $query -> leftjoin('kicp_access_control', 'd', 'd.record_id = a.forum_id AND d.is_deleted= :is_deleted2 AND d.module= :module', [':is_deleted2' => 0, ':module' => 'forum']);
        $query -> leftjoin('xoops_users', 'x', 'x.user_id=a.user_id');
        $query -> leftjoin('xoops_users', 'x2', 'x2.user_id=c.user_id');
        $query-> condition('a.is_deleted', '0');


        $query-> fields('a', ['topic_id', 'title','counter','user_id','is_guest', 'create_datetime', 'poster_name', 'forum_id']);
        $query-> fields('b', ['forum_name']);
        $query-> fields('x', ['user_name']);
        $query->addExpression('count(c.post_id)', 'total_reply');
        $query->addExpression('count(d.id)', 'topic_access');

        $query-> condition('a.is_deleted', '0');
        $query-> groupBy('a.topic_id');
        $query-> orderBy('a.create_datetime', 'DESC');            

        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $record) {
            $record["tags"] = $TagList->getTagsForModule('forum', $record["topic_id"]);   
            $record["follow"] = Follow::getFollow($record["user_id"], $my_user_id); 
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

    public static function getForumPostInfo($post_id) {
        $sql = " SELECT subject, content, is_guest, poster_name, user_id, parent_id, topic_id, create_datetime FROM kicp_forum_post WHERE is_deleted=0 AND post_id=".$post_id;
        $database = \Drupal::database();
        $record = $database-> query($sql)->fetchObject();        
        return $record;
    }


    public static function getForumTopic($topic_id) {
        $sql = " SELECT title FROM kicp_forum_topic WHERE is_deleted=0 AND topic_id=".$topic_id;
        $database = \Drupal::database();
        $record = $database-> query($sql)->fetchObject();   
        
        return $record->title;
    }


    public static function myAccessibleForum($user_id="") {
        
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();
        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
        $sql = "SELECT aa.forum_id
        FROM kicp_forum_forum aa
        LEFT JOIN kicp_access_control d ON (d.is_deleted=0 AND d.module='forum' AND d.record_id=aa.forum_id)
        LEFT JOIN kicp_public_group e ON ( e.is_deleted=0 AND d.group_type='P' AND e.pub_group_id=d.group_id)
        LEFT JOIN kicp_buddy_group f ON ( f.is_deleted=0 AND d.group_type='B' AND f.buddy_group_id=d.group_id)
        LEFT JOIN kicp_public_user_list g ON ( g.is_deleted=0 AND g.pub_group_id=e.pub_group_id AND g.pub_user_id='$user_id')
        LEFT JOIN kicp_buddy_user_list h ON ( h.is_deleted=0 AND h.buddy_group_id=f.buddy_group_id AND h.buddy_user_id='$user_id')
        group by aa.forum_id
        having count(d.id) = 0 or count(g.id) >0 or count(h.buddy_user_id) >0; ";

        
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchCol();

        return $result;
        
    }


}

