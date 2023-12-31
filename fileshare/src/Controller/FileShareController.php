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
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\File;
use Drupal\Core\File\FileSystemInterface;


class FileShareController extends ControllerBase {

  public function __construct() {

    $file_system = \Drupal::service('file_system');


    $this->module = 'fileshare';
    $this->ServerAbsolutePath = CommonUtil::getSysValue('server_absolute_path'); // get server absolute path
    $this->app_path = CommonUtil::getSysValue('app_path'); // app_path
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

       /*

    $content = [];
    $content['message'] = [
      '#markup' => t('File Share Folder'),
    ];

    $headers = [
        t('ID'),
        t('Folder Name'),
        t('Users'),
    ];

*/

   //$table_rows = $this->load_folder();
   $table_rows = FileShareDatatable::load_folder();   
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

   /*

   $content['table'] = [
     '#type' => 'table',
     '#header' => $headers,
     '#rows' => $table_rows,
     '#empty' => t('No entries available.'),
   ];

   
    $content['#cache']['max-age'] = 0;

    return $content;
    */

  }
  
  /**
   * gete files
   *
   * @return array
   * Render array fro the Test Form list
   */
  public function getShareFile() {

    $table_rows_file = FileShareDatatable::getSharedFile();
    //$TagList = new TagList();
    //$taglist = $TagList->getTagsForModule('fileshare');
    
    return [
        '#theme' => 'fileshare-files',
        '#items' => $table_rows_file,
        //'#tags' => $taglist,
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
  public function viewShareFile($file_id) {



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
  
  $table_rows_file = FileShareDatatable::getSharedFile($file_id);
  $table_rows_file['tagURL'] = $tagURL;
    
    return [
        '#theme' => 'fileshare-view',
        '#items' => $table_rows_file,
        '#tags' => $taglist,
        '#slides' => $slideContent,
        '#empty' => t('No entries available.'),
    ];

   
  }

  

  public function deleteShareFile($file_id = NULL) {
    $current_time =  \Drupal::time()->getRequestTime();

    // delete record

      $database = \Drupal::database();
      $query = $database->update('kicp_file_share')->fields([
        'is_deleted'=>1 , 
        'modify_datetime' => date('Y-m-d H:i:s', $current_time),
      ])
      ->condition('file_id', $file_id)
      ->execute();


      // Check file list of shared file
      $this_file_id = str_pad($file_id, 6, "0", STR_PAD_LEFT);
      $myFile = array();
      $file_dir = $this->file_path.'/file/'.$this_file_id;
      $image_dir =  $this->file_path.'/image/'.$this_file_id;
      
      
      // delete file from server physically
      if (is_dir($file_dir)) {
          $myFileList = scandir($file_dir);
          foreach($myFileList as $filename) {
              if($filename == "." || $filename == "..") {
                  continue;
              }
              unlink($file_dir.'/'.$filename);
          }
      }
      

      // delete thumbnail from server physically
      if (is_dir($image_dir)) {
          $myImageList = scandir($image_dir);
          foreach($myImageList as $imagename) {
              if($imagename == "." || $imagename == "..") {
                  continue;
              }
              unlink($image_dir.'/'.$imagename);
          }
      }


      $query = $database->delete('file_managed')
        ->condition('uid', $file_id)
        ->execute();


      // delete tags

      $query = $database->update('kicp_tags')->fields([
        'is_deleted'=>1 , 
      ])
      ->condition('fid', $file_id)
      ->condition('module', 'fileshare')
      ->execute();

      
      return new RedirectResponse("/fileshare");


  }


  public function deleteShareFileFolder($folder_id=null) {


      // delete record

      try {
        $database = \Drupal::database();
        $query = $database->update('kicp_file_share_folder')->fields([
          'is_deleted'=>1 , 
          'modify_datetime' => date('Y-m-d H:i:s', $current_time),
        ])
        ->condition('folder_id', $folder_id)
        ->execute();


      // delete tags

        $query = $database->update('kicp_tags')->fields([
          'is_deleted'=>1 , 
        ])
        ->condition('fid', $folder_id)
        ->condition('module', 'fileshare_folder')
        ->execute();


        return new RedirectResponse("/fileshare_folder");
      } 
      catch (\Exception $e ) {
        \Drupal::messenger()->addError(
          t('Unable to delete files folder at this time due to datbase error. Please try again.')
        ); 
     }      

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
