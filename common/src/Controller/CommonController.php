<?php
// using by: 
/**
 * @file
 */

namespace Drupal\common\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\fileshare\Controller\FileShareController;
use Drupal\fileshare\Common\FileShareDatatable;
use Drupal\blog\Common\BlogDatatable;
use Drupal\survey\Controller\SurveyController;
use Drupal\vote\Controller\VoteController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\common\CommonUtil;
use Drupal\common\AccessControl;
use \Drupal\Core\Routing;
use Drupal\common\LikeItem;
use Drupal\common\TagList;
use Drupal\common\RatingData;
use Drupal\common\Common\CommonDatatable;
use Drupal\common\Follow;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;

class CommonController extends ControllerBase {

    public function downloadModuleFile($module_name=NULL, $file_id=NULL) {

        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();
        $is_authen = $authen->isAuthenticated;
        $my_user_id = $authen->getUserId(); 

        $url = Url::fromUri('base:/no_access');
        if (! $is_authen) {
            return new RedirectResponse($url->toString());
        }

        switch($module_name) {

            case 'blog':
                $hasRecordAccessRight  = true; 
                $fname = \Drupal::request()->query->get('fname');
                $blog_uid = BlogDatatable::getBlogUID($file_id);
                                
                $this_entry_id = str_pad($file_id, 6, "0", STR_PAD_LEFT);
                $this_blog_uid = str_pad($blog_uid, 6, "0", STR_PAD_LEFT);
                $filename = "sites/default/files/private/blog/file/".$this_blog_uid."/".$this_entry_id."/".$fname;
            break;

            case 'fileshare':
                $hasRecordAccessRight  = false;
                $table_rows_file = FileShareDatatable::getSharedFile($file_id);
                if ($table_rows_file['file_id'] != null ) {
                    $hasRecordAccessRight  = true;  
                }
                $filename = FileShareController::getFileLocation($file_id);
            break;

            case 'activities':
                $hasRecordAccessRight  = true;  
                $fname = \Drupal::request()->query->get('fname');
                $this_file_id = str_pad($file_id, 6, "0", STR_PAD_LEFT);
                $filename = "sites/default/files/private/activities/deliverable/".$this_file_id."/".$fname;
                break;

            case 'ppcactivities':
                    $hasRecordAccessRight  = true;  
                    $fname = \Drupal::request()->query->get('fname');
                    $this_file_id = str_pad($file_id, 6, "0", STR_PAD_LEFT);
                    $filename = "sites/default/files/private/ppcactivities/deliverable/".$this_file_id."/".$fname;
            break;

            case 'survey':
                $hasRecordAccessRight  = false;
                $filename = SurveyController::getFileLocation($file_id, $my_user_id);
                if ($filename) {
                    $hasRecordAccessRight  = true;  
                }
            break;

            case 'survey_question':
                $hasRecordAccessRight  = false;
                $question_id = \Drupal::request()->query->get('question');
                $filename = SurveyController::getQuestionFileLocation($file_id, $question_id, $my_user_id);
                if ($filename) {
                    $hasRecordAccessRight  = true;  
                }
            break;

            case 'vote':
                $hasRecordAccessRight  = false;
                $filename = VoteController::getFileLocation($file_id, $my_user_id);
                if ($filename) {
                    $hasRecordAccessRight  = true;  
                }
            break;

            case 'vote_question':
                $hasRecordAccessRight  = false;
                $question_id = \Drupal::request()->query->get('question');
                $filename = VoteController::getQuestionFileLocation($file_id, $question_id, $my_user_id);
                if ($filename) {
                    $hasRecordAccessRight  = true;  
                }
            break;

            
            default:
                break;            
        }

       
        if(!$hasRecordAccessRight) {
            return array(
                '#type' => 'markup',
                '#markup' => $this->t('You do not have access right to download this file'),   
            );
        
            exit;
        } else  if(!file_exists($filename)) {
            return array(
                '#type' => 'markup',
                '#markup' => $this->t('File not found.'),   
            );
            exit;            
        } else {

            $filesize = filesize(urldecode( DRUPAL_ROOT ."/".$filename));

            header('Content-type: application/pdf');
            header("application/force-download");
            header('Content-Disposition: attachment; filename='.basename($filename).';');
            header("Content-length: $filesize");

            @readfile($filename);
            
/*
            return array(
                '#type' => 'markup',
                '#markup' => $this->t(' '),   
            );
*/            
            //return new RedirectResponse($filename);

        }

    }


