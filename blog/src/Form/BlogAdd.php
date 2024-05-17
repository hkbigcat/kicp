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
use Drupal\Core\Utility\Error;

class BlogAdd extends FormBase  {

    public function __construct() {
        $this->module = 'blog';
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->my_user_id = $authen->getUserId();   
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'blog_blog_add';
    }


    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
        'blog.settings',
        ];
    }
	
	

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $config = \Drupal::config('blog.settings'); 

        $my_blog_id = BlogDatatable::getBlogIDByUserID($this->my_user_id);
        if (!$my_blog_id) {
            $my_blog_id = BlogDatatable::createBlogAccount($this->my_user_id);
            if (!$my_blog_id) {
                $form['intro'] = array(
                    '#markup' => t('<i style="font-size:20px; color:red; margin-right:10px;" class="fa-solid fa-ban"></i>Cannot create blog. Please contact KICP Administrator.'),
                  );
                  return $form; 
            }
        }


        $myBlogInfo = BlogDatatable::getBlogInfo($my_blog_id);
        $blogSelection[$my_blog_id] = $myBlogInfo['blog_name'];
        $myAccessibleBlog = BlogDatatable::myAccessibleBlog($this->my_user_id);

        
        for ($i = 0; $i < count($myAccessibleBlog); $i++) {
            $blogSelection[$myAccessibleBlog[$i]["blog_id"]] = $myAccessibleBlog[$i]["blog_name"] . " (" . $myAccessibleBlog[$i]["uname"] . ")";
        }

        if (count($myAccessibleBlog) > 0) {
            $form['blog_id'] = array(
              '#title' => t('Blog<span style="color:red">&nbsp;*</span>'),
              '#type' => 'select',
              '#options' => $blogSelection
            );
        }
        else {
            $form['blog_id'] = array(
              '#title' => t('blog_id'),
              '#type' => 'hidden',
              '#default_value' => $my_blog_id,
            );
        }        

        $form['bTitle'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#size' => 90,
            '#maxlength' => 200,
            '#required' => TRUE,
        ];          
          

        
        $form['bContent'] = [
            '#type' => 'text_format',
            '#format' => 'full_html',
            '#title' => $this->t('Content'),
            '#rows' => 10,
            '#cols' => 30,
            //'#attributes' => array('style' => 'height:400px;'),
            '#required' => TRUE,
            '#allowed_formats' => ['full_html'],
        ];

        $validators = array(
            'file_validate_extensions' => array(CommonUtil::getSysValue('default_file_upload_extensions')),
            'file_validate_size' => array(CommonUtil::getSysValue('default_file_upload_size_limit') * 1024 * 1024),
          );        

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

        $form['tags'] = array(
            '#title' => t('Tags'),
            '#type' => 'textarea',
            '#rows' => 2,
            '#description' => 'Use semi-colon (;) as separator',
        );

        $form['line'] = array(
            '#markup' => t('<hr>'),
            '#attributes' => array('style'=>'1px solid #888888;margin-top:30px;'),
        );

        $form['published'] = [
            '#title' => t('Published'),
            '#type' => 'checkbox',
            '#default_value' => '1',
        ];
        
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


        $taglist = $TagList->getListCopTagForModule();
        $form['t3'] = array(
            '#title' => t('COP Tags'),
            '#type' => 'details',
            '#open' => true,
            '#description' =>  $taglist,
            '#attributes' => array('style'=>'border: 1px solid #7A7A7A;background: #FCFCE6; margin-top: 40px;'),
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
            '#description' =>  $taglist,
        );          

        return $form;

    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }


        $blog_owner_id = BlogDatatable::getUIdByBlogId($blog_id);
        $this_blog_owner_id = str_pad($blog_owner_id, 6, "0", STR_PAD_LEFT);

        $hasAttach = (empty($files))?0:1; 

        $entry = array(
            'blog_id' => $blog_id,
            'entry_title' => $bTitle,
            'entry_content' => $bContent['value'],
            'is_visible' => $published,
            'created_by' => $this->my_user_id,
            'has_attachment' => $hasAttach,
        );

        $database = \Drupal::database();
        $transaction = $database->startTransaction();     

        try {
 
            $query = $database->insert('kicp_blog_entry')
            ->fields($entry);
            $entry_id = $query->execute();

            ////////////  Handle image inside CKEditor [Start] ////////////
        
            $this_entry_id_path = str_pad($entry_id, 6, "0", STR_PAD_LEFT);
            $imgTags = array();
            $origImageSrc = array();

            //new method
            $BlogImageUri = 'private://blog/image';
            $BlogFileUri = 'private://blog/file';
            $file_system = \Drupal::service('file_system');    

            //New
            $newImagePathWebAccess = base_path()  . 'system/files/' . $this->module . '/image';                       
            $newImagePathWebAccess .= '/' . $this_blog_owner_id . '/' . $this_entry_id_path;

            $createDir = $BlogImageUri . '/' . $this_blog_owner_id . '/' . $this_entry_id_path;
            $content = CommonUtil::udpateMsgImagePath($createDir, $bContent['value'], $newImagePathWebAccess ,  $entry_id  );

            if ( strpos($bContent['value'], "<img ") > 0)  {
                $query = \Drupal::database()->update('kicp_blog_entry')->fields([
                    'entry_content'=>$content, 
                    'entry_modify_datetime' => date('Y-m-d H:i:s'),
                ])
                ->condition('entry_id', $entry_id)
                ->execute();
            }

            /////////// Handle image inside CKEditor [End] /////////////                            

            /////////// Handle attachment [Start] /////////////
            if ($hasAttach) {
                $createDir = $BlogFileUri . '/' . $this_blog_owner_id . '/' . $this_entry_id_path;
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

            //////////////  Handle Tags ///////////////////////////
            if ($tags != '') {
                $entry1 = array(
                    'module' => $this->module,
                    'module_entry_id' => intval($entry_id),
                    'tags' => $tags,
                );
                $return1 = TagStorage::insert($entry1);
                
            }                

            \Drupal::logger('blog')->info('Created id: %id, title: %title, attachment: %attach.',   
            array(
                '%id' => $entry_id,
                '%title' => $bTitle,
                '%attach' => ($hasAttach)?'Y':'N',
            ));     

            $url = Url::fromUserInput('/blog_entry/'.$entry_id);
            $form_state->setRedirectUrl($url);

            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Blog has been added. '));

        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
            t('Blog is not created. ' )
            );
            \Drupal::logger('blog')->error('Blog is not created '  . $variables);    
            $transaction->rollBack();               
        }	
        unset($transaction);

    }

}