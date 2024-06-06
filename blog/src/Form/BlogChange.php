<?php

/**
 * @file
 * Contains the settings for administrating the Test Form
 */


namespace Drupal\blog\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blog\Common\BlogDatatable;
use Drupal\common\TagList;
use Drupal\common\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\Core\Database\Database;
use Drupal\file\FileInterface;
use Drupal\file\Entity;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Utility\Error;

class BlogChange extends FormBase  {

    public function __construct() {
        $this->module = 'blog';
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->is_authen = $authen->isAuthenticated;
        $this->my_user_id = $authen->getUserId();        

        $UserInfo = $authen->getKICPUserInfo($this->my_user_id);
        if ($UserInfo==null) {
            \Drupal::logger('blog')->error('uid not found for user_id: '.$this->my_user_id);       
    
        }


    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'blog_blog_change';
    }


    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
        'blog.settings',
        ];
    }


    public function attachemntLink( $entry_id, $a)
    {
        $output = array();
        $attach_id = 1;  
        foreach ($a as $value) {
            $output[] = '<div id="DivEntryAttach' . $attach_id. '"><a href="/download/blog/'.$entry_id.'?fname='.urlencode($value).'" target="_blank"><i class="fa-solid fa-paperclip"></i>'.$value.'</a> <a href="javascript:;" onClick="RemoveAttachment('.$attach_id.');"><i class="fa-solid fa-xmark" style="font-size:16px; color:red"></i></a></div>';
            $attach_id++;
        }
        return implode(" ",$output);
    }


