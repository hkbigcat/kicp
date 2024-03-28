<?php

/**
 * @file
 */

namespace Drupal\survey\Common;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\LikeItem;
use Drupal\common\Controller\TagList;
use Drupal\Core\File\FileSystemInterface;

class SurveyDatatable {

    public static function getSurveyList($tags=null) {
        
        $output=array();
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();
        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 

        $TagList = new TagList();
        $database = \Drupal::database();

        $query = $database-> select('kicp_survey', 'a');
        $query -> leftjoin('kicp_survey_respondent', 'b', 'a.survey_id = b.survey_id');
        $query -> leftjoin('xoops_users', 'x', 'a.user_id = x.user_id');

        if ($tags && count($tags) > 0 ) {
            $tags1 = $database-> select('kicp_tags', 't');
            $tags1-> condition('tag', $tags, 'IN');
            $tags1-> condition('t.module', 'survey');
            $tags1-> condition('t.is_deleted', '0');
            $tags1-> addField('t', 'fid');
            $tags1-> groupBy('t.fid');
            $tags1-> having('COUNT(fid) >= :matches', [':matches' => count($tags)]);        
            $query-> condition('a.survey_id', $tags1, 'IN');
        }

        //$query -> leftjoin('kicp_access_control', 'c', 'a.survey_id = c.record_id AND c.is_deleted= :is_deleted AND c.module= :module', [':is_deleted' => 0, ':module' => 'survey'] );
            $query-> leftjoin('kicp_access_control', 'ac', 'ac.record_id = a.survey_id AND ac.module = :module AND ac.is_deleted = :is_deleted', [':module' => 'survey', ':is_deleted' => '0']);
        if (!$isSiteAdmin) {          
            $query-> leftjoin('kicp_public_user_list', 'e', 'ac.group_id = e.pub_group_id AND ac.group_type= :typeP AND e.is_deleted = :is_deleted AND e.pub_user_id = :user_id', [':is_deleted' => '0',':typeP' => 'P', ':user_id' => $my_user_id]);
            $query-> leftjoin('kicp_buddy_user_list', 'f', 'ac.group_id = f.buddy_group_id AND ac.group_type= :typeB AND f.is_deleted = :is_deleted AND f.buddy_user_id = :user_id', [':is_deleted' => '0', ':typeB' => 'B', ':user_id' => $my_user_id]);
            $query-> leftjoin('kicp_public_group', 'g', 'ac.group_id = g.pub_group_id AND ac.group_type= :typeP AND g.is_deleted = :is_deleted AND g.pub_group_owner = :user_id', [':module' => 'fileshare', ':is_deleted' => '0', ':typeP' => 'P', ':user_id' => $my_user_id]);
            $query-> leftjoin('kicp_buddy_group', 'h', 'ac.group_id = h.buddy_group_id AND ac.group_type= :typeB AND h.is_deleted = :is_deleted AND h.user_id = :user_id', [':is_deleted' => '0', ':typeP' => 'P', ':user_id' => $my_user_id]);
          }
        
        $query-> fields('a', ['survey_id', 'title', 'description', 'user_id', 'start_date', 'expiry_date']);
        $query-> fields('x', ['user_displayname']);
        $query-> addExpression('count(b.survey_id)', 'response');
        $query-> addExpression('count(ac.id)', 'survey_access');
        
        $query-> condition('a.is_deleted', '0');

        if (!$isSiteAdmin) {          
            $query-> having('a.user_id = :user_id OR COUNT(ac.id)=0 OR COUNT(e.pub_user_id)> 0 OR COUNT(f.buddy_user_id)> 0 OR COUNT(g.pub_group_id)> 0 OR COUNT(h.user_id)> 0', [':user_id' => $my_user_id]);
          }

        $query-> groupBy('a.survey_id');
        $query-> orderBy('a.start_date', 'DESC');  
        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);           

