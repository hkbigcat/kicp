<?php

/**
 * @file
 * Provde Site administrators with a list of all the RSVP List signups
 * so  tehy can know who is attending their events
 */

namespace Drupal\Bookmark\Common;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use \Drupal\Core\Routing;
use Drupal\common\TagList;
use Drupal\common\RatingData;


class BookmarkDatatable extends ControllerBase {

  public static function getBookmarks($my_user_id=null,$bid=null ) {

    if ($bid != null && !is_numeric($bid) ) {
      return null;
    }

    $myRecordOnly = \Drupal::request()->query->get('my');
    $tagsUrl = \Drupal::request()->query->get('tags');

    $database = \Drupal::database();
    
    if ($bid==null && $tagsUrl) {
      $escaped = $database->escapeLike($tagsUrl);
      $tags = json_decode($escaped);
    } 

    try {
        $query = $database-> select('kicp_bookmark', 'a');
        $query -> join('xoops_users', 'b', 'a.user_id = b.user_id');
      
        if (isset($tags) && count($tags) > 0 ) {
          $query -> join('kicp_tags', 't', 'a.bid = t.fid');
          $query->addExpression('COUNT(a.bid)', 'bids');
          $query-> condition('t.module', 'bookmark');
          $orGroup = $query->orConditionGroup();
          foreach($tags as $tmp) {
            $orGroup->condition('t.tag', $tmp);
          }
          $query->condition($orGroup);
          $query-> condition('t.is_deleted', '0');
          $query-> groupBy('a.bid', '0');
          $query->addExpression('COUNT(a.bid)>='.count($tags) , 'occ');
          $query->havingCondition('occ', 1);
        }

        $query->fields('a', ['bid', 'user_id', 'bTitle', 'bAddress', 'bDescription', 'bStatus', 'bModified']);
        $query->fields('b', ['user_name']);

        $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 

        if ($myRecordOnly) {
          $query->condition('a.user_id', $my_user_id);
        } else {
          if (!$isSiteAdmin) {
            $orGroup = $query->orConditionGroup()
            ->condition('a.user_id', $my_user_id)
            ->condition('a.bStatus', 0);
            $query->condition($orGroup);
          }
        }

        if ($bid!=null) {
          $query->condition('a.bid', $bid);  
          $result =  $query->execute()->fetchAll(\PDO::FETCH_ASSOC);  
        } else {
          $query->condition('a.is_deleted', '0');

          $query-> orderBy('bModified', 'DESC');
          $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
          $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);  
        }
        
        if (!$result) return null;
        $TagList = new TagList();
        $RatingData = new RatingData();
        foreach ($result as $record) {
          $record["tags"] = $TagList->getTagsForModule('bookmark', $record["bid"]);
          $record["rating"] = $RatingData->getList('bookmark', $record["bid"]);
          $rsHadRate = $RatingData->checkUserHadRate('bookmark', $record["bid"], $my_user_id);
          $record["rating"]['rsHadRate'] = $rsHadRate;          
          $record["rating"]['module'] = "bookmark";          

          if ($bid!=null) {
            return $record;
          }

          $entries[] = $record;
         
        }
        
        //dump($entries);
        return $entries;
      }
  
        catch (\Exception $e) {
  
        \Drupal::messenger()->addStatus(
            t('Unable to load Bookmarks at this time due to datbase error. Please try again.').$e
          );
  
          return NULL;
        }
    }

}