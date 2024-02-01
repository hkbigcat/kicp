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

class ProfileAddGroup extends FormBase {

    public function __construct() {
        $this->module = 'profile';
        $this->access_right_alert = "You do not have privilege on profile admin page.";
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'profile_add_group_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();
        $author = CommonUtil::getSysValue('AuthorClass');

        
        if($_REQUEST['type'] == "B") {
            $field_text = "Personal Group Name";
            $groupInfo = ProfileDatatable::getBuddyGroupByGroupId($_REQUEST['group_id']);
        } else  {
            $field_text = "Personal Public Group Name";
        } 
        
        $form['group_name'] = array(
          '#title' => t($field_text),
          '#type' => 'textfield',
          '#maxlength' => 255,
          '#required' => TRUE,
        );
        
        $form['type'] = array(
          '#type' => 'hidden',
          '#value' => $_REQUEST['type'],
        );
        
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Save'),
        );
        
        $form['actions']['cancel'] = array(
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#prefix' => '&nbsp;',
            '#attributes' => array('onClick' => 'window.open(\'profile_group?type='.$_REQUEST['type'] . '\', \'_self\'); return false;'),
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

        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }

        $url = new Url(
            'profile.profile_group', array('type' => $type)
        );
        $form_state->setRedirectUrl($url);

        try {
            
            if($type == "B") {
                $entry = array(
                    'user_id' => $authen->getUserId(),
                    'buddy_group_name' => $group_name,
                  );
                  $query = \Drupal::database()->insert('kicp_buddy_group')
                  ->fields($entry);
      
            } else {
                $entry = array(
                    'pub_group_owner' => $authen->getUserId(),
                    'pub_group_name' => $group_name,
                    'bool_trusted'  => 0,
                    'source' => 'U',
                  );
                  $query = \Drupal::database()->insert('kicp_public_group')
                  ->fields($entry);
                  
            }
            

            $return = $query->execute();

            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Group has been added: '));

        } catch (Exception $e) {
            
            \Drupal::messenger()->addError(
                t('Unable to add group at this time due to datbase error. Please try again.')
              );             
        }
    }

}
