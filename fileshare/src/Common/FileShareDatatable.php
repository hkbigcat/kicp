<?php

/**
 * @file
 * Provde Site administrators with a list of all the RSVP List signups
 * so  tehy can know who is attending their events
 */

namespace Drupal\fileshare\Common;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use \Drupal\Core\Routing;
use Drupal\Core\File\FileSystemInterface;
use Drupal\common\Controller\TagList;
use Drupal\common\RatingData;


class FileShareDatatable extends ControllerBase {

    public static function load_folder($folder_id=NULL) {

        $search_str = \Drupal::request()->query->get('search_str');

        try {
          $query = \Drupal::database()->select('kicp_file_share_folder', 'r');
          $query->leftJoin('kicp_access_control', 'a', 'r.folder_id = a.record_id AND a.module = :module AND a.is_deleted = :is_deleted', [':module' => 'fileshare', ':is_deleted' => 0]);
          $query->leftJoin('xoops_users', 'c', 'r.user_id = c.user_id');
          $query->fields('r', ['folder_id', 'folder_name','user_id']);
          $query->fields('c', ['user_name']);
          $query->addExpression('COUNT(a.id)', 'folder_access');
          $query->condition('r.is_deleted', '0');
          $query->groupBy('r.folder_id');
          $query->orderBy('r.folder_name');
          if ($folder_id != NULL) {
            $query->condition('r.folder_id', $folder_id);
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
             t('Unable to load fileshare folder at this time due to datbase error. Please try again. '.$e)
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
          $RatingData = new RatingData();
          foreach ($result as $record) {
            $record["tags"] = $TagList->getTagsForModule('fileshare', $record["file_id"]);   
            $record["rating"] = $RatingData->getList('fileshare', $record["file_id"]);
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
        $tags1 = $database-> select('kicp_tags', 't');
        $tags1-> condition('tag', $tags, 'IN');
        $tags1-> condition('t.module', 'fileshare');
        $tags1-> condition('t.is_deleted', '0');
        $tags1-> addField('t', 'fid');
        $tags1-> groupBy('t.fid');
        $tags1-> having('COUNT(fid) >= :matches', [':matches' => count($tags)]);        


/*

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
*/
        $query-> condition('file_id', $tags1, 'IN');
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
          t('Unable to load fileshare by tags at this time due to datbase error. Please try again. '.$e)
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