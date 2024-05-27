<?php

/**
 * @file
 */

namespace Drupal\blog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\TagList;
use Drupal\common\TagStorage;
use Drupal\blog\Common\BlogDatatable;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;


class BlogController extends ControllerBase {
    
    public function __construct() {

        $this->BlogHomepageDisplayNo = 10;
        $this->module = 'blog';
        //$this->LimitPerPage = (isset($_REQUEST['limit']) && $_REQUEST['limit'] != '') ? $_REQUEST['limit'] : $DefaultPageLength;

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->is_authen = $authen->isAuthenticated;

        $this->my_user_id = $authen->getUserId();
        $this->my_blog_id = BlogDatatable::getBlogIDByUserID($this->my_user_id);

        if ($this->my_blog_id)
          $myBlogInfo = BlogDatatable::getBlogInfo($this->my_blog_id);
        
    }
    
    public function content() {

        $url = Url::fromUri('base:/no_access');
        if (! $this->is_authen) {
            return new RedirectResponse($url->toString());
        }

        $ThematicBlogAry = BlogDatatable::getHomepageBlogList($this->my_user_id, 'T', $this->BlogHomepageDisplayNo);
        $PersonalBlogAry = BlogDatatable::getHomepageBlogList($this->my_user_id, 'P', $this->BlogHomepageDisplayNo);
        $LatestBlogContent = BlogDatatable::getHomepageBlogList($this->my_user_id, 'ALL', $this->BlogHomepageDisplayNo);

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

        $url = Url::fromUri('base:/no_access');
        if (! $this->is_authen) {
            return new RedirectResponse($url->toString());
        }


        $entry = BlogDatatable::getBlogEntryContent($entry_id);

        if (!$entry ) {
            return [
                '#type' => 'markup',
                '#markup' => $this->t('Blog entry not avaiable'),
              ];
        } else {

            $entry['my_blog_id'] = $this->my_blog_id;
            $entryCommentAry = BlogDatatable::getEntryComment($entry_id);
            $entry['comments'] = $entryCommentAry;
            $archive = BlogDatatable::getBlogArchiveTree();
            $TagList = new TagList();
            $taglist = $TagList->getTagsForModule('blog', $entry_id);        
        
            return [
                '#theme' => 'blogs-entry',
                '#items' => $entry,
                '#archive' => $archive,
                '#tags' => $taglist,
                '#my_user_id' => $this->my_user_id,
                '#empty' => t('No entries available.'),
            ];      
       }  

    }

