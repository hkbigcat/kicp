<?php

/**
 * @file
 * Provde Site administrators with a list of all the RSVP List signups
 * so  tehy can know who is attending their events
 */

namespace Drupal\fileshare\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\fileshare\Common\FileShareDatatable;
use Drupal\common\CommonUtil;
use Drupal\common\Controller\TagList;
use Drupal\common\Controller\TagStorage;
use Drupal\common\Follow;
use Drupal\common\RatingData;
use Drupal\common\RatingStorage;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\File;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Url;
use Drupal\common\AccessControl;
use Symfony\Component\HttpFoundation\JsonResponse;

class FileShareController extends ControllerBase {

  public function __construct() {
    $AuthClass = "\Drupal\common\Authentication";
    $authen = new $AuthClass();
    $this->my_user_id = $authen->getUserId();
    $file_system = \Drupal::service('file_system');
    $this->module = 'fileshare';
    $FileshareUri = 'private://fileshare/';
    $this->file_path = $file_system->realpath($FileshareUri);
  }

  /**
   * Gets and returns of folders
   *
   * @return array/null
   */


  /**
   * get Folder 
   *
   * @return array
   * Render array fro the Test Form list
   */
  public function myShareFolder() {

   $table_rows = FileShareDatatable::load_folder($this->my_user_id);
   $table_rows['type'] = 'folders';
   $search_str = \Drupal::request()->query->get('search_str');
   $table_rows['search_str'] =  $search_str;
   
   return [
    '#theme' => 'fileshare-foldertable',
    '#items' => $table_rows,
    '#empty' => t('No entries available.'),
    '#pager' => ['#type' => 'pager',
      ],
   ];      


  }
  
  /**
   * gete files
   *
   * @return array
   * Render array fro the Test Form list
   */
  public function getShareFile() {

    $table_rows_file = FileShareDatatable::getSharedFile();
    $myRecordOnly = \Drupal::request()->query->get('my');
        
    return [
        '#theme' => 'fileshare-files',
        '#items' => $table_rows_file,
        '#my_user_id' => $this->my_user_id,
        '#empty' => t('No entries available.'),
        '#myRecordOnly' => $myRecordOnly,
        '#pager' => ['#type' => 'pager',
                    ],
    ];
  
  }

/**
   * gete files
   *
   * @return array
   * Render array fro the Test Form list
   */
  public function viewShareFile($file_id) {

    $table_rows_file = FileShareDatatable::getSharedFile($file_id, $this->my_user_id);
    if ($table_rows_file == null ) {
      return [
        '#markup' => '<p>You are not authorize to access this file or file does no exisit</p>',
      ];

    }

    $this_file_id = str_pad($file_id, 6, "0", STR_PAD_LEFT);

    $image_path =  $this->file_path.'/image/'.$this_file_id;
    
    $dirFile = array();

    if (is_dir($image_path)) {
      $imageDirFile = scandir($image_path);
   
    // has thumbnail(s)    
      if (count($imageDirFile) > 0) {
        $slideContent = array();
        natsort($imageDirFile);
        foreach ($imageDirFile as $attach_id => $attach) {
          if ($attach != "." && $attach != "..") {
            $slideContent[]=$attach;
          }
              
        } 
      
      }
    }
        
  $TagList = new TagList();
  $taglist = $TagList->getTagsForModule('fileshare', $file_id);
  $tagURL = http_build_query($taglist);
  $RatingData = new RatingData();
  $rating = $RatingData->getList('fileshare', $file_id);

  $table_rows_file['tagURL'] = $tagURL;
  $table_rows_file["follow"] = Follow::getFollow($record["user_id"], $my_user_id); 

  $rsHadRate = $RatingData->checkUserHadRate($this->module, $file_id, $this->my_user_id);
  $rating['rsHadRate'] = $rsHadRate;
  $rating['module'] = 'fileshare';
  
    return [
        '#theme' => 'fileshare-view',
        '#user_id' => $this->my_user_id,
        '#items' => $table_rows_file,
        '#tags' => $taglist,
        '#rating' => $rating,
        '#slides' => $slideContent,
        '#empty' => t('No entries available.'),
    ];

   
  }

  

