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
use Drupal\common\Controller\TagList;
use Drupal\common\Controller\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\video\Common\VideoDatatable;
use Drupal\video\Controller\VideoController;
use Drupal\Core\File\FileSystemInterface;


class VideoEventAdd extends FormBase {

    public function __construct() {
        $this->module = 'video';
        $this->default_creator = 'KMU.OGCIO';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'video_event_add_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        
        // display the form

        $form['eTitle'] = array(
          '#title' => t('Event Title'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 255,
          '#required' => TRUE,
        );

        $maxSortOrder = VideoDatatable::getMaxEventSortOrder();
        $thisSortOrder = $maxSortOrder + 1;

        $form['eSortOrder'] = array(
          '#title' => t('Sort Order'),
          '#type' => 'textfield',
          '#size' => 12,
          '#maxlength' => 10,
          '#default_value' => $thisSortOrder,
        );

        $form['eDate'] = array(
          '#title' => t('Event Date'),
          '#type' => 'textfield',
          '#size' => 14,
          '#maxlength' => 10,
          '#default_value' => date('d.m.Y'),
          '#description' => 'Date Format: DD.MM.YYYY',
          '#required' => TRUE,
        );


        $activityTypeSelect = array();
        $activityTypeSelect["0"] = "-- Please select --";
        $activityTypeSelect['KM'] = 'KM';
        $activityTypeSelect['PPC'] = 'PPC';

        $form['eType'] = array(
          '#title' => t('Event Type'),
          '#type' => 'select',
          '#options' => $activityTypeSelect,
          '#attributes' => array('onChange' => 'getAllEventItem(this.value)'),
          '#prefix' => '<div class="div_inline_column">',
          '#suffix' => '</div>',
        );

        $eventSelect = array(0=>'Please select Event');
        
        $form['eId'] = array(
          '#title' => t('Event ID'),
          '#type' => 'select',
          '#options' => $eventSelect,
          '#validated' => TRUE,
          '#prefix' => '<div class="div_inline_column">',
          '#suffix' => '</div><br>',
        );

        $form['eImage'] = array(
          '#title' => t('Event Image'),
          '#type' => 'file',
          '#description' => '(Size: 540 x 405 pixels)',
        );
        
        $form['eCreatedBy'] = array(
          '#title' => t('Contributor'),
          '#type' => 'textfield',
          '#size' => 20,
          '#maxlength' => 30,
          '#default_value' => $this->default_creator,
          '#required' => TRUE,
        );

        $form['eVisible'] = array(
          '#title' => t('Visible'),
          '#type' => 'checkbox',
          '#default_value' => 1,
        );
        
        $form['eWmv'] = array(
          '#title' => t('WMV Format'),
          '#type' => 'checkbox',
          '#default_value' => 1,
        );
        
        $form['tags'] = array(
          '#title' => t('Tags'),
          '#type' => 'textarea',
          '#rows' => 2,
          '#description' => 'Use semi-colon (;) as separator',
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
        $taglist = new TagList();

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
              'media_event_image' => $_FILES['files']['name']['eImage'],
              'evt_id' => $eId, // ********  To be replaced by actual event_id
              'evt_type' => $eType,
              'user_id' => $creator,
            );

            $query = \Drupal::database()->insert('kicp_media_event_name')
            ->fields( $entry);
            $eId = $query->execute();

            if ($eId) {                
                if ($tags != '') {
                    $entry1 = array(
                      'module' => $this->module,
                      'module_entry_id' => intval($eId),
                      'tags' => $tags,
                    );
                    $return1 = TagStorage::insert($entry1);
                }                
                //-----------------------------------------------------------------------------------
                if ($_FILES['files']['name']['eImage'] != "") {
                    /////////// Handle attachment [Start] /////////////
                  $this_eid = str_pad($eId, 6, "0", STR_PAD_LEFT);
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


                    /////////// Handle attachment [End] /////////////
                }
                //-----------------------------------------------------------------------------------
                // write logs to common log table
                \Drupal::logger('video')->info('Created id: %id, title: %title ',   
                array(
                    '%id' => $eId,
                    '%title' => $eTitle,
                ));     
        
                $url = Url::fromUserInput('/video_admin/');
                $form_state->setRedirectUrl($url);
        
                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('Video Event has been added. '));
            }
            else {
              \Drupal::messenger()->addError(
                t('Event (Video) is not created. ' )
                );
                \Drupal::logger('video')->error('Event (Video) is not created (3)');
            }
        }
        catch (Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
              t('Event (Video) is not created. ' )
              );
            \Drupal::logger('video')->error('Event (Video) is not created '  . $variables);    

        }
    }

}
