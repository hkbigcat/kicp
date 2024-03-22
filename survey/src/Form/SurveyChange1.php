<?php

/**
 * @file
 */

namespace Drupal\survey\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal;
use Drupal\Component\Utility\UrlHelper;
use Drupal\common\RatingData;
use Drupal\common\Controller\TagList;
use Drupal\common\Controller\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\survey\Common\SurveyDatatable;
use Drupal\file\FileInterface;
use Drupal\file\Entity\File;
use Drupal\common\AccessControl;


class SurveyChange1 extends FormBase {

    public function __construct() {
        $this->module = 'survey';
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->my_user_id = $authen->getUserId();    
        $this->allow_file_type = CommonUtil::getSysValue('survey_allow_file_type');     
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'survey_edit_form1';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $survey_id="") {
        // display the form

        $form['#attributes'] = array('enctype' => 'multipart/form-data');

        $survey = SurveyDatatable::getSurvey($survey_id,  $this->my_user_id);

        if ($survey->title == null) {
          $form['intro'] = array(
            '#markup' => t('<i style="font-size:20px; color:red; margin-right:10px;" class="fa-solid fa-ban"></i> Survey not found'),
          );
          return $form; 
         }

        // File path
        $this_survey_id = str_pad($survey_id, 6, "0", STR_PAD_LEFT);

        $file_path = 'download?module=survey&file_id=' . $survey_id . '&fname=' . $survey->file_name;

        $expiry_date_formated = explode(" ", $survey->expiry_date);
        $start_date_formated = explode(" ", $survey->start_date);
        $dateType_start = new \DateTime($survey->start_date);
        $dateType_expiry = new \DateTime($survey->expiry_date);
        $currentdate = new \DateTime(date('Y-m-d 00:00:00'));
//page 1        
        $form['title'] = array(
          '#title' => t('Title<'),
          '#type' => 'textfield',
          '#size' => 150,
          '#maxlength' => 200,
          '#prefix' => '<div id="div_step1">',
          '#default_value' => $survey->title,
          '#required' => TRUE,
        );

        $form['startdate'] = array(
          '#title' => t('Start Date:'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 200,
          '#attributes' => array('id' => 'edit-startdate', 'readonly' => 'readonly'), //startdate
          '#default_value' => $start_date_formated[0],
          '#required' => TRUE,
        );
        if ($currentdate <= $dateType_start) {
            $datestring = ' <td align="left">'
                . '<img id="CalStart" style="vertical-align: middle;" src="../modules\custom\common\images\btn_calendar.gif" onclick="changeOutput(\'edit-startdate\');createCalendar(this);showCalendar();"/><div id="calendarDiv"  style="width:auto; height:auto; background-color:#FFFFFF; position:absolute;float:left; visibility:hidden; z-index:1;"></div>'
                . '<a id="calendarLink" name="calendarLink"></a></td>'
                . '<br>';
            $form['date_startmarkup'] = array(
              '#markup' => t($datestring),
            );
        }
        $form['expirydate'] = array(
          '#title' => t('Expiry Date:'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 200,
          '#attributes' => array('id' => 'edit-expirydate', 'readonly' => 'readonly'), //expirydate
          '#default_value' => $expiry_date_formated[0],
          '#required' => TRUE,
        );


        $datestring2 = ' <td align="left">'
            . '<img id="CalStart" style="vertical-align: middle;" src="../modules\custom\common\images\btn_calendar.gif" onclick="changeOutput(\'edit-expirydate\');createCalendar(this);showCalendar();"/><div id="calendarDiv"  style="width:auto; height:auto; background-color:#FFFFFF; position:absolute;float:left; visibility:hidden; z-index:1;"></div>'
            . '<a id="calendarLink" name="calendarLink"></a></td>'
            . '<br>';
        $form['date_expirymarkup'] = array(
          '#markup' => t($datestring2),
        );



        $form['description'] = array(
          '#title' => t('Description'),
          '#type' => 'text_format',
          '#format' => 'basic_html',
          '#rows' => 5,
          '#attributes' => array('style' => 'height:300px;'),
          '#default_value' => $survey->description,
          '#prefix' => '<div style="margin-top:50px;">&nbsp;</div>',
          '#required' => TRUE,
        );
        if ($survey->file_name != '') {
            $form['filePath'] = array(
              '#markup' => '<a href="' . $file_path . '" target="_blank"><img src="../modules/custom/common/images/icon_attachment.png" border="0" align="absmiddle">' . $survey->file_name . '</a><br>'
            );
            $form['deleteFile'] = array(
              '#title' => t('Delete original file?'),
              '#type' => 'checkbox',
              '#attributes' => array('style' => 'display:inline-block;float:left;'),
              '#default_value' => 0,
            );

            $form['filename'] = array(
              '#title' => t('Choose File to overwirte the exiting file'),
              '#type' => 'file',
              '#size' => 150,
              '#description' => 'Only support ' . str_replace(' ', ', ', $this->allow_file_type) . ' file format',
                //'#required' => true,
            );
        } else {
            $form['filename'] = array(
              '#title' => t('File'),
              '#type' => 'file',
              '#size' => 150,
              '#description' => 'Only support ' . str_replace(' ', ', ', $this->allow_file_type) . ' file format',
                //'#required' => true,
            );
        }
        $form['ReadyVote'] = array(
          '#title' => t('Ready for voting'),
          '#type' => 'checkbox',
          '#default_value' => $survey->is_visible,
        );
        
          $form['Allowcopy'] = array(
          '#title' => t('Allow copy:'),
          '#type' => 'checkbox',
          '#default_value' => $survey->allow_copy,
          );

         
        $form['lable'] = array(
          '#markup' => t('Select the information to be included into the report (CSV file) :'),
        );
        $form['Votername'] = array(
          '#title' => t('Voter\'s name'),
          '#type' => 'checkbox',
          '#attributes' => array('style' => 'display:inline-block;float:left;margin-right:10px;'),
          '#prefix' => '<div class="div_inline_column">',
          '#default_value' => $survey->is_showname,
        );
        $form['PostUnit'] = array(
          '#title' => t('Post Unit'),
          '#type' => 'checkbox',
          '#attributes' => array('style' => 'display:inline-block;float:left;margin-right:10px;'),
          '#default_value' => $survey->is_showPost,
        );
        $form['Department'] = array(
          '#title' => t('Department'),
          '#type' => 'checkbox',
          '#attributes' => array('style' => 'display:inline-block;float:left;margin-right:10px;'),
          '#suffix' => '</div><br>',
          '#default_value' => $survey->is_showDep,
        );


        $taglist = new TagList();
        $tags = $taglist->getTagListByRecordId($this->module, $survey_id);

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


        $form['survey_id'] = array(
          '#type' => 'hidden',
          '#value' => $survey_id,
        );

          // Tag List
          $taglist = new TagList();

          $form['t3'] = array(
            '#title' => t('COP Tags'),
            '#type' => 'details',
            '#open' => true,
            '#description' => t($taglist->getListCopTagForModule()),
            '#attributes' => array('style' => 'border: 1px solid #7A7A7A;background: #FCFCE6;'),
          );

          $form['t1'] = array(
            '#title' => t('Survey Tags'),
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

          $form['hiddenCount'] = array(
            '#type' => 'hidden',
            '#value' => $survey->start_survey,
            '#attributes' => array('id' => 'hiddenCount'),
          );


          $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'),
          );

          $form['actions']['cancel'] = array(
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#prefix' => '&nbsp;',
            '#attributes' => array('onClick' => 'history.go(-1); return false;'),
            '#limit_validation_errors' => array(),
          );
        
        return $form;
    }

    //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

       
        $survey = SurveyDatatable::getSurvey($survey_id);

        // File path
        $this_survey_id = str_pad($survey_id, 6, "0", STR_PAD_LEFT);
        $file_path = 'system/files/' . $this->module . '/' . $this_survey_id . '/' . $survey->file_name;

        $expiry_date_formated = explode(" ", $survey->expiry_date);
        $start_date_formated = explode(" ", $survey->start_date);
        $dateType_oldstart = new \DateTime($survey->start_date);
        $dateType_oldexpiry = new \DateTime($survey->expiry_date);
        $currentdate = new \DateTime(date('Y-m-d 00:00:00'));

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }

        $hasError = false;

        $dateType_start = new \DateTime($startdate);
        $dateType_expiry = new \DateTime($expirydate);
        $currentdate = new \DateTime(date('Y-m-d 00:00:00'));

        if (isset($startdate) && ($currentdate > $dateType_start ) && ($currentdate < $dateType_oldstart)) {
            $form_state->setErrorByName(
                'startdate', $this->t("Start Date should not be earlier than today.")
            );
            $hasError = true;
        }
        //  print_r($currentdate);
        if (isset($startdate) && ($currentdate > $dateType_expiry )) {
            $form_state->setErrorByName(
                'expirydate', $this->t("Expiry Date should not be earlier than today.")
            );
            $hasError = true;
        }

        if (isset($expirydate) && ($dateType_expiry < $dateType_start)) {
            $form_state->setErrorByName(
                'expirydate', $this->t("Survey Expriy date should later than Start date")
            );
            $hasError = true;
        }

        // tags
        if (isset($tags) and $tags != '') {

            if (strlen($tags) > 1024) {
                $form_state->setErrorByName(
                    'tags', $this->t("Length of tags > 1024")
                );
                $hasError = true;
            }
        }

        $tmp_name = $_FILES["files"]["tmp_name"]['filename'];
        $this_filename = CommonUtil::file_remove_character($_FILES["files"]["name"]['filename']);
        $file_ext = strtolower(pathinfo($this_filename, PATHINFO_EXTENSION));
        if ($_FILES['files']['name']['filename'] != "") {
            if (!in_array($file_ext, explode(' ', $this->allow_file_type))) {
                $form_state->setErrorByName(
                    'filename', $this->t("File format not supported.")
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

		    $deleteFile = '';
	
        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }

        $entry = array(
          'title' => $title,
          'description' => $description['value'],
          'start_date' => $startdate,
          'expiry_date' => $expirydate . " 23:59:59",
          'modify_datetime' => date('Y-m-d H:i:s'),
          'is_visible' => $ReadyVote,
          'allow_copy' => $Allowcopy,
          'is_showDep' => $Department,
          'is_showPost' => $PostUnit,
          'is_showname' => $Votername,
          'modified_by' => $this->my_user_id,
          'survey_id' => $survey_id,
        );


        $database = \Drupal::database();
        try {
            //*************** File [Start]

            $starttime = time();
            if ($_FILES['files']['name']['filename'] != "") {
              $this_filename = CommonUtil::file_remove_character($_FILES["files"]["name"]['filename']);
              $entry['file_name'] = $this_filename;
            }

            $query = $database->update('kicp_survey')->fields( $entry)
            ->condition('survey_id', $survey_id)
            ->execute();    
            
            // if file is selected-------------------------------------------------------------------------
            if ($_FILES['files']['name']['filename'] != "") {
              SurveyDatatable::saveAttach( $_FILES['files']['name']['filename'], $this_filename, $survey_id);
            }

            if ($tags != $tags_prev) {
              // rewrite tags
              if ($tags_prev != '') {
                  $query = $database->update('kicp_tags')->fields([
                      'is_deleted'=>1 , 
                    ])
                    ->condition('fid', $survey_id)
                    ->condition('module', 'survey')
                    ->execute();                
              }
              if ($tags != '') {
                  $entry1 = array(
                      'module' => 'survey',
                      'module_entry_id' => intval($survey_id),
                      'tags' => $tags,
                    );
                    $return1 = TagStorage::insert($entry1);                
              }
            }

            $questionNo = 1;
            $request = \Drupal::request();
            $session = $request->getSession();
            $session->set('questionNo', $questionNo);

            $_SESSION['survey_id'] = $survey_id;
            if ($hiddenCount > 0) {
                $url = new Url('survey.survey_content');
                $form_state->setRedirectUrl($url);

                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('Survey is started, Questions cannot be edited '));  

            } else {
                $url = Url::fromUserInput('/survey_add_page2/');
            }

            $form_state->setRedirectUrl($url);
        } catch (Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
              t('Unable to update survey at this time due to datbase error. Please try again. '.$variables)
            ); 
        }
    }

}
