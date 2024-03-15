<?php

/**
 * @file
 */

namespace Drupal\activities\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal;
use Drupal\Component\Utility\UrlHelper;
use Drupal\common\CommonUtil;
use Drupal\activities\Common\ActivitiesDatatable;
use Drupal\activities\Controller\ActivitiesController;


class ActivityPhotoChange extends FormBase {

    public function __construct() {
        $this->module = 'activities';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'km_activities_photo_change_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $evt_photo_id="" ) {


        // display the form
        
        $form['#attributes']['enctype'] = 'multipart/form-data';
        
        $photoInfo = ActivitiesDatatable::getEventPhotoInfo($evt_photo_id);
        
        
        $form['evt_photo_url'] = array(
            '#title' => t('Photo Name'),
            '#type' => 'textfield',
            '#default_value' => $photoInfo->evt_photo_url,
            '#attributes' => array('disabled'=>'disabled'),
            '#prefix' => '<div><img src="../system/files/activities/photo/'.str_pad($photoInfo->evt_id, 6, "0", STR_PAD_LEFT).'/'.$photoInfo->evt_photo_url.'" border="0" width="200"></div>',
        );
        
        $form['evt_photo_description'] = array(
            '#title' => t('Description'),
            '#type' => 'textfield',
            '#default_value' => $photoInfo->evt_photo_description,
            '#size' => 40,
            '#maxlength' => 250,
        );
        
        $form['evt_photo_id'] = array(
            '#type' => 'hidden',
            '#default_value' => $evt_photo_id,
        );
        
        $form['evt_id'] = array(
            '#type' => 'hidden',
            '#default_value' => $photoInfo->evt_id,
        );


        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'),
        );

        $form['actions']['cancel'] = array(
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#prefix' => '&nbsp;',
            '#attributes' => array('onClick' => 'window.open(\'../activities_photo/' . $photoInfo->evt_id . '\', \'_self\'); return false;'),
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
            $database = \Drupal::database();
            $query = $database->update('kicp_km_event_photo')->fields([
              'evt_photo_description' => $evt_photo_description, 
            ])
            ->condition('evt_photo_id', $evt_photo_id)
            ->execute();
          
            $url = Url::fromUri('base:/activities_photo/'.$evt_id);
            $form_state->setRedirectUrl($url);
    
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Photo has been updated.'));

        }
        catch (Exception $e) {
            \Drupal::messenger()->addStatus(
                t('Unable to update photo at this time due to datbase error. Please try again. '.$e )
                );
        }

    }

}
