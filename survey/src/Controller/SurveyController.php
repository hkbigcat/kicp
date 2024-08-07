<?php

/**
 * @file
 */

namespace Drupal\survey\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\common\TagList;
use Drupal\common\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\common\AccessControl;
use Drupal\survey\Common\SurveyDatatable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class SurveyController extends ControllerBase {

    public $my_user_id;
    public $module;    

    public function __construct() {
        $this->module = 'survey';
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $current_user = \Drupal::currentUser();
        $this->my_user_id = $current_user->getAccountName();            
    }

    public function SurveyContent() {

        $url = Url::fromUri('base:/no_access');
        $logged_in = \Drupal::currentUser()->isAuthenticated();
        if (!$logged_in) {
            return new RedirectResponse($url->toString());
        }

        $tags = array();
        $tmp = null;
      
        $tagsUrl = \Drupal::request()->query->get('tags');

        if ($tagsUrl) {
            $tags = json_decode($tagsUrl);
            if ($tags && count($tags) > 0 ) {
              $tmp = $tags;
            }
          }


        $surveys = SurveyDatatable::getSurveyList($tags);          

        return [
            '#theme' => 'survey-home',
            '#items' => $surveys,
            '#my_user_id' => $this->my_user_id,
            '#empty' => t('No entries available.'),
            '#tagsUrl' => $tmp,
            '#pager' => ['#type' => 'pager',
            ],
        ];   

    }


    public function SurveyViewOld() {
        $survey_id = \Drupal::request()->query->get('survey_id');
        if ($survey_id && is_numeric($survey_id))
            $url = Url::fromUri('base:/survey_view/'.$survey_id);
        else {
                $url = Url::fromUri('base:/survey/');
        }
        return new RedirectResponse($url->toString(), 301);
      }

    public function deleteSurvey($survey_id) {

        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 

        $actual_files = 0;
        $file_system = \Drupal::service('file_system');
        $SurveyUri = 'private://survey/';
        $survey_path = $file_system->realpath($SurveyUri);
    
        $database = \Drupal::database();
        $transaction = $database->startTransaction();   
        try {
            
            $query = \Drupal::database()->update('kicp_survey')->fields([
                'is_deleted'=>1 , 
                'modify_datetime' => date('Y-m-d H:i:s'),
              ])
            ->condition('survey_id', $survey_id)
            ->condition('is_deleted', 0);
            if (!$isSiteAdmin) {
                $query->condition('user_id', $this->my_user_id);
            }   
            $row_affected = $query->execute();        

            if ($row_affected) {
                $this_survey_id = str_pad($survey_id, 6, "0", STR_PAD_LEFT);
                $survey_dir = $survey_path.'/'.$this_survey_id;
                $survey_uri = $SurveyUri.'/'.$this_survey_id;
                // delete survey attach file from server physically
                if (is_dir($survey_dir)) {
                    $surveyList = scandir($survey_dir);
                    foreach($surveyList as $filename) {

                        if($filename == "." || $filename == "..") {
                            continue;
                        }
                        if (is_dir(($survey_dir.'/'.$filename))) {

                            $surveyQuestionList = scandir($survey_dir.'/'.$filename);
                            foreach($surveyQuestionList as $qustion_filename) {
                                if($qustion_filename == "." || $qustion_filename == "..") {
                                    continue;
                                }
                                $uri = $survey_uri."/".$filename.'/'.$qustion_filename;
                                $fid = CommonUtil::deleteFile($uri);   
                                $actual_files++;                                        
                            }
                        } else {
                          $uri = $survey_uri."/".$filename;
                          $fid = CommonUtil::deleteFile($uri);                                
                          $actual_files++;
                        }
                    }
                }

                // delete tags  
                $return2 = TagStorage::markDelete($this->module, $survey_id);
    
                // write logs to common log table
                \Drupal::logger('survey')->info('Deleted id: %id, delted files: %actual_files' ,   
                array(
                    '%id' => $survey_id,    
                    '%actual_files' => $actual_files,
                ));        

                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('Survey has been deleted.'));    
            } else {
                \Drupal::messenger()->addError(
                    t('Unable to delete survey at this time due to datbase error. Please try again. ' )
                    );
                \Drupal::logger('survey')->error('Survey is not deleted: '.$survey_id);  
                $transaction->rollBack(); 
            }
        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Unable to delete survey: ' )
                );
            \Drupal::logger('survey')->error('Survey is not deleted: '.$variables);  
            $transaction->rollBack();     
        }
        unset($transaction); 
        $response = array('result' => $actual_files);
        return new JsonResponse($response);


    }


    public function exportSurvey($survey_id) {

        $surveyHeader = SurveyDatatable::getSurveyById($survey_id);

        $survey_title = $surveyHeader->title;
        $is_showname = $surveyHeader->is_showname;
        $is_showDep = $surveyHeader->is_showDep;
        $is_showPost = $surveyHeader->is_showPost;
        $num_response = SurveyDatatable::getSurveyRespondentCount($survey_id);
                
        $output =  '<html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\"><html><head><meta http-equiv=\"Content-type\" content=\"text/html;charset=utf-8\" /></head><body>';
        $output .= '<table>';
        $output .= '<tr>';
        $output .= '<td nowrap>Survey Name:</td> <td nowrap>' . $survey_title . '</td>';
        $output .= '<td  nowrap></td><td  nowrap></td>'; 
        $output .= '<td  nowrap>From</td><td nowrap>' .$surveyHeader->start_date . '</td><td  nowrap></td>';
        $output .= '<td  nowrap>To</td><td nowrap>' . $surveyHeader->expiry_date . '</td><td  nowrap></td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<td  nowrap></td><td nowrap>' . $surveyHeader->description . '</td>';
        $output .= '<td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td>';
        $output .= '<td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<td nowrap>Total <b>' . $num_response . '</b> response(s) as at ' . date('H:i:s') . ' on ' . date('d.m.Y') . '</td>';
        $output .= '<td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td>';
        $output .= '</tr>';
        $output .= '</table>';

        $output .= '<table>';

        //Survey Questions
        $surveyQuestions = SurveyDatatable::getSurveyQuestion($survey_id);

        $sectionHeader = "";
        $questionHeader = "";
        $questionRateHeader = "";
        $responseData = "";

        $sectionHeader .= '<tr>';
        $sectionHeader .= '<td  nowrap></td><td  nowrap></td><td  nowrap></td>';

        $questionHeader .= '<tr>';
        $questionHeader .= '<td  nowrap>Response</td>';
        $questionHeader .= '<td  nowrap>Date</td>';

        $questionRateHeader .= '<tr>';
        $questionRateHeader .= '<td  nowrap></td><td  nowrap></td>';
        if ($is_showname == "1") {
            $questionHeader .= '<td  nowrap>Name</td>';
            $questionRateHeader .= '<td  nowrap></td>';
        }
        if ($is_showDep == "1") {
            $questionHeader .= '<td  nowrap>Department</td>';
            $questionRateHeader .= '<td  nowrap></td>';
        }
        if ($is_showPost == "1") {
            $questionHeader .= '<td  nowrap>Post</td>';
            $questionRateHeader .= '<td  nowrap></td>';
        }

        foreach ($surveyQuestions as $record) {
            $questionId = $record['id'];
            $questionType = $record['type_id'];
            $questionHasOther = $record['has_others'];

            if ($questionType == '6') {
                $surveyInfoChoice = SurveyDatatable::getSurveyChoice($questionId);
                $choiceCounter = 0;
                foreach ($surveyInfoChoice as $choice) {
                    $questionRateHeader .= '<td nowrap>' . $choice['choice'] . '</td>';
                    $choiceCounter++;
                }
                $sectionHeader .= '<td colspan="' . $choiceCounter . '" >' . $record['name'] . '</td>'; //nowrap
                $questionHeader .= '<td colspan="' . $choiceCounter . '" >'; //nowrap
                $questionHeader .= $record['content'];
                $questionRateLengcy = SurveyDatatable::getSurveyQuestionRateById($questionId);
                $questionHeader .= '<br> [Option:';
                foreach ($questionRateLengcy as $rate) {
                    $questionHeader .= ' ' . $rate['legend'] . ' - ' . $rate['scale'] . ';';
                }
                if ($questionHasOther == "Y") {
                    $questionHeader .= ' N/A - 0;';
                }
                $questionHeader .= ']';
            } else {
                $sectionHeader .= '<td  nowrap>' . $record['name'] . '</td>';
                $questionHeader .= '<td  nowrap>';
                $questionHeader .= $record['content'];
                $questionRateHeader .= '<td  nowrap></td>';
            }
            $questionHeader .= '</td>';
        }
        $sectionHeader .= '</tr>';
        $questionHeader .= '</tr>';
        $questionRateHeader .= '</tr>';

        $surveyRespondent = SurveyDatatable::getSurveyRespondent($survey_id);
        $respondentIndex = 1;
        foreach ($surveyRespondent as $respondent) {
            $respondentId = $respondent['id'];
            $responseData .= '<tr>';
            $responseData .= '<td  nowrap>' . $respondentIndex . '</td>';
            $responseData .= '<td  nowrap>' . $respondent['modify_datetime'] . '</td>';
            if ($is_showname == "1") {
                $responseData .= '<td  nowrap>' . $respondent['user_name'] . '</td>';
            }
            if ($is_showDep == "1") {
                $responseData .= '<td  nowrap>' . $respondent['user_dept'] . '</td>';
            }
            if ($is_showPost == "1") {
                $responseData .= '<td  nowrap>' . $respondent['user_post_unit'] . '</td>';
            }
            $surveyQuestionss = SurveyDatatable::getSurveyQuestion($survey_id);
            foreach ($surveyQuestionss as $record) {
                $questionId = $record['id'];
                $questionType = $record['type_id'];

                if ($questionType == '6') {
                    $surveyResponseChoice = SurveyDatatable::getSurveyResponseRankById($survey_id, $respondentId, $questionId);
                    foreach ($surveyResponseChoice as $answer) {
                        $responseData .= '<td  nowrap>' . $answer['scale'] . '</td>';
                    }
                } else {
                    $surveyResponse = SurveyDatatable::getSurveyResponseById($survey_id, $respondentId, $questionId);
                    $responseData .= '<td  nowrap>';
                    $responseCount = 0;
                    foreach ($surveyResponse as $answer) {
                        if ($responseCount > 0) {
                            $responseData .= ',';
                        }
                        if( $answer['response']==""){
                            $answerr['response'] = " ";
                        }
                        $responseData .= t(strip_tags($answer['response']));
                        $responseCount++;
                    }
                    $responseData .= '</td>';
                }
            }
            $responseData .= '</tr>';
            $respondentIndex++;
        }
        $output .= $sectionHeader;
        $output .= $questionHeader;
        $output .= $questionRateHeader;
        $output .= $responseData;
        $output .= '</table>';
        $output .= '</body></html>';
        $output = str_replace('’', "'", $output);
        $output = chr(0xEF).chr(0xBB).chr(0xBF).$output;
        $filename = mb_convert_encoding($survey_title,'UTF-8') . "_Report__" . date('Ymd') . ".xls";
        
        $output .= header('Content-Type: application/vnd.ms-excel; charset=utf-8');       
        $output .= header("Content-Disposition: attachment; filename=\"$filename\"");
        $output .= header('Content-Transfer-Encoding: binary');
        $output .= header('Pragma: no-cache');
        $output .= header('Expires: 0');

        $response = new Response();
        $response->setContent($output);
        return $response;

    }

    public static function getFileLocation($survey_id = "") {

        if ( $survey_id == "")
            return false;

        $survey = SurveyDatatable::getSurvey($survey_id, $my_user_id);
        if (!$survey)
            return false;

        $this_survey_id = str_pad($survey_id, 6, "0", STR_PAD_LEFT);
        $file_name = $survey->file_name;

        if (!$file_name)
            return false;

        // file in "private" folder
        $file_path = 'sites/default/files/private/survey/' . $this_survey_id . '/' . $file_name;
        return $file_path;
    }    

    public static function getQuestionFileLocation($survey_id ="", $question_id="",  $my_user_id="") {

        if ($survey_id == "" || $question_id == "") {
            return false;
        }

        $survey = SurveyDatatable::getSurvey($my_user_id, $survey_id);
        if (!$survey)
            return false;
        $this_survey_id = str_pad($survey_id, 6, "0", STR_PAD_LEFT);
        $this_question_id = str_pad($question_id, 6, "0", STR_PAD_LEFT);
        $question = SurveyDatatable::getSurveyQuestionByID($question_id);
        $file_name = $questionInfo->file_name;
        // file in "private" folder
        $file_path = 'sites/default/files/private/survey/' . $this_survey_id . '/' . $this_question_id . '/' . $file_name;
        return $file_path;
    }    

    public static function Breadcrumb() {

        $base_url = Url::fromRoute('survey.survey_content');
        $base_path = [
            'name' => 'Survey', 
            'url' => $base_url,
        ];
        $breads = array();
        $route_match = \Drupal::routeMatch();
        $routeName = $route_match->getRouteName();
        $request = \Drupal::request();
        $session = $request->getSession();
        $survey_id = $route_match->getParameter('survey_id');
        if (!$survey_id || $survey_id=="") {
            $survey_id = (isset($_SESSION['survey_id']) && $_SESSION['survey_id'] != "") ? $_SESSION['survey_id'] : "";
        }

        if ($survey_id != "") {
            $change_url = Url::fromRoute('survey.survey_change_1', ['survey_id' => $survey_id]);
            $edit_path = [
                'name' => 'Edit information', 
                'url' => $change_url??null ,
            ];
        }        
        if ($routeName=="survey.survey_content") {
            $breads[] = [
                'name' => 'Survey', 
            ];
        } else if ($routeName=="survey.survey_view") {
            $survey_id = $route_match->getParameter('survey_id');
            $breads[] = $base_path;
            $title = SurveyDatatable::getSurveyName($survey_id);
            $breads[] = [
                'name' => $title??'No Survey' ,
            ];                 
        } else if ($routeName=="survey.survey_add_page1") {
            $breads[] = $base_path;
            $breads[] = [
                'name' => 'Add' ,
            ];
        } else if ($routeName=="survey.survey_copy") {
            $breads[] = $base_path;
            $breads[] = [
                'name' => 'Copy' ,
            ];           

        } else if ($routeName=="survey.survey_add_page2") {
            $breads[] = $base_path;
            $breads[] = $edit_path;
            $breads[] = [
                'name' => 'Add / Update questions' ,
            ];           

        } else if ($routeName=="survey.survey_add_page3") {
            $breads[] = $base_path; 
            if ($survey_id != "") {
                $add2_url = Url::fromRoute('survey.survey_add_page2', ['survey_id' => $survey_id]);
            }
            $breads[] = $edit_path;
            $breads[] = [
                'name' => 'Add / Update questions' ,
                'url' => $add2_url??null ,
            ];
            $breads[] = [
                'name' => 'Questions Order' ,
            ];           

        } else if ($routeName=="survey.survey_add_page4") {
            $breads[] = $base_path; 
            if ($survey_id != "") {
                $add2_url = Url::fromRoute('survey.survey_add_page2', ['survey_id' => $survey_id]);
                $add3_url = Url::fromRoute('survey.survey_add_page3', ['survey_id' => $survey_id]);
            }
            $breads[] = $edit_path;
            $breads[] = [
                'name' => 'Add / Update questions' ,
                'url' => $add2_url??null ,
            ];
            $breads[] = [
                'name' => 'Questions Order' ,
                'url' => $add3_url??null ,
            ];  
            $breads[] = [
                'name' => 'Invite Participants' ,
            ];           

        } else if ($routeName=="survey.survey_change_1") {
            $breads[] = $base_path;
            $breads[] = [
                'name' => 'Edit Infomation' ,
            ];           

        }


        return $breads;
    }
    
}