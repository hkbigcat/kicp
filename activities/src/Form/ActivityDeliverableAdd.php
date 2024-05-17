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
use Drupal\common\RatingData;
use Drupal\common\TagList;
use Drupal\common\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\activities\Common\ActivitiesDatatable;
use Drupal\activities\Controller\ActivitiesController;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;

class ActivityDeliverableAdd extends FormBase {

    public function __construct() {
        $this->module = 'activities';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'km_activities_deliverable_add_form';
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

        $form['files'] = [
            '#type' => 'managed_file',
            //'#type' => 'file',
            '#title' => $this->t('Upload Multiple Deliverables'),
            '#description' => 'Press \'Ctrl\' to select multiple files',
            '#required' => FALSE,
            '#upload_location' => 'private://activities/deliverable',
            '#multiple' => TRUE,
            '#upload_validators' => $validators
        ];    


        $TagList = new TagList();

        $form['tags'] = array(
            '#title' => t('Tags'),
            '#type' => 'textarea',
            '#rows' => 2,
            '#description' => 'Use semi-colon (;) as separator',
        );
        
        
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
            '#attributes' => array('onClick' => 'window.open(\'../activities_deliverable/' . $evt_id . '\', \'_self\'); return false;'),
            '#limit_validation_errors' => array(),
        );
        
        $taglist = $TagList->getListCopTagForModule();
        $form['t3'] = array(
            '#title' => t('COP Tags'),
            '#type' => 'details',
            '#open' => true,
            '#description' =>  $taglist,
            '#attributes' => array('style'=>'border: 1px solid #7A7A7A;background: #FCFCE6; margin-top:40px;'),
        );

          
        $taglist = $TagList->getList($this->module);
        $form['t1'] = array(
            '#title' => t('KM Activities Tags'),
            '#type' => 'details',
            '#open' => true,
            '#description' =>  $taglist,
        );

        $taglist = $TagList->getList('ALL');
        $form['t2'] = array(
            '#title' => t('All Tags'),
            '#type' => 'details',
            '#open' => false,
            '#description' => $taglist,
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
                'file', $this->t("Deliverable is empty")
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


        //**********************************************************
        $hasDeliverable = (empty($files))?0:1; 
        $ActivitiesDeliverableUri = 'private://activities/deliverable';
        $file_system = \Drupal::service('file_system');
        $deliverable_uploaded = "";
        $deliverable_not_uploaded = "";

         if ( $hasDeliverable) {

            $this_evt_id = str_pad($evt_id, 6, "0", STR_PAD_LEFT);

            $createDir = $ActivitiesDeliverableUri . '/' . $this_evt_id;
            if (!is_dir($file_system->realpath($createDir ))) {
                // Prepare the directory with proper permissions.
                if (!$file_system->prepareDirectory( $createDir , FileSystemInterface::CREATE_DIRECTORY)) {
                throw new \Exception('Could not create the activities deliverable - entry id directory.');
                }
            }

            $database = \Drupal::database();
            $transaction = $database->startTransaction();    
            try {
                foreach ($files as $file1) {

                    if ($file1) {
                        $NewFile = File::load($file1);
                        $deliverable_name = $NewFile->getFilename();
                        $source = $file_system->realpath($ActivitiesDeliverableUri . '/'. $deliverable_name);
                        $destination = $file_system->realpath($createDir . '/' . $deliverable_name);
                        $newFileName = $file_system->move($source, $destination, FileSystemInterface::EXISTS_REPLACE);
                        if (!$newFileName) {
                            throw new \Exception('Could not move the generic placeholder file to the destination directory.');
                        } else {
                            $NewFile->setFileUri($createDir . '/' .   $deliverable_name);
                            $NewFile->uid = $evt_id;
                            $NewFile->setPermanent();
                            $NewFile->save();
                        }
                    }
                
                    $deliverableEntry = array('evt_id' => $evt_id, 'evt_deliverable_url' => $deliverable_name, 'evt_deliverable_name' => $deliverable_name);
                    $query = \Drupal::database()->insert('kicp_km_event_deliverable')
                    ->fields($deliverableEntry);
                    $evt_deliverable_id = $query->execute();

                    if ($evt_deliverable_id != null) {
                        if ($tags != '') {
                            $entry1 = array(
                                'module' => 'activities_deliverable',
                                'module_entry_id' => intval($evt_deliverable_id),
                                'tags' => $tags,
                            );
                            $return1 = TagStorage::insert($entry1);                        
                        } 
                        $deliverable_uploaded .= "$deliverable_name, ";
                    } 
                }
                \Drupal::logger('activities')->info('Event ID: '.$evt_id.' Event deliverables uploaded: '.substr($deliverable_uploaded,0,-2));

            } catch (Exception $e) {
                $variables = Error::decodeException($e);
                \Drupal::messenger()->addError(
                    t('Activity Event deliverables  is not uploaded.' )
                    );
                \Drupal::logger('activities')->error('Activity Event deliverables is not uploaded.: '.$variables);                    
                $transaction->rollBack();                   
            }
            unset($transaction); 

            ///////////////
            $url = Url::fromUserInput('/activities_deliverable/'.$evt_id);
            $form_state->setRedirectUrl($url);            
               
            $messenger = \Drupal::messenger(); 
            if($deliverable_uploaded != "") {
                $messenger->addMessage( t('Deliverable '. substr($deliverable_uploaded,0,-2) . ' uploaded'));                
            }
            
            if($deliverable_not_uploaded != "") {
                $messenger->addError( t('Deliverable '. substr($deliverable_not_uploaded,0,-2) . ' not uploaded'));                
            }


            /////////// Handle attachment [End] /////////////
            
           
        } else {
            $messenger->addError( t('No deliverable is uploaded'));                
        }
            //***********************************************************
        
    }

}
