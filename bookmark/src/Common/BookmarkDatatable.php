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
use Drupal\common\Controller\TagList;


class BookmarkDatatable extends ControllerBase {

  public static function getBookmarks($bid=null) {

    $tagsUrl = \Drupal::request()->query->get('tags');
    if ($bid==null && $tagsUrl) {
      $tags = json_decode($tagsUrl);
    } 

    //dump($tags);

    try {
        $database = \Drupal::database();

        $query = $database-> select('kicp_bookmark', 'a');
        $query -> join('xoops_users', 'b', 'a.user_id = b.user_id');

        if ($tags && count($tags) > 0 ) {
          $query -> join('kicp_tags', 't', 'a.bid = t.fid');
          //$query->addExpression('COUNT(a.bid)', 'bids');
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

        $query->fields('a', ['bid', 'user_id', 'bTitle', 'bAddress', 'bDescription', 'bModified']);
        $query->fields('b', ['user_name']);
        if ($bid!=null) {
          $query->condition('a.bid', $bid);  
          $result =  $query->execute()->fetchAll(\PDO::FETCH_ASSOC);  
        } else {
          $query->condition('a.is_deleted', '0');
          $query-> orderBy('bModified', 'DESC');
          $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
          $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);  
        }
        $TagList = new TagList();
        foreach ($result as $record) {
          $record["tags"] = $TagList->getTagsForModule('bookmark', $record["bid"]);

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
            t('Unable to load Bookmarks at this time due to datbase error. Please try again.')
          );
  
          return NULL;
        }
    }

}