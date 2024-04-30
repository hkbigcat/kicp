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
use Drupal\common\AccessControl;


class SurveyAddPage3 extends FormBase {

    public function __construct() {
        $this->module = 'survey';
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->my_user_id = $authen->getUserId();     

    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'survey_add_form3';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        // display the form


        $form['#attributes'] = array('enctype' => 'multipart/form-data');

        //LOAD DATA
        $survey_id = (isset($_SESSION['survey_id']) && $_SESSION['survey_id'] != "") ? $_SESSION['survey_id'] : "";

        $questionInfo = SurveyDatatable::getSurveyQuestionAll($survey_id);

        $positionarry=array();
        $k = 1;
        foreach ($questionInfo as $result) {
            $positionarry[$k] = $result['position'];
            $k++;
        }

        $introPage3 = 'Change the order that questions are presented by choosing the desired position from the list Order</p> <span style="font-weight: bold">Question</span>';
        $form['introPage3'] = array(
          '#markup' => t($introPage3),
        );
        $i = 1;


        foreach ($questionInfo as $record) {

            foreach ($record as $key => $value) {
                $$key = $value;
            }
            $form['QuestionName' . $i] = array(
              '#markup' => $record['name'],
            );
            $form['order' . $i] = array(
              '#title' => $record['content'],
              '#type' => 'select',
              '#options' => $positionarry,
              '#attributes' => array('style' => 'display:inline-block;float:left;', 'onFocus' => 'setPreviousSequence(this)', 'onChange' => 'changeSequence(this.id)', 'id' => 'order' . $i),
              '#prefix' => '<div class="div_inline_column' . $i . '">',
              '#default_value' => $position,
            );

            $form['hiddenorder' . $i] = array(
              '#type' => 'hidden',
              '#value' => $position,
              '#attributes' => array('id' => 'hiddenorder' . $i),
            );



            $form['remove' . $i] = array(
              '#type' => 'submit',
              '#value' => t('Remove This Question'),
              '#attributes' => array('style' => 'display:inline-block;float:left; margin-left:20px; ', 'onClick' => 'jQuery("#hiddenremove").val(' . $id . ');'),
            );

            $form['questionend' . $i] = array(
              '#suffix' => '</div><br>',
            );
            $form['questionsepartor' . $i] = array(
              '#suffix' => '<div class="greyBorderBottom"></div>',
            );
            $i++;
        }
        $form['hiddenseq'] = array(
          '#type' => 'hidden',
          '#attributes' => array('id' => 'hiddenseq'),
        );

        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Save'),
        );

        $form['actions']['cancel'] = array(
          '#type' => 'button',
          '#value' => t('Cancel'),
          '#prefix' => '&nbsp;',
          '#attributes' => array('onClick' => 'history.go(-1); return false;'),
          '#limit_validation_errors' => array(),
        );
        $form['hiddenremove'] = array(
          '#type' => 'hidden',
          '#default_value' => 0,
          '#attributes' => array('id' => 'hiddenremove'),
        );
        return $form;
    }

    //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $survey_id = (isset($_SESSION['survey_id']) && $_SESSION['survey_id'] != "") ? $_SESSION['survey_id'] : "";

        $questionInfo = SurveyDatatable::getSurveyQuestionAll($survey_id);
        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }


        try {

            $i = 1;

            if ($hiddenremove == 0) {
                foreach ($questionInfo as $record) {

                    foreach ($record as $key => $value) {
                        $$key = $value;
                    }
                    $entry = array(
                      'position' => ${'order' . $i},
                      'modify_datetime' => date('Y-m-d H:i:s'),
                      'modified_by' => $this->my_user_id,
                    );

                    $database = \Drupal::database();
                    $query = $database->update('kicp_survey_question')->fields( $entry)
                    ->condition('id', $record['id']);
                    $return = $query->execute();

                    $i++;
                }
                $url = new Url('survey.survey_add_page4');
                $form_state->setRedirectUrl($url);
            } else {
                $entry = array(
                'modify_datetime' => date('Y-m-d H:i:s'),
                'modified_by' => $this->my_user_id,
                  'deleted'=> 'Y',
                   );

                $database = \Drupal::database();
                $query = $database->update('kicp_survey_question')->fields( $entry)
                ->condition('id', $hiddenremove);
                $return = $query->execute();

                 $url = new Url('survey.survey_add_page3');
                $form_state->setRedirectUrl($url);
            }
        } catch (Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
              t('Squestion is not udpated. ' )
              );  
            \Drupal::logger('survey')->error('question is not updated: ' . $variables);

        }
    }

}
