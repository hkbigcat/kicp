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
use Drupal\common\TagList;
use Drupal\common\TagStorage;
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

  public $my_user_id;
  public $module;
  public $file_path;

  public function __construct() {

    $AuthClass = "\Drupal\common\Authentication";
    $authen = new $AuthClass();

    $current_user = \Drupal::currentUser();
    $this->my_user_id = $current_user->getAccountName();
    
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

    $url = Url::fromRoute('<front>')->toString();
    $logged_in = \Drupal::currentUser()->isAuthenticated();
    if (!$logged_in) {
        return new RedirectResponse($url.'no_access');
    }

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

    $url = Url::fromUri('base:/no_access');
    $logged_in = \Drupal::currentUser()->isAuthenticated();
    if (!$logged_in) {
        return new RedirectResponse($url->toString());
    }

    $tags = array();
    $tmp = "";
    $tagsUrl = \Drupal::request()->query->get('tags');
    if ($tagsUrl) {
      $tags = json_decode($tagsUrl);
      if ($tags && count($tags) > 0 ) {
        $tmp = $tags;
      }
    }    
    $table_rows_file = FileShareDatatable::getSharedFile();
    $myRecordOnly = \Drupal::request()->query->get('my');
    $myfollowed = \Drupal::request()->query->get('my_follow');        
    return [
        '#theme' => 'fileshare-files',
        '#items' => $table_rows_file,
        '#my_user_id' => $this->my_user_id,
        '#tags' => $tags,
        '#empty' => t('No entries available.'),
        '#tagsUrl' => $tmp,
        '#myRecordOnly' => $myRecordOnly,
        '#myfollowed' =>  $myfollowed,        
        '#pager' => ['#type' => 'pager',
                    ],
    ];
  
  }

  public function viewShareFileOld() {
    $id = \Drupal::request()->query->get('id');
    if ($id && is_numeric($id))
        $url = Url::fromUri('base:/fileshare_view/'.$id);
    else {
            $url = Url::fromUri('base:/fileshare/');
    }
    return new RedirectResponse($url->toString(), 301);
  }

/**
   * gete files
   *
   * @return array
   * Render array fro the Test Form list
   */
  public function viewShareFile($file_id) {

    $url = Url::fromRoute('<front>')->toString();
    $logged_in = \Drupal::currentUser()->isAuthenticated();
    if (!$logged_in) {
        return new RedirectResponse($url.'no_access');
    }

    $table_rows_file = FileShareDatatable::getSharedFile($file_id);
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
  $tagURL = "";
  if ($taglist)
    $tagURL = http_build_query($taglist);
  $RatingData = new RatingData();
  $rating = $RatingData->getList('fileshare', $file_id);

  $table_rows_file['tagURL'] = $tagURL;
  $table_rows_file["follow"] = Follow::getFollow($table_rows_file["user_id"], $this->my_user_id); 

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


    $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 

    // delete record
      $database = \Drupal::database();
      $transaction =  $database->startTransaction();

      try{
        $query = $database->update('kicp_file_share')->fields([
          'is_deleted'=>1 , 
          'modify_datetime' => date('Y-m-d H:i:s'),
        ])
        ->condition('file_id', $file_id)
        ->condition('is_deleted', 0);
        if (!$isSiteAdmin) {
          $query->condition('user_id', $this->my_user_id);
        }        
        $row_affected = $query->execute();

        if ($row_affected) {

          // delete files
          $file_deleted = FileShareDatatable::deleteFiles($file_id);
          if ($file_deleted==0) {
            \Drupal::logger('fileshare')->error('no file delete. ID: '.$file_id);
            \Drupal::messenger()->addError(
              t('No file deleted')
            );             
          }

          // delete tags
          $return2 = TagStorage::markDelete($this->module, $file_id);

          // delete rating
          $rating = RatingStorage::markDelete($this->module, $file_id);

          \Drupal::logger('fileshare')->info('Deleted id: %id, row_affected: %row_affected', 
          array(
              '%id' => $file_id,
              '%row_affected' => $row_affected,
          ));       

          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('File has been deleted'));
        } else {
          \Drupal::messenger()->addError(
            t('Unable to delete file. Please try again.')
          );   
        }
      }
      catch (\Exception $e ) {
        \Drupal::messenger()->addError(
          t('Unable to delete file id: '.$file_id.' Please try again.')
        ); 
        $transaction->rollBack();
     }
     unset($transaction);   
     $response = array('result' => 1);
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
        ->condition('is_deleted', 0)
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

        if ($allfiles) {
          foreach ($allfiles as $file) {

            // delete files
            $file_deleted = FileShareDatatable::deleteFiles($file_id);

            // delete tags
            $return2 = TagStorage::markDelete($this->module, $file['file_id']);

            // delete rating
            $rating = RatingStorage::markDelete($this->module, $file['file_id']);

          }
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('File Folder and has been deleted. '));  
        } else {
          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Folder has been deleted. No file inside.'));  
        }

      } 
      catch (\Exception $e ) {
        \Drupal::messenger()->addError(
          t('Unable to delete file folder at this time due to datbase error. Please try again.')
        ); 
        $transaction->rollBack();
     }      
     unset($transaction);
     $response = array('result' => 1);
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


  public function HandleAutocomplete($string)
  {
    
    $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree(
      'tags', // The taxonomy term vocabulary machine name.
      0,                 // The "tid" of parent using "0" to get all.
      1,                 // Get only 1st level.
      TRUE               // Get full load of taxonomy term entity.
    );
     
    $results = [];
     
    if (!$tree )
      return null;
    foreach ($tree as $term) {
      $results[] = $term->getName();
    }

    return results;
  }


  public static function Breadcrumb() {

    $base_url = Url::fromRoute('fileshare.fileshare_content');
    $folder_url = Url::fromRoute('fileshare.fileshare_folder');
    $base_path = [
        'name' => 'Fileshare', 
        'url' => $base_url,
    ];
    $folder_path = [
      'name' => 'Folder Access Control', 
      'url' =>  $folder_url,
   ];
    $breads = array();
    $route_match = \Drupal::routeMatch();
    $routeName = $route_match->getRouteName();

    if ($routeName=="fileshare.fileshare_content") {
      $breads[] = [
          'name' => 'Fileshare',
      ];
    } else if ($routeName=="fileshare.fileshare_view") {
      $file_id = $route_match->getParameter('file_id');
      $file_title = FileShareDatatable::getTitle($file_id);
      $breads[] = $base_path;
      $breads[] = [
        'name' => $file_title??'No File',
      ];
    } else if ($routeName=="fileshare.fileshare_folder_add") {
      $breads[] = $base_path;
      $breads[] = $folder_path;
      $breads[] = [
        'name' => 'Add' ,
      ];
    } else if ($routeName=="fileshare.fileshare_folder_change") {
      $breads[] = $base_path;
      $breads[] = $folder_path;
      $breads[] = [
        'name' => 'Edit' ,
      ];
    } else if ($routeName=="fileshare.fileshare_folder") {
      $breads[] = $base_path;
      $breads[] = [
        'name' => 'Folder Access Control' ,
      ];
    }  else if ($routeName=="fileshare.fileshare_add") {
      $breads[] = $base_path;
      $breads[] = [
        'name' => 'Add' ,
      ];
    }  else if ($routeName=="fileshare.fileshare_change") {
      $breads[] = $base_path;
      $breads[] = [
        'name' => 'Edit' ,
      ];
    }

    return $breads;

  }

}
