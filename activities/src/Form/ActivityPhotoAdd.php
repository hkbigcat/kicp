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
use Drupal\Core\Database\Database;
use Drupal\file\FileInterface;
use Drupal\file\Entity;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Utility\Error;

class ActivityPhotoAdd extends FormBase {

    public $module;
    
    public function __construct() {
        $this->module = 'activities';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'km_activities_photo_add_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $evt_id="") {


        // display the form
        $form['#attributes']['enctype'] = 'multipart/form-data';
       

        $validators = array(
            'file_validate_extensions' => array(CommonUtil::getSysValue('default_file_upload_extensions')),
            'file_validate_size' => array(CommonUtil::getSysValue('default_file_upload_size_limit') * 1024 * 1024),
          );        

        $this_evt_id = str_pad($evt_id, 6, "0", STR_PAD_LEFT);

        $form['files'] = [
            '#type' => 'managed_file',
            //'#type' => 'file',
            '#title' => $this->t('Upload Multiple Files'),
            '#description' => 'Press \'Ctrl\' to select multiple files',
            '#required' => FALSE,
            '#upload_location' => 'private://activities/photo',
            '#multiple' => TRUE,
            '#upload_validators' => $validators
        ];         


        $form['evt_id'] = array(
            '#type' => 'hidden',
            '#value' => $evt_id,
        );


        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Upload'),
        );

        $form['actions']['cancel'] = array(
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#prefix' => '&nbsp;',
            '#attributes' => array('onClick' => 'window.open(\'../activities_photo/' . $evt_id . '\', \'_self\'); return false;'),
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
        
        if (empty($files)) {
            $form_state->setErrorByName(
                'file', $this->t("Photo is empty")
            );
        }
    }

    //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {


        $photo_uploaded = "";

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }


        //**********************************************************
        $hasPhoto = (empty($files))?0:1; 
        $ActivitiesPhotoUri = 'private://activities/photo';
        $file_system = \Drupal::service('file_system');
        if ($hasPhoto) {

            $this_evt_id = str_pad($evt_id, 6, "0", STR_PAD_LEFT);
            
            $createDir = $ActivitiesPhotoUri . '/' . $this_evt_id;
            if (!is_dir($file_system->realpath($createDir ))) {
                // Prepare the directory with proper permissions.
                if (!$file_system->prepareDirectory( $createDir , FileSystemInterface::CREATE_DIRECTORY)) {
                throw new \Exception('Could not create the activities photo - entry id directory.');
                }
            }
            $database = \Drupal::database();
            $transaction = $database->startTransaction();                  
            try {            
                foreach ($files as $file1) {
                    if ($file1) {
                        $NewFile = File::load($file1);
                        $photo_name = $NewFile->getFilename();
                        $source = $file_system->realpath($ActivitiesPhotoUri . '/'. $photo_name);
                        $destination = $file_system->realpath($createDir . '/' . $photo_name);
                        $newFileName = $file_system->move($source, $destination, FileSystemInterface::EXISTS_REPLACE);
                        if (!$newFileName) {
                            throw new \Exception('Could not move the generic placeholder image to the destination directory.');
                        } else {
                            $NewFile->setFileUri($createDir . '/' . $photo_name);
                            $NewFile->uid =$evt_id;
                            $NewFile->setPermanent();
                            $NewFile->save();
                        }
                    }
                
                    $photoEntry = array('evt_id' => $evt_id, 'evt_photo_url' => $photo_name, 'evt_photo_description' => $photo_name);
                    $query = $database->insert('kicp_km_event_photo')
                    ->fields($photoEntry);
                    $entry_id = $query->execute();

                    $photo_uploaded .= "$photo_name, ";
                }

                \Drupal::logger('activities')->info('Event ID: '.$evt_id.' Event photos uploaded: '.$photo_uploaded);
                    
            } catch (Exception $e) {
                    $variables = Error::decodeException($e);
                    \Drupal::messenger()->addError(
                        t('Activity Event Photo  is not uploaded.' )
                        );
                    \Drupal::logger('activities')->error('Activity Event Photo is not uploaded.: '.$variables);                    
                    $transaction->rollBack();   
            }
            unset($transaction); 
            $url = Url::fromUserInput('/activities_photo/'.$evt_id);
            $form_state->setRedirectUrl($url);            
               
            $messenger = \Drupal::messenger(); 
            if($photo_uploaded != "") {
                $messenger->addMessage( t('Photos: '. substr($photo_uploaded,0,-2) . ' uploaded'));                
            }
            
            /////////// Handle attachment [End] /////////////

        } else {
            $messenger->addMessage( t('No photo to upload'));                
        }
        
    }

}