  public function deleteShareFile($file_id = NULL) {

    $delFile = FileShareDatatable::getSharedFile($file_id);
    if ( $delFile['file_id'] == null) {

      $url = Url::fromRoute('fileshare.fileshare_content');
      return new RedirectResponse($url->toString());

      \Drupal::messenger()->addError(
        t('Unable to delete this File ')
        );

    }
    // delete record
      $database = \Drupal::database();
      $transaction =  $database->startTransaction();

      try{
        $query = $database->update('kicp_file_share')->fields([
          'is_deleted'=>1 , 
          'modify_datetime' => date('Y-m-d H:i:s'),
        ])
        ->condition('file_id', $file_id);
        $affected_rows = $query->execute();

        if ($affected_rows) {

          // delete files
          $file_deleted = FileShareDatatable::deleteFiles($file_id,  $this->file_path);
          // delete tags
          $return2 = TagStorage::markDelete($this->module, $file_id);

          // delete rating
          $rating = RatingStorage::markDelete($this->module, $file_id);

          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('File has been deleted'));
        } else {
          \Drupal::messenger()->addError(
            t('Unable to delete file at this time due to datbase error. Please try again.')
          );   
        }
      }
      catch (\Exception $e ) {
        \Drupal::messenger()->addError(
          t('Unable to delete file: '.$imagename.' or '.$filename.' Please try again.')
        ); 
        $transaction->rollBack();
     }
     unset($transaction);   

     $response = array('result' => $err_code);
     return new JsonResponse($response);

  }


  public function deleteShareFileFolder($folder_id=null) {

    $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 

    $folder = FileShareDatatable::load_folder($this->my_user_id,$folder_id);
    if ( $folder['folder_id'] == null) {

      $url = Url::fromRoute('fileshare.fileshare_folder');
      return new RedirectResponse($url->toString());

      \Drupal::messenger()->addError(
        t('Cannot delete this File Folder : '.$folder['folder_name']  )
        );

    }

      // delete record
      $database = \Drupal::database();
      $transaction =  $database->startTransaction();

      try {
        $query = $database->update('kicp_file_share_folder')->fields([
          'is_deleted'=>1 , 
          'modify_datetime' => date('Y-m-d H:i:s'),
        ])
        ->condition('folder_id', $folder_id)
        ->execute();


        // delete tags
        $return2 = TagStorage::markDelete('fileshare_folder', $folder_id);

        // delete access control
        $return3 = AccessControl::deleteAccessControlRecord('fileshare_folder', $folder_id);

        // delete files records
        $query = $database->update('kicp_file_share')->fields([
          'is_deleted'=>1 , 
          'modify_datetime' => date('Y-m-d H:i:s'),
        ])
        ->condition('folder_id', $folder_id);
        if (!$isSiteAdmin) {
          $query->condition('user_id', $this->my_user_id);
        }
        $affected_rows = $query->execute();


        $actual_files = 0;
        $allfiles = FileShareDatatable::getFilesIDInsideFolder($folder_id, $this->my_user_id);

        $myFile = array();
        foreach ($allfiles as $file) {

          // delete files
          $file_deleted = FileShareDatatable::deleteFiles($file_id,  $this->file_path);

          // delete tags
          $return2 = TagStorage::markDelete($this->module, $file['file_id']);

          // delete rating
          $rating = RatingStorage::markDelete($this->module, $file['file_id']);

        }

        $messenger = \Drupal::messenger(); 
        $messenger->addMessage( t('File Folder and has been deleted. '));

      } 
      catch (\Exception $e ) {
        \Drupal::messenger()->addError(
          t('Unable to delete file folder at this time due to datbase error. Please try again.')
        ); 
        $transaction->rollBack();
     }      
     unset($transaction);
     $response = array('result' => $err_code);
     return new JsonResponse($response);

  }

  public static function getFileLocation($file_id=NULL) {
        
    $sharedFile = FileShareDatatable::getSharedFile($file_id);
    
    $this_file_id = str_pad($file_id, 6, "0", STR_PAD_LEFT);
    $file_name = $sharedFile['file_name'];
    
    // file in "private" folder
    $file_path = 'sites/default/files/private/fileshare/file/'.$this_file_id.'/'.$file_name;
    
    return $file_path;
    
  } 


  public function getShareFileTag() {


    $tags = array();
    
  
    $tagsUrl = \Drupal::request()->query->get('tags');

    $table_rows_file = FileShareDatatable::getSharedFileByTags($tags);

    if ($tagsUrl) {
      $tags = json_decode($tagsUrl);
      if ($tags && count($tags) > 0 ) {
        $tmp = $tags;
      }
    }

    return [
        '#theme' => 'fileshare-files',
        '#items' => $table_rows_file,
        '#tags' => $tags,
        '#empty' => t('No entries available.'),
        '#tagsUrl' => $tmp,
        '#pager' => ['#type' => 'pager',
                    ],
    ];    

  }


  public function HandleAutocomplete($string)
  {
    
    $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree(
      'tags', // The taxonomy term vocabulary machine name.
      0,                 // The "tid" of parent using "0" to get all.
      1,                 // Get only 1st level.
      TRUE               // Get full load of taxonomy term entity.
    );
     
    $results = [];
     
    foreach ($tree as $term) {
      $results[] = $term->getName();
    }

    return results;
  }



}
