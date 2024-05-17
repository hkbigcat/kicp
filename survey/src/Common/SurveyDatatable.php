<?php

/**
 * @file
 */

namespace Drupal\survey\Common;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\LikeItem;
use Drupal\common\TagList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\common\Follow;

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

        $query-> leftjoin('kicp_access_control', 'ac', 'ac.record_id = a.survey_id AND ac.module = :module AND ac.is_deleted = :is_deleted', [':module' => 'survey', ':is_deleted' => '0']);
        if (!$isSiteAdmin) {          
            $query-> leftjoin('kicp_public_user_list', 'e', 'ac.group_id = e.pub_group_id AND ac.group_type= :typeP AND e.is_deleted = :is_deleted AND e.pub_user_id = :user_id', [':is_deleted' => '0',':typeP' => 'P', ':user_id' => $my_user_id]);
            $query-> leftjoin('kicp_buddy_user_list', 'f', 'ac.group_id = f.buddy_group_id AND ac.group_type= :typeB AND f.is_deleted = :is_deleted AND f.buddy_user_id = :user_id', [':is_deleted' => '0', ':typeB' => 'B', ':user_id' => $my_user_id]);
            $query-> leftjoin('kicp_public_group', 'g', 'ac.group_id = g.pub_group_id AND ac.group_type= :typeP AND g.is_deleted = :is_deleted AND g.pub_group_owner = :user_id', [':module' => 'survey', ':is_deleted' => '0', ':typeP' => 'P', ':user_id' => $my_user_id]);
            $query-> leftjoin('kicp_buddy_group', 'h', 'ac.group_id = h.buddy_group_id AND ac.group_type= :typeB AND h.is_deleted = :is_deleted AND h.user_id = :user_id', [':is_deleted' => '0', ':typeP' => 'P', ':user_id' => $my_user_id]);
          }
        
        $query-> fields('a', ['survey_id', 'title', 'description', 'user_id', 'start_date', 'expiry_date', 'allow_copy', 'is_completed']);
        $query-> fields('x', ['user_displayname']);
        $query-> addExpression('count(b.survey_id)', 'response');
        $query-> addExpression('count(ac.id)', 'survey_access');
        
        $query-> condition('a.is_deleted', '0');
        if (!$isSiteAdmin) {          
            $query-> having('a.user_id = :user_id OR a.is_completed = 1 AND (COUNT(ac.id)=0 OR COUNT(e.pub_user_id)> 0 OR COUNT(f.buddy_user_id)> 0 OR COUNT(g.pub_group_id)> 0 OR COUNT(h.user_id)> 0)', [':user_id' => $my_user_id]);
          }

        $query-> groupBy('a.survey_id');
        $query-> orderBy('a.start_date', 'DESC');  
        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);           

        if (!$result)
          return null;
        foreach ($result as $record) {
            $record["tags"] = $TagList->getTagsForModule('survey', $record["survey_id"]);   
            $record["countlike"] = LikeItem::countLike('survey', $record["survey_id"]);
            $record["liked"] = LikeItem::countLike('survey', $record["survey_id"],$my_user_id);
            $record["follow"] = Follow::getFollow($record["user_id"], $my_user_id);
            $output[] = $record;
        }
        return $output; 

    }


    public static function getSurvey($survey_id = "", $user_id = "") {

        $sql_access ="";
        $sql_access2 = "";
        $sql_access3 = "";        
        if (isset($user_id) && $user_id!="") {
            $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
            if (!$isSiteAdmin) {
                $sql_access =  "LEFT JOIN kicp_access_control aa ON (aa.record_id = a.survey_id AND aa.is_deleted = 0 AND aa.module = 'survey') 
                LEFT JOIN kicp_public_user_list e ON (aa.group_type='P' AND e.pub_group_id=aa.group_id AND e.is_deleted=0 AND e.pub_user_id = '".$user_id."'  )
                LEFT JOIN kicp_buddy_user_list f ON (aa.group_type='B' AND aa.group_id = f.buddy_group_id AND f.is_deleted=0 AND f.buddy_user_id = '".$user_id."'  )
                LEFT JOIN kicp_public_group g ON (aa.group_type='P' AND g.pub_group_id=aa.group_id AND g.is_deleted=0 AND g.pub_group_owner  = '".$user_id."'  )
                LEFT JOIN kicp_buddy_group h ON (aa.group_type='B' AND h.buddy_group_id=aa.group_id AND h.is_deleted=0 AND h.user_id  = '".$user_id."'  ) ";    
                $sql_access2 = " group by a.survey_id 
                                 having a.user_id = '".$user_id."' OR COUNT(aa.id)=0 OR COUNT(e.pub_user_id)> 0 OR COUNT(f.buddy_user_id)> 0 OR COUNT(g.pub_group_id)> 0 OR COUNT(h.user_id)> 0 ";
            }
        }

        $sql = "SELECT a.file_name,survey_id, a.title, a.description, a.survey_name, a.user_id, a.modify_datetime, a.start_date, a.expiry_date, a.is_visible, a.allow_copy, a.is_showDep, a.is_showPost, a.is_showname,start_survey, a.is_completed FROM kicp_survey a $sql_access WHERE a.is_deleted = 0 and a.survey_id = '$survey_id' $sql_access2 ORDER BY a.survey_id DESC LIMIT 1";
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

    public static function getSurveyQuestionByID($question_id = "") {

        $sql = "SELECT id, survey_id, `name`, type_id, result_id, position, content, `required`, deleted, public, has_na, show_scale, show_legend, list_style_id,has_others, remark,file_name FROM kicp_survey_question WHERE deleted = 'N' and id = $question_id ";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
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

    
    public static function checkSurveyRespondentSumbited($user_id = "", $survey_id = "") {
        $sql = "SELECT survey_id FROM kicp_survey_respondent WHERE username='$user_id' and survey_id = '$survey_id'";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        if (!$result) 
            return null;
        return $result->survey_id;
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
        $file = file_save_upload('filename', $validators, $survey_path ,$delta);

        if($filename != $this_filename) {
        $file_real_path = \Drupal::service('file_system')->realpath($survey_path.'/'.$filename);
        $file_contents = file_get_contents($file_real_path);
        $newFile = \Drupal::service('file.repository')->writeData($file_contents, $survey_path.'/'.$this_filename, FileSystemInterface::EXISTS_REPLACE);
        $file1 = $file[0];
        $file1->delete();        
        } else {
            $newFile = $file[0];
        }
        $newFile->setPermanent();
        $newFile->uid = $survey_id;
        $newFile->save();
        $url = $newFile->createFileUrl(FALSE);
        
        return $url;

    }


    public static function copyAttach($filename, $old_survey_id, $survey_id, $old_question_id="", $question_id="" ) {

        $this_old_survey_id = str_pad($old_survey_id, 6, "0", STR_PAD_LEFT);
        $this_survey_id = str_pad($survey_id, 6, "0", STR_PAD_LEFT);
        $this_old_question_id = $old_question_id!=""?"/".str_pad($old_question_id, 6, "0", STR_PAD_LEFT):"";
        $this_question_id = $question_id!=""?"/".str_pad($question_id, 6, "0", STR_PAD_LEFT):"";

        $file_system = \Drupal::service('file_system');   
        $old_survey_path = 'private://survey/'.$this_old_survey_id.$this_old_question_id;
        $survey_path = 'private://survey/'.$this_survey_id.$this_question_id;
        if (!is_dir($file_system->realpath( $survey_path))) {
            // Prepare the directory with proper permissions.
            if (!$file_system->prepareDirectory($survey_path, FileSystemInterface::CREATE_DIRECTORY)) {
              throw new \Exception('Could not create the survey directory.');
            }
        }

        if (!$file_system->copy($old_survey_path."/".$filename, $survey_path."/".$filename)) {
            throw new \Exception('Could not copy survey file.');
        }

        return $survey_path."/".$filename;

    }

    public static function copyAccessControlGroupRecord($original_survey_id = "", $new_survey_id = "", $user_id = "") {
        $sql = " insert into kicp_access_control (module, record_id, group_type, group_id, user_id, allow_edit) 
                SELECT 'survey', $new_survey_id , group_type,group_id, '$user_id' , allow_edit from kicp_access_control 
                 WHERE record_id='$original_survey_id' AND is_deleted = 0 And module='survey'";

        $database = \Drupal::database();
        $result = $database-> query($sql);
        return $result;        


    }    

    public static function copyQuestionChoice($original_question_id = '', $new_question_id = '', $user_id = '') {

        $sql = " insert into kicp_survey_question_choice (question_id,choice,modified_by)   
                 SELECT $new_question_id ,choice, '$user_id' from kicp_survey_question_choice 
                where question_id = $original_question_id";

        $database = \Drupal::database();
        $result = $database-> query($sql);
        return $result;   
    }

    public static function copyQuestionRate($original_question_id = '', $new_question_id = '', $user_id = '') {

        $sql = " insert into kicp_survey_question_rate  (question_id,scale,position,legend,modified_by) 
                SELECT  $new_question_id ,scale,position,legend,'$user_id' from kicp_survey_question_rate  
                where question_id = $original_question_id";

        $database = \Drupal::database();
        $result = $database-> query($sql);
        return $result;   
    }    

    public static function DeleteSurveyEntryAttachment($survey_id = "", $question_id ="") {
        $file_system = \Drupal::service('file_system');
        $survey_path = 'private://survey';
        $this_survey_id = str_pad($survey_id, 6, "0", STR_PAD_LEFT);
        $this_question_id = $question_id!=""?"/".str_pad($question_id, 6, "0", STR_PAD_LEFT):"";
        $survey_dir = $file_system->realpath( $survey_path.'/'.$this_survey_id.$this_question_id);
        if (is_dir($survey_dir)) {
            $surveyList = scandir($survey_dir);
            foreach($surveyList as $filename) {
                if(is_dir($filename) ||  $filename == "." || $filename == ".." ) {
                    continue;
                }    
                $uri =  $survey_path.'/'.$this_survey_id."/".$filename;
                $fid = CommonUtil::deleteFile($uri);
            }
        }
    }


}
