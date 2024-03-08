<?php

/**
 * @file
 */

namespace Drupal\blog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\Controller\TagList;
use Drupal\blog\Common\BlogDatatable;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;


class BlogController extends ControllerBase {
    
    public function __construct() {

        $this->BlogHomepageDisplayNo = 10;
        $this->module = 'blog';
        $this->LimitPerPage = (isset($_REQUEST['limit']) && $_REQUEST['limit'] != '') ? $_REQUEST['limit'] : $DefaultPageLength;

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();


        $this->$user_id = $authen->getUserId();
        $this->my_blog_id = BlogDatatable::getBlogIDByUserID($this->$user_id );
        
    }
    
    public function content() {

        
        $ThematicBlogAry = BlogDatatable::getHomepageBlogList($this->$user_id, 'T', $this->BlogHomepageDisplayNo);
        $PersonalBlogAry = BlogDatatable::getHomepageBlogList($this->$user_id, 'P', $this->BlogHomepageDisplayNo);
        $LatestBlogContent = BlogDatatable::getHomepageBlogList($this->$user_id, 'ALL', $this->BlogHomepageDisplayNo);

        $items = array();
        $items['thematic'] = $ThematicBlogAry;
        $items['personal'] = $PersonalBlogAry;
        $items['latest'] = $LatestBlogContent;
        $items['my_blog_id'] = $this->my_blog_id;

        return [
            '#theme' => 'blogs-home',
            '#items' => $items,
            '#empty' => t('No entries available.'),
        ];

    }
    

    public function viewEntry($entry_id) {

        
        $entry = BlogDatatable::getBlogEntryContent($entry_id);
        $entry['my_blog_id'] = $this->my_blog_id;
        $entryCommentAry = BlogDatatable::getEntryComment($entry_id);
        $entry['comments'] = $entryCommentAry;
        $archive = BlogDatatable::getBlogArchiveTree($blog_id);
        $TagList = new TagList();
        $taglist = $TagList->getTagsForModule('blog', $entry_id);        
       
        return [
            '#theme' => 'blogs-entry',
            '#items' => $entry,
            '#archive' => $archive,
            '#tags' => $taglist,
            '#empty' => t('No entries available.'),
        ];        

    }

