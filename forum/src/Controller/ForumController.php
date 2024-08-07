<?php

/**
 * @file
 */

namespace Drupal\forum\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\TagList;
use Drupal\forum\Common\ForumDatatable;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

class ForumController extends ControllerBase {

    public $my_user_id;
    public $module;

    public function __construct() {
        $this->module = 'forum';
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $current_user = \Drupal::currentUser();
        $this->my_user_id = $current_user->getAccountName();              
    }
    
    public function content() {

        $url = Url::fromUri('base:/no_access');
        $logged_in = \Drupal::currentUser()->isAuthenticated();
        if (!$logged_in) {
            return new RedirectResponse($url->toString());
        }

        $latest5Topic = ForumDatatable::getLatest5Topic();
        $kicpForum = ForumDatatable::getForumList();
        return [
            '#theme' => 'forum-home',
            '#latest' => $latest5Topic,
            '#forums' => $kicpForum,
            '#my_user_id' => $this->my_user_id,
            '#empty' => t('No entries available.'),
        ];


    }

    public function viewTopicListOld() {
        $forum_id = \Drupal::request()->query->get('forum_id');
        if ($forum_id && is_numeric($forum_id))
            $url = Url::fromUri('base:/forum_view_forum/'.$forum_id);
        else {
                $url = Url::fromUri('base:/forum/');
        }
        return new RedirectResponse($url->toString(), 301);
    }

    
    public function viewTopicList($forum_id="") {

        $url = Url::fromUri('base:/no_access');
        $logged_in = \Drupal::currentUser()->isAuthenticated();
        if (!$logged_in) {
            return new RedirectResponse($url->toString());
        }

        
        $forumName = ForumDatatable::getForumName($forum_id);
        if (!$forumName) {
            return ['#markup' => t('This forum is not available')];    
        }
        $forumPosts = ForumDatatable::getForumPostList($forum_id);
        return [
            '#theme' => 'forum-forum',
            '#forum_info' => [ 'forum_id' => $forum_id, 'forum_name' => $forumName, ],
            '#posts' => $forumPosts,
            '#empty' => t('No entries available.'),
            '#my_user_id' => $this->my_user_id,
            '#pager' => ['#type' => 'pager',
            ],
        ];        

    }

    public function viewPostListOld() {
        $topic_id = \Drupal::request()->query->get('topic_id');
        if ($topic_id && is_numeric($topic_id))
            $url = Url::fromUri('base:/forum_view_topic/'.$topic_id);
        else {
                $url = Url::fromUri('base:/forum/');
        }
        return new RedirectResponse($url->toString(), 301);

    }

    public function viewPostList($topic_id="") {

        $url = Url::fromUri('base:/no_access');
        $logged_in = \Drupal::currentUser()->isAuthenticated();
        if (!$logged_in) {
            return new RedirectResponse($url->toString());
        }

        $forumInfo = ForumDatatable::getForumByTopic($topic_id);
        if (!$forumInfo) {
            return ['#markup' => t('This forum is not available')];    
        }

        $forumThreads = ForumDatatable::getForumThreads($topic_id);
        $TagList = new TagList();
        $taglist = $TagList->getTagsForModule('forum', $topic_id);        

        return [
            '#theme' => 'forum-post',
            '#threads' => $forumThreads,
            '#forum_info' => $forumInfo,
            '#tags' => $taglist,
            '#my_user_id' => $this->my_user_id,
            '#empty' => t('No entries available.'),
        ];        

    }

    public function content_tag() {


        $url = Url::fromUri('base:/no_access');
        $logged_in = \Drupal::currentUser()->isAuthenticated();
        if (!$logged_in) {
            return new RedirectResponse($url->toString());
        }

        $tags = array();
        $tagsUrl = \Drupal::request()->query->get('tags');
    
        if ($tagsUrl) {
          $tags = json_decode($tagsUrl);
          if ($tags && count($tags) > 0 ) {
            $tmp = $tags;
          }
        }  


        $forumPosts = ForumDatatable::getForumListByTag($tags);
        return [
            '#theme' => 'forum-tags',
            '#posts' => $forumPosts,
            '#tags' => $tags,
            '#tagsUrl' => $tmp,            
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],
        ];        


    }


    public static function Breadcrumb() {

        $base_url = Url::fromRoute('forum.forum');
        $base_path = [
            'name' => 'Forum', 
            'url' => $base_url,
        ];
        $breads = array();
        $route_match = \Drupal::routeMatch();
        $routeName = $route_match->getRouteName();
        if ($routeName=="forum.forum") {
            $breads[] = [
                'name' => 'Forum',
            ];
        } else if ($routeName=="forum.forum_view_forum") {
            $forum_id = $route_match->getParameter('forum_id');
            $forumName = ForumDatatable::getForumName($forum_id);
            $breads[] = $base_path;
            $breads[] = [
             'name' => $forumName??'No Forum' ,
           ];
        } else if ($routeName=="forum.forum_view_topic") {
            $topic_id = $route_match->getParameter('topic_id');
            $forumInfo = ForumDatatable::getForumByTopic($topic_id);
            $breads[] = $base_path;
            if ($forumInfo) {
                $forumName = $forumInfo->forum_name;
                $forum_id = $forumInfo->forum_id;
                $forum_url = Url::fromRoute('forum.forum_view_forum', ['forum_id' => $forum_id]);
            }
            $breads[] = [
                'name' => $forumName??'No Forum' ,
                'url' => $forum_url??null ,
            ];
            if ($forumInfo) {
                $forumSubject = ForumDatatable::getForumSubject($topic_id);
                $breads[] = [
                    'name' => $forumSubject??'No Topic' ,
                ];
            }
        } else if ($routeName=="forum.forum_topic_add") {
            $forum_id = $route_match->getParameter('forum_id');
            $forumName = ForumDatatable::getForumName($forum_id);
            $breads[] = $base_path;
            if ($forumName) {
                $forum_url = Url::fromRoute('forum.forum_view_forum', ['forum_id' => $forum_id]);
            }            
            $breads[] = [
             'name' => $forumName??'No Forum' ,
             'url' => $forum_url??null ,
           ];
           if ($forumName) {
                $breads[] = [
                    'name' => 'Add / Reply' ,
                ];            
            }
        } else if ($routeName=="forum.forum_tag") {
            $breads[] = $base_path;
            $breads[] = [
             'name' => 'Tags' ,
           ];
        } 

        return $breads;

    }

 
}