<?php

/**
 * @file
 */

namespace Drupal\video\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal;
use Drupal\Component\Utility\UrlHelper;
use Drupal\common\RatingData;
use Drupal\common\Controller\TagList;
use Drupal\common\Controller\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\video\Common\VideoDatatable;
use Drupal\video\Controller\VideoController;
use Drupal\activities\Common\ActivitiesDatatable;

class VideoEventChange extends FormBase {

    public function __construct() {
        $this->module = 'video';
        $this->default_creator = 'KMU.OGCIO';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'video_event_change_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $media_event_id="") {

        // display the form


        $record = VideoDatatable::getVideoEventInfo($media_event_id);

        $form['eTitle'] = array(
          '#title' => t('Event Title'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 255,
          '#default_value' => $record->media_event_name,
          '#description' => $this->t('File Share Title'),
          '#required' => TRUE,
        );
        
        $form['eTitle_prev'] = array(
          '#type' => 'hidden',
          '#value' => $record->media_event_name,
        );


        $form['eSortOrder'] = array(
          '#title' => t('Sort Order'),
          '#type' => 'textfield',
          '#size' => 12,
          '#maxlength' => 10,
          '#default_value' => $record->media_event_sequence,
        );
        
        $form['eSortOrder_prev'] = array(
          '#type' => 'hidden',
          '#value' => $record->media_event_sequence,
        );

        $newDate = date("d.m.Y", strtotime($record->media_event_date));  
        $form['eDate'] = array(
          '#title' => t('Event Date'),
          '#type' => 'textfield',
          '#size' => 30,
          '#maxlength' => 10,
          '#default_value' => $newDate,
          '#description' => 'Date Format: DD.MM.YYYY',
          '#required' => TRUE,
        );
        
        $form['eDate_prev'] = array(
          '#type' => 'hidden',
          '#value' => $record->media_event_date,
        );

        if($record->evt_id == "") $record->evt_id = '0';
            
        $activityTypeSelect = array();
        $activityTypeSelect["0"] = "-- Please select --";
        $activityTypeSelect['KM'] = 'KM';
        $activityTypeSelect['PPC'] = 'PPC';

        
        $form['eType'] = array(
          '#title' => t('Event Type'),
          '#type' => 'select',
          '#options' => $activityTypeSelect,
          '#default_value' => $record->evt_type,
          '#attributes' => array('onChange' => 'getAllEventItem(this.value)'),
          '#prefix' => '<div class="div_inline_column">',
          '#suffix' => '</div>',
        );
        
        $form['eType_prev'] = array(
          '#type' => 'hidden',
          '#value' => $record->evt_type,
        );


        $eventSelectAry = VideoDatatable::getAllActivityByEventType($record->evt_type);
        $eventSelect = array();
        foreach ($eventSelectAry as $eventAry) {
          $eventSelect[$eventAry['evt_id']] = $eventAry['evt_name'];
        }        
        
        $form['eId'] = array(
          '#title' => t('Event ID'),
          '#type' => 'select',
          '#options' => $eventSelect,
          '#default_value' =>  $record->evt_id,
          '#validated' => TRUE,
          '#prefix' => '<div class="div_inline_column">',
          '#suffix' => '</div><br>',
        );
        
        $form['eId_prev'] = array(
          '#type' => 'hidden',
          '#default_value' =>  $record->evt_id,
        );

        $this_eid = str_pad($media_event_id, 6, "0", STR_PAD_LEFT);

        $image_path = ($record->media_event_image != "") ? '../system/files/' . $this->module . '/image/' . $this_eid . '/' . $record->media_event_image : $this->default_image;

        $form['eImage'] = array(
          '#title' => t('New Event Image'),
          '#type' => 'file',
          '#description' => '(Size: 540 x 405 pixels)',
          '#prefix' => '<b>Current Event Image</b><br><img src="' . $image_path . '" border="0" width="150">',
        );
        
        $form['eCreatedBy'] = array(
          '#title' => t('Contributor'),
          '#type' => 'textfield',
          '#size' => 20,
          '#maxlength' => 30,
          '#default_value' =>  $record->user_id,
          '#required' => TRUE,
        );

        $form['eVisible'] = array(
          '#title' => t('Visible'),
          '#type' => 'checkbox',
          '#default_value' => 1,
          '#default_value' =>  $record->is_visible,
        );
        
        $form['eVisible_prev'] = array(
          '#type' => 'hidden',
          '#value' => $record->is_visible,
        );
        
        $form['eWmv'] = array(
          '#title' => t('WMV Format'),
          '#type' => 'checkbox',
          '#default_value' => 1,
          '#default_value' => $record->is_wmv,
        );
        
        $form['eWmv_prev'] = array(
          '#type' => 'hidden',
          '#value' => $record->is_wmv,
        );
        
                    
        $taglist = new TagList();
        $tags = $taglist->getTagListByRecordId('video',$media_event_id);
       
        $form['tags'] = array(
          '#title' => t('Tags'),
          '#type' => 'textarea',
          '#rows' => 2,
          '#default_value' => implode(";", $tags),
          '#description' => 'Use semi-colon (;) as separator',
        );

        $form['tags_prev'] = array(
          '#type' => 'hidden',
          '#value' => implode(";", $tags),
        );

        

        $form['media_event_id'] = array(
          '#type' => 'hidden',
          '#value' => $media_event_id,
        );

        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Save'),
        );

        $form['actions']['cancel'] = array(
          '#type' => 'button',
          '#value' => t('Cancel'),
          '#prefix' => '&nbsp;',
          '#attributes' => array('onClick' => 'window.open(\'video_admin\', \'_self\'); return false;'),
          '#limit_validation_errors' => array(),
        );
        

        // Tag List

        $form['t3'] = array(
          '#title' => t('COP Tags'),
          '#type' => 'details',
          '#open' => true,
          '#description' => t($taglist->getListCopTagForModule()),
          '#attributes' => array('style'=>'border: 1px solid #7A7A7A;background: #FCFCE6;'),
        );
        
        $form['t1'] = array(
          '#title' => t('Video Tags'),
          '#type' => 'details',
          '#open' => true,
          '#description' => t($taglist->getList($this->module)),
        );

        $form['t2'] = array(
          '#title' => t('All Tags'),
          '#type' => 'details',
          '#open' => false,
          '#description' => t($taglist->getList('ALL')),
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


        $pos_first = strpos($eDate, ".");
        $pos_last = strrpos($eDate, ".");
        // validate the date format in DD.MM.YYYY
        if ($pos_first == "" || $pos_last == "" || $pos_first == $pos_last) {
            $form_state->setErrorByName(
                'eDate', $this->t("Event Date format is invalid (DD.MM.YYYY)")
            );
        }
        else {
            $dateAry = explode('.', $eDate);
            $validDateFormat = checkdate($dateAry[1], $dateAry[0], $dateAry[2]);

            if (!$validDateFormat) {
                $form_state->setErrorByName(
                    'eDate', $this->t("Event Date is invalid")
                );
            }
        }

      
    }

    //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }


        try {
            $newDateFormat = str_replace('.', '-', $eDate);
            $newDateFormat = date('Y-m-d', strtotime($newDateFormat));
            $creator = ($eCreatedBy == "") ? $this->default_creator : strtoupper($eCreatedBy);

            $entry = array(
              'media_event_id' => $media_event_id,
              'media_event_name' => $eTitle,
              'media_event_sequence' => $eSortOrder,
              'media_event_date' => $newDateFormat,
              'is_visible' => $eVisible,
              'is_wmv' => $eWmv,
              'evt_id' => $eId,
              'evt_type' => $eType,
              'user_id' => $creator,
              //'modify_datetime' => date('Y-m-d H:i:s'),
            );

            // update image only if there is another image is selected
            if (isset($_FILES['files']['name']['eImage']) && $_FILES['files']['name']['eImage'] != "") {
                $entry['media_event_image'] = $_FILES['files']['name']['eImage'];
            }
            
            
            // if any changes in content, update the timestamp
            if($eTitle != $eTitle_prev || $eSortOrder != $eSortOrder_prev || $eDate != $eDate_prev || $eType != $eType_prev || $eId != $eId_prev || $eVisible != $eVisible_prev || $eWmv != $eWmv_prev || (isset($_FILES['files']['name']['eImage']) && $_FILES['files']['name']['eImage'] != "")) {
                $entry['modify_datetime'] = date('Y-m-d H:i:s');
            }

            $return = VideoDatatable::updateVideoEventRecord($entry);

            if ($return) {
                
                if ($tags != $tags_prev) {
                    // rewrite tags
                    if ($tags_prev != '') {
                        $entry2 = array(
                          'module' => $this->module,
                          'module_entry_id' => $media_event_id,
                          'tags' => $tags_prev,
                          'is_deleted' => 1,
                        );
                        $return2 = TagStorage::change($entry2);
                    }

                    if ($tags != '') {
                        $entry1 = array(
                          'module' => $this->module,
                          'module_entry_id' => $media_event_id,
                          'tags' => $tags,
                        );
                        $return1 = TagStorage::insert($entry1);
                    }
                }

                //-----------------------------------
                if ($_FILES['files']['name']['eImage'] != "") {
                    /////////// Handle attachment [Start] /////////////

                    $validators = array(
                      'file_validate_extensions' => array(CommonUtil::getSysValue('default_file_upload_extensions')),
                      'file_validate_size' => array(CommonUtil::getSysValue('default_file_upload_size_limit') * 1024 * 1024),
                    );
                    $delta = NULL; // type of $file will be array
                    $ServerAbsolutePath = CommonUtil::getSysValue('server_absolute_path'); // get server absolute path
                    $newImagePath = CommonUtil::getSysValue('app_path') . '/sites/default/files/private/' . $this->module . '/image';    // store in "Private" folder
                    //$this_uid = $authen->getUId();
                    //$this_uid = str_pad($this_uid, 6, "0", STR_PAD_LEFT);
                    $this_eid = str_pad($media_event_id, 6, "0", STR_PAD_LEFT);

                    $doc_folder = $ServerAbsolutePath . $newImagePath;
                    if (!file_exists($doc_folder)) {
                        mkdir($doc_folder, 0777);
                    }
                    /*
                      $doc_folder .= '/' . $this_uid;
                      if (!file_exists($doc_folder)) {
                      mkdir($doc_folder, 0777);
                      }
                     */
                    $doc_folder .= '/' . $this_eid;
                    if (!file_exists($doc_folder)) {
                        mkdir($doc_folder, 0777);
                    }

                    //$storeLocation = 'private://' . $this->module . '/image/' . $this_uid . '/' . $this_eid;		 // store in "Private" folder
                    $storeLocation = 'private://' . $this->module . '/image/' . $this_eid;   // store in "Private" folder
                    // move attachment from temp folder to destination

                    $doc_file = $_FILES['files']['name']['eImage'];

                    $file = file_save_upload('eImage', $validators, $storeLocation, $delta, FILE_EXISTS_REPLACE);


                    if (!isset($file[0]) or $file[0] == '') {
                        // e.g. doc_folder is missing 
                        $transaction->rollback();
                        drupal_set_message('Event is not created.', 'error');
                        \Drupal::logger('error')->notice('Event is not created (4)');
                        drupal_set_message('File "' . $doc_file . '" not uploaded.', 'error');
                        \Drupal::logger('error')->notice('File is not uploaded (4)');
                        return;
                    }
                    //Change the file status to permanent to prevent from deleting by daily system cron job
                    $file[0]->status = FILE_STATUS_PERMANENT;
                    //save to db
                    $file[0]->save();

                    $_file = explode('.', $doc_file);
                    $file_extension = end($_file);

                    /////////// Handle attachment [End] /////////////
                }
                //-----------------------------------
                // write logs to common log table
                $entry3 = array(
                  'module_name' => $this->module,
                  'record_id' => $media_event_id,
                  'action' => 'Update',
                  'description' => 'Updated event, id=' . $media_event_id . ', title=' . $eTitle,
                  'log_user_id' => $authen->getUserId(),
                );
                $return3 = CommonUtil::InsertLog($entry3);

                // if common log record cannot be created
                if (!$return3) {
                    $transaction->rollback();
                    drupal_set_message(t('Event is not updated.'), 'error');
                    \Drupal::logger('error')->notice('Event is not updated (4)');
                    return;
                }

                drupal_set_message(t('Event is updated. Id.=' . $media_event_id));
            }
            else {
                drupal_set_message(t('Event is not updated.'), 'error');
                \Drupal::logger('error')->notice('Event is not updated (3)');
            }
        }
        catch (Exception $e) {
            $transaction->rollback();
            $variables = Error::decodeException($e);
            drupal_set_message(t('Event is not updated.'), 'error');
            \Drupal::logger('error')->notice('Event is not updated: ' . $variables);
        }
    }

}
