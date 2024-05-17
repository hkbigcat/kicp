<?php

/**
 * @file
 */

namespace Drupal\profile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal;
use Drupal\Component\Utility\UrlHelper;
use Drupal\common\CommonUtil;
use Drupal\profile\Common\ProfileDatatable;
use Drupal\Core\Utility\Error;

class ProfileChangeGroup extends FormBase {

    public function __construct() {
        $this->module = 'profile';
        $this->access_right_alert = "You do not have privilege on profile admin page.";
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'profile_change_group_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state,$type="", $group_id="") {

        $type = $type=='P'?'P':'B';
        
        if($type == "P") {
            $field_text = "Personal Public Group Name";
            $groupInfo = ProfileDatatable::getPublicGroupByGroupId($group_id);
        } else  {
            $field_text = "Personal Group Name";
            $groupInfo = ProfileDatatable::getBuddyGroupByGroupId($group_id);
        } 
        
        $form['group_name'] = array(
          '#title' => t($field_text),
          '#type' => 'textfield',
          '#maxlength' => 255,
          '#required' => TRUE,
          '#default_value' => $groupInfo->group_name,
        );
        
        $form['type'] = array(
          '#type' => 'hidden',
          '#value' => $type,
        );
        
        $form['group_id'] = array(
            '#type' => 'hidden',
            '#value' => $group_id,
         );
  
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Save'),
        );
        
        $form['actions']['cancel'] = array(
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#prefix' => '&nbsp;',
            '#attributes' => array('onClick' => 'window.open(\'/profile_group?type='.$type . '\', \'_self\'); return false;'),
            '#limit_validation_errors' => array(),
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

        if (!isset($group_name) or $group_name == '') {
            $form_state->setErrorByName(
                'group_name', $this->t("Group Name is blank")
            );
        }
        
        if (!isset($type) or $type == '') {
            $form_state->setErrorByName(
                'type', $this->t("Group Type is blank")
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

        $url = new Url(
            'profile.profile_group', array('type' => $type)
        );
        $form_state->setRedirectUrl($url);

        $database = \Drupal::database();
        $transaction =  $database->startTransaction();         
        try {
            
            if($type == "B") {
                $entry = array(
                    'buddy_group_name' => $group_name,
                  );
                  $query = \Drupal::database()->update('kicp_buddy_group')
                  ->fields($entry)
                  ->condition('buddy_group_id', $group_id);
      
            } else {
                $entry = array(
                    'pub_group_name' => $group_name,
                  );
                  $query = \Drupal::database()->update('kicp_public_group')
                  ->fields($entry)
                  ->condition('pub_group_id', $group_id);
            }
            $return = $query->execute();
            \Drupal::logger('profile')->info('updated group id: %id, type: %type',   
            array(
                '%id' => $group_id,
                '%type' => $type=='B'?'Buddy':'Public',
            ));   
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Group has been udpated.'));

        } catch (Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::logger('profile')->error('gorup is not updated '  . $variables);   
            \Drupal::messenger()->addError(
                t('Unable to update group at this time due to datbase error. Please try again.')
              );             
            $transaction->rollback();              
        }
        unset($transaction);
    }

}
