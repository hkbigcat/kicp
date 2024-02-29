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
use Drupal\Core\Url;

class BookmarkController extends ControllerBase {

    public function __construct() {
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->my_user_id = $authen->getUserId();        

        $this->module = 'bookmark';
    }

    public function BookmarkContent() {

        $tags = array();
        $tmp = null;
      
        $tagsUrl = \Drupal::request()->query->get('tags');

        $bookmarks = BookmarkDatatable::getBookmarks($this->my_user_id, $bid); 
        
        if ($tagsUrl) {
          $tags = json_decode($tagsUrl);
          if ($tags && count($tags) > 0 ) {
            $tmp = $tags;
          }
        }

        return [
            '#theme' => 'bookmark-list',
            '#items' => $bookmarks,
            '#my_user_id' => $this->my_user_id,
            '#empty' => t('No entries available.'),
            '#tagsUrl' => $tmp,
            '#pager' => ['#type' => 'pager',
          ],
        ];      
        
    }

    public function BookmarkDelete($bid=NULL) {


      $bookmark = BookmarkDatatable::getBookmarks($this->my_user_id, $bid); 
      if ($bookmark['bid'] == null) {

        $url = Url::fromRoute('bookmark.bookmark_content');
        return new RedirectResponse($url->toString());

        \Drupal::messenger()->addStatus(
          t('Unable to delete this bookmark'.$bookmark['bid']  )
          );

      }

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

        // delete tags
        $query = $database->update('kicp_tags')->fields([
          'is_deleted'=>1 , 
        ])
        ->condition('fid', $bid)
        ->condition('module', 'bookmark')
        ->execute();


        $url = Url::fromRoute('bookmark.bookmark_content');
        return new RedirectResponse($url->toString());

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