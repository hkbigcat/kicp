<?php

/**
 * @file
 */

namespace Drupal\fileshare\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\common\TagList;
use Drupal\common\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\file\Entity;
use Drupal\fileshare\Common\FileShareDatatable;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;



class FileShareFolderChange extends FormBase {

  public $my_user_id;
  public $module;

    public function __construct() {
      $current_user = \Drupal::currentUser();
      $this->my_user_id = $current_user->getAccountName();
      $this->module = 'fileshare';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'fileshare_folder_change_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $folder_id=null) {

        $output = NULL;

        $logged_in = \Drupal::currentUser()->isAuthenticated();
        if (!$logged_in) {    
          $form['no_access'] = [
              '#markup' => CommonUtil::no_access_msg(),
          ];     
          return $form;        
      }

        $folder = FileShareDatatable::load_folder($this->my_user_id,$folder_id);

        if ($folder['folder_id'] == null) {
          $messenger = \Drupal::messenger(); 
          $messenger->addWarning( t('This file folder cannot be found.'));              
          return $form;
        }

        $Taglist = new TagList();
        $tags = $Taglist->getTagListByRecordId('fileshare_folder', $folder_id);

        $form['folder_name'] = array(
          '#title' => t('Folder Name'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 200,
          '#required' => TRUE,
          '#default_value' => $folder['folder_name'],
        );

        $form['folder_name_prev'] = array(
            '#type' => 'hidden',
            '#value' => $folder['folder_name'],
          );        

        $form['tags'] = array(
          '#title' => t('Tags'),
          '#type' => 'textarea',
          '#rows' => 2,
          '#description' => 'Use semi-colon (;) as separator',
          '#default_value' => implode(";", $tags),
        );	

        $form['tags_prev'] = array(
            '#type' => 'hidden',
            '#value' => implode(";", $tags),
          );        

        $form['folder_id'] = array(
        '#type' => 'hidden',
        '#default_value' => $folder_id,
        );


        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Save'),
        );

        $form['actions']['cancel'] = array(
          '#type' => 'button',
          '#value' => t('Cancel'),
        );



        // Tag List
        $TagList = new TagList();

        $taglist = $TagList->getListCopTagForModule();
        $form['t3'] = array(
            '#title' => t('COP Tags'),
            '#type' => 'details',
            '#open' => true,
            '#description' => $taglist,
            '#attributes' => array('style'=>'border: 1px solid #7A7A7A;background: #FCFCE6;'),
        );


      $taglist = $TagList->getList($this->module);
      $form['t1'] = array(
          '#title' => t('File Share Tags'),
          '#type' => 'details',
          '#open' => true,
          '#description' => $taglist,
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

      // tags
      if (isset($tags) and $tags != '') {
        
        if (strlen($tags) > 1024) {
          $form_state->setErrorByName(
              'tags', $this->t("Length of tags > 1024")
          );
          $hasError = true;
        }  
      } 

    }

    //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

       //Obtain the value as entered into the Form
       $folder_id =  $form_state->getValue('folder_id');
       $folder_name =  $form_state->getValue('folder_name');
       $folder_name_prev =  $form_state->getValue('folder_name_prev');
	    $tags =  $form_state->getValue('tags');
       $tags_prev =  $form_state->getValue('tags_prev');
       $current_time =  \Drupal::time()->getRequestTime();

       try {
         $database = \Drupal::database();
         if($folder_name != $folder_name_prev) {
            $query = $database->update('kicp_file_share_folder')->fields([
              'folder_name'=>$folder_name , 
              'modify_datetime' => date('Y-m-d H:i:s', $current_time),
            ])
            ->condition('folder_id', $folder_id)
            ->execute();        
         }
         
         if ($tags != $tags_prev) {
            // rewrite tags
            if ($tags_prev != '') {
                $query = $database->update('kicp_tags')->fields([
                    'is_deleted'=>1 , 
                  ])
                  ->condition('fid', $folder_id)
                  ->condition('module', 'fileshare_folder')
                  ->execute();                
            }
            if ($tags != '') {
                $entry1 = array(
                    'module' => 'fileshare_folder',
                    'module_entry_id' => intval($folder_id),
                    'tags' => $tags,
                  );
                  $return1 = TagStorage::insert($entry1);                
            }
         }

         $url = Url::fromRoute('fileshare.fileshare_folder');
         $form_state->setRedirectUrl($url);
    
         $messenger = \Drupal::messenger(); 
         $messenger->addMessage( t('File Folder has been updated'));
           
      }        
      catch (\Exception $e ) {
        \Drupal::messenger()->addError(
          t('Unable to save fileshare folder at this time due to datbase error. Please try again.')
        ); 

      }

      

    }

}
