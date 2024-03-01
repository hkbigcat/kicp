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

  public function __construct() {

    $this->module = 'fileshare';
   
  }  

  public static function load_folder($my_user_id=null,$folder_id=NULL) {

      
      $search_str = \Drupal::request()->query->get('search_str');

      try {
        $query = \Drupal::database()->select('kicp_file_share_folder', 'r');
        $query->leftJoin('kicp_access_control', 'a', 'r.folder_id = a.record_id AND a.module = :module AND a.is_deleted = :is_deleted', [':module' => 'fileshare', ':is_deleted' => 0]);
        $query->leftJoin('xoops_users', 'c', 'r.user_id = c.user_id');
        $query->fields('r', ['folder_id', 'folder_name','user_id']);
        $query->fields('c', ['user_name']);
        $query->addExpression('COUNT(a.id)', 'folder_access');
        $query->condition('r.is_deleted', '0');

        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
        if (!$isSiteAdmin) {
          $query->condition('r.user_id', $my_user_id);
        }

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


    public static function getMyEditableFolderList($my_user= null) {

      $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
      if($isSiteAdmin) {
          $sql = "SELECT a.folder_id, CONCAT(a.folder_name,' [',b.user_name,']') as folder_name FROM kicp_file_share_folder a LEFT JOIN xoops_users b ON (a.user_id=b.user_id) WHERE a.is_deleted=0 ORDER BY folder_name";
      } else {
          $sql = "SELECT a.folder_id, a.folder_name, b.group_type, b.group_id, a.user_id as folder_owner, count(b.id) as is_restricted, c.pub_group_owner as pub_group_owner, d.user_id as buddy_group_owner, e.pub_user_id, f.buddy_user_id, b.allow_edit
                      FROM kicp_file_share_folder a
                      LEFT JOIN kicp_access_control b ON (b.module='fileshare' AND b.record_id=a.folder_id AND b.is_deleted=0)
                      LEFT JOIN kicp_public_group c ON (b.module='fileshare' AND b.group_type='P' AND b.group_id=c.pub_group_id AND c.is_deleted=0)
                      LEFT JOIN kicp_buddy_group d ON (b.module='fileshare' AND b.group_type='B' AND b.group_id=d.buddy_group_id AND d.is_deleted=0)
                      LEFT JOIN kicp_public_user_list e ON (b.module='fileshare' AND b.group_type='P' AND b.group_id=e.pub_group_id AND e.is_deleted=0 AND e.pub_user_id='".$my_user_id."')
                      LEFT JOIN kicp_buddy_user_list f ON (b.module='fileshare' AND b.group_type='B' AND b.group_id=f.buddy_group_id AND f.is_deleted=0 AND f.buddy_user_id='".$my_user_id."')
                      WHERE a.is_deleted=0
                      GROUP BY a.folder_id, b.group_type, b.group_id, b.allow_edit ";
          $sql .= " HAVING is_restricted=0 OR ((pub_user_id='".$my_user_id."' OR buddy_user_id='".$my_user_id."') AND allow_edit=1) ";
          $sql .= " ORDER BY a.folder_name";
      }

      $database = \Drupal::database();
      $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

      $folderAry = array();
      foreach($result as $record) {
          $folderAry[$record['folder_id']] = $record['folder_name'];
      }

      return $folderAry;
  }


     public static function getSharedFile($my_user_id=null, $file_id = NULL) {

      $folder_id = \Drupal::request()->query->get('folder_id');
      
      $AuthClass = "\Drupal\common\Authentication";
      $authen = new $AuthClass();
      $my_user_id = $authen->getUserId();
    

      try {
        $database = \Drupal::database();
        $selected_query = $database-> select('kicp_file_share', 'a'); 
        $selected_query -> leftjoin('kicp_file_share_folder', 'j', 'a.folder_id = j.folder_id');
        $selected_query -> leftjoin('xoops_users', 'u', 'a.user_id = u.user_id');
        $selected_query -> leftjoin('kicp_access_control', 'b', 'b.record_id = j.folder_id AND b.module = :module AND b.is_deleted = :is_deleted', [':module' => 'fileshare', ':is_deleted' => '0']);
        $selected_query -> leftjoin('kicp_public_user_list', 'e', 'b.group_id = e.pub_group_id AND b.module = :module AND b.is_deleted = :is_deleted AND b.group_type= :typeP AND e.is_deleted = :is_deleted AND e.pub_user_id = :user_id', [':module' => 'fileshare', ':is_deleted' => '0', ':typeP' => 'P', 'user_id' => $my_user_id]);
        $selected_query -> leftjoin('kicp_buddy_user_list', 'f', 'b.group_id = f.buddy_group_id AND b.module = :module AND b.is_deleted = :is_deleted AND b.group_type= :typeB AND f.is_deleted = :is_deleted AND f.buddy_user_id = :user_id', [':module' => 'fileshare', ':is_deleted' => '0', ':typeB' => 'B', 'user_id' => $my_user_id]);
        $selected_query -> leftjoin('kicp_public_group', 'g', 'b.group_id = g.pub_group_id AND b.module = :module AND b.is_deleted = :is_deleted AND b.group_type= :typeP AND g.is_deleted = :is_deleted AND g.pub_group_owner = :user_id', [':module' => 'fileshare', ':is_deleted' => '0', ':typeP' => 'P', 'user_id' => $my_user_id]);
        $selected_query -> leftjoin('kicp_buddy_group', 'h', 'b.group_id = h.buddy_group_id AND b.module = :module AND b.is_deleted = :is_deleted AND b.group_type= :typeB AND h.is_deleted = :is_deleted AND h.user_id = :user_id', [':module' => 'fileshare', ':is_deleted' => '0', ':typeP' => 'P', 'user_id' => $my_user_id]);
        $selected_query-> fields('a', ['file_id', 'title','description','file_name', 'folder_id', 'image_name', 'folder_id', 'modify_datetime', 'user_id']);
        $selected_query-> fields('j', ['folder_name']);
        $selected_query-> fields('u', ['user_displayname']);
        $selected_query-> condition('a.is_deleted', '0', '=');
        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
        if (!$isSiteAdmin) {          
          $selected_query-> having('a.user_id = :user_id OR COUNT(b.id)=0 OR COUNT(e.pub_user_id)> 0 OR COUNT(f.buddy_user_id)> 0 OR COUNT(g.pub_group_id)> 0 OR COUNT(h.user_id)> 0', [':user_id' => $my_user_id]);
        }

        $selected_query-> groupBy('a.file_id');
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
            $rsHadRate = $RatingData->checkUserHadRate('fileshare', $record["file_id"], $my_user_id);
            $record["rating"]['rsHadRate'] = $rsHadRate;
            $record["rating"]['module'] = 'fileshare';          
            $entries[] = $record;
          }
          
        }
        if ($entries)  {
          return $entries;
        }
     }
     catch (\Exception $e) {
        \Drupal::messenger()->addStatus(
           t('Unable to load fileshare at this time due to datbase error. Please try again.'.$e)
         );
       return  NULL;
     }

  }

  public static function getSharedFileByTags($tags) {

    
    //$addtag = \Drupal::request()->query->get('addtag');

    $output=array();
    $TagList = new TagList();
    $RatingData = new RatingData();
    
    $tagsUrl = \Drupal::request()->query->get('tags');
    if ($tagsUrl) {
      $tags = json_decode($tagsUrl);
    }    


    try {
      $database = \Drupal::database();
      $query = $database-> select('kicp_file_share', 'a'); 
      $query -> join('kicp_file_share_folder', 'j', 'a.folder_id = j.folder_id');
      $query -> leftjoin('xoops_users', 'u', 'a.user_id = u.user_id');
      $query -> leftjoin('kicp_access_control', 'b', 'b.record_id = j.folder_id AND b.module = :module AND b.is_deleted = :is_deleted', [':module' => 'fileshare', ':is_deleted' => '0']);
      $query -> leftjoin('kicp_public_user_list', 'e', 'b.group_id = e.pub_group_id AND b.module = :module AND b.is_deleted = :is_deleted AND b.group_type= :typeP AND e.is_deleted = :is_deleted AND e.pub_user_id = :user_id', [':module' => 'fileshare', ':is_deleted' => '0', ':typeP' => 'P', 'user_id' => $my_user_id]);
      $query -> leftjoin('kicp_buddy_user_list', 'f', 'b.group_id = f.buddy_group_id AND b.module = :module AND b.is_deleted = :is_deleted AND b.group_type= :typeB AND f.is_deleted = :is_deleted AND f.buddy_user_id = :user_id', [':module' => 'fileshare', ':is_deleted' => '0', ':typeB' => 'B', 'user_id' => $my_user_id]);
      $query -> leftjoin('kicp_public_group', 'g', 'b.group_id = g.pub_group_id AND b.module = :module AND b.is_deleted = :is_deleted AND b.group_type= :typeP AND g.is_deleted = :is_deleted AND g.pub_group_owner = :user_id', [':module' => 'fileshare', ':is_deleted' => '0', ':typeP' => 'P', 'user_id' => $my_user_id]);
      $query -> leftjoin('kicp_buddy_group', 'h', 'b.group_id = h.buddy_group_id AND b.module = :module AND b.is_deleted = :is_deleted AND b.group_type= :typeB AND h.is_deleted = :is_deleted AND h.user_id = :user_id', [':module' => 'fileshare', ':is_deleted' => '0', ':typeP' => 'P', 'user_id' => $my_user_id]);

      if ($tags && count($tags) > 0 ) {
        $tags1 = $database-> select('kicp_tags', 't');
        $tags1-> condition('tag', $tags, 'IN');
        $tags1-> condition('t.module', 'fileshare');
        $tags1-> condition('t.is_deleted', '0');
        $tags1-> addField('t', 'fid');
        $tags1-> groupBy('t.fid');
        $tags1-> having('COUNT(fid) >= :matches', [':matches' => count($tags)]);        
        $query-> condition('file_id', $tags1, 'IN');
      }
      $query-> fields('a', ['file_id', 'title','description','file_name', 'folder_id', 'image_name', 'folder_id', 'modify_datetime', 'user_id']);
      $query-> fields('j', ['folder_name']);
      $query-> fields('u', ['user_displayname']);

      $query-> condition('a.is_deleted', '0');
      $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 
      if (!$isSiteAdmin) {          
        $query-> having('a.user_id = :user_id OR COUNT(b.id)=0 OR COUNT(e.pub_user_id)> 0 OR COUNT(f.buddy_user_id)> 0 OR COUNT(g.pub_group_id)> 0 OR COUNT(h.user_id)> 0', [':user_id' => $my_user_id]);
      }

      $query-> groupBy('a.file_id');
      $query-> orderBy('modify_datetime', 'DESC');
      $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
      $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);

      foreach ($result as $record) {
        $record["tags"] = $TagList->getTagsForModule('fileshare', $record["file_id"]);   
        $record["rating"] = $RatingData->getList('fileshare', $record["file_id"]);
        $rsHadRate = $RatingData->checkUserHadRate('fileshare', $record["file_id"], $my_user_id);
        $record["rating"]['rsHadRate'] = $rsHadRate;
        $record["rating"]['module'] = 'fileshare';          

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