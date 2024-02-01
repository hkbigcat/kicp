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

class ProfileChangeMember extends FormBase {

    public function __construct() {
        $this->module = 'profile';
        $this->access_right_alert = "You do not have privilege on profile admin page.";
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'profile_change_member_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state,$type="", $group_id="", $user_id="") {

        $output = NULL;
        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();
        $author = CommonUtil::getSysValue('AuthorClass');
          
        $userInfo = ProfileDatatable:: getMembersGroupId($type, $group_id,  $user_id);
        
        $form['user_name'] = array(
          '#title' => t('User Name'),
          '#type' => 'textfield',
          '#maxlength' => 255,
          '#required' => TRUE,
          '#default_value' => $userInfo->user_name,
        );
        
        $form['type'] = array(
          '#type' => 'hidden',
          '#value' => $type,
        );
        
        $form['group_id'] = array(
          '#type' => 'hidden',
          '#value' =>  $group_id,
        );
        
        $form['user_id'] = array(
          '#type' => 'hidden',
          '#value' =>  $user_id,
        );
        
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Save'),
        );
        
        $form['actions']['cancel'] = array(
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#prefix' => '&nbsp;',
            '#attributes' => array('onClick' => 'window.open(\'profile_group_member?type='.$_REQUEST['type'] . '&group_id='.$_REQUEST['group_id'].'\', \'_self\'); return false;'),
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

        if (!isset($user_name) or $user_name == '') {
            $form_state->setErrorByName(
                'user_name', $this->t("User name is blank")
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

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }
        $url = new Url(
            'profile.profile_group_member', array('type' => $type, 'group_id' => $group_id)
        );
        $form_state->setRedirectUrl($url);

        try {
            
            if($type == "B") {
                $entry = array(
                    'buddy_user_name' => $user_name,
                  );
                $query = \Drupal::database()->update('kicp_buddy_user_list')
                ->fields($entry)
                ->condition('buddy_group_id', $group_id)
                ->condition('buddy_user_id', $user_id);
            } else if ($type == "P") {
                $entry = array(
                    'pub_user_name' => $user_name,
                  );
                  $query = \Drupal::database()->update('kicp_public_user_list')
                  ->fields($entry)
                  ->condition('pub_group_id', $group_id)
                  ->condition('pub_user_id', $user_id);                  
            }
            $return = $query->execute();

            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Member name has been udpated.'));
            
        } catch (Exception $e) {
            
            \Drupal::messenger()->addError(
                t('Unable to update member name at this time due to datbase error. Please try again.')
              );             
        }
    }

}
