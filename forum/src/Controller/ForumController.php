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
    }
    
    public function content() {

        $latest5Topic = ForumDatatable::getLatest5Topic();
        $kicpForum = ForumDatatable::getForumList();
        return [
            '#theme' => 'forum-home',
            '#latest' => $latest5Topic,
            '#forums' => $kicpForum,
            '#empty' => t('No entries available.'),
        ];


    }

    
    public function viewTopicList($forum_id="") {

        $forumPosts = ForumDatatable::getForumPostList($forum_id);
        $forumName = ForumDatatable::getForumName($forum_id);

        return [
            '#theme' => 'forum-forum',
            '#forum_name' => $forumName,
            '#posts' => $forumPosts,
            '#empty' => t('No entries available.'),
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
            '#empty' => t('No entries available.'),
        ];        

    }

 
}