    public function viewBlog($blog_id) {


        $blog = BlogDatatable::getBlogInfo($blog_id);
        $entry = BlogDatatable::getBlogListContent($blog_id);
        $entry['my_blog_id'] = $this->my_blog_id;
        $archive = BlogDatatable::getBlogArchiveTree($blog_id);
        $entry['blog'] = $blog;
        $isdeletegate = BlogDatatable::isBlogDelegatedUser($blog_id,  $this->$user_id);
        
        return [
            '#theme' => 'blogs-view',
            '#items' => $entry,
            '#my_user_id' => $this->my_user_id,
            '#delegate' => $isdeletegate,
            '#archive' => $archive,
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],
        ];   

    }

    public function ViewBlogByTag() {

        $tags = array();
        $tagsUrl = \Drupal::request()->query->get('tags');
    
        $entry = BlogDatatable::getBlogEntryByTags();
        $entry['my_blog_id'] = $this->my_blog_id;

        if ($tagsUrl) {
            $tags = json_decode($tagsUrl);
            if ($tags && count($tags) > 0 ) {
              $tmp = $tags;
            }
          }        
          
        return [
            '#theme' => 'blogs-view',
            '#items' => $entry,
            '#empty' => t('No entries available.'),
            '#tagsUrl' => $tmp,
            '#pager' => ['#type' => 'pager',
            ],
        ];   

    }


    public function BlogDelete($entry_id=NULL) {

        $current_time =  \Drupal::time()->getRequestTime();

        // delete record
    
        try {
          $database = \Drupal::database();
          $query = $database->update('kicp_blog_entry')->fields([
            'is_deleted'=>1 , 
            'entry_modify_datetime' => date('Y-m-d H:i:s', $current_time),
          ])
          ->condition('entry_id', $entry_id)
          ->execute();
             
          return new RedirectResponse("/blog");

          $messenger = \Drupal::messenger(); 
          $messenger->addMessage( t('Blog has been deleted'));

        }
        catch (\Exception $e) {
            \Drupal::messenger()->addStatus(
                t('Unable to delete blog at this time due to datbase error. Please try again. ' )
                );
            
            }	
    }

    public function CommentAdd() {
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();

        $is_guest = (!isset($_REQUEST['is_guest']) || $_REQUEST['is_guest'] == "") ? 0 : $_REQUEST['is_guest'];

        // add comment for blog entry
        $entry = array(
          'entry_id' => $_REQUEST['entry_id'],
          'comment_content' => $_REQUEST['my_comment'],
          'user_id' => $authen->getUserId(),
          'is_guest' => $is_guest,
          'comment_name' => $_REQUEST['guest_name'],
        );

        try {
            $query = \Drupal::database()->insert('kicp_blog_entry_comment')
            ->fields($entry)->execute();

        }
        catch (\Exception $e) {
            drupal_set_message(t('db_insert failed. Message = %message, query= %query', array(
              '%message' => $e->getMessage(),
              '%query' => $e->query_string,
                )), 'error');
        }
        
        if ( $query) {
            return array(
              '#type' => 'markup',
              '#markup' => $this->t($authen->getUserId() . '/' . $_REQUEST['my_comment'] . '/'),
            );
        }
    }


    public function CommentList($entry_id) {
        //display the comment of blog entry

        $entryCommentAry = BlogDatatable::getEntryComment($entry_id);
        $renderable = [
            '#theme' => 'blogs-comments',
            '#comments' =>  $entryCommentAry,
          ];
        $comment_content = \Drupal::service('renderer')->renderPlain($renderable);

        $response = new Response();
        $response->setContent($comment_content);
        return $response;
    }    

    public function ViewAllBlogList() {

        $entry = BlogDatatable::getAllEntry();
        $entry['my_blog_id'] = $this->my_blog_id;
        $archive = BlogDatatable::getBlogArchiveTree($this->my_blog_id);
        $search_str = \Drupal::request()->query->get('search_str');
        $entry['search_str'] = $search_str;
          
        return [
            '#theme' => 'blogs-all',
            '#items' => $entry,
            '#archive' => $archive,
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],
        ];   

    }


    public function BlogDelegateList() {

        
        $entry = BlogDatatable::getBlogDelegate($this->my_blog_id);
        $entry['my_blog_id'] = $this->my_blog_id;
        $archive = BlogDatatable::getBlogArchiveTree($this->my_blog_id);
        $search_str = \Drupal::request()->query->get('search_str');
        $entry['search_str'] = $search_str;        
        
        return [
            '#theme' => 'blogs-delegate',
            '#items' => $entry,
            '#archive' => $archive,
            '#empty' => t('No entries available.'),
        ];  


    }


    public function BlogDelegateDelete($user_id=null) {

        try {
            $database = \Drupal::database();
            $query = $database->delete('kicp_blog_delegated')
            ->condition('blog_id',  $this->my_blog_id)
            ->condition('user_id', $user_id)
            ->execute();
               
            return new RedirectResponse("/blog_delegate");
  
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Blog has been deleted'));
  
          }
          catch (\Exception $e) {
              \Drupal::messenger()->addStatus(
                  t('Unable to delete blog at this time due to datbase error. Please try again. ' )
                  );
              
              }	


    }

    public function BlogDelegateAdd() {

        $entry = BlogDatatable::getBlogDelegate($this->my_blog_id);
        $entry['my_blog_id'] = $this->my_blog_id;
        $archive = BlogDatatable::getBlogArchiveTree($this->my_blog_id);
        $search_str = \Drupal::request()->query->get('search_str');
        if ($search_str  && $search_str !="") {
            $search_user = BlogDatatable::blog_delegate_add_search($search_str);
            $entry['search_str'] = $search_str;        
        }

        return [
            '#theme' => 'blogs-delegate-add',
            '#items' => $entry,
            '#members' => $search_user,
            '#archive' => $archive,
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],
        ];  

    }

    function BlogDelegateAddAction() {

                

        $add_user_id = (isset($_REQUEST['delegate_user_id']) && $_REQUEST['delegate_user_id'] != "") ? $_REQUEST['delegate_user_id'] : "";
        $search_str = $_REQUEST['search_str'];

        
        try { 
            $query = \Drupal::database()->insert('kicp_blog_delegated')
            ->fields([
                'blog_id' =>  $this->my_blog_id,
                'user_id' => $add_user_id,
            ])->execute();

            return new RedirectResponse("/blog_delegate_list_add?search_str=". $search_str);

            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('new Blog delegation has been added'));
        }
        catch (\Exception $e) {

            return new RedirectResponse("/blog_delegate_list_add?search_str=". $search_str);

            \Drupal::messenger()->addStatus(
                t('Unable to add blog delegate at this time due to datbase error. Please try again. '. $add_user_id.' - ' .$this->my_blog_id )
                );
            
            }           
 


    }


}