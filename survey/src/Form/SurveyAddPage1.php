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
use Drupal\common\TagList;
use Drupal\common\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\survey\Common\SurveyDatatable;
use Drupal\file\FileInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity;
use Drupal\Core\Utility\Error;

class SurveyAddPage1 extends FormBase {

    public function __construct() {
        $this->allow_file_type = CommonUtil::getSysValue('survey_allow_file_type');
        $this->module = 'survey';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'survey_add_form1';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $request = \Drupal::request();
        $session = $request->getSession();
        $session->set('questionNo', "");
        $session->set('totalQuestionNo', "");
        $_SESSION['questionNo'] = "";
        $_SESSION['totalQuestionNo'] = "";
        $output = NULL;

        // display the form

        $form['#attributes'] = array('enctype' => 'multipart/form-data');
//page 1        
        $form['title'] = array(
          '#title' => t('Title'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 200,
          '#prefix' => '<div id="div_step1">',
          '#required' => TRUE,
        );

        $form['startdate'] = array(
          '#title' => t('Start Date:'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 200,
          '#attributes' => array('id' => 'edit-startdate'), 
          '#required' => TRUE,
          '#default_value' => date('Y-m-d'),
        );

        $datestring = ' <td align="left">'
            . '<img id="CalStart" style="vertical-align: middle;" src="modules\custom\common\images\btn_calendar.gif" onclick="changeOutput(\'edit-startdate\');createCalendar(this);showCalendar();"/><div id="calendarDiv"  style="width:auto; height:auto; background-color:#FFFFFF; position:absolute;float:left; visibility:hidden; z-index:1;"></div>'
            . '<a id="calendarLink" name="calendarLink"></a></td>'
            . '<br>';
        $form['date_startmarkup'] = array(
          '#markup' => t($datestring),
        );
        $form['expirydate'] = array(
          '#title' => t('Expiry Date:'),
          '#type' => 'textfield',
          '#size' => 90,
          '#maxlength' => 200,
          '#prefix' => '<div style="margin-top:50px;">&nbsp;</div>',
         '#attributes' => array('id' => 'edit-expirydate', 'readonly' => 'readonly'), 
         '#required' => TRUE,
        );


        $datestring2 = ' <td align="left">'
            . '<img id="CalStart" style="vertical-align: middle;" src="modules\custom\common\images\btn_calendar.gif" onclick="changeOutput(\'edit-expirydate\');createCalendar(this);showCalendar();"/><div id="calendarDiv"  style="width:auto; height:auto; background-color:#FFFFFF; position:absolute;float:left; visibility:hidden; z-index:1;"></div>'
            . '<a id="calendarLink" name="calendarLink"></a></td>'
            . '<br>';
        $form['date_expirymarkup'] = array(
          '#markup' => t($datestring2),
        );

        $form['description'] = array(
          '#title' => t('Description'),
          '#type' => 'text_format',
          '#format' => 'basic_html', //full_html_survey
          '#allowed_formats' => ['basic_html'],
          '#rows' => 5,
          '#prefix' => '<div style="margin-top:50px;">&nbsp;</div>',
          '#attributes' => array('style' => 'height:300px;'),
          '#required' => TRUE,
        );

        $form['filename'] = array(
          '#title' => t('File'),
          '#type' => 'file',
          '#size' => 150,
          '#description' => 'Only support ' . str_replace(' ', ', ', $this->allow_file_type) . ' file format',
          '#required' => false,
        );


        $form['ReadyVote'] = array(
          '#title' => t('Ready for voting'),
          '#description' => t('If disabled, the survey can be accessed but cannot be submitted.'),
          '#type' => 'checkbox',
          '#default_value' => 1,
        );

        $form['Allowcopy'] = array(
          '#title' => t('Allow copy:'),
          '#type' => 'checkbox',
          '#default_value' => 1,
        );


        $form['lable'] = array(
          '#markup' => t('Select the information to be included into the report (CSV file) :'),
        );
        $form['Votername'] = array(
          '#title' => t('Voter\'s name'),
          '#type' => 'checkbox',
          '#default_value' => 1,
          '#attributes' => array('style' => 'display:inline-block;float:left; margin-right:10px;'),
          '#prefix' => '<div>',
        );
        $form['PostUnit'] = array(
          '#title' => t('Post Unit'),
          '#type' => 'checkbox',
          '#default_value' => 1,
          '#attributes' => array('style' => 'display:inline-block;float:left; margin-right:10px;'),
        );
        $form['Department'] = array(
          '#title' => t('Department'),
          '#type' => 'checkbox',
          '#default_value' => 1,
          '#attributes' => array('style' => 'display:inline-block;float:left; margin-right:10px;'),
          '#suffix' => '</div><br>',
        );



        $form['tags'] = array(
          '#title' => t('Tags'),
          '#type' => 'textarea',
          '#rows' => 2,
          '#attributes' => array('id' => 'edit-tags'),
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
          //'#attributes' => array('onClick' => 'window.open(\'bookmark\', \'_self\'); return false;'),
          '#attributes' => array('onClick' => 'history.go(-1); return false;'),
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
            '#attributes' => array('style' => 'border: 1px solid #7A7A7A;background: #FCFCE6;'),
          );

          $taglist = $TagList->getList($this->module);
          $form['t1'] = array(
            '#title' => t('Survey Tags'),
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


          $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'),
            '#attributes' => array('style' => 'margin-bottom:10px;'),
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

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
            // drupal_set_message($key . '||' . $value);
        }
        $dateType_start = new \DateTime($startdate);
        $dateType_expiry = new \DateTime($expirydate);
        $currentdate = new \DateTime(date('Y-m-d 00:00:00'));

        if (isset($startdate) && ($currentdate > $dateType_start )) {
            $form_state->setErrorByName(
                'startdate', $this->t("Start Date should not be earlier than today.")
            );
            $hasError = true;
        }
        // print_r($currentdate);
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

        //*************** File [Start]
        $tmp_name = $_FILES["files"]["tmp_name"]['filename'];
        $this_filename = CommonUtil::file_remove_character($_FILES["files"]["name"]['filename']);
        $file_ext = strtolower(pathinfo($this_filename, PATHINFO_EXTENSION));
        //*************** File [End]

        if ($_FILES['files']['name']['filename'] != "") {
            if (!in_array($file_ext, explode(' ', $this->allow_file_type))) {
                $form_state->setErrorByName(
                    'filename', $this->t("File format not supported.")
                );
                $hasError = true;
            }
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

    }

    //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();

        $my_user_id = $authen->getUserId();
        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }


        
        //*************** File [Start]
        $tmp_name = $_FILES["files"]["tmp_name"]['filename'];
        $this_filename = CommonUtil::file_remove_character($_FILES["files"]["name"]['filename']);
        $file_ext = strtolower(pathinfo($this_filename, PATHINFO_EXTENSION));
        //*************** File [End]


        $entry = array(
          'title' => $title,
          'description' => $description['value'],
          'start_date' => $startdate,
          'expiry_date' => $expirydate . " 23:59:59",
          'create_datetime' => date('Y-m-d H:i:s'),
          'modify_datetime' => date('Y-m-d H:i:s'),
          'is_visible' => $ReadyVote,
          'allow_copy' => $Allowcopy,
          'is_showDep' => $Department,
          'is_showPost' => $PostUnit,
          'is_showname' => $Votername,
          'user_id' => $my_user_id,
          'file_name' => $this_filename,
        );
        

        $database = \Drupal::database();
        $transaction = $database->startTransaction(); 
        try {

            $query = $database->insert('kicp_survey')->fields( $entry);
            $survey_id = $query->execute();

            if ($survey_id) {

              if ($tags != '') {
                $entry1 = array(
                    'module' => $this->module,
                    'module_entry_id' => intval($survey_id),
                    'tags' => $tags,
                );
                $return1 = TagStorage::insert($entry1);
                
              }    
              // if file is selected-------------------------------------------------------------------------
              if ($_FILES['files']['name']['filename'] != "") {
                SurveyDatatable::saveAttach( $_FILES['files']['name']['filename'], $this_filename, $survey_id);
              }

              // write logs to common log table
              \Drupal::logger('survey')->info('Created id: %id, title: %title, filename: %filename.',   
              array(
                  '%id' => $survey_id,
                  '%title' => $title,
                  '%filename' => isset($this_filename)?$this_filename:'No file',
              ));     

              $questionNo = 1;
              $request = \Drupal::request();
              $session = $request->getSession();
              $session->set('questionNo', $questionNo);
              $_SESSION['survey_id'] = $survey_id;
              $url = Url::fromUserInput('/survey_add_page2/');
              $form_state->setRedirectUrl($url);
            } else {
              \Drupal::messenger()->addError(
                t('Survey is not created. ' )
                );
                \Drupal::logger('survey')->error('Survey is not created ' .$survey_id);   
                $transaction->rollBack();  
            }
        } catch (Exception $e) {
            $transaction->rollback();
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
              t('Survey is not created. ' )
              );
            \Drupal::logger('survey')->error('Survey is not created '  . $variables);   
            $transaction->rollBack();  
        }
        unset($transaction);
    }

}