    public static function addLike() {


        $AuthClass = "\Drupal\common\Authentication"; // get the Authentication class name from database
        $authen = new $AuthClass();
        $user_id = $authen->getUserId();    

        $module = \Drupal::request()->query->get('module');
        $record_id = \Drupal::request()->query->get('record_id');

        // check whether current user already "liked"?
        $count = LikeItem::countLike($module, $record_id, $user_id);
        
        // submit the "LIKE" for who does not submit before
        if($count == 0) {
            $entry = array(
              'module' => $module,
              'fid' => $record_id,
              'user_id' => $user_id,
              'is_deleted' => 0,
            );
            $return = LikeItem::insertLike($entry);
        }
        

        $countLike = LikeItem::countLike($module, $record_id);
        $response = array('result' => 1, 'data' => '<a href="javascript:;" onclick="Swal.fire({text:\'You have already liked this page\'});"><i class="fa-solid fa-thumbs-up" style="font-size:1.2rem"></i> (' . $countLike . ')</a>');

        return new JsonResponse($response);
    }
   
    public function getAddGroupMemberUI() {

        $request = \Drupal::request();   // Request from ajax call
        $content = $request->getContent();

        
        $params = array();
        if (!empty($content)) {
            $params = json_decode($content, TRUE);  // Decode json input
        }
        
        $record_id = $params['record_id'];
        $module = $params['module'];
        $current_group = AccessControl::getMyAccessControl($module, $record_id);

        $renderable = [
            '#module' => $module,
            '#groups' => $current_group,
            '#record_id' => $record_id,
            '#theme' => 'common-accesscontrol',
          ];
        $content = \Drupal::service('renderer')->renderPlain($renderable);
          
        $response = array($content);
        return new JsonResponse($response);

    }

    public function getAddGroupMemberGroupType() {

        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();

        $request = \Drupal::request();   // Request from ajax call
        $content = $request->getContent();
        $params = array();
        if (!empty($content)) {
            $params = json_decode($content, TRUE);  // Decode json input
        }

        $group_type = $params['group_type'];
        $record_id = $params['record_id'];

        if ($group_type=="B") {
            $myBuddyGroup = CommonDatatable::getBuddyGroupByUId($authen->getUId());

            $renderable = [
                '#theme' => 'common-accesscontrol-personal',
                '#items' =>  $myBuddyGroup,
                '#record_id' => $record_id,
              ];
        } else {
            $renderable = [
                '#theme' => 'common-accesscontrol-grouptype',
                '#record_id' => $record_id,
            ];

        }
        $content = \Drupal::service('renderer')->renderPlain($renderable);

        $response = array($content);
        return new JsonResponse($response);

    }


    public function getAllPublicGroupForAddAccessControl() {

        $request = \Drupal::request();   // Request from ajax call
        $content = $request->getContent();
        $params = array();
        if (!empty($content)) {
            $params = json_decode($content, TRUE);  // Decode json input
        }

        $record_id = $params['record_id'];
        $search_str = $params['search_str'];
        $module = $params['module'];
        $publicGroup = CommonDatatable::getAllPublicGroup($search_str);
        if ($publicGroup && $publicGroup !="") {
            $renderable = [
                '#theme' => 'common-accesscontrol-grouptype',
                '#items' => $publicGroup,
                '#module' => $module,
                '#record_id' => $record_id,
            ];
            $content = \Drupal::service('renderer')->renderPlain($renderable);
        } else {
            $content = "No Record found";
        }
        
        $response = array($content);
        return new JsonResponse($response);        
        

    }

    public function getGroupMemberDiv() {
        
        $request = \Drupal::request();   // Request from ajax call
        $content = $request->getContent();
        $params = array();
        if (!empty($content)) {
            $params = json_decode($content, TRUE);  // Decode json input
        }
        
        /*
         * 3 parameters from ajax
         * elmt_name
         * group_type
         * group_id
         */
        foreach ($params as $key => $value) {
            $$key = $value;
        }

        $groupInfo = array();
        if($group_type == "P") {
            $groupInfo = CommonDatatable::getPublicGroupByGroupId($group_id);
        } else if($group_type == "B") {
            $groupInfo = CommonDatatable::getBuddyGroupByGroupId($group_id);
        }
        
        $group_member = "";
        $groupMembers = CommonDatatable::getUserListByGroupId($group_type, $group_id, $group_user_id="");
        $i = 0;
        if ($groupMembers) {
            foreach($groupMembers as $groupMember) {                 
                    $group_member .= '<div>'.$groupMember['user_name'].'</div>';
                    $i++;
            }
        }
        
        $group_name = "";
        if ($groupInfo)
          $group_name = $groupInfo->group_name;
                
        if ($groupMembers) {
            $response = array('group_name' => $group_name, 'group_member' => $group_member);
        } else {
            $response = array('group_name' => $group_name, 'group_member' => 'No record');
        }
        return new JsonResponse($response);
        
    }    


