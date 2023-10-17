<?php

/**
 * @file
 * Contains the settings for administrating the Bookmark Form
 */

namespace Drupal\bookmark\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bookmark\Common\BookmarkDatatable;
use Drupal\common\Controller\TagList;
use Drupal\common\Controller\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal;


class BookmarkChange extends FormBase  {

    public function __construct() {
        $this->module = 'bookmark';

    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'bookmark_bookmark_change';
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
    public function buildForm(array $form, FormStateInterface $form_state, $bid=NULL) {

        $config = \Drupal::config('bookmark.settings'); 
        $bookmark = BookmarkDatatable::getBookmarks($bid); 


        $form['bid'] = [
            '#type' => 'hidden',
            '#value' =>  $bookmark['bid'],
        ];

        $form['bTitle'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#size' => 90,
            '#maxlength' => 200,
            '#required' => TRUE,
            '#default_value' =>  $bookmark['bTitle'],
        ];        
        
        $form['bTitle_prev'] = array(
            '#type' => 'hidden',
            '#value' => $bTitle,
          );        
                 
        $form['bDescription'] = [
            '#type' => 'text_format',
            '#format' => 'full_html',
            '#title' => $this->t('Content'),
            '#rows' => 2,
            '#attributes' => array('style' => 'height:300px;'),
            '#required' => TRUE,
            '#default_value' =>  $bookmark['bDescription'],
        ];

        $form['bDescription_prev'] = array(
            '#type' => 'hidden',
            '#value' => $bDescription,
          );        

        $form['bAddress'] = array(
            '#title' => t('Web Address<span style="color:red">&nbsp;*</span>'),
            '#type' => 'textfield',
            '#size' => 150,
            '#maxlength' => 512,
            '#required' => TRUE,
            '#default_value' =>  $bookmark['bAddress'],
        );
  
        $form['bAddress_prev'] = array(
            '#type' => 'hidden',
            '#value' => $bAddress,
        );

        $form['bStatus'] = array(
            '#title' => t('Privacy'),
            '#type' => 'select',
            '#options' => array(0 => "Public", 2 => "Private"),
            '#default_value' =>  $bookmark['bStatus'],
          );

        $form['bStatus_prev'] = array(
            '#type' => 'hidden',
            '#default_value' => $bStatus,
        );          

        $TagList = new TagList();
        $tags_prev = $TagList->getTagListByRecordId('bookmark', $bid);

        $form['tags'] = array(
            '#title' => t('Tags'),
            '#type' => 'textarea',
            '#rows' => 2,
            '#description' => 'Use semi-colon (;) as separator',
            '#default_value' => implode(";", $tags_prev),
        );

        $form['tags_prev'] = array(
            '#type' => 'hidden',
            '#value' => $tags_prev,
        );        
          
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'),
        );
        
        $form['actions']['cancel'] = array(
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#prefix' => '&nbsp;',
            '#attributes' => array('onClick' => 'window.open(\'bookmark\', \'_self\'); return false;'),
            '#limit_validation_errors' => array(),
        );
        
        $taglist = $TagList->getListCopTagForModule();
        $form['t3'] = array(
            '#title' => t('COP Tags'),
            '#type' => 'details',
            '#open' => true,
            '#description' =>  t($taglist),
            '#attributes' => array('style'=>'border: 1px solid #7A7A7A;background: #FCFCE6;'),
        );

          
        $taglist = $TagList->getList($this->module);
        $form['t1'] = array(
            '#title' => t('Bookmark Tags'),
            '#type' => 'details',
            '#open' => true,
            '#description' =>  t($taglist),
        );

        $taglist = $TagList->getList('ALL');
        $form['t2'] = array(
            '#title' => t('All Tags'),
            '#type' => 'details',
            '#open' => false,
            '#description' => t($taglist),
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

        if (!isset($bTitle) or $bTitle == '') {
            $form_state->setErrorByName(
                'bTitle', $this->t("Title is blank")
            );
        }

        if (isset($bDescription) and $bDescription['value'] != '' &&  strlen(trim($bDescription['value'])) > 30000) {
            $form_state->setErrorByName(
                'bDescription', $this->t("Description exceeds 30,000 characters")
            );
        }

        // web address
        if (!isset($bAddress) or $bAddress == '') {
            $form_state->setErrorByName(
                'bAddress', $this->t("Web Address is blank")
            );
        }
        else {
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
            'bIp' => Drupal::request()->getClientIp(),
            'bStatus' => $bStatus,
            'bTitle' => $bTitle,
            'bAddress' => $bAddress,
            'bDescription' => $bDescription['value'],
          );

        if($bTitle != $bTitle_prev || $bDescription != $bDescription_prev || $bAddress!= $bAddress_prev || $bStatus != $bStatus_prev || $tags != $tags_prev ) {
            $entry['bModified'] = date('Y-m-d H:i:s');

          try {

               $query = \Drupal::database()->update('kicp_bookmark')->fields($entry)
                ->condition('bid', $bid)
                ->execute();


                if ($tags != $tags_prev) {
                    // rewrite tags
                    if ($tags_prev != '') {
                        $query = \Drupal::database()->update('kicp_tags')->fields([
                            'is_deleted'=>1 , 
                        ])
                        ->condition('fid', $bid)
                        ->condition('module', $this->module)
                        ->execute();                
                    }
                    if ($tags != '') {
                        $entry1 = array(
                            'module' => $this->module,
                            'module_entry_id' => intval($bid),
                            'tags' => $tags,
                        );
                        $return1 = TagStorage::insert($entry1);                
                    }
                }   

                $url = Url::fromUserInput('/bookmark/');
                $form_state->setRedirectUrl($url);

                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('Bookmark has been updated.'));

            }
            catch (\Exception $e) {
                \Drupal::messenger()->addStatus(
                    t('Unable to save bookmark at this time due to datbase error. Please try again. ' )
                    );
            
            }	
        }

    }

}