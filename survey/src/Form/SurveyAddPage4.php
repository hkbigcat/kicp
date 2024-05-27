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
use Drupal\Core\Utility\Error;

class SurveyAddPage4 extends FormBase {

    public function __construct() {
        $this->module = 'survey';
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->is_authen = $authen->isAuthenticated;
        $this->my_user_id = $authen->getUserId();             
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'survey_add_form4';
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

        $output = "";
        $survey_id = (isset($_SESSION['survey_id']) && $_SESSION['survey_id'] != "") ? $_SESSION['survey_id'] : "";

        /*
        $form['intro'] = array(
          '#markup' => $output,
        );
        */

        // display the form
        $form['#attributes'] = array('enctype' => 'multipart/form-data');

        //Page 4
        $access_control = 'Access Control';
        $output .= '<table><td style="background: rgba(0,0,0,0.063);"><div style="width:15%;float:left;display:block;text-align:center;"><div class="entry_title">If no Access Control is configured, all users can participate in the Survey</a></div><!--<a href="access_control?this_module=survey&record_id=' . $survey_id . '">' . $access_control . '</a>--><a href="#add-record" onClick="getAddGroupMemberUI(\'survey\',' . $survey_id . ');">' . $access_control . '</a></div></td></table><p>';
        $output .= AccessControl::getAccessControlModalElement();
        $form['accessControl'] = array(
          '#markup' => t($output),
        );

        $form['btBack'] = array(
          '#type' => 'button',
          '#value' => t('Back'),
          '#attributes' => array('onClick' => 'return false;$(#div_step1).show()'),
        );

        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Complete'),
        );

        $form['actions']['cancel'] = array(
          '#type' => 'button',
          '#value' => t('Cancel'),
          '#prefix' => '&nbsp;',
          '#attributes' => array('onClick' => 'history.go(-1); return false;'),
          '#limit_validation_errors' => array(),
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

        $database = \Drupal::database();
        $transaction = $database->startTransaction(); 

        try {
          $query = $database->update('kicp_survey')->fields([
            'is_completed' => 1,
            'modify_datetime' => date('Y-m-d H:i:s'),
            'modified_by' => $this->my_user_id,
          ])
          ->condition('survey_id', $survey_id);
          $return = $query->execute();    
          \Drupal::logger('survey')->info('final updated ID: '.$survey_id);
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Survey is created / updated.'));          
        } 
        catch (Exception $e) {
          $variables = Error::decodeException($e);
          \Drupal::messenger()->addError(
            t('survey is not udpated. ' )
            );  
          \Drupal::logger('survey')->error('survey is not updated: ' . $variables);
          $transaction->rollBack(); 
        }
        unset($transaction);
        $url = new Url('survey.survey_content');
        $form_state->setRedirectUrl($url);
    }

}
