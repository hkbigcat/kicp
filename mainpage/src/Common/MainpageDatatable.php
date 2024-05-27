<?php

namespace Drupal\mainpage\Common;

use Drupal\common\TagList;
use Drupal\common\LikeItem;
use Drupal\common\Follow;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\common\RatingData;
use Drupal\blog\Common\BlogDatatable;
use Drupal\Core\Utility\Error;

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

        $myRecordOnly = \Drupal::request()->query->get('my');
        $myfollowed = \Drupal::request()->query->get('my_follow');

        if ($myfollowed) {
            $following_all = Follow::getFolloweringList($my_user_id);
            if ($following_all!=null)
              $following = array_column($following_all, 'contributor_id');
            else {
                return null;
            }
        } 

        if ($tags==null)
            $tags = array();

        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
        $output=array();
        $database = \Drupal::database();

        /***********    Bookmark    ************/
        $query_bookmark = $database-> select('kicp_bookmark', 'a');
        $query_bookmark -> leftjoin('xoops_users', 'u', 'a.user_id = u.user_id');
        $query_bookmark ->addField('a', 'bTitle','Title');
        $query_bookmark ->addField('a', 'bid','record_id');
        $query_bookmark ->addField('a', 'bAddress','link');
        $query_bookmark ->addField('a', 'user_id');
        $query_bookmark ->addField('u', 'user_displayname');
        $query_bookmark ->addField('a', 'bModified','record_time');
        $query_bookmark ->addField('a', 'bDescription','summary');
        $query_bookmark ->addExpression('null', 'image_name');
        $query_bookmark ->addExpression(":module", 'module', array(":module"=>"bookmark" ));
        $query_bookmark ->condition('a.is_deleted', '0');
        
        if ($myRecordOnly) {
            $query_bookmark->condition('a.user_id', $my_user_id);
        } else if ($myfollowed && $following != null) {
            $query_bookmark-> condition('a.user_id', $following, 'IN');
        } else {        
            if (!$isSiteAdmin) {
                $orGroup1 = $query_bookmark->orConditionGroup()
                ->condition('a.user_id', $my_user_id)
                ->condition('a.bStatus', 0);
                $query_bookmark->condition($orGroup1);
            }        
        }

        /***********    Blog    ************/
        $query_blog = $database-> select('kicp_blog_entry', 'a1');
        $query_blog -> leftjoin('kicp_blog', 'b1', 'a1.blog_id = b1.blog_id');
        $query_blog -> leftjoin('xoops_users', 'u1', 'b1.user_id = u1.user_id');
        $query_blog ->addField('a1', 'entry_title','Title');
        $query_blog ->addField('a1', 'entry_id','record_id');
        $query_blog ->addField('a1', 'entry_id','link');
        $query_blog ->addField('b1', 'user_id');
        $query_blog ->addField('u1', 'user_displayname');
        $query_blog ->addField('a1', 'entry_modify_datetime','record_time');
        $query_blog ->addField('a1', 'entry_content','summary');
        $query_blog ->addExpression("IF(b1.image_name = 'shadow.gif', 'shadow.gif' , CONCAT(LPAD(u1.uid, 6, '0'),  '/', b1.image_name))", 'image_name');
        $query_blog ->addExpression(":module2", 'module', array(":module2"=>"blog" ));
        $query_blog ->condition('a1.is_deleted', '0');

        $delegate = $database-> select('kicp_blog_delegated', 'dele');
        $delegate-> condition('user_id', $my_user_id);
        $delegate-> addField('dele', 'blog_id');

        $orGroup = $query_blog->orConditionGroup();
        $orGroup->condition('b1.user_id', $my_user_id);
        $orGroup->condition('a1.is_visible', 1);
        $orGroup->condition('b1.blog_id',  $delegate, 'IN');        
        $query_blog->condition($orGroup);        

        if ($myRecordOnly) {
            $query_blog->condition('b1.user_id', $my_user_id);
        } else if ($myfollowed  && $following != null) {
            $query_blog-> condition('b1.user_id', $following, 'IN');
        } else {            
            if (!$isSiteAdmin) {
                $blog_unaccess = BlogDatatable::myUnAccessibleBlog($my_user_id);
                $query_blog ->condition('b1.blog_id', $blog_unaccess, 'NOT IN');
            }
        }

        /***********    Fileshare    ************/
        $query_file = $database-> select('kicp_file_share', 'a2');
        $query_file -> leftjoin('xoops_users', 'u2', 'a2.user_id = u2.user_id');
        
        if ($myRecordOnly) {
            $query_file->condition('a2.user_id', $my_user_id);
        } else if ($myfollowed && $following != null) {
            $query_file-> condition('a2.user_id', $following, 'IN');
        } else {            
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
        }

        $query_file ->addField('a2', 'title','Title');
        $query_file ->addField('a2', 'file_id','record_id');
        $query_file ->addField('a2', 'file_id','link');
        $query_file ->addField('a2', 'user_id');
        $query_file ->addField('u2', 'user_displayname');
        $query_file ->addField('a2', 'modify_datetime','record_time');
        $query_file ->addField('a2', 'description','summary');
        $query_file ->addExpression("CONCAT(LPAD(a2.file_id, 6, '0'),  '/', a2.image_name)", 'image_name');
        $query_file ->addExpression(":module3", 'module', array(":module3"=>"fileshare" ));
        $query_file ->condition('a2.is_deleted', '0');

        /***********    Forum   ************/
        $query_forum = $database-> select('kicp_forum_topic', 'a3');
        $query_forum -> leftjoin('xoops_users', 'u3', 'a3.user_id = u3.user_id');

        if ($myRecordOnly) {
            $query_forum->condition('a3.user_id', $my_user_id);
        } else if ($myfollowed) {
            $query_forum-> condition('a3.user_id', $following, 'IN');
        } else {    
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
        }

        $query_forum ->addField('a3', 'title','Title');
        $query_forum ->addField('a3', 'topic_id','record_id');
        $query_forum ->addField('a3', 'topic_id','link');
        $query_forum ->addField('a3', 'user_id');
        $query_forum ->addField('u3', 'user_displayname');
        $query_forum ->addField('a3', 'create_datetime','record_time');
        $query_forum ->addField('a3', 'content','summary');
        $query_forum ->addExpression('null', 'image_name');
        $query_forum ->addExpression(":module4", 'module', array(":module4"=>"forum" ));
        $query_forum ->condition('a3.is_deleted', '0');


        /***********    Wiki   ************/
        $query_wiki = $database-> select('wikipage', 'a4');
        $query_wiki ->addField('a4', 'page_title','Title');
        $query_wiki ->addField('a4', 'page_id','record_id');
        $query_wiki ->addField('a4', 'page_title','link');
        $query_wiki ->addExpression('null', 'user_id');
        $query_wiki ->addExpression('null', 'user_displayname');
        $query_wiki ->addExpression("CONVERT_TZ(DATE_FORMAT(a4.page_touched,'%Y-%m-%d %H:%i:%s'),'+00:00','+08:00')", 'record_time');
        $query_wiki ->addExpression('null', 'summary');
        $query_wiki ->addExpression('null', 'image_name');
        $query_wiki ->addExpression(":module5", 'module', array(":module5"=>"wiki" ));
        $query_wiki ->condition('a4.page_namespace', '0');

        if ($myRecordOnly) {
            $query_wiki -> leftjoin('wikirevision', 'w4', 'a4.page_latest = w4.rev_id');
            $query_wiki -> leftjoin('wikiactor', 'u4', 'w4.rev_actor = u4.actor_id');
            $query_wiki->condition('u4.actor_name', $my_user_id);
        } else if ($myfollowed) {
            $query_wiki -> leftjoin('wikirevision', 'w4', 'a4.page_latest = w4.rev_id');
            $query_wiki -> leftjoin('wikiactor', 'u4', 'w4.rev_actor = u4.actor_id');
            $query_wiki-> condition('u4.actor_name', $following, 'IN');
        } else {
            if (!$isSiteAdmin) {     
                $query_access4 = $database-> select('wikipage', 's4');
                $query_access4 -> leftjoin('kicp_access_control', 'b4', 'b4.record_id = s4.page_id AND b4.module = :module5 AND b4.is_deleted = :is_deleted', [':module5' => 'wiki', ':is_deleted' => '0']);
                $query_access4 -> leftjoin('kicp_public_group', 'g4', 'b4.group_id = g4.pub_group_id AND b4.group_type= :typeP AND g4.is_deleted = :is_deleted', [':is_deleted' => '0', ':typeP' => 'P']);
                $query_access4 -> leftjoin('kicp_buddy_group', 'h4', 'b4.group_id = h4.buddy_group_id AND b4.group_type= :typeB AND h4.is_deleted = :is_deleted', [ ':is_deleted' => '0', ':typeP' => 'P']);
                $query_access4 -> leftjoin('kicp_public_user_list', 'e4', 'b4.group_id = e4.pub_group_id AND b4.group_type= :typeP AND e4.is_deleted = :is_deleted AND e4.pub_user_id = :user_id', [':is_deleted' => '0', ':typeP' => 'P', ':user_id' => $my_user_id]);
                $query_access4 -> leftjoin('kicp_buddy_user_list', 'f4', 'b4.group_id = f4.buddy_group_id AND b4.group_type= :typeB AND f4.is_deleted = :is_deleted AND f4.buddy_user_id = :user_id', [':is_deleted' => '0', ':typeB' => 'B', ':user_id' => $my_user_id]);
                $query_access4 -> groupBy('s4.page_id');
                $query_access4 -> addField('s4', 'page_id');
                $query_access4 -> having(' COUNT(b4.id)=0 OR COUNT(e4.pub_user_id)> 0 OR COUNT(f4.buddy_user_id)> 0 ');
                $result4 =  $query_access4->execute()->fetchCol();
                $query_wiki-> condition('a4.page_id', $result4, 'IN');
            }             
        }        

        /***********    Activities    ************/
        $query_activities = $database-> select('kicp_km_event', 'a6');
        $query_activities -> leftjoin('xoops_users', 'u6', 'a6.user_id = u6.user_id');
        $query_activities ->addField('a6', 'evt_name','Title');
        $query_activities ->addField('a6', 'evt_id','record_id');
        $query_activities ->addField('a6', 'evt_id','link');
        $query_activities ->addField('a6', 'user_id');
        $query_activities ->addField('u6', 'user_displayname');
        $query_activities ->addField('a6', 'modify_datetime','record_time');
        $query_activities ->addField('a6', 'evt_description','summary');
        $query_activities ->addField('a6', 'evt_logo_url','image_name');
        $query_activities ->addExpression(":module6", 'module', array(":module6"=>"activities" ));
        $query_activities ->condition('a6.is_deleted', '0');

        if ($myRecordOnly) {
            $query_activities->condition('a6.user_id', $my_user_id);
        } else if ($myfollowed  && $following != null) {
            $query_activities-> condition('a6.user_id', $following, 'IN');
        } else {            
            if (!$isSiteAdmin) {
                $query_activities ->condition('a6.is_visible', '1');
            }
        }        

        /***********    PPC Activities    ************/
        $query_ppcactivities = $database-> select('kicp_ppc_event', 'a7');
        $query_ppcactivities -> leftjoin('xoops_users', 'u7', 'a7.user_id = u7.user_id');
        $query_ppcactivities ->addField('a7', 'evt_name','Title');
        $query_ppcactivities ->addField('a7', 'evt_id','record_id');
        $query_ppcactivities ->addField('a7', 'evt_id','link');
        $query_ppcactivities ->addField('a7', 'user_id');
        $query_ppcactivities ->addField('u7', 'user_displayname');
        $query_ppcactivities ->addField('a7', 'modify_datetime','record_time');
        $query_ppcactivities ->addField('a7', 'evt_description','summary');
        $query_ppcactivities ->addField('a7', 'evt_logo_url','image_name');
        $query_ppcactivities ->addExpression(":module7", 'module', array(":module7"=>"ppcactivities" ));
        $query_ppcactivities ->condition('a7.is_deleted', '0');

        if ($myRecordOnly) {
            $query_ppcactivities->condition('a7.user_id', $my_user_id);
        } else if ($myfollowed  && $following != null) {
            $query_ppcactivities-> condition('a7.user_id', $following, 'IN');
        } else {            
            if (!$isSiteAdmin) {
                $query_ppcactivities ->condition('a7.is_visible', '1');
            }
        }  


        /***********   Tags   ************/
        if ($tags && count($tags) > 0 ) {    

            $tagscount = count($tags);

            $tags1 = $database-> select('kicp_tags', 't1');
            $tags1-> condition('tag', $tags, 'IN');
            $tags1-> condition('t1.is_deleted', '0');
            $tags1-> condition('t1.module', 'bookmark');
            $tags1-> addField('t1', 'fid');
            $tags1-> groupBy('t1.fid');
            $tags1-> having('COUNT(fid) >= :matches', [':matches' => $tagscount]);        
            $query_bookmark-> condition('bid', $tags1, 'IN');

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

            $tags5 = $database-> select('kicp_tags', 't5');
            $tags5-> condition('tag', $tags, 'IN');
            $tags5-> condition('t5.is_deleted', '0');
            $tags5-> condition('t5.module', 'wiki');
            $tags5-> addField('t5', 'fid');
            $tags5-> groupBy('t5.fid');
            $tags5-> having('COUNT(fid) >= :matches', [':matches' => $tagscount]);        
            $query_wiki-> condition('page_id', $tags5, 'IN');

            $tags6 = $database-> select('kicp_tags', 't6');
            $tags6-> condition('tag', $tags, 'IN');
            $tags6-> condition('t6.is_deleted', '0');
            $tags6-> condition('t6.module', 'activities');
            $tags6-> addField('t6', 'fid');
            $tags6-> groupBy('t6.fid');
            $tags6-> having('COUNT(fid) >= :matches', [':matches' => $tagscount]);        
            $query_activities-> condition('evt_id', $tags6, 'IN');

            $tags7 = $database-> select('kicp_tags', 't7');
            $tags7-> condition('tag', $tags, 'IN');
            $tags7-> condition('t7.is_deleted', '0');
            $tags7-> condition('t7.module', 'ppcactivities');
            $tags7-> addField('t7', 'fid');
            $tags7-> groupBy('t7.fid');
            $tags7-> having('COUNT(fid) >= :matches', [':matches' => $tagscount]);        
            $query_ppcactivities-> condition('evt_id', $tags7, 'IN');            
        }        

        $query = $database-> select($query_bookmark->union($query_blog)->union($query_forum)->union($query_file)->union($query_wiki)->union($query_activities)->union($query_ppcactivities))
        ->fields(NULL, ['Title','record_id', 'link', 'user_id', 'user_displayname', 'record_time', 'summary' , 'image_name', 'module']);
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
        $img_no_image_num = 1;
        if ($result) {
            foreach ($result as $record) {
                $img_no_image_num <5?$img_no_image_num:1;
                $record["tags"] = $TagList->getTagsForModule($record["module"], $record["record_id"]);
                $record["follow"] = Follow::getFollow($record["user_id"], $my_user_id);

                switch($record["module"]) {
                    case 'bookmark':
                        $record["img_path"] = "sites/default/files/public/default/img_no_image_".$img_no_image_num++.".png";
                        $record["rating"] = $RatingData->getList('bookmark', $record["record_id"]);
                        $rsHadRate = $RatingData->checkUserHadRate('bookmark', $record["record_id"], $my_user_id);
                        $record["rating"]['rsHadRate'] = $rsHadRate;
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

                    case 'wiki':
                        $record["img_path"] = "mediawiki/resources/assets/kicpedia_logo.png";
                        $record["countlike"] = LikeItem::countLike($record["module"], $record["record_id"]);
                        $record["liked"] = LikeItem::countLike($record["module"], $record["record_id"],$my_user_id);    
                        $record["link"] = "mediawiki/index.php/".$record["link"];
                    break;

                    case 'activities':
                        if (!$record["image_name"] || $record["image_name"] == "") {
                            $record["img_path"] = "sites/default/files/public/default/img_no_image_km_activities_".$img_no_image_num++.".gif";    
                        } else { 
                            $record["img_path"] = "system/files/activities/item/".$record["image_name"];
                        }
                        $record["countlike"] = LikeItem::countLike($record["module"], $record["record_id"]);
                        $record["liked"] = LikeItem::countLike($record["module"], $record["record_id"],$my_user_id);    
                        $record["link"] = "activities_detail/".$record["link"];
                    break;

                    case 'ppcactivities':
                        if (!$record["image_name"] || $record["image_name"] == "") {
                            $record["img_path"] = "sites/default/files/public/default/img_no_image_ppc_activities_".$img_no_image_num++.".gif";    
                        } else { 
                            $record["img_path"] = "system/files/ppcactivities/item/".$record["image_name"];
                        }
                        $record["countlike"] = LikeItem::countLike($record["module"], $record["record_id"]);
                        $record["liked"] = LikeItem::countLike($record["module"], $record["record_id"],$my_user_id);    
                        $record["link"] = "ppcactivities_detail/".$record["link"];
                    break;


                    default:
                    break;
                }
                
                $output[] = $record;
            }
        }
        
        return $output;

    }


    public static function acceptDisclaimer($user_id) {
        $return_value = NULL;
        \Drupal::logger('mainpage')->info('Accept Dsiclimare user id:'.$user_id); 
        $database = \Drupal::database();
        $transaction = $database->startTransaction(); 
        try {
            $return_value = $database->update('xoops_users')->fields(['user_is_disclaimer' => 1])
            ->condition('user_id', $user_id)
            ->execute();
            \Drupal::logger('mainpage')->info('Accept Dsiclimare user id: %user_id', 
            array(
                '%user_id' => $user_id,
            ));                 
        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Unable to accept disclimaer. Please try again. ' )
                );
            \Drupal::logger('mainpage')->error('accept disclimaer is not success: ' . $variables);   
            $transaction->rollBack();
        }
        unset($transaction);
        return $return_value;
    }

    
    
}