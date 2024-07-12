<?php

/**
 * @file
 */

namespace Drupal\vote\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\common\TagList;
use Drupal\common\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\common\Follow;
use Drupal\vote\Common\VoteDatatable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;


class VoteController extends ControllerBase {

    public $is_authen;
    public $my_user_id;
    public $module;    

    public function __construct() {
        $this->module = 'vote';
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->is_authen = $authen->isAuthenticated;
        $this->my_user_id = $authen->getUserId();            
    }

    public function VoteContent() {

        $url = Url::fromUri('base:/no_access');
        if (! $this->is_authen) {
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

    public function VoteViewOld() {
        $vote_id = \Drupal::request()->query->get('vote_id');
        if ($vote_id && is_numeric($vote_id))
            $url = Url::fromUri('base:/vote_view/'.$vote_id);
        else {
                $url = Url::fromUri('base:/vote/');
        }
        return new RedirectResponse($url->toString(), 301);
      }

    public function deleteVote($vote_id) {

        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 

        $actual_files = 0;
        $file_system = \Drupal::service('file_system');
        $VoteUri = 'private://vote';
        $vote_path = $file_system->realpath($VoteUri);
    
        $database = \Drupal::database();
        $transaction = $database->startTransaction();   
        try {
            
            $query = \Drupal::database()->update('kicp_vote')->fields([
                'is_deleted'=>1 , 
                'modify_datetime' => date('Y-m-d H:i:s'),
              ])
            ->condition('vote_id', $vote_id)
            ->condition('is_deleted', 0);
            if (!$isSiteAdmin) {
                $query->condition('user_id', $this->my_user_id);
            }               
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
                \Drupal::logger('vote')->info('Deleted id: %id, delted files: %actual_files' ,   
                array(
                    '%id' => $vote_id,    
                    '%actual_files' => $actual_files,
                ));        

                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('Vote has been deleted.'));    
            } else {
                \Drupal::messenger()->addError(
                    t('Unable to delete vote at this time due to datbase error. Please try again. ' )
                    );
                \Drupal::logger('vote')->error('Vote is not deleted: '.$vote_id);   
                $transaction->rollBack();                     
            }
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addError(
                t('Unable to delete vote: '.$vote_id )
                );
            \Drupal::logger('vote')->error('Vote is not deleted: '.$vote_id);   
            
        }
        unset($transaction); 
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
                
        $output =  '<html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\"><html><head><meta http-equiv=\"Content-type\" content=\"text/html;charset=utf-8\" /></head><body>';
        
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
            $questionId = $record['id'];
            $questionType = $record['type_id'];
            $questionHasOther = $record['has_others'];

            $sectionHeader .= '<td  nowrap>' . $record['name'] . '</td>';
            $questionHeader .= '<td  nowrap>';
            $questionHeader .= $record['content'];
            $questionRateHeader .= '<td  nowrap></td>';

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

    public static function Breadcrumb() {

        $base_url = Url::fromRoute('vote.vote_content');
        $base_path = [
            'name' => 'Vote', 
            'url' => $base_url,
        ];
        $breads = array();
        $route_match = \Drupal::routeMatch();
        $routeName = $route_match->getRouteName();
        $request = \Drupal::request();
        $session = $request->getSession();
        $vote_id = (isset($_SESSION['vote_id']) && $_SESSION['vote_id'] != "") ? $_SESSION['vote_id'] : "";
        if ($vote_id != "") {
            $change_url = Url::fromRoute('vote.vote_change_1', ['vote_id' => $vote_id]);
            $edit_path = [
                'name' => 'Edit information', 
                'url' => $change_url??null ,
            ];
    
        }

        if ($routeName=="vote.vote_content") {
            $breads[] = [
                'name' => 'Vote', 
            ];
        } else if ($routeName=="vote.vote_view") {
            $vote_id = $route_match->getParameter('vote_id');
            $breads[] = $base_path;
            $title = VoteDatatable::getVoteName($vote_id);
            $breads[] = [
                'name' => $title??'No Vote' ,
            ];                 
        } else if ($routeName=="vote.vote_add_page1") {
            $breads[] = $base_path;
            $breads[] = [
                'name' => 'Add' ,
            ];
        } else if ($routeName=="vote.vote_copy") {
            $breads[] = $base_path;
            $breads[] = [
                'name' => 'Copy' ,
            ];           

        } else if ($routeName=="vote.vote_add_page2") {
            $breads[] = $base_path;
            $breads[] = $edit_path;
            $breads[] = [
                'name' => 'Add / Update questions' ,
            ];           

        } else if ($routeName=="vote.vote_add_page3") {
            $breads[] = $base_path; 
            $breads[] = $edit_path;
            if ($vote_id != "") {
                $add2_url = Url::fromRoute('vote.vote_add_page2');
            }
            $breads[] = [
                'name' => 'Add / Update questions' ,
                'url' => $add2_url??null ,
            ];
            $breads[] = [
                'name' => 'Questions Order' ,
            ];           

        } else if ($routeName=="vote.vote_add_page4") {
            $breads[] = $base_path; 
            if ($vote_id != "") {
                $add2_url = Url::fromRoute('vote.vote_add_page2');
                $add3_url = Url::fromRoute('vote.vote_add_page3');
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

        } else if ($routeName=="vote.vote_change_1") {
            $breads[] = $base_path;
            $breads[] = [
                'name' => 'Edit Infomation' ,
            ];           

        }


        return $breads;
    }

}