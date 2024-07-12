<?php

/**
 * @file
 * Contains the settings for administrating the Test Form
 */


namespace Drupal\fileshare\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fileshare\Common\FileShareDatatable;
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

class FileShareAdd extends FormBase  {

    public $my_user_id;
    public $module;
    public $allow_file_type;
    public $target_folder;    

    public function __construct() {

        $current_user = \Drupal::currentUser();
        $this->my_user_id = $current_user->getAccountName();
        $this->module = 'fileshare';
        $this->allow_file_type = 'doc docx ppt pptx pdf';
        $this->target_folder = 'fileshare';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'fileshare_fileshare_add';
    }


    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
        'fileshare.settings',
        ];
    }
	
	

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $config = \Drupal::config('fileshare.settings'); 

        $logged_in = \Drupal::currentUser()->isAuthenticated();
        if (!$logged_in) {    
            $form['no_access'] = [
                '#markup' => CommonUtil::no_access_msg(),
            ];     
            return $form;        
        }    

        $form['title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#size' => 90,
            '#maxlength' => 200,
            '#description' => $this->t('File Share Title'),
            '#required' => TRUE,
        ];

        $form['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Description'),
            '#rows' => 2,
            '#attributes' => array('style'=>'height:300px;'),
            '#description' => $this->t('File Share Description'),
            '#required' => TRUE,
        ];

        $validators = array(
            'file_validate_extensions' => array(CommonUtil::getSysValue('default_file_upload_extensions')),
            'file_validate_size' => array(CommonUtil::getSysValue('default_file_upload_size_limit') * 1024 * 1024),
          );           

		
        $form['filename'] = [
            //'#type' => 'file',
            '#type' => 'managed_file',
            '#title' => $this->t('File'),
            '#upload_location' => 'private://fileshare/file',
            //'#size' => 150,
            '#description' => 'Only support '.str_replace(' ', ', ', $this->allow_file_type).' file format',
            '#required' => TRUE,
            '#multiple' => FALSE,
            '#upload_validators' => $validators
        ];

        $folderAry = FileShareDatatable::getMyEditableFolderList($this->my_user_id);
              
        $form['folder_id'] = [
            '#type' => 'select',
            '#title' => $this->t('Folder Name'),
            '#options' => $folderAry,
        ];
		

		$TagList = new TagList();
		
		
		$form['tags'] = array(
              '#title' => t('Tags'),
              '#type' => 'textarea',
              '#rows' => 2,
              //'#attributes' => array('id' => 'tags'),
              '#description' => 'Use semi-colon (;) as separator',
            );		

        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'),
        );
        
        $form['actions']['cancel'] = array(
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#attributes' => array('onClick' => 'history.go(-1); return false;'),
            '#limit_validation_errors' => [],
        );
 

        $taglist = $TagList->getListCopTagForModule();
        $form['t3'] = array(
            '#title' => t('COP Tags'),
            '#type' => 'details',
            '#open' => true,
            '#description' => $taglist,
            '#attributes' => array('style'=>'border: 1px solid #7A7A7A;background: #FCFCE6; margin-top: 20px;'),
        );
        
        $taglist = $TagList->getList($this->module);
        $form['t1'] = array(
            '#title' => t('File Share Tags'),
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

        $hasError = false;
        
        
        if ((isset($description) && $description != '' && strlen(trim($description)) > 30000)) {
            $form_state->setErrorByName(
                'description', $this->t("Description exceeds 30,000 characters")
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


    }
	
    
    /**
     * {@inheritdoc}
     */

   public function submitForm(array &$form, FormStateInterface $form_state) {

       //Obtain the value as entered into the Form
       foreach ($form_state->getValues() as $key => $value) {
        $$key = $value;
       }


		//*************** File [Start]
        //$tmp_name = $_FILES["files"]["tmp_name"]['filename'];
        

        foreach($filename as $filename1) {
            $NewFile = File::load($filename1);
            $tmp_name = $NewFile->getFilename();   
            $this_filename = CommonUtil::file_remove_character($tmp_name);
        }
        //$this_filename = CommonUtil::file_remove_character($_FILES["files"]["name"]['filename']);
        $file_ext = strtolower(pathinfo($this_filename, PATHINFO_EXTENSION));
		//*************** File [End]
		
		//*************** Thumbnail [Start]

		$this_imagename = str_replace('.'.$file_ext, '', $this_filename);
		$this_pdfname = str_replace('.'.$file_ext, '.pdf', $this_filename);		


       
       $entry = array(
        'title' => $title,
        'description' => $description,
        'file_name' => $this_pdfname,
        'original_file_name' => $this_filename,
        'image_name' => $this_imagename,
        'folder_id' => $folder_id,
        'user_id' => $this->my_user_id,
        );

        $database = \Drupal::database();
        $transaction = $database->startTransaction();       

    try {        

       $query = $database->insert('kicp_file_share')
       ->fields($entry);

       $file_id = $query->execute();
	 
       if ($tags != '') {
           $entry1 = array(
           'module' => $this->module,
           'module_entry_id' => intval($file_id),
           'tags' => $tags,
           );
           $return1 = TagStorage::insert($entry1);                        
       }


/////////////// FILE //////////////
  
      $this_file_id = str_pad($file_id, 6, "0", STR_PAD_LEFT);
     
      /*
      $file_system = \Drupal::service('file_system');  
      $FileshareUri = 'private://fileshare';

      FileShareDatatable::createFileshareDir($FileshareUri, $this_file_id);
      
      $validators = array(
        'file_validate_extensions' => array('jpg jpeg gif png txt doc docx xls xlsx pdf ppt pptx pps odt ods odp zip'),
        'file_validate_size' => array(15 * 1024 * 1024),
        );

      $delta = NULL; // type of $file will be array
      $file = file_save_upload('filename', $validators, 'private://fileshare/file/'.$this_file_id,$delta);

      $file_path = $file_system->realpath($FileshareUri  . '/file/' . $this_file_id);
      $image_path = $file_system->realpath($FileshareUri  . '/image/' . $this_file_id);      

      if($_FILES['files']['name']['filename'] != $this_filename) {
        $file_real_path = \Drupal::service('file_system')->realpath($FileshareUri.'/file/'.$this_file_id.'/'.$_FILES['files']['name']['filename']);
        $file_contents = file_get_contents($file_real_path);
        $newFile = \Drupal::service('file.repository')->writeData($file_contents, $FileshareUri.'/file/'.$this_file_id.'/'.$this_filename, FileSystemInterface::EXISTS_REPLACE);
        $file1 = $file[0];
        $file1->delete();
      } else {
            $newFile = $file[0];
      }

      $newFile->setPermanent();
      $newFile->uid = $file_id;
      $newFile->save();
      $url = $newFile->createFileUrl(FALSE);
    */

    $file_system = \Drupal::service('file_system');  
    $FileshareUri = 'private://fileshare';    

    FileShareDatatable::createFileshareDir($FileshareUri, $this_file_id);        
    $file_path = $file_system->realpath($FileshareUri  . '/file/' . $this_file_id);
    $image_path = $file_system->realpath($FileshareUri  . '/image/' . $this_file_id);   
    $createDir = $FileshareUri . '/file/' . $this_file_id;

    foreach($filename as $filename1) {
        $NewFile = File::load($filename1);
        $attachment_name = $NewFile->getFilename();
        $source = $file_system->realpath($FileshareUri . '/file/'.  $attachment_name);
        $destination = $file_system->realpath($createDir . '/' .  $this_filename);
        $newFileName = $file_system->move($source, $destination, FileSystemInterface::EXISTS_REPLACE);
        if (!$newFileName) {
            throw new \Exception('Could not move the generic placeholder file to the destination directory.');
        } else {
            $NewFile->setFileUri($createDir . '/' .  $attachment_name);
            $NewFile->uid = $file_id;
            $NewFile->setPermanent();
            $NewFile->save();
        }
    }





      
        // create thumbnail(s) of all PDF pages

        //*************************************************
        # convert the uploaded file to PDF first for other file format (e.g. doc, docx, ppt, pptx, txt)


        $file_temp = str_replace(["(",")"],["\(","\)"],$this_pdfname);
        $img_temp = str_replace(["(",")"],["\(","\)"],$this_imagename);
        if($file_ext != "pdf") {
            exec("export HOME=".$file_path." && /usr/bin/libreoffice --headless --convert-to pdf --outdir ".$file_path." \"".$file_path."/".$this_filename."\"");
            $file = File::create([
                'uid' => $file_id,
                'filename' => $this_pdfname,
                'uri' => $FileshareUri."/file/".$this_file_id."/".$this_pdfname,
              ]);        
            $file->setPermanent();
            $file->save();                  
            exec("pdftoppm -png ".$file_path."/".$file_temp." ".$image_path."/".$img_temp);            

        } else {            
            exec("pdftoppm -png ".$file_path."/".$file_temp." ".$image_path."/".$img_temp);
        }
        
        
        //*************************************************

        //******** store image record(s) in table "file_managed" [Start]

        // scan thumbnail folder, insert record in "file_managed" (for accessing private files)
        $dirFile = array();
        if (is_dir($image_path)) {
            $imageDirFile = scandir($image_path);
        }


        if ($imageDirFile && count($imageDirFile) > 0) {
            $i = 0;

            // insert record
            $image_folder = 'private://' . $this->target_folder . '/image/'.$this_file_id;

            // loop for every image inside the thumbnail directory
            foreach ($imageDirFile as $attach_id => $attach) {
                if ($attach == "." || $attach == "..") {
                    continue;
                }

                $delta = NULL; // type of $file will be array
                $file = File::create([
                        'uid' => $file_id,
                        'filename' => $attach,
                        'uri' => $image_folder."/".$attach,
                    ]);
                    $file->setPermanent();
                    $file->save();

                if($i == 0) {
                    // update image name

                    $database = \Drupal::database();
                    $query = $database->update('kicp_file_share')->fields([
                      'image_name'=> $attach , 
                      'no_of_pages' => count($imageDirFile)-2,
                    ])
                    ->condition('file_id', $file_id)
                    ->execute();
                }

                $i++;
            }
        }


        \Drupal::logger('fileshare')->info('Created id: %id, title: %title, filename: %filename.',   
        array(
            '%id' => $file_id,
            '%title' => $title,
            '%filename' => $this_filename,
        ));     

        $url = Url::fromRoute('fileshare.fileshare_content');
        $form_state->setRedirectUrl($url);

        $messenger = \Drupal::messenger(); 
        $messenger->addMessage( t('Files has been added'));

    }

    catch (\Exception $e ) {
        $variables = Error::decodeException($e);
        \Drupal::messenger()->addError(
          t('Unable to save file at this time due to datbase error. Please try again.'.$e)
        ); 
        \Drupal::logger('fileshare')->error('Fileshare is not created ' .$variables);   
        $transaction->rollBack();

    }
    unset($transaction);

  }

    
}


