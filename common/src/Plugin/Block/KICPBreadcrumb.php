<?php

/**
 * @file
 * Create Block
 */

namespace Drupal\common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\blog\Controller\BlogController;

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
        $breads[] = [
            'name' => $module_name,
            'url' => '/'.$module_name,
        ];

        if ($routeName == "blog.blog_view") {
            $breads[] = [
                'name' => 'Blog of ',
                'url' => '/'.$module_name,
            ];
    
        }


        return [
            '#theme' => 'common-breadcrumb',
            '#breads' =>  $breads,
        ];
  
   }

}