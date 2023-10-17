<?php

/**
 * @file
 */

namespace Drupal\blog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\blog\Common\BlogDatatable;

class BlogController extends ControllerBase {
    
    public function __construct() {
        //$Paging = new Paging();
        //$DefaultPageLength = $Paging->getDefaultPageLength();

        $this->BlogHomepageDisplayNo = 10;
        $this->module = 'blog';
        $this->LimitPerPage = (isset($_REQUEST['limit']) && $_REQUEST['limit'] != '') ? $_REQUEST['limit'] : $DefaultPageLength;
        $this->app_path = CommonUtil::getSysValue('app_path');
        $this->app_path_url = CommonUtil::getSysValue('app_path_url');
        $this->domain_name = CommonUtil::getSysValue('domain_name');
        
    }

    public function content() {

        $ThematicBlogAry = BlogDatatable::getHomepageBlogList($blogType = 'T', $this->BlogHomepageDisplayNo);
        //$PersonalBlogAry = BlogDatatable::getHomepageBlogList($blogType = 'P', $this->BlogHomepageDisplayNo);
        //$PersonalBlogAry = BlogDatatable::getHomepageBlogList($blog_type = 'ALL', $this->BlogHomepageDisplayNo);

        $item = array();
        $item['thematic'] = $ThematicBlogAry; 

        return [
            '#theme' => 'blog_theme_hook',
            '#items' => $item,
            '#empty' => t('No entries available.'),
        ];

    }

}