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
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class FileShareChange extends FormBase {

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
        return 'fileshare_fileshare_change';
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
    public function buildForm(array $form, FormStateInterface $form_state, $file_id = NULL) {

         $config = \Drupal::config('fileshare.settings'); 

         $logged_in = \Drupal::currentUser()->isAuthenticated();
         if (!$logged_in) {     
          $form['no_access'] = [
              '#markup' => CommonUtil::no_access_msg(),
          ];     
          return $form;        
      }

         $file = FileShareDatatable::getSharedFile($file_id);

         if (!$file) {
            $messenger = \Drupal::messenger(); 
            $messenger->addWarning( t('This file cannot be found.'));     
            return $form; 
         }

         $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
         if (!$isSiteAdmin && $file['user_id'] != $this->my_user_id  )  {
          $form['intro'] = array(
            '#markup' => t('<i style="font-size:20px; color:red; margin-right:10px;" class="fa-solid fa-ban"></i> You cannot edit this file.'),
             );
            return $form; 
         }

         $Taglist = new TagList();
         $tags = $Taglist->getTagListByRecordId('fileshare', $file_id);


        $form['title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#size' => 90,
            '#maxlength' => 200,
            '#description' => $this->t('File Share Title'),
            '#default_value' =>  $file['title'],
            '#required' => TRUE,
        ];

        $form['title_prev'] = array(
          '#type' => 'hidden',
          '#value' =>  $file['title'],
        );

        $form['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Description'),
            '#rows' => 2,
            '#attributes' => array('style'=>'height:300px;'),
            '#description' => $this->t('File Share Description'),
            '#default_value' =>  $file['description'],
            '#required' => TRUE,
        ];

        $form['description_prev'] = array(
          '#type' => 'hidden',
          '#value' =>  $file['description'],
        );


        $form['filename'] = [
          '#type' => 'file',
          '#title' => $this->t('File'),
          '#size' => 150,
          '#description' => 'Note: Existing file will be deleted. Only support '.str_replace(' ', ', ', $this->allow_file_type).' file format',
        ];        

        $folderAry = FileShareDatatable::getMyEditableFolderList($this->my_user_id);
       
        $form['folder_id'] = [
            '#type' => 'select',
            '#title' => $this->t('Folder Name'),
            '#options' => $folderAry,
            '#default_value' =>  $file['folder_id'],
        ];

        $form['folder_id_prev'] = array(
          '#type' => 'hidden',
          '#value' => $file['folder_id'],
        );        

        $form['file_id'] = array(
          '#type' => 'hidden',
          '#value' => $file_id,
        );
		
		
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

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'),
            '#attributes' => array('style'=>'margin-bottom:20px;'),
          );
        
          $form['cancel'] = array(
            '#type' => 'button',
            '#value' => t('Cancel'),
          );


          $TagList = new TagList();
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

    //----------------------------------------------------------------------------------------------------
    
    /**
     * {@inheritdoc}
     */

   public function submitForm(array &$form, FormStateInterface $form_state) {

    try {
        
        //*************** Thumbnail [Start]

        $tmp_name = $_FILES["files"]["tmp_name"]['filename'];
        $this_filename = CommonUtil::file_remove_character($_FILES["files"]["name"]['filename']);
        $file_ext = strtolower(pathinfo($this_filename, PATHINFO_EXTENSION));

        $this_imagename = str_replace('.'.$file_ext, '', $this_filename);
        $this_pdfname = str_replace('.'.$file_ext, '.pdf', $this_filename);		

        //Obtain the value as entered into the Form
        $title =  $form_state->getValue('title');
        $title_prev =  $form_state->getValue('title_prev');
        $description =  $form_state->getValue('description');
        $description_prev =  $form_state->getValue('description_prev');
        $folder_id =  $form_state->getValue('folder_id');
        $folder_id_prev =  $form_state->getValue('folder_id_prev');
        $file_id =  $form_state->getValue('file_id');
        $tags =  $form_state->getValue('tags');
        $tags_prev =  $form_state->getValue('tags_prev');

        $database = \Drupal::database();
        $transaction = $database->startTransaction();      

        if ($title != $title_prev || $description != $description_prev || $folder_id != $folder_id_prev) {
          
            $query = $database->update('kicp_file_share')->fields([
              'title' => $title, 
              'description' => $description,
              'folder_id' => $folder_id,
              'modify_datetime' => date('Y-m-d H:i:s'),
            ])
            ->condition('file_id', $file_id)
            ->execute();    

          }

          if ($tags != $tags_prev) {
            // rewrite tags
            if ($tags_prev != '') {
                $return2 = TagStorage::markDelete($this->module, $file_id);
            }
            if ($tags != '') {
                $entry1 = array(
                    'module' => 'fileshare',
                    'module_entry_id' => intval($file_id),
                    'tags' => $tags,
                  );
                  $return1 = TagStorage::insert($entry1);                
            }
          }


      if ($_FILES['files']['name']['filename'] != "") {

        //*************** File [Start]
        $tmp_name = $_FILES["files"]["tmp_name"]['filename'];
        $this_filename = CommonUtil::file_remove_character($_FILES["files"]["name"]['filename']);
        $file_ext = strtolower(pathinfo($this_filename, PATHINFO_EXTENSION));
        //*************** File [End]

        $this_file_id = str_pad($file_id, 6, "0", STR_PAD_LEFT);

        $file_system = \Drupal::service('file_system');  
        $FileshareUri = 'private://fileshare';
        $file_path = $file_system->realpath($FileshareUri  . '/file/' . $this_file_id);
        $image_path = $file_system->realpath($FileshareUri  . '/image/' . $this_file_id);        
        FileShareDatatable::createFileshareDir($FileshareUri, $this_file_id);

          // delete previous file from server physically
          if (is_dir($file_path)) {
            $myFileList = scandir($file_path);
            foreach($myFileList as $filename) {
                if(substr($filename,0,1) == "." ) {
                    continue;
                }
                $uri = $FileshareUri."/file/". $this_file_id."/".$filename;
                $fid = CommonUtil::deleteFile($uri);                
            }
          }

        // delete previous image from server physically
        if (is_dir($image_path)) {
            $myFileList = scandir($image_path);
            foreach($myFileList as $filename) {
                if(substr($filename,0,1) == "." ) {
                    continue;
                }
                $uri = $FileshareUri."/image/". $this_file_id."/".$filename;
                $fid = CommonUtil::deleteFile($uri);                 
            }
          }

        $validators = array(
          'file_validate_extensions' => array('jpg jpeg gif png txt doc docx xls xlsx pdf ppt pptx pps odt ods odp zip'),
          'file_validate_size' => array(15 * 1024 * 1024),
          );

        $delta = NULL; // type of $file will be array
        $file = file_save_upload('filename', $validators, 'private://fileshare/file/'.$this_file_id,$delta);

        // rename file, remove white space in filename
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


        if (count($imageDirFile) > 0) {
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

              //******** store image record(s) in table "file_managed" [End]
      } // Files
  
      \Drupal::logger('fileshare')->info('Updated id: %id, title: %title, filename: %filename.',   
      array(
          '%id' => $file_id,
          '%title' => $title,
          '%filename' => $this_filename,
      ));  


      $url = Url::fromUserInput('/fileshare_view/'.$file_id);
      $form_state->setRedirectUrl($url);

      $messenger = \Drupal::messenger(); 
      $messenger->addMessage( t('Files has been updated.'));      


    }

    catch (\Exception $e ) {
       \Drupal::logger('fileshare')->error('Fileshare is not updated ' .$file_id);   
        \Drupal::messenger()->addError(
          t('Unable to update files at this time due to datbase error. Please try again.')
        ); 
        $transaction->rollBack();
    }

    unset($transaction);
  }
    
}


