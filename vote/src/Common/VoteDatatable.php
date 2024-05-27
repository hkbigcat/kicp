<?php

/**
 * @file
 */

namespace Drupal\vote\Common;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\LikeItem;
use Drupal\common\TagList;
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
        
        $query-> fields('a', ['vote_id', 'title', 'description', 'user_id', 'start_date', 'expiry_date', 'allow_copy', 'show_response', 'is_completed']);
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

        if (!$result)
          return null;        
        foreach ($result as $record) {
            $record["tags"] = $TagList->getTagsForModule('vote', $record["vote_id"]);   
            $record["countlike"] = LikeItem::countLike('vote', $record["vote_id"]);
            $record["liked"] = LikeItem::countLike('vote', $record["vote_id"],$my_user_id);
            $record["follow"] = Follow::getFollow($record["user_id"], $my_user_id);
            $output[] = $record;
        }
        return $output; 

    }

    public static function checkOwner($vote_id = "", $user_id = "") {
        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
        if ($isSiteAdmin) return true;
        $sql = "SELECT 1 FROM kicp_vote where vote_id = '$vote_id' AND user_id = '$user_id '";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result;
    }


    public static function getVote($vote_id = "", $user_id="") {

        $sql_access ="";
        $sql_access2 = "";
        $sql_access3 = "";        
        if (isset($user_id) && $user_id!="") {
            $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
            if (!$isSiteAdmin) {
                $sql_access =  "LEFT JOIN kicp_access_control aa ON (aa.record_id = a.vote_id AND aa.is_deleted = 0 AND aa.module = 'vote') 
                LEFT JOIN kicp_public_user_list e ON (aa.group_type='P' AND e.pub_group_id=aa.group_id AND e.is_deleted=0 AND e.pub_user_id = '".$user_id."'  )
                LEFT JOIN kicp_buddy_user_list f ON (aa.group_type='B' AND aa.group_id = f.buddy_group_id AND f.is_deleted=0 AND f.buddy_user_id = '".$user_id."'  )
                LEFT JOIN kicp_public_group g ON (aa.group_type='P' AND g.pub_group_id=aa.group_id AND g.is_deleted=0 AND g.pub_group_owner  = '".$user_id."'  )
                LEFT JOIN kicp_buddy_group h ON (aa.group_type='B' AND h.buddy_group_id=aa.group_id AND h.is_deleted=0 AND h.user_id  = '".$user_id."'  ) ";    
                $sql_access2 = " group by a.vote_id 
                                 having a.user_id = '".$user_id."' OR COUNT(aa.id)=0 OR COUNT(e.pub_user_id)> 0 OR COUNT(f.buddy_user_id)> 0 OR COUNT(g.pub_group_id)> 0 OR COUNT(h.user_id)> 0 ";
            }
        }

        $sql = "SELECT a.file_name,a.vote_id, a.title, a.description, a.vote_name, a.user_id, a.show_response, a.modify_datetime, a.start_date, a.expiry_date, a.is_visible, a.allow_copy, a.is_showDep, a.is_showPost, a.is_showname,start_vote, a.is_completed FROM kicp_vote a $sql_access WHERE a.is_deleted = 0 and a.vote_id = '$vote_id' $sql_access2 ORDER BY a.vote_id DESC LIMIT 1";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result;

    }

    public static function getVoteName($vote_id) {
        $sql = "SELECT title FROM kicp_vote WHERE vote_id = '$vote_id' ";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        if ($result) 
            return $result->title;
        else return null; 

    }

    public static function getVoteQuestionAll($vote_id = "") {

        $cond = ($vote_id != "") ? " AND vote_id='" . $vote_id . "'" : "";

        $sql = "SELECT id,vote_id, name, type_id, result_id, position, content, `required`, deleted, public, has_na, show_scale, show_legend, list_style_id,has_others, remark,file_name FROM kicp_vote_question WHERE deleted = 'N' " . $cond . ' ORDER BY position';
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   

        return $result;
    }    

    public static function hasQuestion($vote_id = "") {

        $sql = "SELECT count(1) as total FROM kicp_vote_question WHERE vote_id = '$vote_id' AND deleted = 'N' ";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();   
        if ($result)
            return $result->total;
        else return 0;
    }

    public static function getVoteQuestion($vote_id = "", $position = "") {

        $cond = '';
        $cond .= ($vote_id != "") ? " AND vote_id='" . $vote_id . "'" : "";
        $cond .= ($position != "") ? " AND position='" . $position . "'" : "";

        $sql = "SELECT id,vote_id, name, type_id, result_id, position, content, required, deleted, public, has_na, show_scale, show_legend, list_style_id,has_others, remark,file_name FROM kicp_vote_question WHERE deleted = 'N' " . $cond . ' ORDER BY position';
        $database = \Drupal::database();

        if ($position != "") {
            $result = $database-> query($sql)->fetchObject();   

        } else {
            $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   
        }

        return $result;
    }    

    public static function getVoteQuestionByID($question_id = "") {

        $sql = "SELECT id, vote_id, `name`, type_id, result_id, position, content, `required`, deleted, public, has_na, show_scale, show_legend, list_style_id,has_others, remark,file_name FROM kicp_vote_question WHERE deleted = 'N' and id = $question_id ";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result;
    }


    public static function getVoteQuestionCount($vote_id = "") {

        $sql = "SELECT count(1) as count FROM kicp_vote_question WHERE  deleted = 'N' and  vote_id='" . $vote_id . "'";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchField();
        return $result;
    }    

    public static function getVoteChoice($question_id = "") {
        $sql = "SELECT id,choice FROM kicp_vote_question_choice WHERE question_id='" . $question_id . "'";
        $sql .= " order by id";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   
        return $result;    
    }

    public static function getVoteChoiceCount($question_id = "") {
        $sql = "SELECT count(1) as count FROM kicp_vote_question_choice WHERE  question_id='" . $question_id . "'";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchField();
        return $result;
    }

    /*
    public static function getVoteRateView($question_id = "") {
        $sql = "SELECT id, scale, legend, position FROM kicp_vote_question_rate WHERE question_id='" . $question_id . "' order by position, id";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   
        return $result;
    }    
    */

    public static function getVoteById($vote_id = "") {

        $cond = '';
        $cond .= ($vote_id != "") ? " AND vote_id='$vote_id'" : "";

        $sql = "SELECT file_name,vote_id, title, description, vote_name, user_id, modify_datetime, start_date, expiry_date, is_visible, allow_copy, is_showDep, is_showPost, is_showname FROM kicp_vote WHERE is_deleted = 0 " . $cond . ' ORDER BY vote_id DESC';
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();   
        return $result;
    }      

    /*
    public static function getVoteResponseRankById($vote_id = "", $respondent_id = "", $question_id = "") {
        $cond = '';
        $cond .= ($respondent_id != "") ? " AND rk.respondent_id='$respondent_id'" : "";
        $cond .= ($question_id != "") ? " AND rk.question_id='$question_id'" : "";

        $sql = "SELECT IFNULL(r.scale,0) as scale FROM kicp_vote_response_rank rk LEFT JOIN kicp_vote_question_choice c on rk.response=c.id LEFT JOIN kicp_vote_question_rate r on rk.rank=r.id WHERE rk.vote_id='$vote_id' " . $cond . ' ORDER BY c.id, r.position';
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC); 

        return $result;
    }
    */

    public static function getVoteRespondentCount($vote_id = "") {

        $sql = "SELECT count(1) as count FROM kicp_vote_respondent WHERE submitted = 'Y' AND vote_id= '$vote_id'";

        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result->count;
    }    


    public static function getVoteRespondent($vote_id = "") {

        $sql = "SELECT r.username, r.modify_datetime, r.id, u.user_dept, u.user_post_unit, u.user_name FROM kicp_vote_respondent r LEFT JOIN xoops_users  u  on r.username=u.user_id WHERE r.submitted = 'Y' AND vote_id= '$vote_id' ORDER BY r.modify_datetime";

        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   
        return $result;
    }

    public static function getVoteResponseById($vote_id = "", $respondent_id = "", $question_id = "") {

        $cond = '';
        $cond .= ($respondent_id != "") ? " AND k.respondent_id='$respondent_id'" : "";
        $cond .= ($question_id != "") ? " AND k.question_id='$question_id'" : "";


        $sql = "SELECT k.respondent_id,k.question_id, IFNULL(c.choice,k.response) as response FROM kicp_vote_response k LEFT JOIN kicp_vote_question_choice c on k.response=c.id WHERE vote_id='$vote_id' " . $cond . ' ORDER BY respondent_id, question_id';
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   

        return $result;
    }

    public static function checkVoteRespondentSumbited($user_id = "", $vote_id = "") {
        $sql = "SELECT vote_id FROM kicp_vote_respondent WHERE username='$user_id' and vote_id = '$vote_id'";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        if (!$result) 
            return null;
        return $result->vote_id;
    }    

    public static function vote($vote_id = "") {

        $sql = "SELECT r.username, r.modify_datetime, r.id, u.user_dept, u.user_post_unit, u.user_name FROM kicp_vote_respondent r LEFT JOIN xoops_users  u  on r.username=u.user_id WHERE r.submitted = 'Y' AND vote_id= '$vote_id' ORDER BY r.modify_datetime";

        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   
        return $result;
    }    

    public static function voteCount($vote_id = "") {

        $sql = "SELECT count(1) as count FROM kicp_vote_respondent WHERE submitted = 'Y' AND vote_id= '$vote_id'";

        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result->count;
    }    

    public static function insertResponse($entry) {

        $query = \Drupal::database()->insert('kicp_vote_response')
                    ->fields($entry);
                    $return_value = $query->execute();

        return $return_value;

    }    

    public static function insertResponseRank($entry) {

        $query = \Drupal::database()->insert('kicp_vote_response_rank')
                    ->fields($entry);
                    $return_value = $query->execute();

        return $return_value;

    }

    public static function resetVoteChoice($question_id = "") {

        $query = \Drupal::database()->delete('kicp_vote_question_choice')
                    ->condition('question_id', $question_id);
                    $return_value = $query->execute();        

        return $return_value;

    }

    /*
    public static function resetVoteRate($question_id = "") {

        $query = \Drupal::database()->delete('kicp_vote_question_rate')
                    ->condition('question_id', $question_id);
                    $return_value = $query->execute();      
                    
        return $return_value;                    

    }
   */

    public static function saveAttach($filename, $this_filename, $vote_id, $question_id="", ) {

        $this_vote_id = str_pad($vote_id, 6, "0", STR_PAD_LEFT);
        $this_question_id = $question_id!=""?"/".str_pad($question_id, 6, "0", STR_PAD_LEFT):"";

        $file_system = \Drupal::service('file_system');   
        $vote_path = 'private://vote/'.$this_vote_id.$this_question_id;
        if (!is_dir($file_system->realpath( $vote_path))) {
            // Prepare the directory with proper permissions.
            if (!$file_system->prepareDirectory($vote_path, FileSystemInterface::CREATE_DIRECTORY)) {
              throw new \Exception('Could not create the vote directory.');
            }
        }                  
        
        $validators = array(
          'file_validate_extensions' => array('jpg jpeg gif png txt doc docx xls xlsx pdf ppt pptx pps odt ods odp zip'),
          'file_validate_size' => array(15 * 1024 * 1024),
          );

        $delta = NULL; // type of $file will be array
        $file = file_save_upload('filename', $validators, $vote_path ,$delta);

        if($filename != $this_filename) {
            $file_real_path = \Drupal::service('file_system')->realpath($vote_path.'/'.$filename);
            $file_contents = file_get_contents($file_real_path);
            $newFile = \Drupal::service('file.repository')->writeData($file_contents, $vote_path.'/'.$this_filename, FileSystemInterface::EXISTS_REPLACE);
            $file1 = $file[0];
            $file1->delete();
        } else {
            $newFile = $file[0];
        }
        $newFile->setPermanent();
        $newFile->uid = $vote_id;
        $newFile->save();
        $url = $newFile->createFileUrl(FALSE);
        
        return $url;

    }

    public static function copyAttach($filename, $old_vote_id, $vote_id, $old_question_id="", $question_id="" ) {

        $this_old_vote_id = str_pad($old_vote_id, 6, "0", STR_PAD_LEFT);
        $this_vote_id = str_pad($vote_id, 6, "0", STR_PAD_LEFT);
        $this_old_question_id = $old_question_id!=""?"/".str_pad($old_question_id, 6, "0", STR_PAD_LEFT):"";
        $this_question_id = $question_id!=""?"/".str_pad($question_id, 6, "0", STR_PAD_LEFT):"";

        $file_system = \Drupal::service('file_system');   
        $old_vote_path = 'private://vote/'.$this_old_vote_id.$this_old_question_id;
        $vote_path = 'private://vote/'.$this_vote_id.$this_question_id;
        if (!is_dir($file_system->realpath( $vote_path))) {
            // Prepare the directory with proper permissions.
            if (!$file_system->prepareDirectory($vote_path, FileSystemInterface::CREATE_DIRECTORY)) {
              throw new \Exception('Could not create the vote directory.');
            }
        }

        if (!$file_system->copy($old_vote_path."/".$filename, $vote_path."/".$filename)) {
            throw new \Exception('Could not copy vote file.');
        }

        return $vote_path."/".$filename;

    }

    public static function copyAccessControlGroupRecord($original_vote_id = "", $new_vote_id = "", $user_id = "") {
        $sql = " insert into kicp_access_control (module, record_id, group_type, group_id, user_id, allow_edit) 
                SELECT 'vote', $new_vote_id , group_type,group_id, '$user_id' , allow_edit from kicp_access_control 
                 WHERE record_id='$original_vote_id' AND is_deleted = 0 And module='vote'";

        $database = \Drupal::database();
        $result = $database-> query($sql);
        return $result;        


    }    

    public static function copyQuestionChoice($original_question_id = '', $new_question_id = '', $user_id = '') {

        $sql = " insert into kicp_vote_question_choice (question_id,choice,modified_by)   
                 SELECT $new_question_id ,choice, '$user_id' from kicp_vote_question_choice 
                where question_id = $original_question_id";

        $database = \Drupal::database();
        $result = $database-> query($sql);
        return $result;   
    }

    public static function DeleteVoteEntryAttachment($vote_id = "", $question_id ="") {

        $file_system = \Drupal::service('file_system');
        $vote_path = 'private://vote';
        $this_vote_id = str_pad($vote_id, 6, "0", STR_PAD_LEFT);
        $this_question_id = $question_id!=""?"/".str_pad($question_id, 6, "0", STR_PAD_LEFT):"";
        $vote_dir = $file_system->realpath( $vote_path.'/'.$this_vote_id.$this_question_id);
        if (is_dir($vote_dir)) {
            $voteList = scandir($vote_dir);
            foreach($voteList as $filename) {
                if(is_dir($vote_dir.'/'.$filename) ||  $filename == "." || $filename == ".." ) {
                    continue;
                }    
                $uri = $vote_path.'/'.$this_vote_id."/".$filename;
                $fid = CommonUtil::deleteFile($uri);
            }
        }
    }

}