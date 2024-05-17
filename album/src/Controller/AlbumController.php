<?php

/**
 * @file
 */

namespace Drupal\album\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\common\CommonUtil;

class AlbumController extends ControllerBase {

    public function __construct() {
        $this->module = 'album';
        $this->thumnail_folder = 'gal';
        $this->download_folder = 'download';
    }

    public function content() {

    }

}