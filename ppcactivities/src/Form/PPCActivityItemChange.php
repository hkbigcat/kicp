<?php

/**
 * @file
 * Contains the settings for administrating the Activities Event Form
 */

namespace Drupal\ppcactivities\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\common\Controller\TagList;
use Drupal\common\Controller\TagStorage;
use Drupal\ppcactivities\Common\PPCActivitiesDatatable;
use Drupal\common\CommonUtil;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\file\Entity;


class PPCActivityItemChange extends FormBase  {

    public function __construct() {
        $this->module = 'ppcactivities';
        $this->default_creator = 'KMU.OGCIO';

    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'ppc_km_activities_item_change_form';
    }


    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
        'ppcactivities.settings',
        ];
    }
	
	

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $evt_id="") {

        $config = \Drupal::config('ppcactivities.settings'); 

        $form['#attributes']['enctype'] = 'multipart/form-data';

        $eventInfo = PPCActivitiesDatatable::getEventDetail($evt_id);

        $form['evt_name'] = [
            '#type' => 'textfield',
            '#title' => t('Event Name'),
            '#size' => 90,
            '#maxlength' => 225,
            '#required' => TRUE,
            '#default_value' => $eventInfo['evt_name'],
        ];  
        
        $form['evt_name_prev'] = array(
            '#type' => 'hidden',
            '#value' => $eventInfo['evt_name'],
          );
                 
        $form['evt_description'] = [
            '#type' => 'textarea',
            '#title' => t('Event Description'),
            '#rows' => 10,
            '#cols' => 30,
            '#attributes' => array('style' => 'height:300px;'),
            '#required' => TRUE,
            '#default_value' => $eventInfo['evt_description'],
        ];

        $form['evt_description_prev'] = array(
            '#type' => 'hidden',
            '#value' => $eventInfo['evt_description'],
          );
  

        $form['evt_venue'] = array(
            '#title' => t('Event Venue'),
            '#type' => 'textfield',
            '#size' => 90,
            '#maxlength' => 255,
            '#default_value' => $eventInfo['venue'],
        );

        $form['evt_venue_prev'] = array(
            '#type' => 'hidden',
            '#value' => $eventInfo['venue'],
          );
  

        $form['evt_start_date'] = array(
            '#title' => t('Event Start Date'),
            '#type' => 'textfield',
            '#size' => 20,
            '#maxlength' => 19,
            '#prefix' => '<div class="div_inline_440">',
            '#suffix' => '</div>',
            '#description' => t('YYYY-MM-DD hh:mm:ss'),
            '#required' => TRUE,
            '#default_value' => $eventInfo['evt_start_date'],
        );

        $form['evt_start_date_prev'] = array(
            '#type' => 'hidden',
            '#value' => $eventInfo['evt_start_date'],
          );

        $form['evt_end_date'] = array(
            '#title' => t('Event End Date'),
            '#type' => 'textfield',
            '#size' => 20,
            '#maxlength' => 19,
            '#prefix' => '<div class="div_inline_440">',
            '#suffix' => '</div>',
            '#description' => t('YYYY-MM-DD hh:mm:ss'),
            '#required' => TRUE,
            '#default_value' => $eventInfo['evt_end_date'],
        );

        $form['evt_end_date_prev'] = array(
            '#type' => 'hidden',
            '#value' => $eventInfo['evt_end_date'],
          );

        $form['evt_enroll_start'] = array(
            '#title' => t('Event Enroll Start Date'),
            '#type' => 'textfield',
            '#size' => 20,
            '#maxlength' => 19,
            '#prefix' => '<br><div class="div_inline_440">',
            '#suffix' => '</div>',
            '#description' => t('YYYY-MM-DD hh:mm:ss'),
            '#default_value' => $eventInfo['evt_enroll_start'],
        );

        $form['evt_enroll_start_prev'] = array(
            '#type' => 'hidden',
            '#value' => $eventInfo['evt_enroll_start'],
          );

        $form['evt_enroll_end'] = array(
            '#title' => t('Event Enroll End Date'),
            '#type' => 'textfield',
            '#size' => 20,
            '#maxlength' => 19,
            '#prefix' => '<div class="div_inline_440">',
            '#suffix' => '</div>',
            '#description' => t('YYYY-MM-DD hh:mm:ss'),
            '#default_value' => $eventInfo['evt_enroll_end'],
        );

        $form['evt_enroll_end_prev'] = array(
            '#type' => 'hidden',
            '#value' =>  $eventInfo['evt_enroll_end'],
          );

        $activityTypeAry = PPCActivitiesDatatable::getAllActivityType();
        $activityTypeSelect = array();
        foreach ($activityTypeAry as $typeAry) {
            $activityTypeSelect[$typeAry['evt_type_id']] = $typeAry['evt_type_name'];
        }

        $form['form_evt_type_id'] = array(
            '#title' => t('Event Type'),
            '#type' => 'select',
            '#options' => $activityTypeSelect,
            '#prefix' => '<div class="div_inline_440">',
            '#suffix' => '</div>',
            '#required' => TRUE,
            '#default_value' => $eventInfo['evt_type_id'],
        );        

        $form['form_evt_type_id_prev'] = array(
            '#type' => 'hidden',
            '#value' => $eventInfo['evt_type_id'],
          );

        $copSelectAry = PPCActivitiesDatatable::getCOPInfo();
        $copSelect = array();
        foreach ($copSelectAry as $copAry) {
            $copSelect[$copAry['cop_id']] = $copAry['cop_name'];
        }

        $form['evt_cop_id'] = array(
            '#title' => t('COP Associated'),
            '#type' => 'select',
            '#prefix' => '<div id="div_evt_cop_id">',
            '#suffix' => '</div>',
            '#options' => $copSelect,
            '#prefix' => '<div class="div_inline_440">',
            '#suffix' => '</div><br>',
            '#default_value' => $eventInfo['cop_id'],
        );


        $form['evt_cop_id_prev'] = array(
            '#type' => 'hidden',
            '#value' => $eventInfo['cop_id'],
          );


        $form['is_recent'] = array(
            '#title' => t('Recent Event '),
            '#type' => 'checkbox',
            '#default_value' => 1,
            '#prefix' => '<div class="div_inline_440">',
            '#suffix' => '</div>',
            '#default_value' => $eventInfo['is_recent'],
        );


        $form['is_recent_prev'] = array(
            '#type' => 'hidden',
            '#alue' => $eventInfo['is_recent'],
          );


        $form['is_visible'] = array(
            '#title' => t('Event Visible '),
            '#type' => 'checkbox',
            '#default_value' => 1,
            '#prefix' => '<div class="div_inline_440">',
            '#suffix' => '</div>',
            '#default_value' => $eventInfo['is_visible'],
        );

        $form['is_visible_prev'] = array(
            '#type' => 'hidden',
            '#default_value' => $eventInfo['is_visible'],
          );


        $form['evt_recent_status'] = array(
            '#title' => t('Event Recent Status'),
            '#type' => 'textfield',
            '#size' => 90,
            '#maxlength' => 255,
            '#default_value' => $eventInfo['evt_recent_status'],
        );

        $form['evt_recent_status_prev'] = array(
            '#type' => 'hidden',
            '#value' => $eventInfo['evt_recent_status'],
          );


        $form['evt_max_enroll'] = array(
            '#title' => t('Maximum # of Enrollment'),
            '#type' => 'textfield',
            '#size' => 40,
            '#maxlength' => 10,
            '#default_value' => $eventInfo['evt_capacity'],
        );

        $form['evt_max_enroll_prev'] = array(
            '#type' => 'hidden',
            '#value' => $eventInfo['evt_capacity'],
          );


        $form['evt_enroll_url'] = array(
            '#title' => t('Enrollment URL '),
            '#type' => 'textarea',
            '#rows' => 4,
            '#cols' => 30,
            '#default_value' => $eventInfo['enroll_URL'],
        );

        $form['evt_enroll_url_prev'] = array(
            '#type' => 'hidden',
            '#value' => $eventInfo['enroll_URL'],
          );


        $form['evt_enroll_status_url'] = array(
            '#title' => t('Enrollment Status URL '),
            '#type' => 'textarea',
            '#rows' => 4,
            '#cols' => 30,
            '#default_value' => $eventInfo['current_enroll_status'],
        );

        $form['evt_enroll_status_url_prev'] = array(
            '#type' => 'hidden',
            '#value' => $eventInfo['current_enroll_status'],
          );

          $form['evt_ets_url'] = array(
            '#title' => t('Enrollment Status from ETS'),
            '#type' => 'textarea',
            '#rows' => 4,
            '#cols' => 30,
            '#default_value' => $eventInfo->ETS_url,
        );
        
        $form['evt_ets_url_prev'] = array(
          '#type' => 'hidden',
          '#default_value' => $eventInfo->ETS_url,
        );

  
        if($eventInfo['evt_logo_url'] != "") {
            $this_evt_id = str_pad($evt_id, 6, "0", STR_PAD_LEFT);
            $original_img = '<img src="../system/files/'.$this->module.'/item/'. $this_evt_id.'/'.$eventInfo['evt_logo_url'].'" border="0" width="50" height="50">';
        } else {
            $original_img = "";
        }


        $form['evt_logo'] = array(
            '#title' => t('Event Logo'),
            '#type' => 'file',
            '#prefix' => $original_img,
        );

        $TagList = new TagList();

        $tags = $TagList->getTagListByRecordId('ppcactivities', $evt_id);

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
          
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'),
        );
        
        
        $form['evt_type_id'] = array(
            '#type' => 'hidden',
            '#value' => $_REQUEST['evt_type_id'],
        );
        
        $form['evt_id'] = array(
            '#type' => 'hidden',
            '#value' => $evt_id,
        );

        $form['actions']['cancel'] = array(
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#prefix' => '&nbsp;',
            '#attributes' => array('onClick' => 'window.open(\'../ppcactivities_admin/\', \'_self\'); return false;'),
            '#limit_validation_errors' => array(),
        );
        
        
        $taglist = $TagList->getListCopTagForModule('ppcactivities', $evt_id);
        $form['t3'] = array(
            '#title' => t('COP Tags'),
            '#type' => 'details',
            '#open' => true,
            '#description' =>  t($taglist),
            '#attributes' => array('style'=>'border: 1px solid #7A7A7A;background: #FCFCE6; margin-top:40px;'),
        );

          
        $taglist = $TagList->getList($this->module);
        $form['t1'] = array(
            '#title' => t('PPC Activities Tags'),
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

        if (!isset($evt_name) || $evt_name == '') {
            $form_state->setErrorByName(
                'evt_name', $this->t("Event Name is blank")
            );
        }

        if (!isset($evt_description) || $evt_description == '') {
            $form_state->setErrorByName(
                'evt_description', $this->t("Description is blank")
            );
        }
        
        if (!isset($evt_start_date) || $evt_start_date == '') {
            $form_state->setErrorByName(
                'evt_start_date', $this->t("Event Start Date is blank")
            );
        }
        
        if (!isset($evt_end_date) || $evt_end_date == '') {
            $form_state->setErrorByName(
                'evt_end_date', $this->t("Event End Date is blank")
            );
        }
        
        if (!isset($form_evt_type_id) || $form_evt_type_id == '0') {
            $form_state->setErrorByName(
                'form_evt_type_id', $this->t("Event Type is blank")
            );
        } else if($form_evt_type_id == 1 && (!isset($evt_cop_id) || $evt_cop_id == '')) {
            $form_state->setRebuild(True);
        }

    }    
    
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }
        
        $img_name = '';
        $hasImage = false;
        $evt_is_cop_evt = 1;
        $evt_cop_id = $evt_cop_id;
        $current_time =  \Drupal::time()->getRequestTime();
        if ($_FILES['files']['name']['evt_logo'] != "") {
            $hasImage = true;
            $img_name = $_FILES['files']['name']['evt_logo'];                
        }        
        $eventEntry = array(
            'evt_name' => $evt_name,
            'evt_type_id' => $form_evt_type_id,
            'evt_start_date' => $evt_start_date,
            'evt_end_date' => $evt_end_date,
            'evt_enroll_start' => ($evt_enroll_start=="" ? NULL : $evt_enroll_start),
            'evt_enroll_end' => ($evt_enroll_end=="" ? NULL : $evt_enroll_end),
            'evt_description' => $evt_description,
            'evt_logo_url' => $_FILES['files']['name']['evt_logo'],
            'evt_is_cop_evt' => $evt_is_cop_evt,
            'cop_id' => $evt_cop_id,
            'is_recent' => $is_recent,
            'evt_recent_status' => $evt_recent_status,
            'is_visible' => $is_visible,
            'evt_capacity' => ($evt_max_enroll=="" ? NULL : $evt_max_enroll),
            'enroll_URL' => $evt_enroll_url,
            'current_enroll_status' => $evt_enroll_status_url,
            'ETS_url' => $evt_ets_url,            
            'venue' => $evt_venue,
          );


        // if changes in content, update timestamp
        if($evt_name != $evt_name_prev || $evt_description != $evt_description_prev || $evt_venue != $evt_venue_prev || $evt_start_date != $evt_start_date_prev || $evt_end_date != $evt_end_date_prev || $evt_enroll_start != $evt_enroll_start_prev || $evt_enroll_end != $evt_enroll_end_prev || $form_evt_type_id != $form_evt_type_id_prev || $evt_cop_id != $evt_cop_id_prev || $is_recent != $is_recent_prev || $is_visible != $is_visible_prev || $evt_recent_status != $evt_recent_status_prev || $evt_max_enroll != $evt_max_enroll_prev || $evt_enroll_url != $evt_enroll_url_prev || $evt_enroll_status_url != $evt_enroll_status_url_prev ||  $evt_ets_url != $evt_ets_url_prev || ( isset($_FILES['files']['name']['evt_logo']) && $_FILES['files']['name']['evt_logo'] != "")) {
            $eventEntry['modify_datetime'] = date('Y-m-d H:i:s', $current_time);
        }

          $this_evt_id = str_pad($evt_id, 6, "0", STR_PAD_LEFT);
          try {
            $query = \Drupal::database()->update('kicp_ppc_event')
            ->fields( $eventEntry)
            ->condition('evt_id', $evt_id)
            ->execute();    

            if ($tags != $tags_prev) {
                // rewrite tags
                if ($tags_prev != '') {
                    $query = $database->update('kicp_tags')->fields([
                        'is_deleted'=>1 , 
                      ])
                      ->condition('fid', $evt_id)
                      ->condition('module', 'ppcactivities')
                      ->execute();                
                }
                if ($tags != '') {
                    $entry1 = array(
                        'module' => 'ppcactivities',
                        'module_entry_id' => intval($evt_id),
                        'tags' => $tags,
                      );
                      $return1 = TagStorage::insert($entry1);                
                }
              }
            
            if ($hasImage) {
                $file_system = \Drupal::service('file_system');   
                $image_path = 'private://ppcactivities/item/'.$this_evt_id;
                if (!is_dir($file_system->realpath($image_path))) {
                    // Prepare the directory with proper permissions.
                    if (!$file_system->prepareDirectory($image_path, FileSystemInterface::CREATE_DIRECTORY)) {
                      throw new \Exception('Could not create the event image directory.');
                    }
                }
                  
                
                $validators = array(
                    'file_validate_extensions' => array(CommonUtil::getSysValue('default_file_upload_extensions')),
                    'file_validate_size' => array(CommonUtil::getSysValue('default_file_upload_size_limit') * 1024 * 1024),
                  );
                
                
                $delta = NULL; // type of $file will be array
                $file = file_save_upload('evt_logo', $validators, $image_path, $delta);
          
                $file[0]->setPermanent();
                $file[0]->uid = $file_id;
                $file[0]->save();
                $url = $file[0]->createFileUrl(FALSE);

            }            

            $url = Url::fromUri('base:/ppcactivities_admin/');
            $form_state->setRedirectUrl($url);
    
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('PPC Event has been updated.'));

        }
        catch (\Exception $e) {
            \Drupal::messenger()->addError(
                t('Unable to save ppc event at this time due to datbase error. Please try again. '.serialize($eventEntry) )
                );

           
        }	


    }

}