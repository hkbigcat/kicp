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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\file\Entity;


class PPCActivityCOPCategoryChange extends FormBase {

    public function __construct() {
      $this->module = 'ppcactivities';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'ppc_km_activities_cop_category_change_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $cop_id="") {

        $copInfo = PPCActivitiesDatatable::getCOPInfo($cop_id);
        
        // display the form

        $form['cop_name'] = array(
          '#title' => t('Category Name'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 255,
          '#default_value' => $copInfo['cop_name'],
          '#required' => TRUE,
        );

        $form['description'] = array(
          '#title' => t('Description'),
          '#type' => 'textarea',
          '#rows' => 10,
          '#cols' => 30,
          '#default_value' => $copInfo['cop_info'],
          '#required' => TRUE,
        );
        
        $form['display_order'] = array(
          '#title' => t('Display Order'),
          '#type' => 'textfield',
          '#size' => 10,
          '#maxlength' => 4,
          '#default_value' => $copInfo['display_order'],
          '#required' => TRUE,
        );

        $form['cop_id'] = array(
          '#type' => 'hidden',
          '#value' => $cop_id,
        );

        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Save'),
        );

        $form['actions']['cancel'] = array(
          '#type' => 'button',
          '#value' => t('Cancel'),
          '#prefix' => '&nbsp;',
          '#attributes' => array('onClick' => 'window.open(\'activities_admin_category\', \'_self\'); return false;'),
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
                        
        $categoryEntry = array(
          'cop_name' => $cop_name,
          'cop_info' => $description,
          'display_order' => $display_order,
        );

        $database = \Drupal::database();
        $transaction = $database->startTransaction();    
    
        try {
            $query = \Drupal::database()->update('kicp_ppc_cop')
            ->fields( $categoryEntry)
            ->condition('cop_id', $cop_id)
            ->execute();   

            \Drupal::logger('ppcactivities')->info('PPC Activities Category updated id: %id, Category name: %cop_name.',   
            array(
                '%id' =>  $cop_id,
                '%cop_name' => $cop_name,
            ));        

            $url = Url::fromUri('base:/ppcactivities_admin_category');
            $form_state->setRedirectUrl($url);
    
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('COP Category is updated.'));

        }
        catch (Exception $e) {
          $variables = Error::decodeException($e);
          \Drupal::messenger()->addError(
              t('PPC Activities Category is not updated' )
              );
          \Drupal::logger('ppcactivities')->error('PPC Activities Category is not updated: '.$variables);                    
          $transaction->rollBack();
        }
        unset($transaction);   
    }

}
