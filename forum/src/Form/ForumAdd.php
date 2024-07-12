<?php

/**
 * @file
 */

namespace Drupal\forum\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal;
use Drupal\Component\Utility\UrlHelper;
use Drupal\common\TagList;
use Drupal\common\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\forum\Common\ForumDatatable;
use Drupal\file\FileInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Utility\Error;

class ForumAdd extends FormBase {

    public $module;

    public function __construct() {
        $this->module = 'forum';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'forum_add_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $forum_id="") {

        $post_id = (isset($_REQUEST['post_id']) && $_REQUEST['post_id'] != "") ? $_REQUEST['post_id'] : "";
        $topic_id = (isset($_REQUEST['topic_id']) && $_REQUEST['topic_id'] != "") ? $_REQUEST['topic_id'] : "";
        $quotePost = (isset($_REQUEST['quotePost']) && $_REQUEST['quotePost'] != "") ? $_REQUEST['quotePost'] : "";

        $form['forum_id'] = array(
          '#type' => 'hidden',
          '#size' => 11,
          '#value' => $forum_id,
        );
        
       $forum_name = ForumDatatable::getForumName($forum_id);
       if (!$forum_name) {
        $messenger = \Drupal::messenger(); 
        $messenger->addWarning( t('This forum is not available'));                
        return $form; 
    }       
        
        $form['forum_name'] = array(
          '#prefix' => '<div class="text-left-100">',
          '#suffix' => 'Forum Name: '.$forum_name.'</div><br><br><br>',
        );
        
        if ( $topic_id != "") {

            $form['topic_id'] = array(
                '#type' => 'hidden',
                '#size' => 11,
                '#value' => $topic_id,
              );

              $form['quotePost'] = array(
                '#type' => 'hidden',
                '#size' => 11,
                '#value' => $quotePost,
            );

            $forum_topic = ForumDatatable::getForumTopic($topic_id);
        
            $form['topic_subject'] = array(
              '#title' => t('Subject'),
              '#type' => 'textfield',
              '#size' => 90,
              '#maxlength' => 255,
              '#required' => TRUE,
              '#default_value' => 'Re: '.$forum_topic,
            );

        } else {

            $form['topic_subject'] = array(
            '#title' => t('Forum Topic'),
            '#type' => 'textfield',
            '#size' => 90,
            '#maxlength' => 255,
            '#required' => TRUE,
            );
        }

        if($quotePost != "") {
            
            $forumPostInfo = ForumDatatable::getForumPostInfo($post_id);
            $userInfo = CommonUtil::getUserInfoByUserId($forumPostInfo->user_id);
            $quoteMsg = "[quotemsg] ".($forumPostInfo->is_guest ? $forumPostInfo->poster_name :$userInfo->user_name)." wrote:<br>";
            $quoteMsg .= $forumPostInfo->content;
            $quoteMsg .= "[/quotemsg]<br><br>";
        } else {
            $quoteMsg = "";
        }        
        
        $form['topic_content'] = array(
          '#title' => t('Message'),
          '#type' => 'text_format',
          '#format' => 'full_html',
          '#allowed_formats' => ['full_html'],
          '#rows' => 6,
          '#cols' => 60,
          '#default_value' => $quoteMsg,
          '#required' => TRUE,
        );

        $validators = array(
            'file_validate_extensions' => array(CommonUtil::getSysValue('default_file_upload_extensions')),
            'file_validate_size' => array(CommonUtil::getSysValue('default_file_upload_size_limit') * 1024 * 1024),
          );        

        $form['filename'] = [
            '#type' => 'managed_file',
            '#title' => $this->t('File'),
            '#description' => 'Only support doc, docx, ppt, pptx, pdf file format',
            '#required' => FALSE,
            '#upload_location' => 'private://forum/file',
            '#upload_validators' => $validators
        ];         

        
        $form['is_guest'] = array(
            '#title' => t('Post as'),
            '#type' => 'checkbox',
            '#prefix' => '<div class="text-left-100">Option</div><div class="text-left-6">',
            '#attributes' => array('onclick' => 'if(this.checked) {document.getElementById("edit-poster-name").select();}'),
            '#suffix' => '</div>',
        );
        
        $form['poster_name'] = array(
          '#type' => 'textfield',
          '#size' => 40,
          '#maxlength' => 200,
          //'#default_value' => 'Anonymous',
          '#attributes' => array('placeholder' => t('Anonymous')),
          '#description' => 'Please type your preferred name or else your "Display Name" stated in Profile will be posted as default.',
          '#prefix' => '<div class="text-left-90">',
          '#suffix' => '</div>',
        );
        
        
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Save'),
        );

