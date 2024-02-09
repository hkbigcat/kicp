<?php
// using by: 
/**
 * @file
 */

namespace Drupal\common\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\fileshare\Controller\FileShareController;
use Drupal\blog\Common\BlogDatatable;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\common\CommonUtil;
use Drupal\common\AccessControl;
use \Drupal\Core\Routing;
use Drupal\common\LikeItem;
use Drupal\common\Controller\TagList;
use Drupal\common\Common\CommonDatatable;

class CommonController extends ControllerBase {

    public function downloadModuleFile($module_name=NULL, $file_id=NULL) {

        switch($module_name) {

            
            case 'blog':
                 $fname = \Drupal::request()->query->get('fname');
                 $entry_uid = BlogDatatable::getBlogIDByEntryID($file_id);
                                
                $this_entry_id = str_pad($file_id, 6, "0", STR_PAD_LEFT);
                $this_entry_uid = str_pad($entry_uid, 6, "0", STR_PAD_LEFT);
                $filename = "sites/default/files/private/blog/file/".$this_entry_uid."/".$this_entry_id."/".$fname;
            break;

            case 'fileshare':
                $filename = FileShareController::getFileLocation($file_id);
            break;

            default:
                break;            
        }

        
        $filesize = filesize(urldecode($filename));

        header('Content-type: application/pdf');
        header("application/force-download");
        header('Content-Disposition: attachment; filename='.basename($filename).';');
        header("Content-length: $filesize");

        @readfile($filename);
        

        return array(
            '#type' => 'markup',
            '#markup' => $this->t(' '),   
        );
        //return new RedirectResponse($filename);

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
        $response = array('result' => 1, 'data' => '<i class="fa-solid fa-thumbs-up" style="font-size:1.2rem"></i> (' . $countLike . ')');

        return new JsonResponse($response);
    }

    public function CheckAttachmentDuplication() {
        // check file duplication during selecting attachment

        $AuthClass = CommonUtil::getSysValue('AuthClass');
        $authen = new $AuthClass();

        $fileList = $_POST['fileList'];
        $entry_id = $_POST['entry_id'];
        $module = $_POST['module'];
        $fileAry = explode("###", $fileList);    // "###" is the delimitor used in frontend "blog_form.js"

        $server_path = CommonUtil::getSysValue("server_absolute_path");
        $app_path = CommonUtil::getSysValue('app_path');
        $blog_id = BlogDatatable::getBlogIDByEntryID($entry_id);
        $user_id = BlogDatatable::getUserIdByBlogId($blog_id);
        $UserInfo = $authen->getKICPUserInfo($user_id);
        $this_entry_id = str_pad($entry_id, 6, "0", STR_PAD_LEFT);
        $this_uid = str_pad($UserInfo['uid'], 6, "0", STR_PAD_LEFT);

        $filePath = $app_path . '/sites/default/files/private/' . $module . '/file/' . $this_uid . '/' . $this_entry_id;
        $dirFile = array();

        // retrieve existing attachment from server
        if (is_dir($server_path . $filePath)) {
            $dirFile = scandir($server_path . $filePath);
        }

        $returnAry = array();
        if (count($dirFile) > 0) {
            $i = 0;
            foreach ($dirFile as $attach_id => $attach) {
                if ($attach == "." || $attach == "..") {
                    continue;
                }
                // compare selected files with server files 
                if (in_array($attach, $fileAry)) {
                    $returnAry[] = $attach;
                }
            }
        }

        $returnFlag = (count($returnAry) == 0) ? false : true;    // true: with duplication; false: without duplication


        $response = new Response();
        $response->setContent('||' . $returnFlag . '@@' . implode('##', $returnAry) . '||');
        return $response;
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
        $current_group = AccessControl::getMyAccessControl($params['module'], $record_id);

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
        $publicGroup = CommonDatatable::getAllPublicGroup($search_str);
        if ($publicGroup && $publicGroup !="") {
            $renderable = [
                '#theme' => 'common-accesscontrol-grouptype',
                '#items' => $publicGroup,
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

        
        if($group_type == "P") {
            $groupInfo = CommonDatatable::getPublicGroupByGroupId($group_id);
        } else if($group_type == "B") {
            $groupInfo = CommonDatatable::getBuddyGroupByGroupId($group_id);
        }
        

        $groupMembers = CommonDatatable::getUserListByGroupId($group_type, $group_id, $group_user_id="");
        
        $i = 0;
        foreach($groupMembers as $groupMember) {                 
                $group_member .= '<div>'.$groupMember['user_name'].'</div>';
                $i++;
        }
        
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
                $content .= "Updated";

            }
            catch (Exception $e) {
            
                //$variables = Error::decodeException($e);

                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('Access control list is not updated.'));

                $content .= "Error";
                
            }
            
            
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

        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();
        $author = CommonUtil::getSysValue('AuthorClass'); 
        
        $user_id = $authen->getUserId();
        $isSiteAdmin = $author::isSiteAdmin($user_id);

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
        
        // check whether the record is deleted        
        $isDeleted = CommonUtil::isRecordDeleted('kicp_access_control', array('module' => $this_module, 'record_id' => $record_id, 'group_type' => $group_type, 'group_id' => $group_id));
        
        //  if the blog entry already deleted
        if ($isDeleted) {
            $err_code = '2';
        }
        else {
            try {
                // delete record
                $database = \Drupal::database();
                $query = $database->update('kicp_access_control')->fields([
                    'is_deleted'=>1, 
                  ])
                ->condition('module',$this_module)
                ->condition('record_id', $record_id)
                ->condition('group_type', $group_type)
                ->condition('group_id', $group_id)
                ->execute();

                $response = array('result' => $query);
                return new JsonResponse($response);
        

            }
            catch (Exception $e) {
                \Drupal::messenger()->addStatus(
                    t('Unable to delete this record due to datbase error. Please try again. ' )
                    );
            }
        }

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


}