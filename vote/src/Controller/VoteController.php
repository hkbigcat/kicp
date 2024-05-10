<?php

/**
 * @file
 */

namespace Drupal\vote\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\common\Controller\TagList;
use Drupal\common\Controller\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\common\Follow;
use Drupal\vote\Common\VoteDatatable;
use Symfony\Component\HttpFoundation\Response;


class VoteController extends ControllerBase {

    public function __construct() {
        $this->module = 'vote';
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->my_user_id = $authen->getUserId();            
    }

    public function VoteContent() {

        $tags = array();
        $tmp = null;
      
        $tagsUrl = \Drupal::request()->query->get('tags');

        if ($tagsUrl) {
            $tags = json_decode($tagsUrl);
            if ($tags && count($tags) > 0 ) {
              $tmp = $tags;
            }
          }


        $votes = VoteDatatable::getVoteList($tags);          
        return [
            '#theme' => 'vote-home',
            '#items' => $votes,
            '#my_user_id' => $this->my_user_id,
            '#empty' => t('No entries available.'),
            '#tagsUrl' => $tmp,
            '#pager' => ['#type' => 'pager',
            ],
        ];   

    }

    public function deleteVote($vote_id) {

        $actual_files = 0;
        $file_system = \Drupal::service('file_system');
        $VoteUri = 'private://vote';
        $vote_path = $file_system->realpath($VoteUri);
    

        $vote = VoteDatatable::getVote($vote_id,  $this->my_user_id);
        if ($vote->title == null) {
            \Drupal::messenger()->addError(
                t('Unable to delete this vote: '.$vote_id )
                );
      
              $response = array('result' => 0);
              return new JsonResponse($response);
        }

        $database = \Drupal::database();

        try {
            
            $query = \Drupal::database()->update('kicp_vote')->fields([
                'is_deleted'=>1 , 
                'modify_datetime' => date('Y-m-d H:i:s'),
              ])
            ->condition('vote_id', $vote_id);
            $row_affected = $query->execute();        

            if ($row_affected) {
                $this_vote_id = str_pad($vote_id, 6, "0", STR_PAD_LEFT);
                $vote_dir = $vote_path.'/'.$this_vote_id;
                $vote_uri = $VoteUri.'/'.$this_vote_id;
                // delete vote attach file from server physically
                if (is_dir($vote_dir)) {
                    $voteList = scandir($vote_dir);
                    foreach($voteList as $filename) {

                        if($filename == "." || $filename == "..") {
                            continue;
                        }
                        
                        // delete vote question attach file from server physically
                        if (is_dir(($vote_dir.'/'.$filename))) {

                            $voteQuestionList = scandir($vote_dir.'/'.$filename);
                            foreach($voteQuestionList as $qustion_filename) {
                                if($qustion_filename == "." || $qustion_filename == "..") {
                                    continue;
                                }
                                $uri = $vote_uri."/".$filename.'/'.$qustion_filename;
                                $fid = CommonUtil::deleteFile($uri);                                
                                $actual_files++;                                        
                            }
                        } else {
                          $uri = $vote_uri."/".$filename;
                          $fid = CommonUtil::deleteFile($uri);                                
                          $actual_files++;
                        }
                    }
                }

                // delete tags  
                $return2 = TagStorage::markDelete($this->module, $vote_id);
    
                // write logs to common log table
                \Drupal::logger('vote')->info('Deleted id: %id, title: %title, delted files: %actual_files' ,   
                array(
                    '%id' => $vote_id,    
                    '%title' => $vote->title,
                    '%actual_files' => $actual_files,
                ));        

                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('Vote has been deleted.'));    
            } else {

                \Drupal::messenger()->addError(
                    t('Unable to delete vote at this time due to datbase error. Please try again. ' )
                    );
                \Drupal::logger('vote')->error('Vote is not deleted: '.$vote_id);   
                    

            }
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addError(
                t('Unable to delete vote: '.$vote_id )
                );
            \Drupal::logger('vote')->error('Vote is not deleted: '.$vote_id);   
            
        }

