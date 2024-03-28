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


class VideoChange extends FormBase {

    public function __construct() {
        $this->module = 'video';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'video_change_form';
        $this->default_image = 'sites/default/files/public/default/img_no_image.gif';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $media_id="") {


        $VideoInfo = VideoDatatable::getVideoInfo($media_id);
        $media_event_id = $VideoInfo['media_event_id'];

        $form['vTitle'] = array(
          '#title' => t('Video Title'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 255,
          '#default_value' => $VideoInfo['media_title'],
          '#required' => TRUE,
        );
        
        $form['vTitle_prev'] = array(
            '#type' => 'hidden',
            '#value' => $VideoInfo['media_title'],
        );

        $form['vDescription'] = array(
          '#title' => t('Description'),
          '#type' => 'textarea',
          '#rows' => 10,
          '#cols' => 30,
          '#default_value' => $VideoInfo['media_description'],
          '#required' => TRUE,
        );
        
        $form['vDescription_prev'] = array(
            '#type' => 'hidden',
            '#value' => $VideoInfo['media_description'],
        );

        $form['vDuration'] = array(
          '#title' => t('Duration'),
          '#type' => 'textfield',
          '#size' => 20,
          '#maxlength' => 10,
          '#description' => 'Time Format: hh:mm:ss',
          '#default_value' => $VideoInfo['media_duration'],
          '#required' => TRUE,
        );
        
        $form['vDuration_prev'] = array(
            '#type' => 'hidden',
            '#value' => $VideoInfo['media_duration'],
        );

        $form['vFilePath'] = array(
          '#title' => t('File Path'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 200,
          '#default_value' => $VideoInfo['media_file_path'],
          '#required' => TRUE,
          
        );
        
        $form['vFilePath_prev'] = array(
            '#type' => 'hidden',
            '#value' => $VideoInfo['media_file_path'],
        );

        $this_eid = str_pad($media_event_id, 6, "0", STR_PAD_LEFT);

        $image_path = ($VideoInfo['media_img'] != "") ? '../system/files/' . $this->module . '/video_image/' . $this_eid . '/' . $VideoInfo['media_img'] : $this->default_image;

        $form['vImage'] = array(
          '#type' => 'file',
          '#title' => t('New Image'),
          '#prefix' => '<b>Current Image</b><br><img src="' . $image_path . '" border="0" width="150"> ',
        );

        $form['vDate'] = array(
          '#title' => t('Date'),
          '#type' => 'date',
          '#size' => 14,
          '#maxlength' => 10,
          '#default_value' => date('Y-m-d', strtotime($VideoInfo['media_postdate'])),
          '#description' => 'Date Format: DD/MM/YYYY',
          '#required' => TRUE,
        );
        
        $form['vDate_prev'] = array(
            '#type' => 'hidden',
            '#value' => $VideoInfo['media_postdate'],
        );

        $maxSortOrder = VideoDatatable::getMaxVideoSortOrder($media_event_id);
        $thisSortOrder = $maxSortOrder + 1;

        $form['vSortOrder'] = array(
          '#title' => t('Sort Order'),
          '#type' => 'textfield',
          '#size' => 12,
          '#maxlength' => 10,
          '#default_value' => $VideoInfo['sort_field'],
        );
        
        $form['vSortOrder_prev'] = array(
            '#type' => 'hidden',
            '#value' => $VideoInfo['sort_field'],
        );

        $form['vVisible'] = array(
          '#title' => t('Visible'),
          '#type' => 'checkbox',
          '#default_value' => $VideoInfo['is_visible'],
        );
        
        $form['vVisible_prev'] = array(
            '#type' => 'hidden',
            '#value' => $VideoInfo['is_visible'],
        );

        $form['vBan'] = array(
          '#title' => t('Ban on KICP Main Page'),
          '#type' => 'checkbox',
          '#default_value' => $VideoInfo['is_banned'],
        );
        
        $form['vBan_prev'] = array(
            '#type' => 'hidden',
            '#value' => $VideoInfo['is_banned'],
        );
            
        $taglist = new TagList();
        $tags = $taglist->getTagListByRecordId('video_video', $media_id, $returnInArray=0);
        
        $form['tags'] = array(
            '#title' => t('Tags'),
            '#type' => 'textarea',
            '#rows' => 2,
            '#default_value' => implode(";", $tags),
            '#description' => 'Use semi-colon (;) as separator',
        );

        $form['tags_prev'] = array(
            '#type' => 'hidden',
            '#default_value' => implode(";", $tags),
        );


        $form['media_id'] = array(
          '#type' => 'hidden',
          '#value' => $media_id,
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
          '#attributes' => array('onClick' => 'window.open(\'../video_list_admin/' . $media_event_id . '\', \'_self\'); return false;'),
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

            $newDateFormat = str_replace(':', '-', $vDate);
            $newDateFormat = date('Y-m-d', strtotime($newDateFormat));
            
            $entry = array(
              'media_title' => $vTitle,
              'media_description' => $vDescription,
              'media_duration' => $vDuration,
              'media_postdate' => $newDateFormat,
              //'counter' => 0,
              'media_file_path' => $vFilePath,
              'is_banned' => $vBan,
              'is_visible' => $vVisible,
              'sort_field' => $vSortOrder,
              'media_event_id' => $media_event_id,
              //'modify_datetime' => date('Y-m-d H:i:s'),
            );

            // if any changes in content, update the timestamp
            if($vTitle != $vTitle_prev || $vDescription != $vDescription_prev || $vDuration != $vDuration_prev || $vDate != $vDate_prev || $vFilePath != $vFilePath_prev || $vSortOrder != $vSortOrder_prev || $vVisible != $vVisible_prev || $vBan != $vBan_prev || (isset($_FILES['files']['name']['vImage']) && $_FILES['files']['name']['vImage'] != "")) {
                $entry['modify_datetime'] = date('Y-m-d H:i:s');
            }

            // update image only if there is another image is selected
            if (isset($_FILES['files']['name']['vImage']) && $_FILES['files']['name']['vImage'] != "") {
                $entry['media_img'] = $_FILES['files']['name']['vImage'];
            }
            
            $query = \Drupal::database()->update('kicp_media_info')
            ->fields( $entry)
            ->condition('media_id', $media_id);
            $vId = $query->execute();

            if ($vId) {

                if ($tags != $tags_prev) {                    
                    // rewrite tags
                    if ($tags_prev != '') {
                        $return2 = TagStorage::markDelete('video_vide', $media_id);
                    }

                    if ($tags != '') {
                        $entry1 = array(
                          'module' => 'video_video',
                          'module_entry_id' => $media_id,
                          'tags' => $tags,
                        );
                        $return1 = TagStorage::insert($entry1);
                    }
                }
                
                $filename = " / ";
                //-----------------------------------
                if ($_FILES['files']['name']['vImage'] != "") {
                    /////////// Handle attachment [Start] /////////////
                    $this_eid = str_pad($media_event_id, 6, "0", STR_PAD_LEFT);

                    $file_system = \Drupal::service('file_system');   
                    $vImage_path = 'private://video/video_image/'.$this_eid;
                    if (!is_dir($file_system->realpath($vImage_path))) {
                        // Prepare the directory with proper permissions.
                        if (!$file_system->prepareDirectory($vImage_path, FileSystemInterface::CREATE_DIRECTORY)) {
                          throw new \Exception('Could not create the vImage directory.');
                        }
                    }
                      
                    $validators = array(
                        'file_validate_extensions' => array(CommonUtil::getSysValue('default_file_upload_extensions')),
                        'file_validate_size' => array(CommonUtil::getSysValue('default_file_upload_size_limit') * 1024 * 1024),
                      );
                    
                    $delta = NULL; // type of $file will be array
                    $file = file_save_upload('vImage', $validators, $vImage_path, $delta);
              
                    $file[0]->setPermanent();
                    $file[0]->uid = $media_id;
                    $file[0]->save();
                    $url = $file[0]->createFileUrl(FALSE);

                    $filename = $_FILES['files']['name']['vImage'];

                    /////////// Handle attachment [End] /////////////
                }
                //-----------------------------------
                // write logs to common log table
                \Drupal::logger('video')->info('updated Video id: %id, title: %title, filename: %filename ',   
                array(
                    '%id' => $media_id,
                    '%title' => $vTitle,
                    '%filename' => $filename,
                ));     

                $url = Url::fromUserInput('/video_list_admin/'.$media_event_id);
                $form_state->setRedirectUrl($url);

                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('Video has been added. '));
            }
            else {
                \Drupal::messenger()->addError(
                    t('Video is not updated. ' )
                    );
                    \Drupal::logger('video')->error('Video is not udpated (3)');
            }
        }
        catch (Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Video is not updated. ' )
                );
              \Drupal::logger('video')->error('Video is not updated '  . $variables);   
        }
    }

}
