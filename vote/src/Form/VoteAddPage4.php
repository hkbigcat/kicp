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
use Drupal\common\TagList;
use Drupal\common\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\vote\Common\VoteDatatable;
use Drupal\file\FileInterface;
use Drupal\file\Entity\File;
use Drupal\common\AccessControl;
use Drupal\Core\Utility\Error;

class VoteAddPage4 extends FormBase {

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

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'vote_add_form4';
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
        $vote_id = (isset($_SESSION['vote_id']) && $_SESSION['vote_id'] != "") ? $_SESSION['vote_id'] : "";

        /*
        $form['intro'] = array(
          '#markup' => $output,
        );
        */

        // display the form
        $form['#attributes'] = array('enctype' => 'multipart/form-data');

        //Page 4
        $access_control = 'Access Control';
        $output .= '<table><td style="background: rgba(0,0,0,0.063);"><div style="width:15%;float:left;display:block;text-align:center;"><div class="entry_title">If no Access Control is configured, all users can participate in the Vote</a></div><!--<a href="access_control?this_module=vote&record_id=' . $vote_id . '">' . $access_control . '</a>--><a href="#add-record" onClick="getAddGroupMemberUI(\'vote\',' . $vote_id . ');">' . $access_control . '</a></div></td></table><p>';
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
        $vote_id = (isset($_SESSION['vote_id']) && $_SESSION['vote_id'] != "") ? $_SESSION['vote_id'] : "";

        $database = \Drupal::database();
        $transaction = $database->startTransaction(); 
        try {
          $query = $database->update('kicp_vote')->fields([
            'is_completed' => 1,
            'modify_datetime' => date('Y-m-d H:i:s'),
            'modified_by' => $this->my_user_id,
          ])
          ->condition('vote_id', $vote_id);
          $return = $query->execute();    
          \Drupal::logger('vote')->info('final updated ID: '.$vote_id);
        } 
      catch (Exception $e) {
        $variables = Error::decodeException($e);
        \Drupal::messenger()->addError(
          t('vote is not udpated. ' )
          );  
        \Drupal::logger('vote')->error('vote is not updated: ' . $variables);
        $transaction->rollBack(); 
      }        
      unset($transaction);
      $url = new Url('vote.vote_content');
      $form_state->setRedirectUrl($url);
    }

}
