<?php

/**
 * @file
 */

namespace Drupal\vote\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal;
use Drupal\Component\Utility\UrlHelper;
use Drupal\common\CommonUtil;
use Drupal\vote\Common\VoteDatatable;
use Drupal\file\FileInterface;
use Drupal\file\Entity\File;

class VoteView extends FormBase {

    public function __construct() {
        $this->module = 'vote';
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->my_user_id = $authen->getUserId();         
        $this->allow_file_type = CommonUtil::getSysValue('vote_allow_file_type');
        $this->max_preview_page = CommonUtil::getSysValue('vote_max_preview_page');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'vote_view_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $form['#attributes'] = array('enctype' => 'multipart/form-data');

        $vote_id = (isset($_REQUEST['vote_id']) && $_REQUEST['vote_id'] != "") ? $_REQUEST['vote_id'] : "";
        $vote = VoteDatatable::getVote($vote_id);
        
        $isShowSubmit = true;
        if (!$vote) {
          $isShowSubmit = false;
          $messenger = \Drupal::messenger(); 
          $messenger->addStatus( t('This vote does not exist or you do not have authroity'));             
          return $form; 
        }        
        $questionInfo = VoteDatatable::getVoteQuestionAll($vote_id);
        $respondent = VoteDatatable::checkVoteRespondentSumbited($this->my_user_id, $vote_id);

        $dateType_start = new \DateTime($vote->start_date);
        $dateType_expiry = new \DateTime($vote->expiry_date);
        $currentdate = new \DateTime(date('Y-m-d 00:00:00'));        

        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 

        $isShowSubmit = true;
        if ($dateType_start > $currentdate) {
            $isShowSubmit = false;
            $form['intro'] = array(
              '#markup' => t('<p><i style="font-size:20px; color:red; margin-right:10px;" class="fa-solid fa-ban"></i> This vote does not start yet.</p>'),
            );            
            if (!$isSiteAdmin and ( $this->my_user_id != $vote->user_id)) {      
              $form['Close2'] = array(
                  '#type' => 'button',
                  '#value' => t('Close'),
                  '#prefix' => '&nbsp;',
                  '#attributes' => array('onClick' => 'history.go(-1); return false;'),
                  '#limit_validation_errors' => array(),
                );    
              return $form; 
          }
        }
        if ($dateType_expiry < $currentdate) {
          $isShowSubmit = false;
          $form['intro'] = array(
                '#markup' => t('<p><i style="font-size:20px; color:red; margin-right:10px;" class="fa-solid fa-ban"></i> This vote is ended.</p>'),
          );    
          if (!$isSiteAdmin and ( $this->my_user_id != $vote->user_id))  {
              $form['Close2'] = array(
                  '#type' => 'button',
                  '#value' => t('Close'),
                  '#prefix' => '&nbsp;',
                  '#attributes' => array('onClick' => 'history.go(-1); return false;'),
                  '#limit_validation_errors' => array(),
                );    
               return $form; 
          }              
        }

        if ($respondent) {
          $isShowSubmit = false;
          $form['intro'] = array(
              '#markup' => t('<p><i style="font-size:20px; color:red; margin-right:10px;" class="fa-solid fa-ban"></i> You have already answered this Vote</p>'),
            );
          if (!$isSiteAdmin and (  $this->my_user_id != $vote->user_id)) {    
              $form['Close2'] = array(
                  '#type' => 'button',
                  '#value' => t('Close'),
                  '#prefix' => '&nbsp;',
                  '#attributes' => array('onClick' => 'history.go(-1); return false;'),
                  '#limit_validation_errors' => array(),
                );    
              return $form; 
          }
      }          
        
//page 1        

        $form['VoteTitle'] = array(
          '#markup' => '<span class="titleView">' . $vote->title . '</span><p>',
        );

        $form['VoteDescription'] = array(
          '#markup' => t($vote->description),
        );
        $i = 1;
        $form['mandatoryIntro'] = array(
          '#markup' => '<p><strong>Question marked with <span class="redstar">&nbsp;*</span> is mandatory.</strong></p>',
        );


        if ($vote->file_name != '') {
            $file_path = 'download/vote/' . $vote_id;
            $form['filePath'] = array(
              '#markup' => '<p><a href="' . $file_path . '" target="_blank"><i class="fa-solid fa-paperclip"></i><span class="w20px"></span>' . $vote->file_name. '</a><br>',
            );
        }
        $redstar = '<span class="redstar1">&nbsp;*</span>';
        $questionCounter = 0;

        if ($questionInfo) {
          foreach ($questionInfo as $record) {
              $questionCounter++;
              $choicearry = [];
            
              foreach ($record as $key => $value) {
                  $$key = $value;
              }
              $voteInfoChoice = VoteDatatable::getVoteChoice($id);
              $k = 1;
                                
              $questionTitle = "";

              if ($record['required'] == 'Y') {
                  $questionTitle .= $redstar; 
              }

              $questionTitle .= t($record['content']);

              $form['QuestionName' . $i] = array(
                '#markup' => t('<span class="sectionView">'.$record['name'].'</span>'),
              );

              if ($record['file_name'] != '') {
                  $file_question_path = 'download/vote_question/' . $vote_id . '?question='.$record['id'];
                  $form['fileQuestionPath' . $i] = array(
                    '#markup' => '<p><a href="' . $file_question_path . '" target="_blank"><i class="fa-solid fa-paperclip"></i><span class="w20px"></span>' . $record['file_name'] . '</a></p><div class="spacer"></div>',
                  );
              }

              switch ($type_id) {
                  case 1: {
                          $yesno = array();
                          $yesno["Yes"] = "Yes";
                          $yesno["No"] = "No";

                          $form['answer' . $i] = array(
                            '#title' => t($questionTitle), 
                            '#type' => 'select',
                            '#options' => $yesno,
                          );
                          break;
                      }
              
                  case 4: {
                          foreach ($voteInfoChoice as $result) {
                              $choicearry[$result['id']] = $result['choice'];
                          }

                          $form['answer' . $i] = array(
                            '#title' => t($questionTitle), 
                            '#type' => 'radios',
                            '#options' => $choicearry,
                            '#attributes' => array('onClick' => 'clearRadioOther(\'other' . $i . '\')'),
                          );
                          if ($record['has_others'] == 'Y')
                              $form['other' . $i] = array(
                                '#title' => 'Others:',
                                '#type' => 'textfield',
                                '#size' => 50,
                                '#maxlength' => 255,
                                '#attributes' => array('onClick' => 'clearRadio(\'answer' . $i . '\')', 'id' => 'other' . $i),
                              );
                          break;
                      }
                  case 5: {
                          $k = 1;
                          $form['CheckBoxTitle' . $i] = array(
                            '#markup' => t($questionTitle), 
                          );

                          foreach ($voteInfoChoice as $recordcoice) {
                              $form['answer' . $i .'_'. $k] = array(
                                '#title' => $recordcoice['choice'],
                                '#type' => 'checkbox',
                                '#default_value' => '0',
                                '#attributes' => array('id' => ('answer' . $i . $k)),
                              );
                              $k = $k + 1;
                          }
                          if ($record['has_others'] == 'Y')
                              $form['other' . $i] = array(
                                '#title' => 'Others:',
                                '#type' => 'textfield',
                                '#size' => 50,
                                '#maxlength' => 255,
                              );
                          break;
                      }
                
                  default:
                      break;
                }
                $form['questionsepartor' . $i] = array(
                  '#suffix' => '<div class="greyBorderBottom"></div>',
                );
                $i++;
          }
        }

        $form['actions']['print'] = array(
          '#type' => 'submit',
          '#value' => t('Print'),
          '#attributes' => array('onClick' => 'printDiv("vote-view-form");'),
        );



      if ($isShowSubmit && $vote->is_visible==1 && $vote->is_completed==1  ) {    
          $form['actions']['submitVote'] = array(
            '#type' => 'submit',
            '#value' => t('Submit Vote'),
            '#access' => $isShowSubmit,
            '#attributes' => array('onClick' => 'submitVote();'),
          );
          $form['hiddenSubmit'] = array(
            '#type' => 'hidden',
            '#value' => 0,
            '#attributes' => array('id' => 'hiddenSubmit'),
          );

          $form['actions']['Rest'] = array(
            '#type' => 'submit',
            '#value' => t('Reset'),
            '#attributes' => array('onClick' => 'resetVote();'),
          );
        } else {
          $messenger = \Drupal::messenger(); 
          $messenger->addStatus( t('This vote cannot be submitted.'));    
          $form['Close2'] = array(
              '#type' => 'button',
              '#value' => t('Close'),
              '#prefix' => '&nbsp;',
              '#attributes' => array('onClick' => 'history.go(-1); return false;'),
              '#limit_validation_errors' => array(),
            );       
        }


        $form['actions']['Close'] = array(
          '#type' => 'button',
          '#value' => t('Close'),
          '#prefix' => '&nbsp;',
          '#attributes' => array('onClick' => 'history.go(-1); return false;'),
          '#limit_validation_errors' => array(),
        );

        $form['vote_id'] = array(
          '#type' => 'hidden',
          '#value' => $vote_id,
        );
        return $form;
    }

