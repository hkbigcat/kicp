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
use Drupal\Core\File\FileSystemInterface;

class VideoAdd extends FormBase {

    public function __construct() {
        $this->module = 'video';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'video_add_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $media_event_id="") {

        // display the form

        $form['vTitle'] = array(
          '#title' => t('Video Title'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 255,
          '#required' => TRUE,
        );

        $form['vDescription'] = array(
          '#title' => t('Description'),
          '#type' => 'textarea',
          '#rows' => 10,
          '#cols' => 30,
          '#required' => TRUE,
        );

        $form['vDuration'] = array(
          '#title' => t('Duration'),
          '#type' => 'textfield',
          '#size' => 20,
          '#maxlength' => 10,
          '#description' => 'Time Format: hh:mm:ss',
          '#required' => TRUE,
        );

        $form['vFilePath'] = array(
          '#title' => t('File Path'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 200,
          '#required' => TRUE,
        );

        $form['vImage'] = array(
          '#type' => 'file',
          '#title' => t('Image'),
        );


        $form['vDate'] = array(
          '#title' => t('Date'),
          '#type' => 'date',
          '#date_date_format' => 'd.m.Y',
          '#date_format' => 'd.m.Y',
//          '#size' => 14,
//          '#maxlength' => 10,
          '#description' => 'Date Format: DD.MM.YYYY',
          '#required' => TRUE,
          '#default_value' => date('Y-m-d'),
        );

        $maxSortOrder = VideoDatatable::getMaxVideoSortOrder($media_event_id);
        $thisSortOrder = $maxSortOrder + 1;

        $form['vSortOrder'] = array(
          '#title' => t('Sort Order'),
          '#type' => 'textfield',
          '#size' => 12,
          '#maxlength' => 10,
          '#default_value' => $thisSortOrder,
        );

        $form['vVisible'] = array(
          '#title' => t('Visible'),
          '#type' => 'checkbox',
          '#default_value' => 1,
        );

        $form['vBan'] = array(
          '#title' => t('Ban on KICP Main Page'),
          '#type' => 'checkbox',
        );

        $form['media_event_id'] = array(
          '#type' => 'hidden',
          '#default_value' => $media_event_id,
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
          '#attributes' => array('onClick' => 'window.open(\'video_list_admin/' . $media_event_id . '\', \'_self\'); return false;'),
          '#limit_validation_errors' => array(),
        );
        

        // Tag List
        $TagList = new TagList();

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

        $pos_first = strpos($vDuration, ":");
        $pos_last = strrpos($vDuration, ":");
        // validate the date format in DD.MM.YYYY
        if ($pos_first == "" || $pos_last == "" || $pos_first == $pos_last) {
            $form_state->setErrorByName(
                'vDuration', $this->t("Time format is invalid hh:mm:ss")
            );
        }
        else {
            $validTimeFormat = CommonUtil::isTime($vDuration);

            if (!$validTimeFormat) {
                $form_state->setErrorByName(
                    'vDuration', $this->t("Duration is invalid")
                );
            }
        }


/*
        $pos_first = strpos($vDate, ".");
        $pos_last = strrpos($vDate, ".");
        // validate the date format in DD.MM.YYYY
        if ($pos_first == "" || $pos_last == "" || $pos_first == $pos_last) {
            $form_state->setErrorByName(
                'vDate', $this->t("Date format is invalid (DD.MM.YYYY)")
            );
        }
        else {
            $dateAry = explode('.', $vDate);
            $validDateFormat = checkdate($dateAry[1], $dateAry[0], $dateAry[2]);

            if (!$validDateFormat) {
                $form_state->setErrorByName(
                    'vDate', $this->t("Date is invalid")
                );
            }
        }
*/

    }

    //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }

        $url = new Url(
            'video.admin_video_content', array(
          'media_event_id' => $_REQUEST['media_event_id'],
            )
            );

        try {
            $newDateFormat = str_replace(':', '-', $vDate);
            $newDateFormat = date('Y-m-d', strtotime($newDateFormat));

            $entry = array(
              'media_title' => $vTitle,
              'media_description' => $vDescription,
              'media_duration' => $vDuration,
              'media_postdate' => $newDateFormat,
              'counter' => 0,
              'media_file_path' => $vFilePath,
              'is_banned' => $vBan,
              'is_visible' => $vVisible,
              'media_img' => $_FILES['files']['name']['vImage'],
              'sort_field' => $vSortOrder,
              'media_event_id' => $media_event_id,
            );

            $query = \Drupal::database()->insert('kicp_media_info')
            ->fields( $entry);
            $eId = $query->execute();

            if ($eId) {
                
                if ($tags != '') {
                    $entry1 = array(
                      'module' => 'video_video',
                      'module_entry_id' => intval($eId),
                      'tags' => $tags,
                    );
                    $return1 = TagStorage::insert($entry1);
                }
                
                //-----------------------------------------------------------------------------------
                if ($_FILES['files']['name']['vImage'] != "") {
                    /////////// Handle attachment [Start] /////////////

                    $this_eid = str_pad($media_event_id, 6, "0", STR_PAD_LEFT);

                    $file_system = \Drupal::service('file_system');   
                    $vImage_path = 'private://video/video_image/'.$this_eid;
                    if (!is_dir($file_system->realpath($vImage_path))) {
                        // Prepare the directory with proper permissions.
                        if (!$file_system->prepareDirectory($vImage_path, FileSystemInterface::CREATE_DIRECTORY)) {
                          throw new \Exception('Could not create the eImage directory.');
                        }
                    }
                      
                    $validators = array(
                        'file_validate_extensions' => array(CommonUtil::getSysValue('default_file_upload_extensions')),
                        'file_validate_size' => array(CommonUtil::getSysValue('default_file_upload_size_limit') * 1024 * 1024),
                      );
                    
                    $delta = NULL; // type of $file will be array
                    $file = file_save_upload('vImage', $validators, $vImage_path, $delta);
              
                    $file[0]->setPermanent();
                    $file[0]->uid = $eId;
                    $file[0]->save();
                    $url = $file[0]->createFileUrl(FALSE);

                    /////////// Handle attachment [End] /////////////
                }
                //-----------------------------------------------------------------------------------
                // write logs to common log table

                \Drupal::logger('video')->info('Created Video id: %id, title: %title, filename: %filename ',   
                array(
                    '%id' => $eId,
                    '%title' => $$vTitle,
                    '%filename' => $filename,
                ));     
        
                $url = Url::fromUserInput('/video_list_admin/'.$media_event_id);
                $form_state->setRedirectUrl($url);
        
                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('Video has been added. '));

            }
            else {
                \Drupal::messenger()->addError(
                    t('Video is not created. ' )
                    );
                    \Drupal::logger('video')->error('Video is not created (3)');
            }
        }
        catch (Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Video is not created. ' )
                );
              \Drupal::logger('video')->error('Video is not created '  . $variables);   

        }
    }

}