        $response = array('result' => $actual_files);
        return new JsonResponse($response);


    }


    public function exportVote($vote_id) {

        $voteHeader = VoteDatatable::getVoteById($vote_id);

        $vote_title = $voteHeader->title;
        $is_showname = $voteHeader->is_showname;
        $is_showDep = $voteHeader->is_showDep;
        $is_showPost = $voteHeader->is_showPost;
        $num_response = VoteDatatable::getVoteRespondentCount($vote_id);
                
        $output .=  '<html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\"><html><head><meta http-equiv=\"Content-type\" content=\"text/html;charset=utf-8\" /></head><body>';
        
        $output .= '<table>';
        $output .= '<tr>';
        $output .= '<td nowrap>Vote Name:</td> <td nowrap>' . $vote_title . '</td>';
        $output .= '<td  nowrap></td><td  nowrap></td>'; 
        $output .= '<td  nowrap>From</td><td nowrap>' .$voteHeader->start_date . '</td><td  nowrap></td>';
        $output .= '<td  nowrap>To</td><td nowrap>' . $voteHeader->expiry_date . '</td><td  nowrap></td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<td  nowrap></td><td nowrap>' . $voteHeader->description . '</td>';
        $output .= '<td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td>';
        $output .= '<td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= '<td nowrap>Total <b>' . $num_response . '</b> response(s) as at ' . date('H:i:s') . ' on ' . date('d.m.Y') . '</td>';
        $output .= '<td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td><td  nowrap></td>';
        $output .= '</tr>';
        $output .= '</table>';

        $output .= '<table>';

        //Vote Questions
        $voteQuestions = VoteDatatable::getVoteQuestion($vote_id);

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

        foreach ($voteQuestions as $record) {
            $questionId = $record->id;
            $questionType = $record->type_id;
            $questionHasOther = $record->has_others;

            if ($questionType == '6') {
                $voteInfoChoice = VoteDatatable::getVoteChoice($questionId);
                $choiceCounter = 0;
                foreach ($voteInfoChoice as $choice) {
                    $questionRateHeader .= '<td nowrap>' . $choice['choice'] . '</td>';
                    $choiceCounter++;
                }
                $sectionHeader .= '<td colspan="' . $choiceCounter . '" >' . $record->name . '</td>'; //nowrap
                $questionHeader .= '<td colspan="' . $choiceCounter . '" >'; //nowrap
                $questionHeader .= $record->content;
                $questionRateLengcy = VoteDatatable::getVoteQuestionRateById($questionId);
                $questionHeader .= '<br> [Option:';
                foreach ($questionRateLengcy as $rate) {
                    $questionHeader .= ' ' . $rate->legend . ' - ' . $rate->scale . ';';
                }
                if ($questionHasOther == "Y") {
                    $questionHeader .= ' N/A - 0;';
                }
                $questionHeader .= ']';
            } else {
                $sectionHeader .= '<td  nowrap>' . $record->name . '</td>';
                $questionHeader .= '<td  nowrap>';
                $questionHeader .= $record['content'];
                $questionRateHeader .= '<td  nowrap></td>';
            }
            $questionHeader .= '</td>';
        }
        $sectionHeader .= '</tr>';
        $questionHeader .= '</tr>';
        $questionRateHeader .= '</tr>';

        $voteRespondent = VoteDatatable::getVoteRespondent($vote_id);
        $respondentIndex = 1;
        foreach ($voteRespondent as $respondent) {
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
            $voteQuestionss = VoteDatatable::getVoteQuestion($vote_id);
            foreach ($voteQuestionss as $record) {
                $questionId = $record['id'];
                $questionType = $record['type_id'];

                if ($questionType == '6') {
                    /*
                    $voteResponseChoice = VoteDatatable::getVoteResponseRankById($vote_id, $respondentId, $questionId);
                    foreach ($voteResponseChoice as $answer) {
                        $responseData .= '<td  nowrap>' . $answer['scale'] . '</td>';
                    }
                    */
                } else {
                    $voteResponse = VoteDatatable::getVoteResponseById($vote_id, $respondentId, $questionId);
                    $responseData .= '<td  nowrap>';
                    $responseCount = 0;
                    foreach ($voteResponse as $answer) {
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
        $filename = mb_convert_encoding($vote_title,'UTF-8') . "_Report__" . date('Ymd') . ".xls";
        
        $output .= header('Content-Type: application/vnd.ms-excel; charset=utf-8');       
        $output .= header("Content-Disposition: attachment; filename=\"$filename\"");
        $output .= header('Content-Transfer-Encoding: binary');
        $output .= header('Pragma: no-cache');
        $output .= header('Expires: 0');

        $response = new Response();
        $response->setContent($output);
        return $response;

    }    

    public static function getFileLocation($vote_id = "", $my_user_id="") {

        if ($vote_id == "")
            return false;

        $vote = VoteDatatable::getVote($vote_id, $my_user_id);
        if (!$vote)
            return false;

        $this_vote_id = str_pad($vote_id, 6, "0", STR_PAD_LEFT);
        $file_name = $vote->file_name;

        // file in "private" folder
        $file_path = 'sites/default/files/private/vote/' . $this_vote_id . '/' . $file_name;
        return $file_path;
    }    

    public static function getQuestionFileLocation($vote_id = "", $question_id = "", $my_user_id="") {

        if ($vote_id == "" || $question_id == "") {
            return false;
        }

        $vote = VoteDatatable::getVote($vote_id, $my_user_id);
        if (!$vote)
            return false;

        $this_vote_id = str_pad($vote_id, 6, "0", STR_PAD_LEFT);
        $this_question_id = str_pad($question_id, 6, "0", STR_PAD_LEFT);
        $question = VoteDatatable::getVoteQuestionByID($question_id);        
        $file_name = $question->file_name;
        // file in "private" folder
        $file_path = 'sites/default/files/private/vote/' . $this_vote_id . '/' . $this_question_id . '/' . $file_name;
        \Drupal::logger('vote')->info('file_path : '.$file_path);
        return $file_path;
    }        

}