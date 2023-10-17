<?php

/**
 * @file
 */

namespace Drupal\demo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;


class DemoController extends ControllerBase {
    
    public function __construct() {

        
    }
    
    public function content() {

        return [
            '#theme' => 'demo-box',
            '#items' => 'Test',
        ];   

    }


}