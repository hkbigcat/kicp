<?php

/**
 * @file
 * Provde Site administrators with a list of all the RSVP List signups
 * so  tehy can know who is attending their events
 */

namespace Drupal\fileshare\Common;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use \Drupal\Core\Routing;
use Drupal\Core\File\FileSystemInterface;
use Drupal\common\Controller\TagList;

class FileShareDatatable extends ControllerBase {

    public static function load_folder() {

        $search_str = \Drupal::request()->query->get('search_str');

        try {
          $database = \Drupal::database();
          $query = $database-> select('kicp_file_share_folder', 'r');
          $query->fields('r', ['folder_id', 'folder_name', 'user_id']);
          $query->condition('folder_name', '', '<>');
          $query->condition('is_deleted', '0');
          if ($folder_id != NULL) {
            $query->condition('folder_id', $folder_id);
            $result =  $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($result as $row) {
              $entries = $row;
            }
          }  else {
            if ($search_str && $search_str !="") {
              $query->condition('r.folder_name', '%' . $search_str . '%', 'LIKE');
            }            
            $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
            $entries =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);
          }
   
          return $entries;
       }
   
         catch (\Exception $e) {
   
          \Drupal::messenger()->addStatus(
             t('Unable to load fileshare folder at this time due to datbase error. Please try again.')
           );
   
           return NULL;
         }
     }

     public static function getSharedFile($file_id = NULL) {

      $folder_id = \Drupal::request()->query->get('folder_id');
      

      try {
        $database = \Drupal::database();
        $selected_query = $database-> select('kicp_file_share', 'a'); 
        $selected_query -> join('kicp_file_share_folder', 'b', 'a.folder_id = b.folder_id');
        $selected_query-> fields('a', ['file_id', 'title','description','file_name', 'folder_id', 'image_name', 'folder_id', 'modify_datetime']);
        $selected_query-> fields('b', ['folder_name']);
        $selected_query-> condition('a.is_deleted', '0', '=');
        if ($folder_id != NULL) {
          $selected_query-> condition('a.folder_id', $folder_id, '=');
        }
        if ($file_id != NULL) {
          $selected_query-> condition('file_id', $file_id, '=');
          $entries =  $selected_query->execute()->fetch(\PDO::FETCH_ASSOC);
        } else {  
          $selected_query-> orderBy('modify_datetime', 'DESC');
          $pager = $selected_query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
          $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);
          $entries=array();
          $TagList = new TagList();
          foreach ($result as $record) {
            $record["tags"] = $TagList->getTagsForModule('fileshare', $record["file_id"]);   
            $entries[] = $record;
          }
        }
        if ($entries)  {
          return $entries;
        }
     }
     catch (\Exception $e) {
        \Drupal::messenger()->addStatus(
           t('Unable to load fileshare folder at this time due to datbase error. Please try again.')
         );
       return  NULL;
     }

  }

  public static function getSharedFileByTags($tags) {

    
    //$addtag = \Drupal::request()->query->get('addtag');

    $output=array();
    $TagList = new TagList();

    $tagsUrl = \Drupal::request()->query->get('tags');
    if ($tagsUrl) {
      $tags = json_decode($tagsUrl);
    }    

    try {
      //$sql = "SELECT a.*, b.folder_name FROM `kicp_file_share` a left join kicp_file_share_folder b on a.folder_id = b.folder_id where a.file_id in (select fid FROM `kicp_tags` b where module like 'fileshare' and lower(tag) like '$tags' and is_deleted = 0 ) and a.is_deleted = 0";
      $database = \Drupal::database();
      $query = $database-> select('kicp_file_share', 'a'); 
      $query -> join('kicp_file_share_folder', 'b', 'a.folder_id = b.folder_id');
      if ($tags && count($tags) > 0 ) {
        $query -> join('kicp_tags', 't', 'a.file_id = t.fid');
        $query-> condition('t.module', 'fileshare');
        $orGroup = $query->orConditionGroup();
        foreach($tags as $tmp) {
          $orGroup->condition('t.tag', $tmp);
        }
        $query->condition($orGroup);
        $query-> condition('t.is_deleted', '0');        
        $query-> groupBy('a.file_id', '0');
        $query->addExpression('COUNT(a.file_id)>='.count($tags) , 'occ');
        $query->havingCondition('occ', 1);        

      }
      $query-> fields('a', ['file_id', 'title','description','file_name', 'folder_id', 'image_name', 'folder_id', 'modify_datetime']);
      $query-> fields('b', ['folder_name']);
      $query-> condition('a.is_deleted', '0');
      $query-> orderBy('modify_datetime', 'DESC');
      $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
      $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);

      foreach ($result as $record) {
        $record["tags"] = $TagList->getTagsForModule('fileshare', $record["file_id"]);   
        $output[] = $record;
      }

      return $output;

    }
    catch (\Exception $e) {
       \Drupal::messenger()->addStatus(
          t('Unable to load fileshare by tags at this time due to datbase error. Please try again.')
        );
      return  NULL;
    }
    
  }

  public static  function createFileshareDir($FileshareUri, $this_file_id) {
     

    $file_system = \Drupal::service('file_system');

    $createDir = $FileshareUri  . '/file/' . $this_file_id;
    if (!is_dir($file_system->realpath($createDir ))) {
      // Prepare the directory with proper permissions.
      if (!$file_system->prepareDirectory( $createDir , FileSystemInterface::CREATE_DIRECTORY)) {
          throw new \Exception('Could not create the fileshare file_id directory.');
      }
    }

    $createDir = $FileshareUri  . '/image/' . $this_file_id;
    if (!is_dir($file_system->realpath($createDir ))) {
      // Prepare the directory with proper permissions.
      if (!$file_system->prepareDirectory( $createDir , FileSystemInterface::CREATE_DIRECTORY)) {
          throw new \Exception('Could not create the fileshare file_id directory.');
      }
    }

  }


}