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
use Drupal\common\CommonUtil;
use Drupal\survey\Common\SurveyDatatable;

class SurveyView extends FormBase {

    public function __construct() {
        $this->module = 'survey';
        $this->allow_file_type = CommonUtil::getSysValue('survey_allow_file_type');
        $this->max_preview_page = CommonUtil::getSysValue('survey_max_preview_page');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'survey_view_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $survey_id = \Drupal::request()->query->get('survey_id');

        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();

        $record_user_id = $authen->getUserId();
        $survey = SurveyDatatable::getSurvey($survey_id);
        $questionInfo = SurveyDatatable::getSurveyQuestionAll($survey_id);

        
        $form['SurveyTitle'] = array(
            '#markup' => '<span class="titleView">' . $survey->title . '</span><p>',
        );
  
        $form['SurveyDescription'] = array(
           '#markup' => t('<div class="SurveyDescription">' . $survey->description . '</div>'),
        );

        $form['mandatoryIntro'] = array(
            '#markup' => '<p><strong>Question marked with <span class="redstar">&nbsp;*</span> is mandatory.</strong></p>',
        );
   
        $redstar = '<span class="redstar1">&nbsp;*</span>';
        $questionCounter = 0;
        $i=1;
        foreach ($questionInfo as $record) {

            $questionCounter++;
            $choicearry = [];
            $rateScalearry = [];
            $rateLegendarry = [];
            $ratePositionarry = [];
            $questionTitle = "";
            foreach ($record as $key => $value) {
                $$key = $value;
            }

            $surveyInfoChoice = SurveyDatatable::getSurveyChoice($id);
            $surveyInfoRate = SurveyDatatable::getSurveyRateView($id);
            $k = 1;

            $counter = 0;
            foreach ($surveyInfoRate as $rateresult) {

                $rateScalearry[$counter] = $rateresult['scale'];
                if ($show_scale == 1) {
                    $rateLegendarry[$rateresult['id']] = $rateresult['legend'] . " (" . $rateresult['scale'] . ")";
                }
                else {
                    $rateLegendarry[$rateresult['id']] = $rateresult['legend'];
                }
                $ratePositionarry[$counter] = $rateresult['position'];
                $counter++;
            }
            if ($has_others == 'Y') {
                if ($show_scale == 1) {
                    $rateLegendarry['other'] = 'N/A (0)';
                }
                else {
                    $rateLegendarry['other'] = 'N/A';
                }
            }

            if ($record['required'] == 'Y') {
                $questionTitle = $redstar; 
            }
            $questionTitle .= $content;

                    
            switch ($type_id) {
                case 1: {
                        $yesno = array();
                        $yesno["Yes"] = "Yes";
                        $yesno["No"] = "No";
                        $form['yesNoTitle' . $i] = array(
                          '#markup' => $questionTitle, 
                        );
                        $form['answer' . $i] = array(
                          '#type' => 'select',
                          '#options' => $yesno,
                        );

                        break;
                    }
                case 2: {
                        $form['textBoxTitle' . $i] = array(
                          '#markup' => $questionTitle, 
                        );
                        $form['answer' . $i] = array(
                          '#type' => 'textfield',
                          '#size' => 90,
                          '#maxlength' => 255,
                        );
                        break;
                    }
                case 3: {
                        $form['essayTitle' . $i] = array(
                          '#markup' => $questionTitle, 
                        );
                        $form['answer' . $i] = array(
                          '#type' => 'text_format',
                          '#format' => 'full_html',                
                          '#rows' => 3,
                          '#cols' => 30,
                          '#attributes' => array('style' => 'height:100px;'),
                          
                        );

                        break;
                    }
                case 4: {
                        foreach ($surveyInfoChoice as $result) {
                            $choicearry[$result['id']] = $result['choice'];
                        }
                        $form['radioTitle' . $i] = array(
                          '#markup' => $questionTitle, 
                        );
                        $form['answer' . $i] = array(
                          '#type' => 'radios',
                          '#options' => $choicearry,
                          '#attributes' => array('onClick' => 'clearRadioOther(\'other' . $i . '\')'),
                        );
                        if ($has_others == 'Y') {
                            $form['radioOtherTitle' . $i] = array(
                              '#markup' => 'Others: please specify',
                                
                            );
                            $form['other' . $i] = array(
                              '#type' => 'textfield',
                              '#size' => 50,
                              '#maxlength' => 255,
                              '#attributes' => array('onClick' => 'clearRadio(\'answer' . $i . '\')', 'id' => 'other' . $i),
                            );
                        }
                        
                        break;
                    }
                case 5: {
                        $k = 1;
                        $form['CheckBoxTitle' . $i] = array(
                          '#markup' => $questionTitle, 
                        );

                        foreach ($surveyInfoChoice as $recordcoice) {
                            $form['answer' . $i . '_' . $k] = array(
                              '#title' => $recordcoice->choice,
                              '#type' => 'checkbox',
                              '#default_value' => '0',
                              '#attributes' => array('id' => ('answer' . $i . $k)),
                            );
                            $k = $k + 1;
                        }
                        if ($has_others == 'Y') {
                            $form['checkboxOtherTitle' . $i] = array(
                              '#markup' => 'Others:',
                            );
                            $form['other' . $i] = array(
                              '#type' => 'textfield',
                              '#size' => 50,
                              '#maxlength' => 255,
                            );
                        }
                        break;
                    }
                case 6: {
                        $k = 1;


                        $form['rateTitle' . $i] = array(
                          '#markup' => $questionTitle, 
                        );
                        
                        $rateTable = '<table class="tb_rate">';
                        $rateTableHeader = "<tr>";
                        $rateColHeader = "<th></th>";

                        for ($x = 0; $x < $counter; $x++) {
                            $rateColHeader .= "<th>";
                            if ($show_legend == 1) {
                                $rateColHeader .= $rateLegendarry[$x];
                            }
                            if ($show_scale == 1) {
                                $rateColHeader .= " ( " . $rateScalearry[$x] . " ) ";
                            }
                            $rateColHeader .= "</th>";
                        }
                        $rateTableHeader .= $rateColHeader;
                        $rateTableHeader .= "</tr>";

                        

                        $form['ratetableopen' . $i] = array(
                          '#markup' => $rateTable,
                        );

                        
                        foreach ($surveyInfoChoice as $recordchoice) {
                            $form['hiddenchoice' . $i . '_' . $k] = array(
                              '#type' => 'hidden',
                              '#default_value' => $recordchoice->id,
                              '#attributes' => array('id' => 'hiddenchoice'),
                            );


                            $rateTableRows = "<tr>";
                            $rateTableRows .= "<td>" . $recordchoice['choice'] . "</td>";
                            $form['ratetablecolopen' . $i . '_' . $k] = array(
                              '#markup' => $rateTableRows,
                            );
                            $rateColSpan = $counter + 1;
                            $form['answer' . $i . '_' . $k] = array(
                              '#type' => 'radios',
                              '#options' => $rateLegendarry,
                              '#prefix' => '<td colspan="' . $rateColSpan . '" style="text-wrap=nowrap;">',
                              '#suffix' => '</td>',
                            );
                            $rateTableRows = "</tr>";
                            $form['ratetableend' . $i . '_' . $k] = array(
                              '#markup' => $rateTableRows,
                            );
                            $k++;
                        }

                        $form['ratetableend' . $i] = array(
                          '#markup' => '</table>',
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

        $form['actions']['submitSurvey'] = array(
            '#type' => 'submit',
            '#value' => t('Submit Survey'),
            '#access' => $isShowSubmit,
            '#attributes' => array('onClick' => 'submitSurvey();'),
        );

        $form['actions']['Rest'] = array(
            '#type' => 'submit',
            '#value' => t('Reset'),
            '#attributes' => array('onClick' => 'resetSurvey();'),
          );
  
          $form['actions']['Close'] = array(
            '#type' => 'button',
            '#value' => t('Close'),
            '#prefix' => '&nbsp;',
            //'#attributes' => array('onClick' => 'window.open(\'bookmark\', \'_self\'); return false;'),
            '#attributes' => array('onClick' => 'history.go(-1); return false;'),
            '#limit_validation_errors' => array(),
          );
  
          $form['survey_id'] = array(
            '#type' => 'hidden',
            '#default_value' => $survey_id,
          );        

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {

         foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }
        $hasError = false;
        $questionInfo = SurveyDatatable::getSurveyQuestionAll($survey_id);

        $i = 1;
        $surveyInfoChoice = [];
        $choicearry = [];        
        $firstError = 0;
        $errorMessage = "";
        foreach ($questionInfo as $record) {
            foreach ($record as $key => $value) {
                $$key = $value;
            }

            if ($required == 'Y') {
                if (($type_id == 1) || ($type_id == 2) ) {
                    if (${'answer' . $i}['value'] == '') {
                        $errorMessage .= $this->t("Question" . $i . " is blank<br>");
                        $hasError = true;
                        if ($firstError == 0) {
                            $firstError = $i;
                        }
                    }
                } elseif ($type_id == 3) {
                    if ((!isset(${'answer' . $i}['value']) || ${'answer' . $i}['value'] == '')) {
                        $errorMessage .= $this->t("Question" . $i . " is blank<br>");
                        $hasError = true;
                        if ($firstError == 0) {
                            $firstError = $i;
                        }
                    }
                } elseif ($type_id == 4) {
                    if ((!isset(${'answer' . $i}) || ${'answer' . $i} == '') && (!isset(${'other' . $i}) || ${'other' . $i} == '')) {
                        $errorMessage .= $this->t("Question" . $i . " is blank<br>");
                        $hasError = true;
                        if ($firstError == 0) {
                            $firstError = $i;
                        }                        
                    }
                } elseif ($type_id == 5) {
                    $j = 1;
                    $surveyInfoChoice = SurveyDatatable::getSurveyChoice($record['id']);
                    $k = 1;
                    $hasError = true;
                    foreach ($surveyInfoChoice as $result) {
                        $choicearry[$result->choice] = $result->choice;
                        $k++;
                    }
                    foreach ($choicearry as $recordcoice) {
                        if (${'answer' . $i . '_' . $j} == 1)
                            $hasError = false;
                        $j++;
                    }
                    if (${'other' . $i}['value'] != '') {
                        $hasError = false;
                    }
                    if ($hasError) {
                        $errorMessage .= $this->t("Question" . $i . " is blank<br>");
                        $hasError = true;
                        if ($firstError == 0) {
                            $firstError = $i . '_' . $j;
                        }                        
                    }
                } elseif ($type_id == 6) {
                    $surveyInfoChoiceSumbit = SurveyDatatable::getSurveyChoice($record['id']);
                    $isQuestionFilled = true;
                    $j = 1;
                    foreach ($surveyInfoChoiceSumbit as $recordcoice) {
                        if (${'answer' . $i . '_' . $j} == '') {
                            $isQuestionFilled = false;
                            if ($firstError == 0) {
                                $firstError = $i . '_' . $j;
                            }
                        }
                        $j++;
                    }
                    if (!$isQuestionFilled) {
                        $hasError = true;
                        $errorMessage .= $this->t("Question " . $i . " is not filled<br>");
                    }
                }
            }
            $i++;

        }
        if ($hasError) {
            $form_state->setErrorByName(
                'answer' . $firstError, t($errorMessage)
            );
        }
    }


     //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();
        $record_user_id = $authen->getUserId();

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }

        $RespondentEntry = array(
            'submitted' => 'Y',
            'username' => $record_user_id,
            'create_datetime' => date('Y-m-d H:i:s'),
            'modify_datetime' => date('Y-m-d H:i:s'),
            'survey_id' => $survey_id,
        );
        $query = \Drupal::database()->insert('kicp_survey_respondent')
        ->fields($RespondentEntry);
        $Respondent_id = $query->execute();

        $questionInfo = SurveyDatatable::getSurveyQuestionAll($survey_id);

        $i=1;
        foreach ($questionInfo as $record) {

            foreach ($record as $key => $value) {
                $$key = $value;
            }


            if (($type_id == 1) || ($type_id == 2) ) {

                $QuestionEntry = array(
                    'question_id' => $record['id'],
                    'response' => ${'answer' . $i},
                    'create_datetime' => date('Y-m-d H:i:s'),
                    'modify_datetime' => date('Y-m-d H:i:s'),
                    'survey_id' => $survey_id,
                    'respondent_id' => $Respondent_id,
                );
                $return = SurveyDatatable::insertResponse($QuestionEntry);

            } elseif ($type_id == 3) {
                $QuestionEntry = array(
                    'question_id' => $record['id'],
                    'response' => ${'answer' . $i}['value'],
                    'create_datetime' => date('Y-m-d H:i:s'),
                    'modify_datetime' => date('Y-m-d H:i:s'),
                    'survey_id' => $survey_id,
                    'respondent_id' => $Respondent_id,
                );
                $return = SurveyDatatable::insertResponse($QuestionEntry);
            } elseif ($type_id == 4) {
                if (${'other' . $i} == '') {
                    $QuestionEntry = array(
                      'question_id' => $record['id'],
                      'response' => ${'answer' . $i},
                      'create_datetime' => date('Y-m-d H:i:s'),
                      'modify_datetime' => date('Y-m-d H:i:s'),
                      'survey_id' => $survey_id,
                      'respondent_id' => $Respondent_id,
                    );
                }
                else {
                    $QuestionEntry = array(
                      'question_id' => $record['id'],
                      'response' => ${'other' . $i},
                      'create_datetime' => date('Y-m-d H:i:s'),
                      'modify_datetime' => date('Y-m-d H:i:s'),
                      'survey_id' => $survey_id,
                      'respondent_id' => $Respondent_id,
                    );
                }
                $return = SurveyDatatable::insertResponse($QuestionEntry);

            } elseif ($type_id == 5) {

                $surveyInfoChoice = SurveyDatatable::getSurveyChoice($record['id']);
                foreach ($surveyInfoChoice as $recordcoice) {
                    if (${'answer' . $i . '_' . $j} == 1) {
                        $QuestionEntry = array(
                          'question_id' => $record['id'],
                          'response' => $recordcoice['id'],
                          'create_datetime' => date('Y-m-d H:i:s'),
                          'modify_datetime' => date('Y-m-d H:i:s'),
                          'survey_id' => $survey_id,
                          'respondent_id' => $Respondent_id,
                        );
                        $return = SurveyDatatable::insertResponse($QuestionEntry);
                            }
                    $j = $j + 1;
                }
                if (${'other' . $i} != '') {
                    $QuestionEntry = array(
                      'question_id' => $record->id,
                      'response' => ${'other' . $i},
                      'create_datetime' => date('Y-m-d H:i:s'),
                      'modify_datetime' => date('Y-m-d H:i:s'),
                      'survey_id' => $survey_id,
                      'respondent_id' => $Respondent_id,
                    );
                    $return = SurveyDatatable::insertResponse($QuestionEntry);
                }
            }  elseif ($type_id == 6) {

                $surveyInfoChoiceSumbit = SurveyDatatable::getSurveyChoice($record['id']);
                $j=1;
                foreach ($surveyInfoChoiceSumbit as $recordcoice) {
                    $QuestionEntry = array(
                        'question_id' => $record['id'],
                        'response' => ${'hiddenchoice' . $i . '_' . $j},
                        'rank' => ${'answer' . $i . '_' . $j},
                        'create_datetime' => date('Y-m-d H:i:s'),
                        'modify_datetime' => date('Y-m-d H:i:s'),
                        'survey_id' => $survey_id,
                        'respondent_id' => $Respondent_id,
                    );
                    $return = SurveyDatatable::insertResponseRank($QuestionEntry);
                    $j++;
                    if (${'other' . $i} != '') {
                        $QuestionEntry = array(
                          'question_id' => $record->id,
                          'response' => ${'other' . $i},
                          'rank' => ${'other' . $i},
                          'create_datetime' => date('Y-m-d H:i:s'),
                          'modify_datetime' => date('Y-m-d H:i:s'),
                          'survey_id' => $survey_id,
                          'respondent_id' => $Respondent_id,
                        );
                        $return = SurveyDatatable::insertResponse($QuestionEntry);
                    }

                }

            }
            $i++;
        }

        $url = new Url('survey.survey_content');
        $form_state->setRedirectUrl($url);

        $messenger = \Drupal::messenger(); 
        $messenger->addMessage( t('Thanks you for sumbiting the answers.'));  




    }
    


}


