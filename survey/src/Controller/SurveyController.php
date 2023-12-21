<?php

/**
 * @file
 */

namespace Drupal\survey\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\common\TagList;
use Drupal\common\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\common\AccessControl;
use Drupal\survey\Common\SurveyDatatable;
use Symfony\Component\HttpFoundation\Response;


class SurveyController extends ControllerBase {

    public function __construct() {
        $this->module = 'survey';
        $this->domain_name = CommonUtil::getSysValue('domain_name');
        $this->ServerAbsolutePath = CommonUtil::getSysValue('server_absolute_path'); // get server absolute path
        $this->app_path = CommonUtil::getSysValue('app_path'); // app_path
    
    }

    public function SurveyContent() {

        $surveys = SurveyDatatable::getSurveyList();
        return [
            '#theme' => 'survey-home',
            '#items' => $surveys,
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],
        ];   

    }
    
}