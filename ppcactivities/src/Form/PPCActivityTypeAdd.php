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

class PPCActivityTypeAdd extends FormBase {

    public function __construct() {
        $this->module = 'ppcactivities';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'ppc_km_activities_event_type_add_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        // display the form

        $form['evt_type_name'] = array(
          '#title' => t('Type Name'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 255,
          '#required' => TRUE,
        );

        $form['evt_description'] = array(
          '#title' => t('Description'),
          '#type' => 'textarea',
          '#rows' => 10,
          '#cols' => 30,
          '#required' => TRUE,
        );
        
        $currentMaxDisplayOrder = PPCActivitiesDatatable::getActivityTypeMaxDisplayOrder();
        $newDisplayOrder = $currentMaxDisplayOrder + 1;
        
        $form['display_order'] = array(
          '#title' => t('Display Order'),
          '#type' => 'textfield',
          '#size' => 10,
          '#maxlength' => 4,
          '#default_value' => $newDisplayOrder,
          '#required' => TRUE,
        );

        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Save'),
        );

        $form['actions']['cancel'] = array(
          '#type' => 'button',
          '#value' => t('Cancel'),
          '#prefix' => '&nbsp;',
          '#attributes' => array('onClick' => 'window.open(\'activities_admin\', \'_self\'); return false;'),
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
        
        try {
            
            $categoryEntry = array(
                'evt_type_name' => $evt_type_name,
                'description' => $evt_description,
                'display_order' => $display_order,
            );


            $query = \Drupal::database()->insert('kicp_ppc_event_type')
            ->fields( $categoryEntry);
            $evt_type_id = $query->execute();


            if ($evt_type_id) {

                $url = Url::fromUri('base:/ppcactivities_admin_type');
                $form_state->setRedirectUrl($url);
        
                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('PPC Activity Type has been added.'));
                
            }
            else {
                \Drupal::messenger()->addError(
                    t('PPC Activity Type is not created. ' )
                    );

            }
        }
        catch (Exception $e) {
            \Drupal::messenger()->addError(
                t('PPC Activity Type is not created. ' )
                );
    }
    }

}