        foreach ($result as $record) {
            $record["tags"] = $TagList->getTagsForModule('survey', $record["survey_id"]);   
            $record["countlike"] = LikeItem::countLike('survey', $record["survey_id"]);
            $record["liked"] = LikeItem::countLike('survey', $record["survey_id"],$my_user_id);
            $output[] = $record;
        }
        return $output; 

    }


    public static function getSurvey($survey_id = "", $my_user_id="") {

        $cond = "";
        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
        if (!$isSiteAdmin) {
            $cond = " AND user_id = '$my_user_id' ";
        }

        $sql = "SELECT file_name,survey_id, title, description, survey_name, user_id, modify_datetime, start_date, expiry_date, is_visible, allow_copy, is_showDep, is_showPost, is_showname,start_survey FROM kicp_survey WHERE is_deleted = 0 and survey_id = '$survey_id' $cond ORDER BY survey_id DESC";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result;

    }


    public static function getSurveyQuestionAll($survey_id = "") {

        $cond = ($survey_id != "") ? " AND survey_id='" . $survey_id . "'" : "";

        $sql = "SELECT id,survey_id, name, type_id, result_id, position, content, required, deleted, public, has_na, show_scale, show_legend, list_style_id,has_others, remark,file_name FROM kicp_survey_question WHERE deleted = 'N' " . $cond . ' ORDER BY position';
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   

        return $result;
    }


    public static function getQuestion($kicp_survey_question = "") {

        $cond = ($survey_id != "") ? " AND survey_id='" . $survey_id . "'" : "";

        $sql = "SELECT id,survey_id, name, type_id, result_id, position, content, required, deleted, public, has_na, show_scale, show_legend, list_style_id,has_others, remark,file_name FROM kicp_survey_question WHERE deleted = 'N' " . $cond . ' ORDER BY position';
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   

        return $result;
    }


    public static function getSurveyQuestion($survey_id = "", $position = "") {

        $cond = '';
        $cond .= ($survey_id != "") ? " AND survey_id='" . $survey_id . "'" : "";
        $cond .= ($position != "") ? " AND position='" . $position . "'" : "";

        $sql = "SELECT id,survey_id, name, type_id, result_id, position, content, required, deleted, public, has_na, show_scale, show_legend, list_style_id,has_others, remark,file_name FROM kicp_survey_question WHERE deleted = 'N' " . $cond . ' ORDER BY position';
        $database = \Drupal::database();

        if ($position != "") {
            $result = $database-> query($sql)->fetchObject();   

        } else {
            $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   
        }

        return $result;
    }

    public static function getSurveyQuestionCount($survey_id = "") {

        $sql = "SELECT count(1) as count FROM kicp_survey_question WHERE  deleted = 'N' and  survey_id='" . $survey_id . "'";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchField();
        return $result;
    }


    public static function getSurveyChoice($question_id = "") {
        $sql = "SELECT id,choice FROM kicp_survey_question_choice WHERE question_id='" . $question_id . "'";
        $sql .= " order by id";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   
        return $result;    
    }

    public static function getSurveyChoiceCount($question_id = "") {
        $sql = "SELECT count(1) as count FROM kicp_survey_question_choice WHERE  question_id='" . $question_id . "'";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchField();
        return $result;
    }

    public static function getSurveyRateView($question_id = "") {
        $sql = "SELECT id, scale, legend, position FROM kicp_survey_question_rate WHERE question_id='" . $question_id . "' order by position, id";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   
        return $result;
    }

    public static function getSurveyById($survey_id = "") {

        $cond = '';
        $cond .= ($survey_id != "") ? " AND survey_id='$survey_id'" : "";

        $sql = "SELECT file_name,survey_id, title, description, survey_name, user_id, modify_datetime, start_date, expiry_date, is_visible, allow_copy, is_showDep, is_showPost, is_showname FROM kicp_survey WHERE is_deleted = 0 " . $cond . ' ORDER BY survey_id DESC';
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();   
        return $result;
    }    

    public static function getSurveyResponseRankById($survey_id = "", $respondent_id = "", $question_id = "") {
        $cond = '';
        $cond .= ($respondent_id != "") ? " AND rk.respondent_id='$respondent_id'" : "";
        $cond .= ($question_id != "") ? " AND rk.question_id='$question_id'" : "";

        $sql = "SELECT IFNULL(r.scale,0) as scale FROM kicp_survey_response_rank rk LEFT JOIN kicp_survey_question_choice c on rk.response=c.id LEFT JOIN kicp_survey_question_rate r on rk.rank=r.id WHERE rk.survey_id='$survey_id' " . $cond . ' ORDER BY c.id, r.position';
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC); 

        return $result;
    }


    public static function getSurveyRespondentCount($survey_id = "") {

        $sql = "SELECT count(1) as count FROM kicp_survey_respondent WHERE submitted = 'Y' AND survey_id= '$survey_id'";

        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        return $result->count;
    }    

    public static function getSurveyRespondent($survey_id = "") {

        $sql = "SELECT r.username, r.modify_datetime, r.id, u.user_dept, u.user_post_unit, u.user_name FROM kicp_survey_respondent r LEFT JOIN xoops_users  u  on r.username=u.user_id WHERE r.submitted = 'Y' AND survey_id= '$survey_id' ORDER BY r.modify_datetime";

        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   
        return $result;
    }

    public static function getSurveyResponseById($survey_id = "", $respondent_id = "", $question_id = "") {

        $cond = '';
        $cond .= ($respondent_id != "") ? " AND k.respondent_id='$respondent_id'" : "";
        $cond .= ($question_id != "") ? " AND k.question_id='$question_id'" : "";


        $sql = "SELECT k.respondent_id,k.question_id, IFNULL(c.choice,k.response) as response FROM kicp_survey_response k LEFT JOIN kicp_survey_question_choice c on k.response=c.id WHERE survey_id='$survey_id' " . $cond . ' ORDER BY respondent_id, question_id';
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);   

        return $result;
    }


    public static function insertResponse($entry) {

        $query = \Drupal::database()->insert('kicp_survey_response')
                    ->fields($entry);
                    $return_value = $query->execute();

        return $return_value;

    }

    public static function insertResponseRank($entry) {

        $query = \Drupal::database()->insert('kicp_survey_response_rank')
                    ->fields($entry);
                    $return_value = $query->execute();

        return $return_value;

    }

    public static function resetSurveyChoice($question_id = "") {

        $query = \Drupal::database()->delete('kicp_survey_question_choice')
                    ->condition('question_id', $question_id);
                    $return_value = $query->execute();        

        return $return_value;

    }

    public static function resetSurveyRate($question_id = "") {

        $query = \Drupal::database()->delete('kicp_survey_question_rate')
                    ->condition('question_id', $question_id);
                    $return_value = $query->execute();      
                    
        return $return_value;                    

    }


    public static function saveAttach($filename, $this_filename, $survey_id, $question_id="", ) {

        $this_survey_id = str_pad($survey_id, 6, "0", STR_PAD_LEFT);
        $this_question_id = $question_id!=""?"/".str_pad($question_id, 6, "0", STR_PAD_LEFT):"";

        $file_system = \Drupal::service('file_system');   
        $survey_path = 'private://survey/'.$this_survey_id.$this_question_id;
        if (!is_dir($file_system->realpath( $survey_path))) {
            // Prepare the directory with proper permissions.
            if (!$file_system->prepareDirectory($survey_path, FileSystemInterface::CREATE_DIRECTORY)) {
              throw new \Exception('Could not create the survey directory.');
            }
        }                  
        
        $validators = array(
          'file_validate_extensions' => array('jpg jpeg gif png txt doc docx xls xlsx pdf ppt pptx pps odt ods odp zip'),
          'file_validate_size' => array(15 * 1024 * 1024),
          );

        $delta = NULL; // type of $file will be array
        $file = file_save_upload('filename', $validators, $survey_path,$delta);

        // rename file, remove white space in filename
        if(file_exists($survey_path."/".$filename) && $filename != $this_filename) {
            exec("mv \"".$survey_path."/".$filename."\" \"".$survey_path."/".$this_filename."\"");     
        }

        $file[0]->setPermanent();
        $file[0]->uid = $survey_id;
        $file[0]->save();
        $url = $file[0]->createFileUrl(FALSE);

        return $url;

    }


}
