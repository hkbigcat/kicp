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
use \Drupal\Core\Routing;
use Drupal\common\LikeItem;
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

        $renderable = [
            '#theme' => 'common-accesscontrol',
          ];
        $content = \Drupal::service('renderer')->renderPlain($renderable);
          
        $response = array($content);
        return new JsonResponse($response);

    }

    public function getAddGroupMemberGroupType() {

        $renderable = [
            '#theme' => 'common-accesscontrol-grouptype',
          ];
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

        
        $search_str = $params['search_str'];
        $publicGroup = CommonDatatable::getAllPublicGroup($search_str);
        if ($publicGroup && $publicGroup !="") {
            $renderable = [
                '#theme' => 'common-accesscontrol-grouptype',
                '#items' => $publicGroup,
                //'#search_str' => $search_str,
            ];
            $content = \Drupal::service('renderer')->renderPlain($renderable);
        } else {
            $content = "No Record found";
        }
        
        $response = array($content);
        return new JsonResponse($response);        
        

    }

}