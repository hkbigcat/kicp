<?php

/**
 * @file
 */

namespace Drupal\survey\Common;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Drupal\common\CommonUtil;
use Drupal\common\LikeItem;
use Drupal\common\Controller\TagList;
class SurveyDatatable {

    public static function getSurveyList() {
        
        $output=array();
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $my_user_id = $authen->getUserId();


        $TagList = new TagList();
        $database = \Drupal::database();

        $database = \Drupal::database();
        $query = $database-> select('kicp_survey', 'a');
        $query -> leftjoin('kicp_survey_respondent', 'b', 'a.survey_id = b.survey_id');
        $query -> leftjoin('xoops_users', 'x', 'a.user_id = x.user_id');
        $query-> fields('a', ['survey_id', 'title', 'description', 'user_id', 'start_date', 'expiry_date']);
        $query-> fields('x', ['user_displayname']);
        $query-> addExpression('count(b.survey_id)', 'response');
        $query-> groupBy('a.survey_id');
        $query-> orderBy('a.start_date', 'DESC');  
        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result =  $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);           

        foreach ($result as $record) {
            $record["tags"] = $TagList->getTagsForModule('survey', $record["survey_id"]);   
            $record["countlike"] = LikeItem::countLike('survey', $record["survey_id"]);
            $record["liked"] = LikeItem::countLike('survey', $record["survey_id"],$my_user_id);
            $output[] = $record;
        }
        return $output; 

    }

}
