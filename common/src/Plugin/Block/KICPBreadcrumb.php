<?php

/**
 * @file
 * Create Block
 */

namespace Drupal\common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\blog\Controller\BlogController;
use Drupal\bookmark\Controller\BookmarkController;
use Drupal\fileshare\Controller\FileShareController;
use Drupal\forum\Controller\ForumController;
use Drupal\activities\Controller\ActivitiesController;
use Drupal\ppcactivities\Controller\PPCActivitiesController;
use Drupal\survey\Controller\SurveyController;
use Drupal\vote\Controller\VoteController;
use Drupal\video\Controller\VideoController;
use Drupal\profile\Controller\ProfileController;
use Drupal\mainpage\Controller\MainpageController;
use Drupal\common\Controller\CommonController;
use Drupal\node\Entity;

 /**
  * Provide the Form main Block.
  *
  * @Block(
  *   id = "common_block",
  *   admin_label = @Translation("KCP Breadcrumb Block"),
  * )
  */
 class KICPBreadcrumb extends BlockBase {
   /**
    * {@inheritdoc}
    */
   public function build() {

        $breads = array();
        $routeName = \Drupal::routeMatch()->getRouteName();  
        $module_name = explode(".", $routeName)[0];
        if ( $module_name == "bookmark") {
            $breads = BookmarkController::Breadcrumb();
        } else if ( $module_name == "blog") { 
            $breads = BlogController::Breadcrumb();
        } else if ( $module_name == "fileshare") { 
            $breads = FileshareController::Breadcrumb();
        } else if ( $module_name == "forum") { 
            $breads = ForumController::Breadcrumb();
        } else if ( $module_name == "activities") { 
            $breads = ActivitiesController::Breadcrumb();
        } else if ( $module_name == "ppcactivities") { 
            $breads = PPCActivitiesController::Breadcrumb();
        } else if ( $module_name == "survey") { 
            $breads = SurveyController::Breadcrumb();
        } else if ( $module_name == "vote") { 
            $breads = VoteController::Breadcrumb();
        } else if ( $module_name == "video") { 
            $breads = VideoController::Breadcrumb();
        } else if ( $module_name == "profile") { 
            $breads = ProfileController::Breadcrumb();
        } else if ( $module_name == "mainpage") { 
            $breads = MainpageController::Breadcrumb();
        } else if ( $module_name == "common") { 
            $breads = CommonController::Breadcrumb();
         } else if ( $module_name == "contact") { 
            $breads[] = [
                'name' => 'Contact Us',
            ];    
         } else {        
            $path = \Drupal::service('path.current')->getPath();  
            if ($path=="/node/1") {
                $breads[] = [
                    'name' => 'About Knowledge Management',
                ];                    
            } else {
                $breads[] = [
                    'name' => $module_name,
                    'url' => '/'.$module_name,
                ];    
            }
        }


        return [
            '#theme' => 'common-breadcrumb',
            '#breads' =>  $breads,
        ];
  
   }

}