    //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }
        $hasError = false;
        $questionInfo = VoteDatatable::getVoteQuestionAll($vote_id);

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }

        $i = 1;
        $voteInfoChoice = [];
        $choicearry = [];
        $errorMessage = "";
        $firstError = 0;
        $firstErrorQuestion;
        foreach ($questionInfo as $record) {

            foreach ($record as $key => $value) {
                $$key = $value;
            }

            if($required == 'Y'){
                if ($type_id == 4) {
                    if ((!isset(${'answer' . $i}) || ${'answer' . $i} == '') && (!isset(${'other' . $i}) || ${'other' . $i} == '')) {
                        $errorMessage .= $this->t("Question" . $i . " is blank<br>");
                        $hasError = true;
                        if($firstError == 0){$firstError = $i;}
                    }
                } elseif ($type_id == 5) {
                    $j = 1;
                    $voteInfoChoice = VoteDatatable::getVoteChoice($id);
                    $k = 1;
                    $hasError = true;
                    foreach ($voteInfoChoice as $result) {
                        $choicearry[$result['choice']] = $result['choice'];
                        $k++;
                    }

                    foreach ($choicearry as $recordcoice) {
                        $tmp .= ${'answer' . $i .'_'. $j} ." ";
                        if (${'answer' . $i .'_'. $j} == 1)
                            $hasError = false;
                        $j++;
                    }
                    if (isset(${'other' . $i}) && ${'other' . $i} != '') {
                        $hasError = false;

                    }
                    if ($hasError) {
                        $errorMessage .= $this->t("Question" . $i . " is blank<br>");
                        $hasError = true;
                        if($firstError == 0){$firstError = $i.'_'. $j;}
                    }
                } 
            }
            
            $i++;
        }
        if($hasError){
            $form_state->setErrorByName(
                            'answer' . $firstError , t($errorMessage)
            );
        }
        

    }

    //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }

    
        $RespondentEntry = array(
          'submitted' => 'Y',
          'username' => $this->my_user_id,
          'create_datetime' => date('Y-m-d H:i:s'),
          'modify_datetime' => date('Y-m-d H:i:s'),
          'vote_id' => $vote_id,
        );

        try {

          $query = \Drupal::database()->insert('kicp_vote_respondent')
          ->fields($RespondentEntry);
          $Respondent_id = $query->execute();          
    
            $questionInfo = VoteDatatable::getVoteQuestionAll($vote_id);            
            $i = 1;            
            foreach ($questionInfo as $record) {

                foreach ($record as $key => $value) {
                    $$key = $value;
                }

                if (($type_id == 1)) {
                  $QuestionEntry = array(
                    'question_id' => $id,
                    'response' => ${'answer' . $i},
                    'create_datetime' => date('Y-m-d H:i:s'),
                    'modify_datetime' => date('Y-m-d H:i:s'),
                    'vote_id' => $vote_id,
                    'respondent_id' => $Respondent_id,
                  );
                  $vote_id = VoteDatatable::insertResponse($QuestionEntry);                    

                }  elseif (($type_id == 3) ) {
                    $QuestionEntry = array(
                      'question_id' => $id,
                      'response' => ${'answer' . $i}['value'],
                      'create_datetime' => date('Y-m-d H:i:s'),
                      'modify_datetime' => date('Y-m-d H:i:s'),
                      'vote_id' => $vote_id,
                      'respondent_id' => $Respondent_id,
                    );

                    $vote_id = VoteDatatable::insertResponse($QuestionEntry);                    

                  } elseif (($type_id == 4)) {
                    if (${'other' . $i} == '') {
                        $QuestionEntry = array(
                          'question_id' => $id,
                          'response' => ${'answer' . $i},
                          'create_datetime' => date('Y-m-d H:i:s'),
                          'modify_datetime' => date('Y-m-d H:i:s'),
                          'vote_id' => $vote_id,
                          'respondent_id' => $Respondent_id,
                        );
                    } else {
                        $QuestionEntry = array(
                          'question_id' => $id,
                          'response' => ${'other' . $i},
                          'create_datetime' => date('Y-m-d H:i:s'),
                          'modify_datetime' => date('Y-m-d H:i:s'),
                          'vote_id' => $vote_id,
                          'respondent_id' => $Respondent_id,
                        );
                    }

                    $vote_id = VoteDatatable::insertResponse($QuestionEntry);                    

                  } elseif ($type_id == 5) {
                    $j = 1;

                    $voteInfoChoice = VoteDatatable::getVoteChoice($id);

                    $k = 1;

                    foreach ($voteInfoChoice as $recordcoice) {
                        if (${'answer' . $i .'_'. $j} == 1) {
                            $QuestionEntry = array(
                              'question_id' => $id,
                              'response' => $recordcoice['id'],
                              'create_datetime' => date('Y-m-d H:i:s'),
                              'modify_datetime' => date('Y-m-d H:i:s'),
                              'vote_id' => $vote_id,
                              'respondent_id' => $Respondent_id,
                            );
                            $return = VoteDatatable::insertResponse($QuestionEntry);                           

                        }
                        $j = $j + 1;
                    }
                    if (${'other' . $i} != '') {
                        $QuestionEntry = array(
                          'question_id' => $id,
                          'response' => ${'other' . $i},
                          'create_datetime' => date('Y-m-d H:i:s'),
                          'modify_datetime' => date('Y-m-d H:i:s'),
                          'vote_id' => $vote_id,
                          'respondent_id' => $Respondent_id,
                        );
                        $return = VoteDatatable::insertResponse($QuestionEntry);                        

                    }
                
                }
                $i++;
                // $return1 = TagStorage::insert($entry1);
            }
            //end looping
            $k++;

            $url = new Url('vote.vote_content');
            $form_state->setRedirectUrl($url);
    
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Thanks you for sumbiting the answers.'));  

        } catch (Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
              t('Vote is not created ' )
              );
            \Drupal::logger('error')->notice('Vote is not created: ' . $variables);
        }
    }

}
