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


class BlogAdd extends FormBase  {

    public function __construct() {
        $this->module = 'blog';

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

        
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $user_id = $authen->getUserId();

        $blog_id = BlogDatatable::getBlogIDByUserID($entry_id);


        $myAccessibleBlog = BlogDatatable::myAccessibleBlog($user_id);

        
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
              '#default_value' => $blog_id,
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
        
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'),
        );
        
        $form['actions']['cancel'] = array(
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#prefix' => '&nbsp;',
            '#attributes' => array('onClick' => 'window.open(\'blog_view?blog_id=1234\', \'_self\'); return false;'),
            '#limit_validation_errors' => array(),
        );


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

        $current_time =  \Drupal::time()->getRequestTime();

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $user_id = $authen->getUserId();
        

        $blog_owner_id = str_pad($blog_id, 6, "0", STR_PAD_LEFT);

        $hasAttach = (empty($files))?0:1; 

        try {
            $entry = array(
                'blog_id' => $blog_id,
                'entry_title' => $bTitle,
                'entry_content' => $bContent['value'],
                'created_by' => $user_id,
                'has_attachment' => $hasAttach,
            );

 
            $query = \Drupal::database()->insert('kicp_blog_entry')
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
            $newImagePathWebAccess .= '/' . $blog_owner_id . '/' . $this_entry_id_path;

            $createDir = $BlogImageUri . '/' . $blog_owner_id . '/' . $this_entry_id_path;
            $content = CommonUtil::udpateMsgImagePath($createDir, $bContent['value'], $newImagePathWebAccess  );

            if ( strpos($bContent['value'], "<img ") > 0)  {
                $query = \Drupal::database()->update('kicp_blog_entry')->fields([
                    'entry_content'=>$content, 
                    'entry_modify_datetime' => date('Y-m-d H:i:s'),
                ])
                ->condition('entry_id', $entry_id)
                ->execute();
            }

/////////// Handle image inside CKEditor [End] /////////////                            

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

    /////////// Handle attachment [End] /////////////
        }

//////////////  Handle Tags ///////////////////////////
        if ($tags != '') {
            $entry1 = array(
                'module' => $this->module,
                'module_entry_id' => intval($entry_id),
                'tags' => $tags,
            );
            $return1 = TagStorage::insert($entry1);
            
        }                

//dump ($file);

        $url = Url::fromUserInput('/blog_entry/'.$entry_id);
        $form_state->setRedirectUrl($url);


        $messenger = \Drupal::messenger(); 
        $messenger->addMessage( t('Blog has been added. '. $oldImagePath . ' - '. $newImagePathWebAccess));

    }
    catch (\Exception $e) {
        \Drupal::messenger()->addStatus(
            t('Unable to save blog at this time due to datbase error. Please try again. '. $uuid  )
            );
        
        }	
    }

}