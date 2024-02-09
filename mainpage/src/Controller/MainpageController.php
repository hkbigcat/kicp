<?php

//using by Henry
/**
 * @file
 */

namespace Drupal\mainpage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;
use Drupal;
use Drupal\common\LikeItem;
use Drupal\common\Controller\TagList;
use Drupal\common\Controller\CommonController;
use Drupal\common\CommonUtil;
use Drupal\common\Follow;
use Drupal\mainpage\Common\MainpageDatatable;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Database\Query\PagerSelectExtender;

class MainpageController extends ControllerBase {

    public function __construct() {
        $this->module = 'mainpage';
        $this->record_in_mainpage = 12;
        $this->tag_usage = 3;
        $this->showRating = array('bookmark', 'video', 'fileshare');
        $this->showLike = array('blog', 'activities', 'ppcactivities', 'survey', 'vote', 'forum', 'wiki');
        $this->showFollowIcon = array('bookmark', 'blog', 'video', 'fileshare', 'activities', 'ppcactivities', 'survey',' vote', 'forum');
    }

    public function contentByView() {

        $taglist = new TagList();
        $cop_tags = $taglist->getCOPTagList();

        $other_tags = $taglist->getOtherTagList();

        $editorChoice = MainpageDatatable::getEditorChoiceRecord();                

        $latest = MainpageDatatable::getLatest();

        return [
            '#theme' => 'mainpage-home',
            '#editor_choice' => $editorChoice,
            '#items' => $latest,
            '#cop_tags' => $cop_tags,
            '#other_tags' => $other_tags,
        ];  
    }


    public function GeneralTagContent() {

        $tags = array();  
        $tagsUrl = \Drupal::request()->query->get('tags');
    
        if ($tagsUrl) {
          $tags = json_decode($tagsUrl);
          if ($tags && count($tags) > 0 ) {
            $tmp = $tags;
          }
        }

        $latest = MainpageDatatable::getLatest($tags);

        return [
            '#theme' => 'mainpage-tags',
            '#items' => $latest,
            '#tags' => $tags,
            '#tagsUrl' => $tmp,
            '#pager' => ['#type' => 'pager',
                        ],
        ];          

    }
}