/**
 * {@inheritdoc}
 */
    public function buildForm(array $form, FormStateInterface $form_state, $entry_id=NULL) {

        $config = \Drupal::config('blog.settings'); 

        if (! $this->is_authen) {
            $form['no_access'] = [
                '#markup' => CommonUtil::no_access_msg(),
            ];     
            return $form;        
        } 


        $entry = BlogDatatable::getBlogEntryContent($entry_id);
        
        if (!$entry) {
            $form['intro'] = array(
              '#markup' => t('<i style="font-size:20px; color:red; margin-right:10px;" class="fa-solid fa-ban"></i> This blog cannot be found'),
            );
            return $form; 
           }

        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
        $isdeletegate = BlogDatatable::isBlogDelegatedUser($entry['blog_id'],  $this->my_user_id);          
        if (!$isSiteAdmin && $entry['user_id'] != $this->my_user_id  )  {
            $form['intro'] = array(
              '#markup' => t('<i style="font-size:20px; color:red; margin-right:10px;" class="fa-solid fa-ban"></i> You cannot edit this blog.'),
               );
              return $form; 
           }


        $BlogFileUri = 'private://blog/file';
             
        $form['blog_id'] = [
            '#type' => 'hidden',
            '#value' => $entry['blog_id'],
        ];

        $form['entry_id'] = [
            '#type' => 'hidden',
            '#value' => $entry_id,
        ];
        
        $form['bTitle'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#size' => 90,
            '#maxlength' => 200,
            '#default_value' =>  $entry['entry_title'],
            '#required' => TRUE,
        ];          

        $form['bTitle_prev'] = [
            '#type' => 'hidden',
            '#value' => $entry['entry_title'],
        ];
            
        $form['bContent'] = [
            '#type' => 'text_format',
            '#format' => 'full_html',
            '#title' => $this->t('Content'),
            '#rows' => 10,
            '#cols' => 30,
            //'#attributes' => array('style' => 'height:400px;'),
            '#default_value' =>  $entry['entry_content'],
            '#required' => TRUE,
            '#allowed_formats' => ['full_html'],
        ];

        $form['bContent_prev'] = [
            '#type' => 'hidden',
            '#value' => $entry['entry_content'],
        ];


        $attachments = array();
        if ($entry['has_attachment']) {
            $attachments = BlogDatatable::getAttachments($entry['blog_id'], $entry_id);
            $form['help'] = [
                '#type' => 'item',
                '#title' => t('Current Attachment(s)'),
                '#markup' => t( self::attachemntLink( $entry_id, $attachments)),
            ];

        }

        $form['upload_files'] = array(
            '#type' => 'hidden',
            '#value' => count($attachments),
        );

        $validators = [
            'file_validate_extensions' => array(CommonUtil::getSysValue('default_file_upload_extensions')),
            'file_validate_size' => array(CommonUtil::getSysValue('default_file_upload_size_limit') * 1024 * 1024),
        ];        

        $form['delete_doc_id'] = [
            '#type' => 'hidden',
            '#default_value' => '',            
        ];

        $form['files'] = [
            '#type' => 'managed_file',
            //'#type' => 'file',
            '#title' => $this->t('Upload Multiple Files'),
            '#description' => 'Press \'Ctrl\' to select multiple files',
            '#required' => FALSE,
            '#upload_location' => 'private://blog/file',
            '#multiple' => TRUE,
            '#upload_validators' => $validators
        ];         

        $TagList = new TagList();
        $tags_prev = $TagList->getTagListByRecordId('blog', $entry_id);

        $form['tags'] = [
            '#title' => t('Tags'),
            '#type' => 'textarea',
            '#rows' => 2,
            '#description' => 'Use semi-colon (;) as separator',
            '#default_value' =>  implode(";", $tags_prev),
        ];

        $form['tags_prev'] = [
            '#type' => 'hidden',
            '#value' => $tags_prev,
        ];

        $form['line'] = array(
            '#markup' => t('<hr>'),
            '#attributes' => array('style'=>'1px solid #888888;margin-top:30px;'),
        );

        $form['allow_comment'] = [
            '#title' => t('Allow comment?'),
            '#type' => 'checkbox',
            '#default_value' => '1',
        ];        

        $form['allow_comment_prev'] = [
            '#type' => 'hidden',
            '#value' => $entry['is_pub_comment'],
        ];        
        

        $form['published'] = [
            '#title' => t('Published'),
            '#type' => 'checkbox',
            '#default_value' => $entry['is_visible'],
        ];        

        $form['published_prev'] = [
            '#type' => 'hidden',
            '#value' => $entry['is_visible'],
        ];        

      
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => t('Save'),
            '#attributes' => array('style' => 'margin-bottom:20px'),            
        ];

        $form['actions']['cancel'] = [
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#prefix' => '&nbsp;',
            '#attributes' => array('onClick' => 'window.open(\'../blog_entry/'.$entry_id.'\', \'_self\'); return false;'),
            '#limit_validation_errors' => array(),
        ];

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
            '#title' => t('Blog Tags'),
            '#type' => 'details',
            '#open' => true,
            '#description' =>  $taglist,
        );

        $taglist = $TagList->getList('ALL');
        $form['t2'] = array(
            '#title' => t('All Tags'),
            '#type' => 'details',
            '#open' => false,
            '#description' => $taglist,
        );          

        return $form;

    }

    public function submitForm(array &$form, FormStateInterface $form_state) {


        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }

        $blog_owner_id = BlogDatatable::getUIdByBlogId($blog_id);
        $this_blog_owner_id = str_pad($blog_owner_id, 6, "0", STR_PAD_LEFT);      

