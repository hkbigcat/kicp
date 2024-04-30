<?php

/**
 * @file
 */

namespace Drupal\vote\Common;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\LikeItem;
use Drupal\common\Controller\TagList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\common\Follow;

class VoteDatatable {

    public static function getVoteList($tags=null) {
        
        $output=array();
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();
        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 

        $TagList = new TagList();
        $database = \Drupal::database();

        $query = $database-> select('kicp_vote', 'a');
        $query -> leftjoin('kicp_vote_respondent', 'b', 'a.vote_id = b.vote_id');
        $query -> leftjoin('xoops_users', 'x', 'a.user_id = x.user_id');

        if ($tags && count($tags) > 0 ) {
            $tags1 = $database-> select('kicp_tags', 't');
            $tags1-> condition('tag', $tags, 'IN');
            $tags1-> condition('t.module', 'vote');
            $tags1-> condition('t.is_deleted', '0');
            $tags1-> addField('t', 'fid');
            $tags1-> groupBy('t.fid');
            $tags1-> having('COUNT(fid) >= :matches', [':matches' => count($tags)]);        
            $query-> condition('a.vote_id', $tags1, 'IN');
        }

        $query-> leftjoin('kicp_access_control', 'ac', 'ac.record_id = a.vote_id AND ac.module = :module AND ac.is_deleted = :is_deleted', [':module' => 'vote', ':is_deleted' => '0']);
        if (!$isSiteAdmin) {          
            $query-> leftjoin('kicp_public_user_list', 'e', 'ac.group_id = e.pub_group_id AND ac.group_type= :typeP AND e.is_deleted = :is_deleted AND e.pub_user_id = :user_id', [':is_deleted' => '0',':typeP' => 'P', ':user_id' => $my_user_id]);
            $query-> leftjoin('kicp_buddy_user_list', 'f', 'ac.group_id = f.buddy_group_id AND ac.group_type= :typeB AND f.is_deleted = :is_deleted AND f.buddy_user_id = :user_id', [':is_deleted' => '0', ':typeB' => 'B', ':user_id' => $my_user_id]);
            $query-> leftjoin('kicp_public_group', 'g', 'ac.group_id = g.pub_group_id AND ac.group_type= :typeP AND g.is_deleted = :is_deleted AND g.pub_group_owner = :user_id', [':module' => 'vote', ':is_deleted' => '0', ':typeP' => 'P', ':user_id' => $my_user_id]);
            $query-> leftjoin('kicp_buddy_group', 'h', 'ac.group_id = h.buddy_group_id AND ac.group_type= :typeB AND h.is_deleted = :is_deleted AND h.user_id = :user_id', [':is_deleted' => '0', ':typeP' => 'P', ':user_id' => $my_user_id]);
          }
        
        $query-> fields('a', ['vote_id', 'title', 'description', 'user_id', 'start_date', 'expiry_date']);
        $query-> fields('x', ['user_displayname']);
        $query-> addExpression('count(b.vote_id)', 'response');
        $query-> addExpression('count(ac.id)', 'vote_access');
        
        $query-> condition('a.is_deleted', '0');

        if (!$isSiteAdmin) {          
            $query-> having('a.user_id = :user_id OR COUNT(ac.id)=0 OR COUNT(e.pub_user_id)> 0 OR COUNT(f.buddy_user_id)> 0 OR COUNT(g.pub_group_id)> 0 OR COUNT(h.user_id)> 0', [':user_id' => $my_user_id]);
          }

        $query-> groupBy('a.vote_id');
        $query-> orderBy('a.start_date', 'DESC');  
        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);           

        foreach ($result as $record) {
            $record["tags"] = $TagList->getTagsForModule('vote', $record["vote_id"]);   
            $record["countlike"] = LikeItem::countLike('vote', $record["vote_id"]);
            $record["liked"] = LikeItem::countLike('vote', $record["vote_id"],$my_user_id);
            $record["follow"] = Follow::getFollow($record["user_id"], $my_user_id);
            $output[] = $record;
        }
        return $output; 

    }


    public static function getVote($vote_id = "", $my_user_id="") {

        $cond = "";

        if ($user_id !="") {
            $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
            if (!$isSiteAdmin) {
                $cond = " AND user_id = '$my_user_id' ";
            }
        }

        $sql = "SELECT file_name,vote_id, title, description, vote_name, user_id, modify_datetime, start_date, expiry_date, is_visible, allow_copy, is_showDep, is_showPost, is_showname,start_vote FROM kicp_vote WHERE is_deleted = 0 and vote_id = '$vote_id' $cond ORDER BY vote_id DESC";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result;

    }

    public static function getVoteQuestionAll($vote_id = "") {

        $cond = ($vote_id != "") ? " AND vote_id='" . $vote_id . "'" : "";

        $sql = "SELECT id,vote_id, name, type_id, result_id, position, content, required, deleted, public, has_na, show_scale, show_legend, list_style_id,has_others, remark,file_name FROM kicp_vote_question WHERE deleted = 'N' " . $cond . ' ORDER BY position';
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   

        return $result;
    }    

    public static function getVoteChoice($question_id = "") {
        $sql = "SELECT id,choice FROM kicp_vote_question_choice WHERE question_id='" . $question_id . "'";
        $sql .= " order by id";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   
        return $result;    
    }

    public static function insertResponse($entry) {

        $query = \Drupal::database()->insert('kicp_vote_response')
                    ->fields($entry);
                    $return_value = $query->execute();

        return $return_value;

    }    

}