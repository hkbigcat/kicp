<?php

/**
 * @file
 */

namespace Drupal\ppcactivities\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal;
use Drupal\Component\Utility\UrlHelper;
use Drupal\common\CommonUtil;
use Drupal\ppcactivities\Common\PPCActivitiesDatatable;
use Drupal\Core\Utility\Error;

class PPCActivityTypeChange extends FormBase {

  public $module; 

    public function __construct() {
        $this->module = 'ppcactivities';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'ppc_km_activities_event_type_change_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $evt_type_id="") {
        
        $activityTypeInfoAry = PPCActivitiesDatatable::getActivityTypeInfo($evt_type_id);
        if (!$activityTypeInfoAry) {
          $messenger = \Drupal::messenger(); 
          $messenger->addWarning( t('This activity type is not available'));           
          return $form;            
        }   
        
        // display the form

        $form['evt_type_name'] = array(
          '#title' => t('Type Name'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 255,
          '#default_value' => $activityTypeInfoAry['evt_type_name'],
          '#required' => TRUE,
        );

        $form['evt_description'] = array(
          '#title' => t('Description'),
          '#type' => 'textarea',
          '#rows' => 10,
          '#cols' => 30,
          '#default_value' => $activityTypeInfoAry['description'],
          '#required' => TRUE,
        );
        
        $form['display_order'] = array(
          '#title' => t('Display Order'),
          '#type' => 'textfield',
          '#size' => 10,
          '#maxlength' => 4,
          '#default_value' => $activityTypeInfoAry['display_order'],
          '#required' => TRUE,
        );

        $form['evt_type_id'] = array(
          '#type' => 'hidden',
          '#default_value' => $evt_type_id,
        );
        
       
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Save'),
        );

        $form['actions']['cancel'] = array(
          '#type' => 'button',
          '#value' => t('Cancel'),
          '#prefix' => '&nbsp;',
          '#attributes' => array('onClick' => 'window.open(\'ppcactivities_admin_type\', \'_self\'); return false;'),
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


        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }
        
            
        $typeEntry = array(
            'evt_type_name' => $evt_type_name,
            'description' => $evt_description,
            'display_order' => $display_order,
        );

        $database = \Drupal::database();
        $transaction = $database->startTransaction();            
        try {            
            $query = \Drupal::database()->update('kicp_ppc_event_type')
            ->fields(  $typeEntry )
            ->condition('evt_type_id', $evt_type_id)
            ->execute();

            \Drupal::logger('ppcactivities')->info('PPC Activities Type updated id: %id, Type name: %evt_type_name.',   
            array(
                '%id' =>  $evt_type_id,
                '%evt_type_name' => $evt_type_name,
            ));               

            $url = Url::fromUri('base://ppcactivities_admin_type');
            $form_state->setRedirectUrl($url);
    
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Event Type has been updated.'));            
        }
        catch (Exception $e) {
          $variables = Error::decodeException($e);
          \Drupal::messenger()->addError(
              t('PPC Activities Type is not  updated' )
              );
          \Drupal::logger('ppcactivities')->error('PPC Activities Type  is not  updated: '.$variables);                    
          $transaction->rollBack();
        }
        unset($transaction);   
    }

}
