<?php

/**
 * @file
 * Provde Site administrators with a list of all the RSVP List signups
 * so  tehy can know who is attending their events
 */

namespace Drupal\bookmark\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\bookmark\Common\BookmarkDatatable;
use Drupal\common\CommonUtil;
use Drupal\common\Controller\TagList;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;


class BookmarkController extends ControllerBase {

    public function __construct() {
        $this->module = 'bookmark';
    }

    public function BookmarkContent() {

        $tags = array();
        $tmp = null;
      
        $tagsUrl = \Drupal::request()->query->get('tags');

        $bookmarks = BookmarkDatatable::getBookmarks(); 
        
        if ($tagsUrl) {
          $tags = json_decode($tagsUrl);
          if ($tags && count($tags) > 0 ) {
            $tmp = $tags;
          }
        }


        return [
            '#theme' => 'bookmark-list',
            '#items' => $bookmarks,
            '#empty' => t('No entries available.'),
            '#tagsUrl' => $tmp,
            '#pager' => ['#type' => 'pager',
          ],
        ];      
        
    }

    public function BookmarkDelete($bid=NULL) {

      $current_time =  \Drupal::time()->getRequestTime();

      // delete record
  
      try {
        $database = \Drupal::database();
        $query = $database->update('kicp_bookmark')->fields([
          'is_deleted'=>1 , 
          'bModified' => date('Y-m-d H:i:s', $current_time),
        ])
        ->condition('bid', $bid)
        ->execute();
           
        return new RedirectResponse("/blog");

        $messenger = \Drupal::messenger(); 
        $messenger->addMessage( t('Bookmark has been deleted'));

      }
      catch (\Exception $e) {
          \Drupal::messenger()->addStatus(
              t('Unable to delete bookmark at this time due to datbase error. Please try again. ' )
              );
          
          }	
  }    


}