    public function viewBlog($blog_id) {

        $url = Url::fromUri('base:/no_access');
        if (! $this->is_authen) {
            return new RedirectResponse($url->toString());
        }


        if ($blog_id==0) {
            return [
                '#type' => 'markup',
                '#markup' => $this->t('You don\'t have a blog yet. <a href="../blog_add">Write your First Blog</a>.'),
              ];

        }

        $blog = BlogDatatable::getBlogInfo($blog_id);
        $entry = BlogDatatable::getBlogListContent($blog_id);

        if ($entry==null) {
            return [
                '#type' => 'markup',
                '#markup' => $this->t('Blog is not avaiable'),
              ];
        }

        $entry['my_blog_id'] = $this->my_blog_id;
        $archive = BlogDatatable::getBlogArchiveTree($blog_id);
        $entry['blog'] = $blog;
        $isdeletegate = BlogDatatable::isBlogDelegatedUser($blog_id,  $this->my_user_id);
        
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

        $url = Url::fromUri('base:/no_access');
        if (! $this->is_authen) {
            return new RedirectResponse($url->toString());
        }


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


    public function BlogDelete($entry_id=null) {

        //$entry = BlogDatatable::getBlogEntryContent($entry_id);
        if ($entry_id == null) {
            \Drupal::messenger()->addError(
                t('you cannot delete this blog' )
                );

                $response = array('result' => 0);
                return new JsonResponse($response);    	                    
        }

        //Check owner, admin and delegate
        $entry_title = BlogDatatable::checkBlogowner($entry_id, $this->my_user_id);

        if (!$entry_title && !$isdeletegate) {
            \Drupal::messenger()->addError(
                t('you cannot delete this blog' )
                );

                $response = array('result' => 0);
                return new JsonResponse($response);    	        
        } 

        $file_system = \Drupal::service('file_system');
        $BlogUri = 'private://blog';
        $blog_path = $file_system->realpath($BlogUri);


        $database = \Drupal::database();
        $transaction = $database->startTransaction(); 
        // delete record
        try {

            $query = $database->update('kicp_blog_entry')->fields([
            'is_deleted'=>1 , 
            'entry_modify_datetime' => date('Y-m-d H:i:s'),
            ])
            ->condition('entry_id', $entry_id)
            ->condition('is_deleted', 0);
            $row_affected = $query->execute();        
 


            if ($row_affected) {                

                $blog_uid = BlogDatatable::getBlogUID($entry_id);                          
                $this_entry_id = str_pad($entry_id, 6, "0", STR_PAD_LEFT);
                $this_blog_uid = str_pad($blog_uid, 6, "0", STR_PAD_LEFT);
                $blog_file_uri = $BlogUri."/file/".$this_blog_uid."/". $this_entry_id;
                $blog_image_uri = $BlogUri."/image/".$this_blog_uid."/". $this_entry_id;
                $blog_file_path = $blog_path."/file/".$this_blog_uid."/". $this_entry_id;
                $blog_image_path = $blog_path."/image/".$this_blog_uid."/". $this_entry_id;

                if (is_dir($blog_file_path)) {
                    $blogFileList = scandir($blog_file_path);
                    foreach($blogFileList as $filename) {
                        if($filename == "." || $filename == "..") {
                            continue;
                        }
                        $uri =  $blog_file_uri."/".$filename;
                        $fid = CommonUtil::deleteFile($uri);                                  
                    }
                }
                if (is_dir($blog_image_path)) {
                    $blogImageList = scandir($blog_image_path);
                    foreach($blogImageList as $filename) {
                        if($filename == "." || $filename == "..") {
                            continue;
                        }
                        $uri =  $blog_image_uri."/".$filename;
                        $fid = CommonUtil::deleteFile($uri);                                  
                    }
                }


                // delete tags  
                $return2 = TagStorage::markDelete($this->module, $entry_id);
            
                // write logs to common log table
                \Drupal::logger('blog')->info('Deleted id: %id, title: %title, row_affected: %row_affected',   
                array(
                    '%id' => $entry_id,
                    '%title' =>  $entry_title,
                    '%row_affected' => $row_affected,
                ));    

                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('Blog has been deleted') );
            }

        }
        catch (\Exception $e) {
            \Drupal::messenger()->addStatus(
                t('Unable to delete blog at this time due to datbase error. Please try again. ' )
                );
                \Drupal::logger('survey')->error('Blog is not deleted: '.$entry_id);   
                $transaction->rollBack();     
            }
            unset($transaction);
            
        $response = array('result' => 1);
        return new JsonResponse($response);    	

        
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

        $entry = BlogDatatable::getAllEntry($this->my_user_id);
        $entry['my_blog_id'] = $this->my_blog_id;
        $archive = BlogDatatable::getBlogArchiveTree($this->my_blog_id);
        $search_str = \Drupal::request()->query->get('search_str');
        $entry['search_str'] = $search_str;
          
        return [
            '#theme' => 'blogs-all',
            '#items' => $entry,
            '#archive' => $archive,
            '#my_user_id' => $this->my_user_id,
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],
        ];   

    }


    public function BlogDelegateList() {

        $url = Url::fromUri('base:/no_access');
        if (! $this->is_authen) {
            return new RedirectResponse($url->toString());
        }


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

            // write logs to common log table
            \Drupal::logger('blog')->info('Delegation deleted id: %id, user: %user',   
            array(
                '%id' => $this->my_blog_id,
                '%user' =>   $user_id,
            ));    

            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Blog delegation has been deleted'));       
            
            
            $response = array('result' => 1);
            return new JsonResponse($response);    	
  
          }
          catch (\Exception $e) {
              \Drupal::logger('blog')->error('Unable to delete blog delegation');   
              \Drupal::messenger()->addStatus(
                  t('Unable to delete blog delegation at this time due to datbase error. Please try again. ' )
                  );
              
              }	


    }

    public function BlogDelegateAdd() {

        $url = Url::fromUri('base:/no_access');
        if (! $this->is_authen) {
            return new RedirectResponse($url->toString());
        }


        $entry = BlogDatatable::getBlogDelegate($this->my_blog_id);
        $entry['my_blog_id'] = $this->my_blog_id;
        $archive = BlogDatatable::getBlogArchiveTree($this->my_blog_id);
        $search_str = \Drupal::request()->query->get('search_str');
        $search_user = "";
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

            // write logs to common log table
            \Drupal::logger('blog')->info('Delegation added id: %id, user: %user',   
            array(
                '%id' => $this->my_blog_id,
                '%user' =>  $add_user_id,
            ));                

            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('New blog delegation has been added: '.$add_user_id));

            $url = Url::fromUri("base:/blog_delegate_list_add");
            return new RedirectResponse($url->toString()."?search_str=". $search_str);



        }
        catch (\Exception $e) {

            $url = Url::fromUri("base:/blog_delegate_list_add?search_str=". $search_str);
            return new RedirectResponse($url->toString());

            \Drupal::messenger()->addStatus(
                t('Unable to add blog delegate at this time due to datbase error. Please try again. '. $add_user_id.' - ' .$this->my_blog_id )
                );
            
            }           
 


    }

    public static function Breadcrumb() {

        $base_url = Url::fromRoute('blog.blog_content');
        $base_path = [
            'name' => 'Blog', 
            'url' => $base_url,
        ];
        $breads = array();
        $route_match = \Drupal::routeMatch();
        $routeName = $route_match->getRouteName();
                
        if ($routeName=="blog.blog_content") {
          $breads[] = [
              'name' => 'Blog',
          ];
        } else if ($routeName=="blog.blog_view") {
           $blog_id = $route_match->getParameter('blog_id');
           $blog_name = BlogDatatable::getBlogName($blog_id);                   
           $breads[] = $base_path;
           $breads[] = [
            'name' => $blog_name??'No Blog' ,
          ];
        } else if ($routeName=="blog.blog_entry") {
           $entry_id = $route_match->getParameter('entry_id');
           $entry = BlogDatatable::getEntryName($entry_id);
           if ($entry) {
            $blog_id = $entry['blog_id'];
            $blog_name = BlogDatatable::getBlogName($blog_id);
            $blog_url = Url::fromRoute('blog.blog_view', ['blog_id' => $blog_id]);
           }
           $breads[] = $base_path;
           $breads[] = [
              'name' => $blog_name??'No Blog' ,
              'url' => $blog_url??null,
          ];
          if ($entry) {
            $breads [] = [
                'name' => htmlspecialchars_decode($entry['entry_title']??'No Entry'),
            ];
        }
        } else if ($routeName=="blog.add_data") {
            $breads[] = $base_path;
            $breads [] = [
                'name' => 'Add',
              ];            
        } else if ($routeName=="blog.change_data") {
            $breads[] = $base_path;
            $breads [] = [
                'name' => 'Edit',
              ];            
        } else if ($routeName=="blog.my_photo") {
            $breads[] = $base_path;
            $breads [] = [
                'name' => 'My Photo',
              ];            
        } else if ($routeName=="blog.blog_all_list") {
            $breads[] = $base_path;
            $breads [] = [
                'name' => 'View All Blogs',
              ];            
        } else if ($routeName=="blog.blog_delegate") {
            $breads[] = $base_path;
            $breads [] = [
                'name' => 'My Delegated Users',
              ];            
        } else if ($routeName=="blog.blog_delegate_list_add") {
            $breads[] = $base_path;
            $breads [] = [
                'name' => 'Add My Delegated Users',
              ];            
        } else if ($routeName=="blog.blog_tag") {
            $breads[] = $base_path;
            $breads [] = [
                'name' => 'Tags',
              ];            
        }

        return $breads;
    
      }    


}