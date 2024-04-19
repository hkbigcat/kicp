<?php

/**
 * @file
 */

namespace Drupal\forum\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\Controller\TagList;
use Drupal\forum\Common\ForumDatatable;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

class ForumController extends ControllerBase {

    public function __construct() {
        $this->module = 'forum';
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->my_user_id = $authen->getUserId();        
    }
    
    public function content() {

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

    
    public function viewTopicList($forum_id="") {

        $forumPosts = ForumDatatable::getForumPostList($forum_id);
        $forumName = ForumDatatable::getForumName($forum_id);

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

    public function viewPostList($topic_id="") {

        $forumThreads = ForumDatatable::getForumThreads($topic_id);
        $forumInfo = ForumDatatable::getForumByTopic($topic_id);
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

 
}