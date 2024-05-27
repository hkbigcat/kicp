<?php

/**
 * @file
 */

namespace Drupal\survey\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal;
use Drupal\Component\Utility\UrlHelper;
use Drupal\common\RatingData;
use Drupal\common\TagList;
use Drupal\common\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\survey\Common\SurveyDatatable;
use Drupal\file\FileInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use Drupal\common\AccessControl;
use Drupal\Core\Utility\Error;

class SurveyAddPage2 extends FormBase {

    public function __construct() {
        $this->module = 'survey';
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->is_authen = $authen->isAuthenticated;
        $this->my_user_id = $authen->getUserId();     
        $this->allow_file_type = CommonUtil::getSysValue('survey_allow_file_type');
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'survey_add_form2';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

      if (! $this->is_authen) {
        $form['no_access'] = [
            '#markup' => CommonUtil::no_access_msg(),
        ];     
        return $form;        
      }

        // display the form

        $request = \Drupal::request();
        $session = $request->getSession();
        $questionNumber = $session->get('questionNo');

        $form['#attributes'] = array('enctype' => 'multipart/form-data');
        $get_survey_id = (isset($_SESSION['survey_id']) && $_SESSION['survey_id'] != "") ? $_SESSION['survey_id'] : "";

        $isEdit = 0;

        $questionInfo = SurveyDatatable::getSurveyQuestion($get_survey_id, $questionNumber);

        $surveyInfoCount = SurveyDatatable::getSurveyQuestionCount( $get_survey_id);

        $surveyChoice = array();
        $surveyInfoRate = array();
        if ($questionInfo) {
          $surveyChoice = SurveyDatatable::getSurveyChoice($questionInfo->id);
          $surveyInfoRate = SurveyDatatable::getSurveyRateView($questionInfo->id);
          $surveyChoiceCount = SurveyDatatable::getSurveyChoiceCount($questionInfo->id);
        }
        $totalQuestionNo = !isset($surveyInfoCount) || ($surveyInfoCount == "" ) ? 1 : $surveyInfoCount;
        $totalChoiceNo = !isset($surveyChoiceCount) || ($surveyChoiceCount == "" ) ? 1 : $surveyChoiceCount;
        $this_survey_id = str_pad( $get_survey_id, 6, "0", STR_PAD_LEFT);

        $choicearry = [];
        $k = 1;
        $choiceCounter = 0;

        if ($surveyChoice) {
          foreach ($surveyChoice as $result) {
              $choicearry[$k]['choice'] = $result['choice'];
              $k++;
              $choiceCounter++;
          }
        }
        $ratearry = [];
        $k = 1;
        $rateCounter = 0;
        foreach ($surveyInfoRate as $rateresult) {
            $ratearry[$k]['scale'] = $rateresult['scale'];
            $ratearry[$k]['legend'] = $rateresult['legend'];
            $ratearry[$k]['position'] = $rateresult['position'];
            $k++;
            $rateCounter++;
        }

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
        /*
        if ( !isset($surveyChoice) || $surveyChoice==null || $choiceCounter == 0) {
            $resultChoicearry = array(
              '#choice' => '',
            );
        } else {
            $resultChoicearry = array(
              '#choice' => $surveyChoice['id'],
            );
        }
        */
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
            $file_path = 'download?module=survey_question&file_id=' . $get_survey_id . '&fname=' . $questionInfo->file_name . '&question_id=' . $questionInfo->id;
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
          '#allowed_formats' => ['basic_html'],
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
        $questionTypeSelect['2'] = 'Text Box';
        $questionTypeSelect['3'] = 'Essay Box';
        $questionTypeSelect['4'] = 'Radio Buttons';
        $questionTypeSelect['5'] = 'Check Boxes (more than one answer)';
        $questionTypeSelect['6'] = 'Rate (scale m..n)';


        $form['answerType'] = array(
          '#title' => t('Answer Type'),
          '#type' => 'select',
          '#options' => $questionTypeSelect,
          '#attributes' => array('onChange' => 'showDiv(this.value)', 'id' => 'answerType', 'onload' => 'showDiv(this.value)'),
          '#prefix' => '<div class="div_inline_column">',
          '#suffix' => '</div><div class="w20px"></div>Reminder: The Answer Type of a confirmed question cannot be changed.',
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


        $form['DisplayScale'] = array(
          '#title' => t('Display Scale'),
          '#type' => 'checkbox',
          '#prefix' => '<div id="Rate">',
          '#default_value' => $resultQuestionarry['show_scale'],
        );

        $form['DisplayLegend'] = array(
          '#title' => t('Display Legend'),
          '#type' => 'checkbox',
          '#default_value' => $resultQuestionarry['show_legend'],
        );
        $form['Rateintro1'] = array(
          '#markup' => '( You could click <a href="modules\custom\common\images\sample_rate.png" target="_blank">here</a> to see the sample of scale / legend. )'
        );

        $form['Rateintro2'] = array(
          '#markup' => 'Please edit the scale/ legend below (Column marked with <font color="#FF0000">*</font> is mandatory. Leave the textbox blank when unnecessary.)'
        );
        $form['rateHeader'] = array(
          '#markup' => '<table id="scaleTable"><thead><tr>'//
          . '<th align="center" style="width:80px;">Scale<font color="#FF0000">*</font></th>'
          . '<th align="left" style="width:300px;">&nbsp;&nbsp;Legend</th>'//left
          . '<th align="center" style="width:80px;">Order</th>'

          . '</tr></thead>', //
        );
        $form['rateFooter'] = array(
          '#type' => 'submit',
          '#value' => t('Add another line'),
          '#prefix' => '<tfoot><tr><td colspan="3">', //
          '#suffix' => '</td></tr></tfoot>', //
          '#attributes' => array('onClick' => 'addRateLine("scaleTable");'),
        );
        $suffixlegend = '';
        $rateDefaultCount = 5;
        if ($rateCounter > $rateDefaultCount) {
            $rateDefaultCount = $rateCounter;
        }

        for ($i = 1; $i < $rateDefaultCount+1; $i++) {
            $currentRateIndex = $rateDefaultCount - $i + 1;
            $defaultScale = !isset($ratearry[$i]['scale']) || ($ratearry[$i]['scale'] == "" ) ? $currentRateIndex : $ratearry[$i]['scale'];
            $defaultPosition = !isset($ratearry[$i]['Position']) || ($ratearry[$i]['Position'] == "" ) ? $i : $ratearry[$i]['Position'];
            $trPrefix = '<tr>';
            $form['rateScale' . $i] = array(
              '#type' => 'textfield',
              '#size' => 50,
              '#prefix' => '<tr><td align="center"><input style="text-align:center" type="text" size="60" id="rate_scale_1" name="rate_scale_1" value="1">',
              '#suffix' => '</td>',
              '#default_value' => $defaultScale,
            );
            if ($i == 1) {//$rateDefaultCount
                $suffixlegend = ' eg. Excellent</td>';
            } else if ($i == 2) {//$rateDefaultCount - 1
                $suffixlegend = ' eg. Very Good</td>';
            } else {
                $suffixlegend = '</td>';
            }
            $form['rateLegend' . $i] = array(
              '#type' => 'textfield',
              '#size' => 50,
              '#prefix' => '<td align="center"><input style="text-align:center" type="text" size="60" id="rate_scale_1" name="rate_scale_1" value="1">',
              //'#prefix' => '<td align="center"><input style="text-align:center" type="text" size="60" name="rateLegend[]" value="1">',
              '#suffix' => $suffixlegend,
              '#default_value' =>  isset($ratearry[$i]['legend'])? $ratearry[$i]['legend']:'',
            );

            $form['ratePosition' . $i] = array(
              '#type' => 'textfield',
              '#size' => 50,
              '#prefix' => '<td align="center"><input style="text-align:center" type="text" size="60" id="rate_scale_1" name="rate_scale_1" value="1">',
              '#suffix' => '</td></tr>',
              '#default_value' => $defaultPosition,
            );
        }
        $form['rateTableEnd'] = array(
          '#markup' => '</table></div>'
        );
        $form['hiddenRates'] = array(
          '#type' => 'hidden',
          '#default_value' => 0,
          '#attributes' => array('id' => 'hiddenRates'),
        );
        $form['hiddenExistingRates'] = array(
          '#type' => 'hidden',
          '#default_value' => $rateDefaultCount,
          '#attributes' => array('id' => 'hiddenExistingRates'),
        );
        $choiceDefaultCount = 10;
        if ($choiceCounter > $choiceDefaultCount) {
            $choiceDefaultCount = $choiceCounter;
        }
        $form['hiddenExistingChoices'] = array(
          '#type' => 'hidden',
          '#default_value' => $choiceDefaultCount,
          '#attributes' => array('id' => 'hiddenExistingChoices'),
        );

        //Ratio Check Box DIV---------------------------------------------------------------
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
          '#attributes' => array( 'onClick' => 'addAnswerLine("RadioCheckbox");'),
          '#prefix' => '<div class="inline">',
          '#suffix' => '<div class="w30px"></div>',
        );

        $form['btClearAll'] = array(
          '#type' => 'submit',
          '#value' => t('Clear all answer lines'),
          '#attributes' => array('onClick' => 'clearAnswerLine("RadioCheckbox");'),
          '#suffix' => '<div class="w30px"></div>',
        );

        $form['btReset'] = array(
          '#type' => 'submit',
          '#value' => t('Reset Whole question'),
          '#attributes' => array( 'onClick' => 'resetSurvey();showDiv(0);'),
          //'#suffix' => '</div><br></div>',
          '#suffix' => '</div><div class="spacer"></div></div>',
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

        $form['QuestionButtonLine'] = array(
          '#markup' => '<div class="inline">',
        );
         
        for ($i = 1; $i <= $totalQuestionNo; $i++) {
            if ($i != $questionNumber) {
                $form['btQuestion' . $i] = array(
                  '#type' => 'submit',
                  '#name' => 'btQuestion' . $i,
                  '#value' => $i,
                  //'#attributes' => array('style' => 'display:inline-block;float:left; margin-left:5px; margin-right:5px; ', ),
                );
            }
        }
        $form['btNewQuestion'] = array(
          '#type' => 'submit',
          '#name' => 'btNewQuestion',
          '#value' => t('Save & New Question'),
          '#attributes' => array('id' => 'NewQuestion'),
          '#prefix' => '<div class="w30px"></div>',
          '#suffix' => '</div>',
          '#limit_validation_errors' => [],
        );

        $form['btSaveOnly'] = array(
          '#type' => 'submit',
          '#name' => 'btSaveOnly',
          '#value' => t('Save This Question'),
        );
        

        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Save & Complete'),
          //'#attributes' => array('style' => 'margin-left:30px;', ),
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

    //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }

        $button_clicked = $form_state->getTriggeringElement()['#name'];

        if (substr($button_clicked,0,10)=="btQuestion") {
          $request = \Drupal::request();
          $session = $request->getSession();
          $session->set('questionNo', 1);
          $url = new Url('survey.survey_add_page2');
          $form_state->setRedirectUrl($url);
          return;
        } 

        $hiddenJump = 0;
        $hiddenAddanother = ($button_clicked == "btNewQuestion")?1:0;
        $btSaveOnly = ($button_clicked == "btSaveOnly")?1:0;

        $get_survey_id = (isset($_SESSION['survey_id']) && $_SESSION['survey_id'] != "") ? $_SESSION['survey_id'] : "";
        $questionInfo = SurveyDatatable::getSurveyQuestion($get_survey_id, $questionNo);

        if (isset($deleteFile) && $deleteFile == 1) {
          $attach_deleted = SurveyDatatable::DeleteSurveyEntryAttachment($get_survey_id,$questionInfo->id );
          $query = \Drupal::database()->update('kicp_survey_question')->fields([
            'file_name' => '',
          ]) 
          ->condition('survey_id',  $get_survey_id);
          $row_affected = $query->execute();                  
        }

        // File path
        $this_survey_id = $get_survey_id!=""?str_pad($get_survey_id, 6, "0", STR_PAD_LEFT):"";

        //*************** File [Start]
        $tmp_name = $_FILES["files"]["tmp_name"]['filename'];
        $this_filename = CommonUtil::file_remove_character($_FILES["files"]["name"]['filename']);
        $file_ext = strtolower(pathinfo($this_filename, PATHINFO_EXTENSION));
        //*************** File [End]

        $question_id = $questionId;

        $database = \Drupal::database();
        $transaction = $database->startTransaction(); 
        try {
           
//        if ($hiddenIsEdit == 0) {
          if (!isset($question_id) || $question_id == '') {
            $entry = array(
              'survey_id' => $get_survey_id,
              'name' => $name['value'],
              'content' => $question['value'],
              'position' => $questionNo,
              'has_Others' => $includeOption,
              'required' => $required,
              'type_id' => $answerType,
              'modified_by' => $this->my_user_id,
              'file_name' => $this_filename,
              'show_scale' => $DisplayScale,
              'show_legend' => $DisplayLegend,
            );

            
            $query = $database->insert('kicp_survey_question')->fields( $entry);
            $question_id = $query->execute();

            if ($question_id) {

                // if file is selected-------------------------------------------------------------------------
                if ($_FILES['files']['name']['filename'] != "") {
                  SurveyDatatable::saveAttach( $_FILES['files']['name']['filename'], $this_filename, $get_survey_id, $question_id);
                }

                for ($i = 1; $i <= $hiddenExistingChoices; $i++) {
                    if (${'title' . $i} != '') {
                        $entry = array(
                          'question_id' => $question_id,
                          'choice' => ${'title' . $i},
                          'modified_by' => $this->my_user_id,);

                        $query = $database->insert('kicp_survey_question_choice')->fields( $entry);
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
                              'modified_by' => $this->my_user_id,);

                              $query = $database->insert('kicp_survey_question_choice')->fields( $entry);
                              $titlereuturn = $query->execute();
                        }
                    }
                }
                for ($i = $hiddenExistingRates; $i > 0; $i--) {
                    if (${'rateLegend' . $i} != '') {
                        $entry = array(
                          'question_id' => $question_id,
                          'scale' => ${'rateScale' . $i},
                          'legend' => ${'rateLegend' . $i},
                          'position' => ${'ratePosition' . $i},
                          'modified_by' => $this->my_user_id,);

                          $query = $database->insert('kicp_survey_question_rate')->fields( $entry);
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
                              'modified_by' => $this->my_user_id,);

                            $query = $database->insert('kicp_survey_question_rate')->fields( $entry);
                            $ratereturn = $query->execute();
                        }
                    }
                }

                if ($hiddenAddanother == "1") {
                      $questionNo = $questionNo + 1;
                      $totalQuestionNo = $totalQuestionNo + 1;
                      $request = \Drupal::request();
                      $session = $request->getSession();
                      $session->set('questionNo', $questionNo);
                      $session->set('totalQuestionNo', $totalQuestionNo); 
                      $url = new Url('survey.survey_add_page2');
                } elseif ($btSaveOnly == 1) {
                  $url = new Url('survey.survey_add_page2');
                } else {
                    $url = new Url('survey.survey_add_page3');
                }
                \Drupal::logger('survey')->info('Created question id: %id.',   
                array(
                    '%id' => $question_id,
                ));   
                $form_state->setRedirectUrl($url);
              } else {

                \Drupal::messenger()->addError(
                  t('New Survey question is not created. ' )
                  );
                \Drupal::logger('survey')->error('New Survey question is not created (3)');   
                $transaction->rollBack();  
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
                  'show_scale' => $DisplayScale,
                  'show_legend' => $DisplayLegend,
                );
                if ($this_filename != "") {
                    $entry['file_name']= $this_filename;
                }
          
