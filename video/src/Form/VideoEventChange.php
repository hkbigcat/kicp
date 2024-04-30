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

        //$newDate = date("d.m.Y", strtotime($record->media_event_date));  
        $form['eDate'] = array(
          '#title' => t('Event Date'),
          '#type' => 'date',
          '#date_date_format' => 'd.m.Y',
          '#date_format' => 'd.m.Y',             
          '#size' => 30,
          '#maxlength' => 10,
          '#default_value' => date('Y-m-d',$newDate),
          '#description' => 'Date Format: DD/MM/YYYY',
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
        
        
        // Tag List
        $TagList = new TagList();        
        $tags = $TagList->getTagListByRecordId('video',$media_event_id);
       
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
          '#title' => t('Video Tags'),
          '#type' => 'details',
          '#open' => true,
          '#description' =>  $taglist,
        );

        $taglist = $TagList->getList('ALL');
        $form['t2'] = array(
          '#title' => t('All Tags'),
          '#type' => 'details',
          '#open' => false,
          '#description' =>  $taglist,
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

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }


        try {
            $newDateFormat = str_replace('.', '-', $eDate);
            $newDateFormat = date('Y-m-d', strtotime($newDateFormat));
            $creator = ($eCreatedBy == "") ? $this->default_creator : strtoupper($eCreatedBy);

            $entry = array(
              'media_event_name' => $eTitle,
              'media_event_sequence' => $eSortOrder,
              'media_event_date' => $newDateFormat,
              'is_visible' => $eVisible,
              'is_wmv' => $eWmv,
              'evt_id' => $eId,
              'evt_type' => $eType,
              'user_id' => $creator,
            );

            // update image only if there is another image is selected
            if (isset($_FILES['files']['name']['eImage']) && $_FILES['files']['name']['eImage'] != "") {
                $entry['media_event_image'] = $_FILES['files']['name']['eImage'];
            }
            
            
            // if any changes in content, update the timestamp
            if($eTitle != $eTitle_prev || $eSortOrder != $eSortOrder_prev || $eDate != $eDate_prev || $eType != $eType_prev || $eId != $eId_prev || $eVisible != $eVisible_prev || $eWmv != $eWmv_prev || (isset($_FILES['files']['name']['eImage']) && $_FILES['files']['name']['eImage'] != "")) {
                $entry['modify_datetime'] = date('Y-m-d H:i:s');
            }

            $query = \Drupal::database()->update('kicp_media_event_name')
            ->fields( $entry)
            ->condition ('media_event_id',$media_event_id );
            $eId = $query->execute();

            if ($eId) {
                
                if ($tags != $tags_prev) {
                    // rewrite tags
                    if ($tags_prev != '') 
                    {
                      // delete tags
                      $return2 = TagStorage::markDelete($this->module, $media_event_id);
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

                $filename = "/";
                //-----------------------------------
                if ($_FILES['files']['name']['eImage'] != "") {
                    /////////// Handle attachment [Start] /////////////

                    $this_eid = str_pad($media_event_id, 6, "0", STR_PAD_LEFT);
                    $file_system = \Drupal::service('file_system');   
                    $eImage_path = 'private://video/image/'.$this_eid;
                    if (!is_dir($file_system->realpath($eImage_path))) {
                        // Prepare the directory with proper permissions.
                        if (!$file_system->prepareDirectory($eImage_path, FileSystemInterface::CREATE_DIRECTORY)) {
                          throw new \Exception('Could not create the eImage directory.');
                        }
                    }
                      
                    $validators = array(
                        'file_validate_extensions' => array(CommonUtil::getSysValue('default_file_upload_extensions')),
                        'file_validate_size' => array(CommonUtil::getSysValue('default_file_upload_size_limit') * 1024 * 1024),
                      );
                    
                    $delta = NULL; // type of $file will be array
                    $file = file_save_upload('eImage', $validators, $eImage_path, $delta);
              
                    $file[0]->setPermanent();
                    $file[0]->uid = $eId;
                    $file[0]->save();
                    $url = $file[0]->createFileUrl(FALSE);                    
                    $filename = $_FILES['files']['name']['eImage'];

                    /////////// Handle attachment [End] /////////////
                }
                //-----------------------------------
                // write logs to common log table
                \Drupal::logger('video')->info('Updated id: %id, title: %title, file: %filename',   
                array(
                    '%id' => $eId,
                    '%title' => $eTitle,
                    '%filename' => $filename,
                ));    

                $url = Url::fromUserInput('/video_admin/');
                $form_state->setRedirectUrl($url);
        
                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('Video Event has been update: '.$media_event_id));

            }
            else {
              \Drupal::messenger()->addError(
                t('Event  is not updated. ' )
                );
                \Drupal::logger('video')->error('Event is not updated (3)');

                drupal_set_message(t('Event is not updated.'), 'error');
                \Drupal::logger('error')->notice('Event is not updated (3)');
            }
        }
        catch (Exception $e) {
          $variables = Error::decodeException($e);
          \Drupal::messenger()->addError(
            t('Event  is not updated. ' )
            );
          \Drupal::logger('video')->error('Event is not updated '  . $variables);   
        }
    }

}
