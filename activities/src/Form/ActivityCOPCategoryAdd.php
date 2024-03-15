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

class ActivityCOPCategoryAdd extends FormBase {

    public function __construct() {
        $this->module = 'activities';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'km_activities_cop_category_add_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        // display the form

        $form['group_name'] = array(
          '#title' => t('Category Name'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 255,
          '#required' => TRUE,
        );

        $form['description'] = array(
          '#title' => t('Description'),
          '#type' => 'textarea',
          '#rows' => 10,
          '#cols' => 30,
          '#required' => TRUE,
        );
                
        $form['#attributes']['enctype'] = 'multipart/form-data';
        
        $form['group_image'] = array(
          '#title' => t('Image'),
          '#type' => 'file',
          '#size' => 100,
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
            
            $img_name = '';
            $hasImage = false;
            
            if ($_FILES['files']['name']['group_image'] != "") {
                $hasImage = true;
                $img_name = $_FILES['files']['name']['group_image'];                
            }
            
            $categoryEntry = array(
                'group_name' => $group_name,
                'group_description' => $description,
                'img_name' => $img_name,
            );

            $query = \Drupal::database()->insert('kicp_km_cop_group')
            ->fields( $categoryEntry);
            $group_id = $query->execute();

            if ($group_id != null) {
                            
                if($hasImage) {
                    // upload image to private folder
                    //$this_group_id = str_pad($group_id, 6, "0", STR_PAD_LEFT);

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

                    $ServerAbsolutePath = CommonUtil::getSysValue('server_absolute_path'); // get server absolute path
                }
                
                //-----------------------------------------------------------------------------------

                $url = Url::fromUri('base:/activities_admin_category/');
                $form_state->setRedirectUrl($url);
        
                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('COP Category is created. ID: '.$group_id));

            } else {
                \Drupal::messenger()->addError(
                    t('COP Category is not created - data not save' )
                    );
            }

        }
        catch (Exception $e) {
            \Drupal::messenger()->addError(
                t('COP Category is not created' )
                );
        }
    }

}