        $form['actions']['cancel'] = array(
          '#type' => 'button',
          '#value' => t('Cancel'),
          '#prefix' => '&nbsp;',
          '#attributes' => array('onClick' => 'window.open(\'forum?forum_id='.$forum_id.'\', \'_self\'); return false;'),
          '#limit_validation_errors' => array(), //skip all validations
        );
        
		$TagList = new TagList();
		
		
		  $form['tags'] = array(
              '#title' => t('Tags'),
              '#type' => 'textarea',
              '#rows' => 2,
              //'#attributes' => array('id' => 'tags'),
              '#description' => 'Use semi-colon (;) as separator',
            );	

            $taglist = $TagList->getListCopTagForModule();
            $form['t3'] = array(
                '#title' => t('COP Tags'),
                '#type' => 'details',
                '#open' => true,
                '#description' => $taglist,
                '#attributes' => array('style'=>'border: 1px solid #7A7A7A;background: #FCFCE6;'),
            );
            
            $taglist = $TagList->getList($this->module);
            $form['t1'] = array(
                '#title' => t('Forum Tags'),
                '#type' => 'details',
                '#open' => true,
                '#description' => $taglist,
            );
    
            $taglist = $TagList->getList('ALL');
            $form['t2'] = array(
                '#title' => t('All Tags'),
                '#type' => 'details',
                '#open' => false,
                '#description' => $taglist,
            );            
        
            $jsOutput = '<script>disableTopicEditorFormat();</script>';
            $form['docReady'] = array(
            '#markup' => t($jsOutput),
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
        
        if (isset($topic_content['value']) && $topic_content['value'] != '' && strlen(trim($topic_content['value'])) > 30000) {
            $form_state->setErrorByName(
                'topic_content', $this->t("Content exceeds 30,000 characters")
            );
        }
        
        if ($is_guest == 1 && $poster_name == '') {
            $form_state->setErrorByName(
                'poster_name', $this->t("Preferred name is blank")
            );
        }

    }

