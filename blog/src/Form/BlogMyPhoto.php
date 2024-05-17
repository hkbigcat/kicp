<?php

/**
 * @file
 * Contains the settings for administrating the Test Form
 */


namespace Drupal\blog\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blog\Common\BlogDatatable;
use Drupal\common\CommonUtil;
use Drupal\Core\Database\Database;
use Drupal\file\FileInterface;
use Drupal\file\Entity;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Utility\Error;

class BlogMyPhoto extends FormBase  {

    public function __construct() {
        $this->module = 'blog';

    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'blog_my_photo_form';
    }


    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
        'blog.settings',
        ];
    }
	

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $config = \Drupal::config('blog.settings'); 

        
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $user_id = $authen->getUserId();

        $blog_id = BlogDatatable::getBlogIDByUserID($user_id);

        $form['blog_id'] = [
            '#type' => 'hidden',
            '#value' => $blog_id,
        ];


        $validators = array(
            'file_validate_extensions' => array(CommonUtil::getSysValue('default_file_upload_extensions')),
            'file_validate_size' => array(CommonUtil::getSysValue('default_file_upload_size_limit') * 1024 * 1024),
          );        

        $form['photo_file'] = [
            //'#type' => 'managed_file',
            '#type' => 'file',
            '#title' => $this->t('Upload Your Photo (maximum size: 2MB)'),
            '#required' => TRUE,
        ];         
          

       
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'),
        );
        
        $form['actions']['cancel'] = array(
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#prefix' => '&nbsp;',
            '#attributes' => array('onClick' => 'window.open(\'blog_view\', \'_self\'); return false;'),
            '#limit_validation_errors' => array(),
        );


        return $form;

    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }

        $current_time =  \Drupal::time()->getRequestTime();

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $uid = $authen->getUid();
        

        $user_owner_id = str_pad($uid, 6, "0", STR_PAD_LEFT);

           /* Upload image [Start] */
         
        $BlogPhoto = 'private://blog/icon';
        $file_system = \Drupal::service('file_system');    

        
        $BlogPhotoPath = $BlogPhoto.'/'. $user_owner_id;
        if (!is_dir($file_system->realpath($BlogPhotoPath))) {
            // Prepare the directory with proper permissions.
            if (!$file_system->prepareDirectory($BlogPhotoPath, FileSystemInterface::CREATE_DIRECTORY)) {
                throw new \Exception('Could not create the blog icon directory:'.$BlogPhotoPath);
            }
        }
      
        $validators = array(
            'file_validate_extensions' => array('jpg jpeg gif png'),
            'file_validate_size' => array(2 * 1024 * 1024),
            );
    
        $delta = NULL; // type of $file will be array
        $file = file_save_upload('photo_file', $validators, $BlogPhotoPath,$delta);

        $file[0]->setPermanent();
        $file[0]->uid = $blog_id;
        $file[0]->save();

        $filename = $file[0]->getFilename();
        $url = $file[0]->createFileUrl(FALSE);

        $entry = array(
            'image_name' =>  $filename,
          );

        $database = \Drupal::database();
        $transaction = $database->startTransaction();             

        try {  
            $query = \Drupal::database()->update('kicp_blog')->fields($entry)
                    ->condition('blog_id', $blog_id)
                    ->execute();          

            // write logs to common log table
            \Drupal::logger('blog')->info('Photo added to blog blog id: %id, photo: %image',   
            array(
                '%id' => $blog_id,
                '%image' => $filename,
            ));      

            $url = Url::fromUserInput('/blog_view/'.$blog_id);
            $form_state->setRedirectUrl($url);

            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Blog Photo has been updaed.'));
        } 
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addStatus(
                t('Unable to save photo at this time due to datbase error. Please try again. ' )
                );
            \Drupal::logger('blog')->error('Photo is not updated '  . $variables);                    
            $transaction->rollBack();        
        }
        unset($transaction);	

    }

}