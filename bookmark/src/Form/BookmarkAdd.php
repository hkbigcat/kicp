<?php

/**
 * @file
 * Contains the settings for administrating the Bookmark Form
 */

namespace Drupal\bookmark\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\common\TagList;
use Drupal\common\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal;
use Drupal\Core\Utility\Error;

class BookmarkAdd extends FormBase  {

    public $is_authen;
    public $module;    

    public function __construct() {
        $this->module = 'bookmark';
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->is_authen = $authen->isAuthenticated;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'bookmark_bookmark_add';
    }


    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
        'bookmark.settings',
        ];
    }
	
	

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {


        $config = \Drupal::config('bookmark.settings'); 

        if (! $this->is_authen) {
            $form['no_access'] = [
                '#markup' => CommonUtil::no_access_msg(),
            ];     
            return $form;        
        }

        $form['bTitle'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#size' => 90,
            '#maxlength' => 200,
            '#required' => TRUE,
        ];          
                 
        $form['bDescription'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Content'),
            '#rows' => 2,
            '#attributes' => array('style' => 'height:300px;'),
            '#required' => TRUE,
        ];

        $form['bAddress'] = array(
            '#title' => t('Web Address'),
            '#type' => 'textfield',
            '#size' => 150,
            '#maxlength' => 512,
            '#required' => TRUE,
        );
  

        $form['bStatus'] = array(
            '#title' => t('Privacy'),
            '#type' => 'select',
            '#options' => array(0 => "Public", 2 => "Private"),
          );

        $TagList = new TagList();

        $form['tags'] = array(
            '#title' => t('Tags'),
            '#type' => 'textarea',
            '#rows' => 2,
            '#description' => 'Use semi-colon (;) as separator',
        );
          
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'),
            '#attributes' => array('style'=>'margin-bottom: 20px;'),
        );
        
        $form['actions']['cancel'] = array(
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#prefix' => '&nbsp;',
            '#attributes' => array('onClick' => 'window.open(\'blog_view?blog_id=1234\', \'_self\'); return false;'),
            '#limit_validation_errors' => array(),
        );
        
        $taglist = $TagList->getListCopTagForModule();
        $form['t3'] = array(
            '#title' => t('COP Tags'),
            '#type' => 'details',
            '#open' => true,
            '#description' =>  $taglist,
            '#attributes' => array('style'=>'border: 1px solid #7A7A7A;background: #FCFCE6;'),
        );

          
        $taglist = $TagList->getList($this->module);
        $form['t1'] = array(
            '#title' => t('Blog Tags'),
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

        if (isset($bDescription) and $bDescription != '' &&  strlen(trim($bDescription)) > 30000) {
            $form_state->setErrorByName(
                'bDescription', $this->t("Description exceeds 30,000 characters")
            );
        }

        // web address

        if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $bAddress)) {
            $form_state->setErrorByName(
                'bAddress', $this->t(
                    "The web address '%1' is invalid", array('%1' => $form_state->getValue('bAddress'))
                )
            );
        }
        else {
            $url1 = filter_var($bAddress, FILTER_SANITIZE_URL); //// Remove all illegal characters
            if (filter_var($url1, FILTER_VALIDATE_URL) === false) {
                $form_state->setErrorByName(
                    'bAddress', $this->t("The web address '%1' is invalid.", array('%1' => $form_state->getValue('bAddress')))
                );
            }
        }


        // tags
        if (isset($tags) and $tags != '') {

            if (strlen($tags) > 1024) {
                $form_state->setErrorByName(
                    'tags', $this->t("Length of tags > 1024")
                );
            }
        }
    }    
    
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }
        
        $entry = array(
            'user_id' => $authen->getUserId(),
            'bIp' => Drupal::request()->getClientIp(),
            'bStatus' => $bStatus,
            'bTitle' => $bTitle,
            'bAddress' => $bAddress,
            'bDescription' => $bDescription,
            'bVotes' => 0,
            'bVoting' => 0,
          );


          $database = \Drupal::database();
          $transaction = $database->startTransaction();   

          try {
            $query = $database->insert('kicp_bookmark')
            ->fields($entry);
            $bid = $query->execute();

            if ($tags != '') {
                $entry1 = array(
                    'module' => $this->module,
                    'module_entry_id' => intval($bid),
                    'tags' => $tags,
                );
                $return1 = TagStorage::insert($entry1);
                
            }      

            \Drupal::logger('bookmark')->info('Created id: %id, title: %title.',   
            array(
                '%id' => $bid,
                '%title' => $bTitle,
            ));            

            $url = Url::fromRoute('bookmark.bookmark_content');
            $form_state->setRedirectUrl($url);
    
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Bookmark has been added.'));

        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Unable to save bookmark at this time due to datbase error. Please try again. ' )
                );
            \Drupal::logger('bookmark')->error('Bookmark is not created: ' . $variables);   
            $transaction->rollBack();
        }	
        unset($transaction);
    }

}