                $query = $database->update('kicp_survey_question')->fields( $entry)
                ->condition('id', $question_id);
                $return = $query->execute();

                if ($return) {

                  // if file is selected-------------------------------------------------------------------------
                  if ($_FILES['files']['name']['filename'] != "") {
                    SurveyDatatable::saveAttach( $_FILES['files']['name']['filename'], $this_filename, $get_survey_id, $question_id);
                  }
                  //update choice
                  $resetreuturn = SurveyDatatable::resetSurveyChoice($question_id);
                  for ($i = 1; $i <= $hiddenExistingChoices; $i++) {
                      if (${'title' . $i} != '') {
                          $entry = array(
                            'question_id' => $question_id,
                            'choice' => ${'title' . $i},
                            'modified_by' => $this->my_user_id,);
                            
                          $query = $database->insert('kicp_survey_question_choice')->fields( $entry);
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
                                'modified_by' => $this->my_user_id,);

                              $query = $database->insert('kicp_survey_question_choice')->fields( $entry);
                              $titlereuturn = $query->execute();                              
    
                          }
                      }
                  }
                  //update rate
                  $resetratereuturn = SurveyDatatable::resetSurveyRate($question_id);
                  for ($i = $hiddenExistingRates; $i > 0; $i--) {
                      if (${'rateLegend' . $i} != '') {
                          $entry = array(
                            'question_id' => $question_id,
                            'scale' => ${'rateScale' . $i},
                            'legend' => ${'rateLegend' . $i},
                            'position' => ${'ratePosition' . $i},
                            'modified_by' => $this->my_user_id,);

                          $query = $database->insert('kicp_survey_question_rate')->fields( $entry);
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
                                  'modified_by' => $this->my_user_id,);

                                $query = $database->insert('kicp_survey_question_rate')->fields( $entry);
                                $ratereturn = $query->execute();
      
                            }
                        }
                    }
                    \Drupal::logger('survey')->info('Created updated id: %id.',   
                    array(
                        '%id' => $question_id,
                    ));                       

                    if ($hiddenAddanother == "1") {
                      $questionNo = $totalQuestionNo + 1;
                      if ($questionNo > $totalQuestionNo) {
                        $totalQuestionNo = $totalQuestionNo + 1;
                      }
                      $request = \Drupal::request();
                      $session = $request->getSession();
                      $session->set('questionNo', $questionNo);
                      $session->set('totalQuestionNo', $totalQuestionNo);                        
                      $url = new Url('survey.survey_add_page2');
                    } elseif ($btSaveOnly == 1) {
                        $url = new Url('survey.survey_add_page2');
                    } else {
                        $url = new Url('survey.survey_add_page3');
                    }
                    $form_state->setRedirectUrl($url);
                } else {
                  \Drupal::messenger()->addError(
                    t('update Survey : ( '.$get_survey_id.' ) question ( '.$questionNo.' )  is not sucesss. ' )
                    );
                    \Drupal::logger('survey')->error('question is not created (3) ');
                    $transaction->rollBack();  
                }
            }
        } catch (Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
              t('Squestion is not created. ' )
              );            
            \Drupal::logger('survey')->error('question is not created: ' . $variables);
            $transaction->rollBack();  
        }
        unset($transaction);
    }

}