    //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $current_user = \Drupal::currentUser();
        $my_user_id = $current_user->getAccountName();    

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }
        $hasAttach = (empty($filename))?0:1; 
        
        //$url = new Url('forum.forum_view_topic');
        $url = new Url('forum.forum_view_forum', array('forum_id' => $forum_id));        
        $form_state->setRedirectUrl($url);

        $database = \Drupal::database();
        $transaction = $database->startTransaction();   

        try {

            
            if (!isset($topic_id) || $topic_id == "") {

                $entry = array(
                'title' => $topic_subject,
                'content' => $topic_content['value'],
                'user_id' => $my_user_id,
                'counter' => 0,
                'forum_id' => $forum_id,
                'is_deleted' => 0,
                'is_guest' => $is_guest,
                'poster_name' => $poster_name,
                );
            
                
                $query = $database->insert('kicp_forum_topic')
                ->fields($entry);
                $topic_id = $query->execute();

                if ($topic_id == 0) {

                    \Drupal::messenger()->addStatus(
                        t('Forum topic is not created. ' )
                        );                
                    return;
                }

                $parent_id = 0;
            
            } else {
                $parent_id = ($quotePost) ? $post_id : 0;
            }
            
            // add corresponding post
            $entry2 = array(
              'subject' => $topic_subject,
              'content' => $topic_content['value'],
              'is_guest' => $is_guest,
              'poster_name' => $poster_name,
              'user_id' => $my_user_id,
              'parent_id' => $parent_id,
              'topic_id' => $topic_id,
              'is_deleted' => 0,
            );
            

            $query = $database->insert('kicp_forum_post')
            ->fields($entry2);
            $new_post_id = $query->execute();

            
            if ($tags != '') {
                    $entry3 = array(
                      'module' => $this->module,
                      'module_entry_id' => intval($topic_id),
                      'tags' => $tags,
                    );
                    $return3 = TagStorage::insert($entry3);
                }
  
                
             $this_topic_id = str_pad($topic_id, 6, "0", STR_PAD_LEFT);
             $this_new_post_id = str_pad($new_post_id, 6, "0", STR_PAD_LEFT);

            /////////// Image inside topic [Start] ///////////
            if ( strpos( $topic_content['value'], "<img ") > 0)  {
                $imgTags = array();
                $origImageSrc = array();            
                $oldImagePath = base_path() . 'sites/default/files/public/inline-images';   // image pool once upload the image
                $newImagePath = base_path() . 'sites/default/files/private/' . $this->module . '/image';    // store in "Private" folder
                $newImagePathWebAccess = base_path()  . 'system/files/' . $this->module . '/image';                       
                $newImagePathWebAccess .= '/' . $this_topic_id. '/' .$this_new_post_id;
                $ForumImageUri = 'private://forum/image';
                $createDir = $ForumImageUri . '/' . $this_topic_id. '/' .$this_new_post_id;
                $content = CommonUtil::udpateMsgImagePath($createDir, $topic_content['value'],  $newImagePathWebAccess, $new_post_id  );
                $query = \Drupal::database()->update('kicp_forum_post')->fields([
                    'content'=>$content, 
                ])
                ->condition('post_id', $new_post_id)
                ->execute();
            }

            /////////// Image inside topic [End] ///////////
            

            /////////// Handle attachment [Start] /////////////

    
            $ForumFileUri = 'private://forum/file';
            $file_system = \Drupal::service('file_system');  

            if ($hasAttach) {
                /////////// Handle attachment [Start] /////////////

                $createDir = $ForumFileUri . '/' . $this_topic_id . '/' . $this_new_post_id;  
                if (!is_dir($file_system->realpath($createDir ))) {
                    // Prepare the directory with proper permissions.
                    if (!$file_system->prepareDirectory( $createDir , FileSystemInterface::CREATE_DIRECTORY)) {
                    throw new \Exception('Could not create the directory.');
                    }
                }

                foreach ($filename as $file1) {
                    $NewFile = File::load($file1);
                    $attachment_name = $NewFile->getFilename();
                    $source = $file_system->realpath($ForumFileUri . '/'. $attachment_name);
                    $destination = $file_system->realpath($createDir . '/' .  $attachment_name);
                    $newFileName = $file_system->move($source, $destination, FileSystemInterface::EXISTS_REPLACE);
                    if (!$newFileName) {
                        throw new \Exception('Could not move the generic placeholder file to the destination directory.');
                    } else {
                        $NewFile->setFileUri($createDir . '/' .  $attachment_name);
                        $NewFile->uid = $new_post_id;
                        $NewFile->setPermanent();
                        $NewFile->save();
                    }

                }

            }
            /////////// Handle attachment [End] /////////////

            \Drupal::logger('forum')->info('Created topc id: %topic_id, post id: %post_id, subject: %subject',   
            array(
                '%topic_id' => $topic_id,
                '%post_id' => $new_post_id,
                '%subject' => $topic_subject,
            ));   

            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Forum topic is created.'));  
            
        }
        catch (Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::logger('forum')->error('Forum is not created ' . $variables);   
            $transaction->rollBack();            
            \Drupal::messenger()->addStatus(
                t('Unable to create forum. ' )
                );
        }
        unset($transaction);


    }

}
