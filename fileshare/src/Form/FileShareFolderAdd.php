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
use Drupal\Core\Utility\Error;


class FileShareFolderAdd extends FormBase {

  public $is_authen;
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
        return 'fileshare_folder_add_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $output = NULL;
        
        $logged_in = \Drupal::currentUser()->isAuthenticated();
        if (!$logged_in) {    
          $form['no_access'] = [
              '#markup' => CommonUtil::no_access_msg(),
          ];     
          return $form;        
      }
        
        $form['folder_name'] = array(
          '#title' => t('Folder Name'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 200,
          '#required' => TRUE,
        );        

        $form['tags'] = array(
          '#title' => t('Tags'),
          '#type' => 'textarea',
          '#rows' => 2,
          //'#attributes' => array('id' => 'tags'),
          '#description' => 'Use semi-colon (;) as separator',
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
       $folder_name =  $form_state->getValue('folder_name');
	     $tags =  $form_state->getValue('tags');
       $current_time =  \Drupal::time()->getRequestTime();

       $database = \Drupal::database();
       $transaction = $database->startTransaction(); 

      try {

        $query = $database  ->insert('kicp_file_share_folder');

        
        $query->fields([
          'folder_name',
          'user_id',
          'create_datetime',
          'modify_datetime',
        ]);

        $query->values([
          $folder_name,
          $this->my_user_id,
          date('Y-m-d H:i:s', $current_time), 
          date('Y-m-d H:i:s', $current_time),
        ]);       

        $folder_id = $query->execute();

        if ($tags != '') {
           $entry1 = array(
            'module' => 'fileshare_folder',
            'module_entry_id' => intval($folder_id),
            'tags' => $tags,
          );
          $return1 = TagStorage::insert($entry1);
           
        }        

        $url = Url::fromRoute('fileshare.fileshare_folder');
        $form_state->setRedirectUrl($url);
  
        $messenger = \Drupal::messenger(); 
        $messenger->addMessage( t('File Folder has been added'));


      }
      catch (\Exception $e ) {
        $variables = Error::decodeException($e);
        \Drupal::messenger()->addError(
          t('Unable to save fileshare folder at this time due to datbase error. Please try again.')
        ); 
        \Drupal::logger('fileshare')->error('Fileshare is not created ' .$variables); 
        $transaction->rollBack();
      }
      unset($transaction);
      

    }

}