    public function AccessControlAddAction() {
        
        
        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();
        $author = CommonUtil::getSysValue('AuthorClass');
        $my_user_id = $authen->getUserId();
        
        $request = \Drupal::request();   // Request from ajax call
        $content = $request->getContent();
        $params = array();
        if (!empty($content)) {
            $params = json_decode($content, TRUE);  // Decode json input
        }
        
        foreach ($params as $key => $value) {
            $$key = $value;
        }
        
        $content = "";
        if($this_module == "" || $record_id == "") {
            $content .= "Missing data 1";
        } else if($group_type == "" || $group_id == "") {
            $content .= "Missing data 2";
        }

        
        // check whether the selected group already exist
        $record = AccessControl::accessControlInfo("", array('module' => $this_module, 'user_id' => $my_user_id, 'record_id' => $record_id, 'group_type' => $group_type, 'group_id' => $group_id, 'is_deleted' => 0));

        $isRecordExist = (isset($record->id) && $record->id != "") ? true : false;
        
        // record already exist
  
        if($isRecordExist) {
        
                   
            $content .= "Already exist.";
            
        } else {
            $database = \Drupal::database();
            $transaction = $database->startTransaction();   
            try {
                $entry = array(
                  'module' => $this_module,
                  'group_type' => $group_type,
                  'group_id' => $group_id,
                  'record_id' =>$record_id,
                  'user_id' => $my_user_id,
                  'create_datetime' => date('Y-m-d H:i:s'),
                );

                $query = \Drupal::database()->insert('kicp_access_control')
                ->fields($entry);
                $entry_id = $query->execute();
                if ($entry_id) {
                    \Drupal::logger('common')->info('add access control module %module, record_id: %record_id, group_type: %group_type, group_id: %group_id.',   
                    array(
                        '%module' => $this_module,
                        '%record_id' => $record_id,
                        '%group_type' => $group_type,
                        '%group_id' => $group_id,
                    ));
                    $content .= "Updated";
                }     

            }
            catch (Exception $e) {
            
                $variables = Error::decodeException($e);
                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('Access control list is not updated.'));
                \Drupal::logger('common')->error('access control is not added '  . $variables);
                $content .= "Error";
                $transaction->rollBack();   
            }
            unset($transaction);
            
        }
       
