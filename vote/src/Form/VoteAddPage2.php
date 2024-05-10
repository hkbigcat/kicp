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
use Drupal\common\RatingData;
use Drupal\common\Controller\TagList;
use Drupal\common\Controller\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\vote\Common\VoteDatatable;
use Drupal\file\FileInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use Drupal\common\AccessControl;

class VoteAddPage2 extends FormBase {

    public function __construct() {
        $this->module = 'vote';
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->my_user_id = $authen->getUserId();     
        $this->allow_file_type = CommonUtil::getSysValue('vote_allow_file_type');
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'vote_add_form2';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        // display the form

        $request = \Drupal::request();
        $session = $request->getSession();
        $questionNumber = $session->get('questionNo');

        $form['#attributes'] = array('enctype' => 'multipart/form-data');
        $get_vote_id = (isset($_SESSION['vote_id']) && $_SESSION['vote_id'] != "") ? $_SESSION['vote_id'] : "";

        $isEdit = 0;

        $questionInfo = VoteDatatable::getVoteQuestion($get_vote_id, $questionNumber);

        $voteInfoCount = VoteDatatable::getVoteQuestionCount( $get_vote_id);

        //$voteInfoRate = array();
        if ($questionInfo) {
          $voteChoice = VoteDatatable::getVoteChoice($questionInfo->id);
          //$voteInfoRate = VoteDatatable::getVoteRateView($questionInfo->id);
          $voteChoiceCount = VoteDatatable::getVoteChoiceCount($questionInfo->id);
        }
        $totalQuestionNo = !isset($voteInfoCount) || ($voteInfoCount == "" ) ? 1 : $voteInfoCount;
        $totalChoiceNo = !isset($voteChoiceCount) || ($voteChoiceCount == "" ) ? 1 : $voteChoiceCount;
        $this_vote_id = str_pad( $get_vote_id, 6, "0", STR_PAD_LEFT);

        $choicearry = [];
        $k = 1;
        $choiceCounter = 0;

        if ($questionInfo) {
          foreach ($voteChoice as $result) {
              $choicearry[$k]['choice'] = $result['choice'];
              $k++;
              $choiceCounter++;
          }
        }
        /*
        $ratearry = [];
        $k = 1;
        $rateCounter = 0;
        foreach ($voteInfoRate as $rateresult) {
            $ratearry[$k]['scale'] = $rateresult['scale'];
            $ratearry[$k]['legend'] = $rateresult['legend'];
            $ratearry[$k]['position'] = $rateresult['position'];
            $k++;
            $rateCounter++;
        }
        */

        if ($questionInfo==null) {
            $resultQuestionarry = array(
              'question_id' => '',
              'name' => '',
              'question' => '',
              'answerType' => 1,
              'includeOption' => 'Y',
              'required' => 'Y',
              'position' => 1,
              'show_legend' => 1,
              'show_scale' => 1,
            );
        } else {
            $resultQuestionarry = array(
              'question_id' => $questionInfo->id,
              'name' => $questionInfo->name,
              'question' => $questionInfo->content,
              'answerType' => $questionInfo->type_id,
              'includeOption' => $questionInfo->has_others,
              'required' => $questionInfo->required,
              'position' => $questionInfo->position,
              'show_legend' => $questionInfo->show_legend,
              'show_scale' => $questionInfo->show_scale,
            );

            $this_question_id = str_pad($resultQuestionarry['question_id'], 6, "0", STR_PAD_LEFT);
            
  
        }
        if ( !isset($voteChoice) || $voteChoice==null || $choiceCounter == 0) {
            $resultChoicearry = array(
              '#choice' => '',
            );
        } else {
            $resultChoicearry = array(
              '#choice' => $voteChoice->id,
            );
        }
        $form['name'] = array(
          '#title' => t('Section:</p>  Here, you could input some descriptive text or instructions for a group of questions.[For example, Q2-Q8 are of similar nature]'),
          '#type' => 'text_format',
          '#format' => 'basic_html',
          '#allowed_formats' => ['basic_html'],
          '#rows' => 10,
          '#cols' => 30,
          '#attributes' => array('style' => 'height:400px;'),
          '#prefix' => '<div class="div_step2">',
          '#suffix' => '</div>',
          '#default_value' => $resultQuestionarry['name'],
        );
        if (isset($questionInfo->file_name) && $questionInfo->file_name != '') {
            $file_path = 'download?module=vote_question&file_id=' . $get_vote_id . '&fname=' . $questionInfo->file_name . '&question_id=' . $questionInfo->id;
            $form['filePath'] = array(
              '#markup' => '<a href="' . $file_path . '" target="_blank"><img src="modules/custom/common/images/icon_attachment.png" border="0" align="absmiddle">' . $questionInfo->file_name . '</a><br>'
            );
            $form['deleteFile'] = array(
              '#title' => t('Delete original file?'),
              '#type' => 'checkbox',
              '#attributes' => array('style' => 'display:inline-block;float:left;'),
              '#default_value' => 0,
            );
            $form['filename'] = array(
              '#title' => t('Choose File to overwirte the exiting file'),
              '#type' => 'file',
              '#size' => 150,
              '#description' => 'Only support ' . str_replace(' ', ', ', $this->allow_file_type) . ' file format',
            );
        } else {
            $form['filename'] = array(
              '#title' => t('File'),
              '#type' => 'file',
              '#size' => 150,
              '#description' => 'Only support ' . str_replace(' ', ', ', $this->allow_file_type) . ' file format',
                //'#required' => true,
            );
        }


        $form['question'] = array(
          '#title' => t('Question ' . $questionNumber . '</p> 	Please type the question below'),
          '#type' => 'text_format',
          '#format' => 'basic_html',
          '#rows' => 10,
          '#cols' => 30,
          '#attributes' => array('style' => 'height:400px;'),
          '#prefix' => '<div class="div_step2">',
          '#suffix' => '</div>',
          '#default_value' => $resultQuestionarry['question'],
        );

        $questionTypeSelect = array();
        $questionTypeSelect["0"] = "-- Please select --";
        $questionTypeSelect['1'] = 'Yes/No';
//      $questionTypeSelect['2'] = 'Text Box';
//      $questionTypeSelect['3'] = 'Essay Box';
        $questionTypeSelect['4'] = 'Radio Buttons';
        $questionTypeSelect['5'] = 'Check Boxes (more than one answer)';
//      $questionTypeSelect['6'] = 'Rate (scale m..n)';


        $form['answerType'] = array(
          '#title' => t('Answer Type'),
          '#type' => 'select',
          '#options' => $questionTypeSelect,
          '#attributes' => array('onChange' => 'showDiv(this.value)', 'id' => 'answerType', 'onload' => 'showDiv(this.value)'),
          '#prefix' => '<div class="div_inline_column">',
          '#suffix' => '</div>Reminder: The Answer Type of a confirmed question cannot be changed.',
          '#default_value' => $resultQuestionarry['answerType'],
        );

        $includeoption = array();
        $includeoption['Y'] = "Yes";
        $includeoption['N'] = "No";

        if (empty($answerType)) {
            $answerType = $resultQuestionarry['answerType'];
        }

        $form['required'] = array(
          '#title' => t('Required'),
          '#type' => 'select',
          '#options' => $includeoption,
          '#default_value' => ($resultQuestionarry['required']=='N')?'N':'Y',
        );

        //Rate DIV---------------------------------------------------------------
        /*
        $form['hiddenExistingRates'] = array(
          '#type' => 'hidden',
          '#value' => $rateDefaultCount,
          '#attributes' => array('id' => 'hiddenExistingRates'),
        );
        */
        $choiceDefaultCount = 10;
        if ($choiceCounter > $choiceDefaultCount) {
            $choiceDefaultCount = $choiceCounter;
        }
        $form['hiddenExistingChoices'] = array(
          '#type' => 'hidden',
          '#value' => $choiceDefaultCount,
          '#attributes' => array('id' => 'hiddenExistingChoices'),
        );

        //---------------------------------------------------------------

        $form['includeOption'] = array(
          '#title' => t('Include "Others" option?'),
          '#type' => 'select',
          '#options' => $includeoption,
          '#attributes' => array('onChange' => 'getAllEventItem(this.value)'),
          '#default_value' => $resultQuestionarry['includeOption'],
          '#prefix' => '<div id="RadioCheckbox">', //
          '#suffix' => '<p>Answer Options</p>Enter the answer choices below (leave the textbox blank when unnecessary)',
        );

        for ($i = 1; $i <= $choiceDefaultCount; $i++) {
            $form['title' . $i] = array(
              '#title' => t($i . '.'),
              '#type' => 'textfield',
              '#size' => 90,
              '#maxlength' => 500,
              '#default_value' => isset($choicearry[$i]['choice'])?$choicearry[$i]['choice']:'',
            );
        }

        $form['btAddanother'] = array(
          '#type' => 'submit',
          '#value' => t('Add another answer line'),
          '#attributes' => array('style' => 'display:inline-block;margin:20px;', 'onClick' => 'addAnswerLine("RadioCheckbox");'),
          '#prefix' => '<div class="div_inline_column">  ',
        );

        $form['btClearAll'] = array(
          '#type' => 'submit',
          '#value' => t('Clear all answer lines'),
          '#attributes' => array('style' => 'display:inline-block;margin:20px;', 'onClick' => 'clearAnswerLine("RadioCheckbox");'),
        );

        $form['btReset'] = array(
          '#type' => 'submit',
          '#value' => t('Reset Whole question'),
          '#attributes' => array('style' => 'display:inline-block;margin:20px;', 'onClick' => 'resetVote();showDiv(0);'),
          '#suffix' => '</div></div>',
        );

        $form['#suffix'] = '</div>';
        $form['hiddenChoices'] = array(
          '#type' => 'hidden',
          '#value' => 0,
          '#attributes' => array('id' => 'hiddenChoices'),
        );
        /*
        $form['hiddenAddanother'] = array(
          '#type' => 'hidden',
          '#value' => 0,
          '#attributes' => array('id' => 'hiddenAddanother'),
        );
        */
        $form['hiddenIsEdit'] = array(
          '#type' => 'hidden',
          '#value' => $isEdit,
          '#attributes' => array('id' => 'hiddenIsEdit'),
        );
        $form['hiddenSelectanother'] = array(
          '#type' => 'hidden',
          '#value' => 0,
          '#attributes' => array('id' => 'hiddenSelectanother'),
        );
        $form['totalQuestionNo'] = array(
          '#type' => 'hidden',
          '#value' => $totalQuestionNo,
          '#attributes' => array('id' => 'totalQuestionNo'),
        );

        $form['questionNo'] = array(
          '#type' => 'hidden',
          '#value' => $questionNumber,
          '#attributes' => array('id' => 'questionNo'),
        );
        $form['questionId'] = array(
          '#type' => 'hidden',
          '#value' => $resultQuestionarry['question_id'],
          '#attributes' => array('id' => 'questionId'),
        );
        /*
        $form['hiddenJump'] = array(
          '#type' => 'hidden',
          '#value' => 0,
          '#attributes' => array('id' => 'hiddenJump'),
        );
        */

        for ($i = 1; $i <= $totalQuestionNo; $i++) {
            if ($i != $questionNumber) {
                $form['btQuestion' . $i] = array(
                  '#type' => 'submit',
                  '#name' => 'btQuestion' . $i,
                  '#value' => $i,
                  '#attributes' => array('style' => 'display:inline-block; margin-left:5px; margin-right:5px; ', ),
                );
            }
        }
        $form['btNewQuestion'] = array(
          '#type' => 'submit',
          '#name' => 'btNewQuestion',
          '#value' => t('NewQuestion'),
          '#attributes' => array('id' => 'NewQuestion', 'style' => 'display:inline-block;margin-left:5px; margin-right:5px ', ),
        );
 

        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Save'),
          '#attributes' => array('style' => 'display:inline-block;margin-left:30px;', ),
          '#suffix' => '</div>',
        );