////////////  Handle image inside CKEditor [Start] ////////////
        
        $this_entry_id_path = str_pad($entry_id, 6, "0", STR_PAD_LEFT);
        $imgTags = array();
        $origImageSrc = array();

        //new method
        $PublicUri = 'public://inline-images';
        $BlogImageUri = 'private://blog/image';
        $BlogFileUri = 'private://blog/file';
        $file_system = \Drupal::service('file_system');    

        //New
        
        $newImagePathWebAccess = base_path()  . 'system/files/' . $this->module . '/image/' . $this_blog_owner_id . '/' . $this_entry_id_path;
        
        $database = \Drupal::database();
        $transaction = $database->startTransaction(); 

        if ($bContent['value'] != $bContent_prev)  {      

            $createDir = $BlogImageUri . '/' . $this_blog_owner_id . '/' . $this_entry_id_path;
            $content = CommonUtil::udpateMsgImagePath($createDir, $bContent['value'], $newImagePathWebAccess, $entry_id );

        }  // end content changed
       

        $hasAttach = (empty($files))?0:1; 


        /////////// Handle image inside CKEditor [End] /////////////  


        ///// Delete attachment [Start] ///////

        $delAttach = 0;
        if ($delete_doc_id != "") {
            $delAttach = BlogDatatable::DeleteBlogEntryAttachment($delete_doc_id, $blog_owner_id, $this_entry_id_path);
        }

        ///// Delete attachment [End] ///////              

        if ($hasAttach) {
        /////////// Handle attachment [Start] /////////////

            $createDir = $BlogFileUri . '/' . $blog_owner_id . '/' . $this_entry_id_path;
            if (!is_dir($file_system->realpath($createDir ))) {
                // Prepare the directory with proper permissions.
                if (!$file_system->prepareDirectory( $createDir , FileSystemInterface::CREATE_DIRECTORY)) {
                    throw new \Exception('Could not create the blog image - entry id directory.');
                }
            }

            foreach ($files as $file1) {
                if ($file1) {
                    $NewFile = File::load($file1);
                    $attachment_name = $NewFile->getFilename();
                    $source = $file_system->realpath($BlogFileUri . '/'.  $attachment_name);
                    $destination = $file_system->realpath($createDir . '/' .  $attachment_name);
                    $newFileName = $file_system->move($source, $destination, FileSystemInterface::EXISTS_REPLACE);
                    if (!$newFileName) {
                        throw new \Exception('Could not move the generic placeholder file to the destination directory.');
                    } else {
                        $NewFile->setFileUri($createDir . '/' .  $attachment_name);
                        $NewFile->uid =$entry_id;
                        $NewFile->setPermanent();
                        $NewFile->save();
                    }

                }
            }


        }
 /////////// Handle attachment [End] /////////////

       $hasAttach = ($upload_files - $delAttach > 0 || count($files) > 0)?1:0;

        $entry = array();
        // if any changes in content, update the timestamp
        if($bTitle != $bTitle_prev || $bContent['value'] != $bContent_prev || $delete_doc_id != "" || $tags != $tags_prev || $published != $published_prev || !empty($files) ) {
            $entry['entry_modify_datetime'] = date('Y-m-d H:i:s');
            if ($bTitle != $bTitle_prev) {
                $entry['entry_title'] = $bTitle;
            }
            if ($bContent['value'] != $bContent_prev) {
                $entry['entry_content'] = $content;
            }
            if ($allow_comment != $allow_comment_prev) {
                $entry['is_pub_comment'] = $allow_comment;
            }            
            if ($published != $published_prev) {
                $entry['is_visible'] = $published;
            }     
                   
            $entry['has_attachment'] = $hasAttach;
        }

        try {

            $query = $database->update('kicp_blog_entry')->fields($entry)
                ->condition('entry_id', $entry_id)
                ->execute();                

//////////////  Handle Tags ///////////////////////////
            if ($tags != $tags_prev) {
                // rewrite tags
                if ($tags_prev != '') {
                    $return2 = TagStorage::markDelete($this->module, $entry_id);  
                }
                if ($tags != '') {
                    $entry1 = array(
                        'module' => 'blog',
                        'module_entry_id' => intval($entry_id),
                        'tags' => $tags,
                    );
                    $return1 = TagStorage::insert($entry1);                
                }
            }            

            \Drupal::logger('blog')->info('Updated id: %id, title: %title, attachment: %attach.',   
            array(
                '%id' => $entry_id,
                '%title' => $bTitle,
                '%attach' => ($hasAttach)?'Y':'N',
            ));     

            $url = Url::fromUserInput('/blog_entry/'.$entry_id);
            $form_state->setRedirectUrl($url);

            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Blog has been updated. '));

        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addStatus(
                t('Unable to save blog at this time due to datbase error. Please try again. ' )
                );
            \Drupal::logger('blog')->error('Blog is not created '  . $variables);                    
            $transaction->rollBack();        
        }
        unset($transaction);	
        
    }

}