        $response = array($content);
        return new JsonResponse($response);
        
    }    


    public function getCurrentAccessControlGroup() {

        $request = \Drupal::request();   // Request from ajax call
        $content = $request->getContent();
        $params = array();
        if (!empty($content)) {
            $params = json_decode($content, TRUE);  // Decode json input
        }

        foreach ($params as $key => $value) {
            $$key = $value;
        }

        $current_group = AccessControl::getMyAccessControl($this_module, $record_id);

        $renderable = [
            '#theme' => 'common-accesscontrol-modal-left',
            '#groups' => $current_group,
            '#record_id' => $record_id,
        ];
        $content = \Drupal::service('renderer')->renderPlain($renderable);        


        $response = array($content);
        return new JsonResponse($response); 

 
    }    


    public function AccessControlDeleteAction() {

        /*
        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();
        $author = CommonUtil::getSysValue('AuthorClass'); 
        
        $user_id = $authen->getUserId();
        $isSiteAdmin = $author::isSiteAdmin($user_id);
        */

        /*
        if (!$authen->isAuthenticated) {
            $response = array('result' => '3');
            return new JsonResponse($response);
        }
        */

        $request = \Drupal::request();   // Request from ajax call
        $content = $request->getContent();
        $params = array();

        if (!empty($content)) {
            $params = json_decode($content, TRUE);  // Decode json input
        }
        
        foreach ($params as $key => $value) {
            $$key = $value;
        }

        /*
        if (!$isSiteAdmin) {
            if (!$author::hasPermission($this_module, TRUE)) {
                $response = array('result' => '3');
                return new JsonResponse($response);
            }
            if (!$author::hasRight($this_module.'_maint', $authen->getUId(), 'D', true)) {
                $response = array('result' => '3');
                return new JsonResponse($response);
            }
        }
        */
        
        $database = \Drupal::database();
        $transaction = $database->startTransaction();             
        try {
            // delete record
            $query = $database->update('kicp_access_control')->fields([
                'is_deleted'=>1, 
                ])
            ->condition('module',$this_module)
            ->condition('record_id', $record_id)
            ->condition('group_type', $group_type)
            ->condition('group_id', $group_id)
            ->condition('is_deleted', 0);
            $row_affected = $query->execute();

            if ($row_affected) {
                \Drupal::logger('common')->info('delete access control module %module, record_id: %record_id, group_type: %group_type, group_id: %group_id.',   
                array(
                    '%module' => $this_module,
                    '%record_id' => $record_id,
                    '%group_type' => $group_type,
                    '%group_id' => $group_id,
                ));
            }     

            $response = array('result' => $query);
            return new JsonResponse($response);
    

        }
        catch (Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('Unable to delete this record due to datbase error. Please try again. ' )
                );
            \Drupal::logger('common')->error('access control is not deleted '  . $variables);                   
            $transaction->rollBack();                  
        }
        unset($transaction);

    }    

    public function getMoreTag() {

        $current_no_of_loaded_tag = \Drupal::request()->query->get('current_no_of_loaded_tag');
        $start = (isset($current_no_of_loaded_tag) && $current_no_of_loaded_tag != "") ? $current_no_of_loaded_tag : 0;

        $interval = \Drupal::request()->query->get('$interval');
        $interval = (isset($interval) && $interval != "") ? $interval : 0;

        $taglist = new TagList();
        $other_tags = $taglist->getOtherTagList($start, $interval);

        $renderable = [
            '#theme' => 'common-tags-other',
            '#other_tags' => $other_tags,
        ];
        $allTags = \Drupal::service('renderer')->renderPlain($renderable);        

        $response = new Response();
        $response->setContent($allTags);
        return $response;
    }


    public function showCpRateBox(Request $request) {
        $Wording = array('N/A', 'Poor', 'Nothing special', 'Worth watching', 'Pretty cool', 'Excellent');

        $content = $request->getContent();   // Get request content, this should be JSON params as this is specified in the ajax call param "dataType: JSON"
        $params = array();
        if (!empty($content)) {
            $params = json_decode($content, TRUE);  // Decode json input
        }
        ////var_dump($params);

        foreach ($params as $key => $value) {
            $$key = $value;
        }

        $strHTML = "<div class='cpRateBoxDiv'>" .
            "<form name='cpRateBox_$rateId' id='cpRateBox_$rateId' onsubmit='return false'>";
        $strHTML .= 'Your Rating is ';
        for ($j = 0; $j < $rating; $j++) {
            $strHTML .= "<span class='cpRateStarOn'>&nbsp;</span>";
        }
        $strHTML .= '&nbsp;' . $Wording[$rating] . '<br/><br/>' .
            '<center><button style="font:14px arial,sans-serif" onclick="cpRating(' . $rateId . ',\'' . $userId . '\',' . $rating . ',this.parentNode.parentNode.parentNode.parentNode,\'' . $module . '\',\'' . $type . '\')" >Submit</button>&emsp;&emsp;' .
            '<button style="font:14px arial,sans-serif" onclick="this.parentNode.parentNode.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode.parentNode.parentNode)" >Cancel</button></center>';

        $strHTML .= "<input type=hidden name=rating />" .
            "</form></div>";

        return new Response($strHTML);
    }
    
    
    public function addRatingRecord() {
     
        $request = \Drupal::request();   // Request from ajax call
        $content = $request->getContent();   // Get request content, this should be JSON params as this is specified in the ajax call param "dataType: JSON"        
        $params = array();
        if (!empty($content)) {
            $params = json_decode($content, TRUE);  // Decode json input
        }

        foreach ($params as $key => $value) {
            $$key = $value;
        }

 
        // add record
        $entry = array(
            'module' => $module,
            'rate_id' => $id,
            'user_id' => strtoupper($user),
            'rating' => $rating
        );
        

        try {
            $database = \Drupal::database();
            $return = $database-> insert('kicp_rate')
                ->fields($entry)
                ->execute();
        }
        catch (\Exception $e) {

            \Drupal::messenger()->addError(
                t('Unable to add rating. '.$id )
                );

        }

        if ($return) {
            $err_code = '1';
        }
        else {
            $err_code = '0';
        }

        $RatingData = new RatingData();
        $rating = $RatingData->getList($module, $id);
        
        $renderable = [
            '#theme' => 'common-rating',
            '#rating' => $rating,
            '#user_id' => strtoupper($user),
            '#justsubmit' => 1,
          ];
          $rendered = \Drupal::service('renderer')->renderPlain($renderable);
                  
        //Construct json for output
        $response = array('result' => $err_code, 'ratingpic' => $rendered);
        return new JsonResponse($response);
    }    


    public function getKicpediaTag() {

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $is_authen = $authen->isAuthenticated;

        $url = Url::fromUri('base:/no_access');
        if (! $is_authen) {
            return new RedirectResponse($url->toString());
        }

        $tags = array();
        $tagsUrl = \Drupal::request()->query->get('tags');
        
        $wikipage = CommonDatatable::getWikiTags();
    
        if ($tagsUrl) {
          $tags = json_decode($tagsUrl);
          if ($tags && count($tags) > 0 ) {
            $tmp = $tags;
          }
        }
    
        return [
            '#theme' => 'common-wikipages',
            '#items' => $wikipage,
            '#tags' => $tags,
            '#empty' => t('No entries available.'),
            '#tagsUrl' => $tmp,
            '#pager' => ['#type' => 'pager',
                        ],
        ];            

    }

    public function updateFollowStatus() {
        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();
        $user_id = $authen->getUserId();
        
        $contributor_id = (isset($_REQUEST['contributor_id']) && $_REQUEST['contributor_id'] != "") ? $_REQUEST['contributor_id'] : "";
        $status = (isset($_REQUEST['status']) && $_REQUEST['status'] != "") ? $_REQUEST['status'] : "";
        
        $err_code = 0;
        // if current user wants to follow himeself/herself, then return "0" (i.e. fail)
        if($user_id == $contributor_id) {
            $err_code = 1;
        } else {
        
            // add follow
            if($status == 1) {

                $followDetail = array('user_id'=>$user_id, 'contributor_id'=>$contributor_id, 'is_deleted'=>0, 'create_datetime'=>date('Y-m-d h:m:s'));
                $return = Follow::addFollow($followDetail);

                // write logs to common log table
                \Drupal::logger('mainpage')->info('Insert follow list, user_id : %user_id, contributor_id: %contributor_id',   
                array(
                    '%user_id' => $user_id,
                    '%contributor_id' => $contributor_id,

                ));   


            } else if($status == 0) {
                // cancel follow
                $followDetail = array('user_id'=>$user_id, 'contributor_id'=>$contributor_id);
                $return = Follow::updateFollow($followDetail);

                // write logs to common log table
                \Drupal::logger('mainpage')->info('Delete follow list, user_id : %user_id, contributor_id: %contributor_id',   
                array(
                    '%user_id' => $user_id,
                    '%contributor_id' => $contributor_id,

                ));   

            }
        }
        
        if ($err_code == 1) {
            \Drupal::logger('mainpage')->error('Follow status is not updated (1)');
            $messenger = \Drupal::messenger(); 
            $messenger->addError( t('follow is not updated.'));

        }
        $following = Follow::getFollow($contributor_id, $user_id);

        $renderable = [
            '#theme' => 'common-follow',
            '#following' => $following,
            '#contributor_id' => $contributor_id,
            '#my_user_id' => $user_id,
        ];
        $rendered = \Drupal::service('renderer')->renderPlain($renderable);

        $response = array('result' => $err_code, 'following' => $rendered);
        return new JsonResponse($response);
                
    }

    public static function notFound() {
        return [
            '#type' => 'markup',
            '#markup' => 'Sorry. This page cannot be found.',
          ];
    }

    public static function Breadcrumb() {

        $breads = array();
        $routeName = \Drupal::routeMatch()->getRouteName();
        if ($routeName=="common.kicpedia_tag") {
            $breads[] = [
                'name' => 'KICPedia', 
                'url' => 'mediawiki'
            ];
            $breads[] = [
                'name' => 'Tags', 
            ];
        } 

        return $breads;

    }

}