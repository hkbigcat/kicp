<?php

/**
 * @file
 * Contains the settings for administrating the Test Form
 */


namespace Drupal\fileshare\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fileshare\Common\FileShareDatatable;
use Drupal\common\Controller\TagList;
use Drupal\common\Controller\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\Core\Database\Database;
use Drupal\file\FileInterface;
use Drupal\file\Entity;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class FileShareChange extends FormBase {

  public function __construct() {
    $AuthClass = "\Drupal\common\Authentication";
    $authen = new $AuthClass();
    $this->$my_user_id = $authen->getUserId();      
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


         $file = FileShareDatatable::getSharedFile($file_id);
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
          '#default_value' =>  $file['description'],
        );


        $form['filename'] = [
          '#type' => 'file',
          '#title' => $this->t('File'),
          '#size' => 150,
          '#description' => 'Only support '.str_replace(' ', ', ', $this->allow_file_type).' file format',
        ];        

        $folderAry = FileShareDatatable::getMyEditableFolderList($this->$my_user_id);
       
        $form['folder_id'] = [
            '#type' => 'select',
            '#title' => $this->t('Folder Name'),
            '#options' => $folderAry,
            '#default_value' =>  $file['folder_id'],
        ];

        $form['folder_id_prev'] = array(
          '#type' => 'hidden',
          '#value' => $file->folder_id,
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
              '#description' => t($taglist),
              '#attributes' => array('style'=>'border: 1px solid #7A7A7A;background: #FCFCE6;'),
          );
          
          $taglist = $TagList->getList($this->module);
          $form['t1'] = array(
              '#title' => t('File Share Tags'),
              '#type' => 'details',
              '#open' => true,
              '#description' => t($taglist),
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

      //*************** File [Start]

      $tmp_name = $_FILES["files"]["tmp_name"]['filename'];
      $this_filename = str_replace(' ', '_', $_FILES["files"]["name"]['filename']);
      $this_filename = str_replace("'", "", $this_filename);      // remove single quote
      $this_filename = str_replace('"', '', $this_filename);      // remove double quote
      $this_filename = str_replace('&', '_', $this_filename);      // remove & sign
      $this_filename = str_replace('!', '', $this_filename);      // remove ! sign
      $this_filename = str_replace('@', '', $this_filename);      // remove @ sign
      $this_filename = str_replace('#', '', $this_filename);      // remove # sign
      $this_filename = str_replace('$', '', $this_filename);      // remove $ sign
      $this_filename = str_replace('%', '', $this_filename);      // remove % sign
      $this_filename = str_replace('^', '', $this_filename);      // remove ^ sign
      $this_filename = str_replace('+', '', $this_filename);      // remove + sign
      $this_filename = str_replace('=', '', $this_filename);      // remove = sign

      
      $file_ext = strtolower(pathinfo($this_filename, PATHINFO_EXTENSION));		

        //*************** File [End]
        
        //*************** Thumbnail [Start]


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
        $current_time =  \Drupal::time()->getRequestTime();

        $database = \Drupal::database();
        

        if ($title != $title_prev || $description != $description_prev || $folder_id != $folder_id_prev) {

          
            $query = $database->update('kicp_file_share')->fields([
              'title' => $title, 
              'description' => $description,
              'folder_id' => $folder_id,
              'modify_datetime' => date('Y-m-d H:i:s', $current_time),
            ])
            ->condition('file_id', $file_id)
            ->execute();    

          }

          

          if ($tags != $tags_prev) {
            // rewrite tags
            if ($tags_prev != '') {
                $query = $database->update('kicp_tags')->fields([
                    'is_deleted'=>1 , 
                  ])
                  ->condition('fid', $file_id)
                  ->condition('module', 'fileshare')
                  ->execute();                
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

        /////////////// FILE //////////////

        //$ServerAbsolutePath = CommonUtil::getSysValue('server_absolute_path'); // get server absolute path
        //$app_path = CommonUtil::getSysValue('app_path'); // app_path

        $this_file_id = str_pad($file_id, 6, "0", STR_PAD_LEFT);

        $file_system = \Drupal::service('file_system');  
        $FileshareUri = 'private://fileshare/';
        $file_path = $file_system->realpath($FileshareUri  . '/file/' . $this_file_id);
        $image_path = $file_system->realpath($FileshareUri  . '/image/' . $this_file_id);        
        FileShareDatatable::createFileshareDir($FileshareUri, $this_file_id);


          // delete previous file from server physically
          if (is_dir($file_path)) {
            $myFileList = scandir($file_path);
            foreach($myFileList as $filename) {
                if($filename == "." || $filename == "..") {
                    continue;
                }
                unlink($file_path.'/'.$filename);
            }
          }

          $query = $database->delete('file_managed')
          ->condition('uid', $file_id)
          ->execute();

        // delete previous image from server physically
        if (is_dir($image_path)) {
            $myFileList = scandir($image_path);
            foreach($myFileList as $filename) {
                if($filename == "." || $filename == "..") {
                    continue;
                }
                unlink($image_path.'/'.$filename);
            }
          }

        $validators = array(
          'file_validate_extensions' => array('jpg jpeg gif png txt doc docx xls xlsx pdf ppt pptx pps odt ods odp zip'),
          'file_validate_size' => array(15 * 1024 * 1024),
          );

        $delta = NULL; // type of $file will be array
        $file = file_save_upload('filename', $validators, 'private://fileshare/file/'.$this_file_id,$delta);

        // rename file, remove white space in filename
        if(file_exists($file_path."/".$_FILES['files']['name']['filename'])) {
            exec("mv \"".$file_path."/".$_FILES['files']['name']['filename']."\" \"".$file_path."/".$this_filename."\"");     
        }

        $file[0]->setPermanent();
        $file[0]->uid = $file_id;
        $file[0]->save();
        $url = $file[0]->createFileUrl(FALSE);

        // create thumbnail(s) of all PDF pages

        //*************************************************
        # convert the uploaded file to PDF first for other file format (e.g. doc, docx, ppt, pptx, txt)


        $file_temp = str_replace(["(",")"],["\(","\)"],$this_pdfname);
        $img_temp = str_replace(["(",")"],["\(","\)"],$this_imagename);
        if($file_ext != "pdf") {
        
            exec("export HOME=".$file_path." && /usr/bin/libreoffice --headless --convert-to pdf --outdir ".$file_path." \"".$file_path."/".$this_filename."\"");
          
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
  

      $url = Url::fromUserInput('/fileshare_view/'.$file_id);
      $form_state->setRedirectUrl($url);

      $messenger = \Drupal::messenger(); 
      $messenger->addMessage( t('Files has been updated '));      


    }

    catch (\Exception $e ) {
        \Drupal::messenger()->addError(
          t('Unable to update filess at this time due to datbase error. Please try again.')
        ); 

    }

    // Redirect to home.
    //$form_state->setRedirect('<front>');
  }
    
}


