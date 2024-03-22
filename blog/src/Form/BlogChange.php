<?php

/**
 * @file
 * Contains the settings for administrating the Test Form
 */


namespace Drupal\blog\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blog\Common\BlogDatatable;
use Drupal\common\Controller\TagList;
use Drupal\common\Controller\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\Core\Database\Database;
use Drupal\file\FileInterface;
use Drupal\file\Entity;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\File\FileSystemInterface;


class BlogChange extends FormBase  {

    public function __construct() {
        $this->module = 'blog';

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
        $entry = BlogDatatable::getBlogEntryContent($entry_id);
        
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $user_id = $authen->getUserId();


        if ($entry['entry_title'] == null) {
            $form['intro'] = array(
              '#markup' => t('<i style="font-size:20px; color:red; margin-right:10px;" class="fa-solid fa-ban"></i> Blog enrty not found'),
            );
            return $form; 
           }


        $BlogFileUri = 'private://blog/file';

        $blog_id = BlogDatatable::getBlogIDByUserID($user_id);
                
        $file_system = \Drupal::service('file_system');   
        $filePath = $file_system->realpath($BlogFileUri . '/' . $blog_owner_id . '/' . $this_entry_id);

        $form['blog_id'] = [
            '#type' => 'hidden',
            '#value' => $blog_id,
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
            '#value' => $entry_title,
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
        ];

        $form['bContent_prev'] = [
            '#type' => 'hidden',
            '#value' => $entry_content,
        ];


        $attachments = array();
        if ($entry['has_attachment']) {
            $attachments = BlogDatatable::getAttachments($blog_id, $entry_id);
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

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => t('Save'),
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
            '#description' =>  t($taglist),
            '#attributes' => array('style'=>'border: 1px solid #7A7A7A;background: #FCFCE6;'),
        );

        $taglist = $TagList->getList($this->module);
        $form['t1'] = array(
            '#title' => t('Blog Tags'),
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

    public function submitForm(array &$form, FormStateInterface $form_state) {

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }

        $aa= $delete_doc_id;

        $blog_owner_id = str_pad($blog_id, 6, "0", STR_PAD_LEFT);


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
        
        $newImagePathWebAccess = base_path()  . 'system/files/' . $this->module . '/image/' . $blog_owner_id . '/' . $this_entry_id_path;
        
        $database = \Drupal::database();

        if ($bContent['value'] != $bContent_prev)  {      

            $createDir = $BlogImageUri . '/' . $blog_owner_id . '/' . $this_entry_id_path;
            $content = CommonUtil::udpateMsgImagePath($createDir, $bContent['value'], $newImagePathWebAccess  );

        }  // end content changed
       

        $hasAttach = (empty($files))?0:1; 


        /////////// Handle image inside CKEditor [End] /////////////  


        ///// Delete attachment [Start] ///////

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
                    $uuid = $NewFile->uuid();
                    $source = $file_system->realpath($BlogFileUri . '/'. $NewFile->getFilename());
                    $destination = $file_system->realpath($createDir . '/' . $NewFile->getFilename());
                    if (!$file_system->move($source, $destination, FileSystemInterface::EXISTS_REPLACE)) {
                        throw new \Exception('Could not move the generic placeholder image to the destination directory.');
                    } else {
                        $rs = CommonUtil::updateDrupalFileManagedUri($uuid, $createDir . '/' . $NewFile->getFilename(), '');
                    }
                }
            }


        }
 /////////// Handle attachment [End] /////////////

       $hasAttach = ($upload_files - $delAttach > 0 || count($files) > 0)?1:0;

        $entry = array();
        // if any changes in content, update the timestamp
        if($bTitle != $bTitle_prev || $content != $bContent_prev || $delete_doc_id != "" || $tags != $tags_prev || !empty($files) ) {
            $entry['entry_modify_datetime'] = date('Y-m-d H:i:s');
            if ($bTitle != $bTitle_prev) {
                $entry['entry_title'] = $bTitle;
            }
            if ($content != $bContent_prev) {
                $entry['entry_content'] = $content;
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
                    $query = $database->update('kicp_tags')->fields([
                        'is_deleted'=>1 , 
                    ])
                    ->condition('fid', $entry_id)
                    ->condition('module', 'blog')
                    ->execute();                
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

//dump ($file);

            $url = Url::fromUserInput('/blog_entry/'.$entry_id);
            $form_state->setRedirectUrl($url);


            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Blog has been updated. '));

        }
        catch (\Exception $e) {
            \Drupal::messenger()->addStatus(
                t('Unable to save blog at this time due to datbase error. Please try again. '. $uuid  )
                );
            
        }	
        
    }

}