        $jsOutput = '<script>showDiv(' . $resultQuestionarry['answerType'] . ');</script>';
        $form['docReady'] = array(
        '#markup' => t($jsOutput),
      );

        return $form;
    }

    //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {


      $button_clicked = $form_state->getTriggeringElement()['#name'];
      if (substr($button_clicked,0,10)!="btQuestion") {

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }
        //*************** File [Start]
        $tmp_name = $_FILES["files"]["tmp_name"]['filename'];
        $this_filename = CommonUtil::file_remove_character($_FILES["files"]["name"]['filename']);
        $file_ext = strtolower(pathinfo($this_filename, PATHINFO_EXTENSION));
        //*************** File [End]

        if ($_FILES['files']['name']['filename'] != "") {
            if (!in_array($file_ext, explode(' ', $this->allow_file_type))) {
                $form_state->setErrorByName(
                    'filename', $this->t("File format not supported.")
                );
                $hasError = true;
            }
        }

        $hasError = false;
        if (!isset($question) || $question['value'] == '') {
            $form_state->setErrorByName(
                'question', $this->t("Question is blank")
            );
            $hasError = true;
        }


        if (!isset($answerType) || $answerType == '0') {
            $form_state->setErrorByName(
                'answerType', $this->t("Answer type is blank")
            );
            $hasError = true;
        }

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

        $hiddenAddanother = 0;
        $hiddenJump = 0;
        $button_clicked = $form_state->getTriggeringElement()['#name'];

        if (substr($button_clicked,0,10)=="btQuestion") {
          $hiddenJump   = $$button_clicked;
          $request = \Drupal::request();
          $session = $request->getSession();
          $session->set('questionNo', $hiddenJump);
          $url = new Url('vote.vote_add_page2');
          $form_state->setRedirectUrl($url);
          return;
        } 

        $hiddenJump = 0;
        $hiddenAddanother = ($button_clicked == "btNewQhuestion")?1:0;
        
        $get_vote_id = (isset($_SESSION['vote_id']) && $_SESSION['vote_id'] != "") ? $_SESSION['vote_id'] : "";
        $questionInfo = VoteDatatable::getVoteQuestion($get_vote_id, $questionNo);

        if ($deleteFile == 1) {
          $attach_deleted = VoteDatatable::DeleteVoteEntryAttachment($get_vote_id,$questionInfo->id );
          $query = \Drupal::database()->update('kicp_vote_question')->fields([
            'file_name' => '',
          ]) 
          ->condition('vote_id',  $get_vote_id);
          $row_affected = $query->execute();                  
        }

        // File path
        $this_vote_id = $get_vote_id!=""?str_pad($get_vote_id, 6, "0", STR_PAD_LEFT):"";

        //*************** File [Start]
        $tmp_name = $_FILES["files"]["tmp_name"]['filename'];
        $this_filename = CommonUtil::file_remove_character($_FILES["files"]["name"]['filename']);
        $file_ext = strtolower(pathinfo($this_filename, PATHINFO_EXTENSION));
        //*************** File [End]

        $question_id = $questionId;

        $database = \Drupal::database();
        try {
           
//        if ($hiddenIsEdit == 0) {
          if (!isset($question_id) || $question_id == '') {
            $entry = array(
              'vote_id' => $get_vote_id,
              'name' => $name['value'],
              'content' => $question['value'],
              'position' => $questionNo,
              'has_Others' => $includeOption,
              'required' => $required,
              'create_datetime' => date('Y-m-d H:i:s'),
              'modify_datetime' => date('Y-m-d H:i:s'),
              'type_id' => $answerType,
              'modified_by' => $this->my_user_id,
              'file_name' => $this_filename,
              //'show_scale' => $DisplayScale,
              //'show_legend' => $DisplayLegend,
            );

            
            $query = $database->insert('kicp_vote_question')->fields( $entry);
            $question_id = $query->execute();

              if ($question_id) {

                // if file is selected-------------------------------------------------------------------------
                if ($_FILES['files']['name']['filename'] != "") {
                  VoteDatatable::saveAttach( $_FILES['files']['name']['filename'], $this_filename, $get_vote_id, $question_id);
                }
                // write logs to common log table

                \Drupal::logger('vote')->info('Created question id: %id, filename: %filename.',   
                array(
                    '%id' => $question_id,
                    '%filename' => isset($this_filename)?$this_filename:'no file',
                ));   

                for ($i = 1; $i <= $hiddenExistingChoices; $i++) {
                    if (${'title' . $i} != '') {
                        $entry = array(
                          'question_id' => $question_id,
                          'choice' => ${'title' . $i},
                          'create_datetime' => date('Y-m-d H:i:s'),
                          'modify_datetime' => date('Y-m-d H:i:s'),
                          'modified_by' => $this->my_user_id,);

                        $query = $database->insert('kicp_vote_question_choice')->fields( $entry);
                        $titlereuturn = $query->execute();

                    }
                }

                $regQuotes = '/".*?"|\'.*?\'/';
                $newAns = preg_match_all($regQuotes, $hiddenChoices, $match);
                if ($newAns > 0) {
                    $ansControls = "";
                    $tempCounter = 0;
                    foreach ($match[0] as $eachInput) {
                        if ($eachInput != '') {
                            $entry = array(
                              'question_id' => $question_id,
                              'choice' => trim(str_replace('"', '', $eachInput)),
                              'create_datetime' => date('Y-m-d H:i:s'),
                              'modify_datetime' => date('Y-m-d H:i:s'),
                              'modified_by' => $this->my_user_id,);

                              $query = $database->insert('kicp_vote_question_choice')->fields( $entry);
                              $titlereuturn = $query->execute();
                        }
                    }
                }
                /*
                for ($i = $hiddenExistingRates; $i > 0; $i--) {
                    if (${'rateLegend' . $i} != '') {
                        $entry = array(
                          'question_id' => $question_id,
                          'scale' => ${'rateScale' . $i},
                          'legend' => ${'rateLegend' . $i},
                          'position' => ${'ratePosition' . $i},
                          'create_datetime' => date('Y-m-d H:i:s'),
                          'modify_datetime' => date('Y-m-d H:i:s'),
                          'modified_by' => $this->my_user_id,);

                          $query = $database->insert('kicp_vote_question_rate')->fields( $entry);
                          $ratereturn = $query->execute();

                    }
                }
                $regBrackets = '/\{([^}]+)\}/';
                $newRates = preg_match_all($regBrackets, $hiddenRates, $match);
                if ($newRates > 0) {
                    foreach ($match[1] as $eachInput) {
                        $regQuotes = '/".*?"|\'.*?\'/';
                        $newRate = preg_match_all($regQuotes, $eachInput, $match2);
                        $tempscale = trim(str_replace('"', '', $match2[0][1]));
                        $tempposition = trim(str_replace('"', '', $match2[0][5]));
                        $scale_value = ctype_digit($tempscale) ? intval($tempscale) : null;
                        $legend_value = trim(str_replace('"', '', $match2[0][3]));
                        $position_value = ctype_digit($tempposition) ? intval($tempposition) : null;
                        if ($legend_value != '') {
                            $entry = array(
                              'question_id' => $question_id,
                              'scale' => $scale_value,
                              'legend' => $legend_value,
                              'position' => $position_value,
                              'create_datetime' => date('Y-m-d H:i:s'),
                              'modify_datetime' => date('Y-m-d H:i:s'),
                              'modified_by' => $this->my_user_id,);

                            $query = $database->insert('kicp_vote_question_rate')->fields( $entry);
                            $ratereturn = $query->execute();
                        }
                    }
                }
                */
                if ($hiddenAddanother == "1") {
                      $questionNo = $questionNo + 1;
                      $totalQuestionNo = $totalQuestionNo + 1;
                      $request = \Drupal::request();
                      $session = $request->getSession();
                      $session->set('questionNo', $questionNo);
                      $session->set('totalQuestionNo', $totalQuestionNo); 
                      $url = new Url('vote.vote_add_page2');
                } else {
                    $url = new Url('vote.vote_add_page3');
                }
                $form_state->setRedirectUrl($url);
            } else {

                \Drupal::messenger()->addError(
                  t('New Vote question is not created. ' )
                  );
                  \Drupal::logger('vote')->error('New Vote question is not created (3)');   
                
              }
            } else {   // is Edit
                $entry = array(
                  'name' => $name['value'],
                  'content' => $question['value'],
                  'has_Others' => $includeOption,
                  'required' => $required,
                  'modify_datetime' => date('Y-m-d H:i:s'),
                  'type_id' => $answerType,
                  'modified_by' => $this->my_user_id,
                );
                if ($this_filename != "") {
                    $entry['file_name']= $this_filename;
                }
          
                $query = $database->update('kicp_vote_question')->fields( $entry)
                ->condition('id', $question_id);
                $return = $query->execute();

                if ($return) {

                  // if file is selected-------------------------------------------------------------------------
                  if ($_FILES['files']['name']['filename'] != "") {
                    VoteDatatable::saveAttach( $_FILES['files']['name']['filename'], $this_filename, $get_vote_id, $question_id);
                  }
                  // write logs to common log table
                  \Drupal::logger('vote')->info('update question id: %id, name: %name, filename: %filename.',   
                  array(
                      '%id' => $question_id,
                      '%name' =>  $question['value'],
                      '%filename' => $this_filename,
                  )); 
                  //update choice
                  $resetreuturn = VoteDatatable::resetVoteChoice($question_id);
                  for ($i = 1; $i <= $hiddenExistingChoices; $i++) {
                      if (${'title' . $i} != '') {
                          $entry = array(
                            'question_id' => $question_id,
                            'choice' => ${'title' . $i},
                            'create_datetime' => date('Y-m-d H:i:s'),
                            'modify_datetime' => date('Y-m-d H:i:s'),
                            'modified_by' => $this->my_user_id,);
                            
                          $query = $database->insert('kicp_vote_question_choice')->fields( $entry);
                          $titlereuturn = $query->execute();                              
                          
                      }
                  }
                  $regQuotes = '/".*?"|\'.*?\'/';
                  $newAns = preg_match_all($regQuotes, $hiddenChoices, $match);
                  if ($newAns > 0) {
                      $ansControls = "";
                      $tempCounter = 0;
                      foreach ($match[0] as $eachInput) {
                          if ($eachInput != '') {
                              $entry = array(
                                'question_id' => $question_id,
                                'choice' => trim(str_replace('"', '', $eachInput)),
                                'create_datetime' => date('Y-m-d H:i:s'),
                                'modify_datetime' => date('Y-m-d H:i:s'),
                                'modified_by' => $my_user_id,);

                              $query = $database->insert('kicp_vote_question_choice')->fields( $entry);
                              $titlereuturn = $query->execute();                              
    
                          }
                      }
                  }
                  //update rate
                  //$resetratereuturn = VoteDatatable::resetVoteRate($question_id);
                  /*
                  for ($i = $hiddenExistingRates; $i > 0; $i--) {
                      if (${'rateLegend' . $i} != '') {
                          $entry = array(
                            'question_id' => $question_id,
                            'scale' => ${'rateScale' . $i},
                            'legend' => ${'rateLegend' . $i},
                            'position' => ${'ratePosition' . $i},
                            'create_datetime' => date('Y-m-d H:i:s'),
                            'modify_datetime' => date('Y-m-d H:i:s'),
                            'modified_by' => $this->my_user_id,);

                          $query = $database->insert('kicp_vote_question_rate')->fields( $entry);
                          $ratereturn = $query->execute();

                      }
                  }
                  */
                    $regBrackets = '/\{([^}]+)\}/';
                    /*
                    $newRates = preg_match_all($regBrackets, $hiddenRates, $match);
                    if ($newRates > 0) {
                        foreach ($match[1] as $eachInput) {
                            $regQuotes = '/".*?"|\'.*?\'/';
                            $newRate = preg_match_all($regQuotes, $eachInput, $match2);
                            $tempscale = trim(str_replace('"', '', $match2[0][1]));
                            $tempposition = trim(str_replace('"', '', $match2[0][5]));
                            $scale_value = ctype_digit($tempscale) ? intval($tempscale) : null;
                            $legend_value = trim(str_replace('"', '', $match2[0][3]));
                            $position_value = ctype_digit($tempposition) ? intval($tempposition) : null;
                            if ($legend_value != '') {
                                $entry = array(
                                  'question_id' => $question_id,
                                  'scale' => $scale_value,
                                  'legend' => $legend_value,
                                  'position' => $position_value,
                                  'create_datetime' => date('Y-m-d H:i:s'),
                                  'modify_datetime' => date('Y-m-d H:i:s'),
                                  'modified_by' => $this->my_user_id,);

                                $query = $database->insert('kicp_vote_question_rate')->fields( $entry);
                                $ratereturn = $query->execute();
      
                            }
                        }
                    }
                      */

                    if ($hiddenAddanother == "1") {
                      $questionNo = $totalQuestionNo + 1;
                      if ($questionNo > $totalQuestionNo) {
                        $totalQuestionNo = $totalQuestionNo + 1;
                      }
                      $request = \Drupal::request();
                      $session = $request->getSession();
                      $session->set('questionNo', $questionNo);
                      $session->set('totalQuestionNo', $totalQuestionNo);                        
                      $url = new Url('vote.vote_add_page2');
                    } else {
                        $url = new Url('vote.vote_add_page3');
                    }
                    $form_state->setRedirectUrl($url);
                } else {
                  \Drupal::messenger()->addError(
                    t('update Vote : ( '.$get_vote_id.' ) question ( '.$questionNo.' )  is not sucesss. ' )
                    );
                    \Drupal::logger('vote')->error('question is not created (3) ');
                }
            }
        } catch (Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
              t('Squestion is not created. ' )
              );            
            \Drupal::logger('vote')->error('question is not created: ' . $variables);
        }
    }

}
