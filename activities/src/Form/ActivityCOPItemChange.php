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

class ActivityCOPItemChange extends FormBase {

    public function __construct() {
        $this->module = 'activities';
    }
    /**
     * {@inheritdoc}
     */

    public function getFormId() {
        return 'km_activities_cop_item_change_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $cop_id="") {

        $copItemInfo = ActivitiesDatatable::getCOPItem($cop_id);
        
        // display the form

        $form['cop_name'] = array(
          '#title' => t('COP Name'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 255,
          '#default_value' => $copItemInfo['cop_name'],
          '#required' => TRUE,
        );

        $form['description'] = array(
          '#title' => t('Description'),
          '#type' => 'textarea',
          '#rows' => 10,
          '#cols' => 30,
          '#default_value' => $copItemInfo['cop_info'],
          '#required' => TRUE,
        );
                
        if($copItemInfo['img_name'] != "") {
            $original_img = '<img src="../system/files/'.$this->module.'/item/'.$copItemInfo['img_name'].'" border="0" width="50" height="50">';
        } else {
            $original_img = '';
        }
        
        $form['#attributes']['enctype'] = 'multipart/form-data';
        
        $form['cop_image'] = array(
          '#title' => t('Image'),
          '#type' => 'file',
          '#size' => 100,
          '#prefix' => $original_img,
        );
        
        $form['display_order'] = array(
          '#title' => t('Display Order'),
          '#type' => 'textfield',
          '#size' => 10,
          '#maxlength' => 30,
          '#default_value' =>$copItemInfo['display_order'],
        );
               
        $form['cop_group_id'] = array(
          '#type' => 'hidden',
          '#value' =>$copItemInfo['cop_group_id'],
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
          '#attributes' => array('onClick' => 'window.open(\'../activities_cop/' . $copItemInfo['cop_group_id'] . '\', \'_self\'); return false;'),
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
            
            //$img_name = '';
            $hasImage = false;
            
            if ($_FILES['files']['name']['cop_image'] != "") {
                $hasImage = true;
                $img_name = $_FILES['files']['name']['cop_image'];                
            }
            
            $copEntry = array(
                'cop_id' => $cop_id,
                'cop_name' => $cop_name,
                'cop_info' => $description,
                'cop_group_id' => $cop_group_id,
                'display_order' => $display_order,
            );
            if($hasImage) {
                $copEntry['img_name'] = $img_name;
            }

            $query = \Drupal::database()->update('kicp_km_cop')
            ->fields( $copEntry)
            ->condition('cop_id', $cop_id)
            ->execute();  
            
            if($hasImage) {
                // upload image to private folder
                
                $file_system = \Drupal::service('file_system');   
                $image_path = 'private://activities/item';
                if (!is_dir($file_system->realpath($image_path))) {
                    // Prepare the directory with proper permissions.
                    if (!$file_system->prepareDirectory($image_path, FileSystemInterface::CREATE_DIRECTORY)) {
                      throw new \Exception('Could not create the category image directory.');
                    }
                }
                  
                $validators = array(
                    'file_validate_extensions' => array(CommonUtil::getSysValue('default_file_upload_extensions')),
                    'file_validate_size' => array(CommonUtil::getSysValue('default_file_upload_size_limit') * 1024 * 1024),
                  );
                
                
                $delta = NULL; // type of $file will be array
                $file = file_save_upload('cop_image', $validators, $image_path, $delta);
          
                $file[0]->setPermanent();
                $file[0]->uid = $file_id;
                $file[0]->save();
                $url = $file[0]->createFileUrl(FALSE);
                
            }
            
            //-----------------------------------------------------------------------------------

            $url = Url::fromUri('base:/activities_cop/'.$cop_group_id);
            $form_state->setRedirectUrl($url);
    
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('COP Item is updated'));
 
        }
        catch (Exception $e) {

            \Drupal::messenger()->addError(
                t('COP Item is not updated.' )
                );            

        }
    }

}
