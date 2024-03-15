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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\file\Entity;


class ActivityCOPCategoryChange extends FormBase {

    public function __construct() {
        $this->module = 'activities';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'km_activities_cop_category_change_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $group_id="") {

        $groupInfo = ActivitiesDatatable::getCOPGroupInfo($group_id);
        
        // display the form

        $form['group_name'] = array(
          '#title' => t('Category Name'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 255,
          '#default_value' => $groupInfo['group_name'],
          '#required' => TRUE,
        );

        $form['description'] = array(
          '#title' => t('Description'),
          '#type' => 'textarea',
          '#rows' => 10,
          '#cols' => 30,
          '#default_value' => $groupInfo['group_description'],
          '#required' => TRUE,
        );
        
        if($groupInfo['img_name'] != "") {
            $original_img = '<img src="../system/files/'.$this->module.'/category/'.$groupInfo['img_name'].'" border="0" width="50" height="50">';
        } else {
            $original_img = '';
        }
                
        $form['#attributes']['enctype'] = 'multipart/form-data';
        
        $form['group_image'] = array(
          '#title' => t('Image.'),
          '#type' => 'file',
          '#size' => 100,
          '#prefix' => $original_img,
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
        
        try {
            
            //$img_name = '';
            $hasImage = false;
            
            if ($_FILES['files']['name']['group_image'] != "") {
                $hasImage = true;
                $img_name = $_FILES['files']['name']['group_image'];
            }
            
            $categoryEntry = array(
                'group_name' => $group_name,
                'group_description' => $description,
            );
            if($hasImage) {
                $categoryEntry['img_name'] = $img_name;
            }
            $query = \Drupal::database()->update('kicp_km_cop_group')
            ->fields( $categoryEntry)
            ->condition('group_id', $group_id)
            ->execute();   

            if($hasImage) {
                // upload image to private folder
                $this_group_id = str_pad($group_id, 6, "0", STR_PAD_LEFT);
                
                $file_system = \Drupal::service('file_system');   
                $image_path = 'private://activities/category';
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
                $file = file_save_upload('group_image', $validators, $image_path, $delta);
          
                $file[0]->setPermanent();
                $file[0]->uid = $file_id;
                $file[0]->save();
                $url = $file[0]->createFileUrl(FALSE);
            }

            $url = Url::fromUri('base:/activities_admin_category/');
            $form_state->setRedirectUrl($url);
    
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('COP Category is updated.'));

        }
        catch (Exception $e) {
            \Drupal::messenger()->addError(
                t('COP Category is not updated.' )
                );
        }
    }

}
