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
use Drupal\common\TagStorage;
use Drupal\common\RatingStorage;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Utility\Error;

class BookmarkController extends ControllerBase {

  public $is_authen;
  public $my_user_id;
  public $module;

    public function __construct() {
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->is_authen = $authen->isAuthenticated;
        $this->my_user_id = $authen->getUserId();        

        $this->module = 'bookmark';
    }

    public function BookmarkContent() {

      $url = Url::fromUri('base:/no_access');
      if (! $this->is_authen) {
          return new RedirectResponse($url->toString());
      }

        $tags = array();
        $tmp = null;
      
        $myRecordOnly = \Drupal::request()->query->get('my');
        $tagsUrl = \Drupal::request()->query->get('tags');

        $bookmarks = BookmarkDatatable::getBookmarks($this->my_user_id); 
        
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
            '#myRecordOnly' => $myRecordOnly,
            '#tagsUrl' => $tmp,
            '#pager' => ['#type' => 'pager',
          ],
        ];      
        
    }

    public function BookmarkDelete($bid=NULL) {

      $isSiteAdmin = \Drupal::currentUser()->hasPermission('access administration pages'); 

      // delete record
      $database = \Drupal::database();
      $transaction = $database->startTransaction(); 

      try {
        $query = $database->update('kicp_bookmark')->fields([
          'is_deleted'=>1 , 
          'bModified' => date('Y-m-d H:i:s'),
        ])
        ->condition('bid', $bid)
        ->condition('is_deleted', 0);
        if (!$isSiteAdmin) {
          $query->condition('user_id', $this->my_user_id);
        }    
        $row_affected = $query->execute();        

        if ($row_affected) {
          // delete tags  
          $return2 = TagStorage::markDelete($this->module, $bid);

          // delete rating
          $rating = RatingStorage::markDelete($this->module, $bid);

          \Drupal::logger('bookmark')->info('Deleted id: %id, row_affected: %row_affected', 
          array(
              '%id' => $bid,
              '%row_affected' => $row_affected,
          ));       

          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Bookmark has been deleted'));
        }

      }
      catch (\Exception $e) {
          $variables = Error::decodeException($e);
          \Drupal::messenger()->addError(
              t('Unable to delete bookmark at this time due to datbase error. Please try again. ' )
              );
          \Drupal::logger('bookmark')->error('Bookmark is not deleted: ' . $variables);   
          $transaction->rollBack();
       }
       unset($transaction);
       $response = array('result' => 1);
       return new JsonResponse($response);
  
  }   
  
  
  public static function Breadcrumb() {

    $base_url = Url::fromRoute('bookmark.bookmark_content');
    $breads = array();
    $routeName = \Drupal::routeMatch()->getRouteName();
    if ($routeName=="bookmark.bookmark_content") {
      $breads[] = [
          'name' => 'Bookmarks',
      ];
    } else if ($routeName=="bookmark.add_data") {
      $breads[] = [
          'name' => 'Bookmarks',
          'url' => $base_url,
      ];
      $breads [] = [
        'name' => 'Add',
      ];
    } else if ($routeName=="bookmark.change_data") {
      $breads[] = [
          'name' => 'Bookmarks',
          'url' => $base_url,
      ];
      $breads [] = [
        'name' => 'Edit',
      ];
    }

    return $breads;

  }


}