<?php
// using by: 

namespace Drupal\common;

use Drupal;
use Drupal\common\CommonUtil;
use Drupal\Core\Database\Database;

class Authorisation {

    static function setMenuTabsByModulePermission() {

        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();
        
        if(self::isSiteAdmin($authen->getUserId())) {
            return "";
        }
        
        //Construct javascript to hide unauthorized menu tabs
        
        $output = '<script type="text/javascript">';
        //$output .= 'window.onload = function() {';

        $userModulePermissionList = self::userUnauthorizedModuleList();

        foreach ($userModulePermissionList as $module) {
            //$output .= 'jQuery("a[href=\'' . $config->get('app_path') . '/' . $module . '\']").parent().remove();';
            $output .= 'jQuery("a[href=\'' . CommonUtil::getSysValue('app_path_url') . '/' . $module . '\']").parent().remove();';
        }

        //$output .= '};';
        $output .= '</script>';

        return $output;
    }

    static function removeAllModuleMenuTabs() {

        //Construct javascript to hide unauthorized menu tabs
        
        $output = '<script type="text/javascript">';
        //$output .= 'window.onload = function() {';

        $allModulesList = self::getAllModules();
        foreach ($allModulesList as $record) {
            $module = $record->module_name;
            $output .= 'jQuery("a[href=\'' . CommonUtil::getSysValue('app_path') . '/' . $module . '\']").parent().remove();';
        }

        //$output .= '};';
        $output .= '</script>';

        return $output;
    }

    //To Check if current user has permission on the input module $InModle
    static function hasPermission($InModule, $ShowWarnMsg = \FALSE) {

        $user_id = \Drupal::currentUser()->getAccount()->getAccountName();
        $group_id = null;

        //To get buddy_group which currently user belongs to
        $sql = " SELECT buddy_group_id FROM kicp_buddy_user_list WHERE buddy_user_id = '" . $user_id . "' AND is_deleted = 0";
        //$result = db_query($sql);
        $result = \Drupal::database() -> query($sql);
        foreach ($result as $record) {
            $group_id[] = $record->buddy_group_id;
        }
        /*
          ### Checking 1 ###
          //If current user does not belongs to any group under buddy_user_list then return false
          if (count($group_id) == 0) {
          \Drupal::logger('Authorisation-hasPermission')->notice('current user does not belongs to any group under buddy list');
          return false;
          }

          $group_list = implode(",", $group_id);
          \Drupal::logger('Authorisation-hasPermission')->notice('$group_list:::' . $group_list);
         */

        /*
          ### Checking 2 ###
          //check public group of current user for the input module $InModule
          $sql = "select * from xoops_users a join kicp_public_user_list b on a.user_id = b.pub_user_id" .
          " join kicp_public_group c on b.pub_group_id = c.pub_group_id".
          " join kicp_group_privilege d on b.pub_group_id = d.group_id" .
          " WHERE a.user_is_inactive = 0 and d.type = 'P' and a.user_id='" . $user_id .
          "' and d.module= '" . $InModule . "' ";

          $result = db_query($sql);

          foreach ($result as $record) {
          return true;
          }

          \Drupal::logger('Authorisation-hasPermission')->notice('public group: current user '.$user_id.' does not have privilege on the module:::' . $InModule);


          ### Checking 3 ###
          //check buddy group of current user for the input module $InModule
          $sql = "select * from xoops_users a join kicp_buddy_user_list b on a.user_id = b.buddy_id" .
          " join kicp_buddy_group c on b.buddy_group_id = c.buddy_group_id".
          " join kicp_group_privilege d on b.buddy_group_id = d.group_id" .
          " WHERE a.user_is_inactive = 0 and d.type = 'B' and a.user_id='" . $user_id .
          "' and d.module= '" . $InModule . "' ";

          $result = db_query($sql);
          foreach ($result as $record) {
          return true;
          }

          \Drupal::logger('Authorisation-hasPermission')->notice('buddy group: current user '.$user_id.' does not have privilege on the module:::' . $InModule);
         */


        ### Combined Checking (2+3) ###
        // check whether current user is member of any public groups and buddy groups in module "$InModule"
        $sql = "
            SELECT 
                    a.uid
            FROM 
                    xoops_users a
                            LEFT JOIN kicp_public_user_list b ON (a.user_id = b.pub_user_id AND a.user_id = '" . $user_id . "'  AND b.is_deleted = 0)
                            JOIN kicp_public_group c ON (b.pub_group_id = c.pub_group_id AND c.is_deleted = 0)
                            LEFT JOIN kicp_group_privilege d ON (b.pub_group_id = d.group_id AND d.type = 'P' AND d.is_deleted = 0)
                            WHERE
                                    a.user_is_inactive = 0 AND a.user_id = '" . $user_id . "' AND d.module='" . $InModule . "'

                            UNION

                            SELECT 
                                    a.uid
                            FROM 
                                    xoops_users a
                            LEFT JOIN kicp_buddy_user_list e ON (a.user_id = e.buddy_user_id AND a.user_id = '" . $user_id . "'  AND e.is_deleted = 0)
                            JOIN kicp_buddy_group f ON (e.buddy_group_id = f.buddy_group_id AND f.is_deleted = 0)
                            LEFT JOIN kicp_group_privilege g ON (e.buddy_group_id = g.group_id AND g.type = 'B' AND g.is_deleted = 0)
                            WHERE
                                    a.user_is_inactive = 0 AND a.user_id = '" . $user_id . "' AND g.module = '" . $InModule . "'
	";
        
        //$result = db_query($sql);
        $result = \Drupal::database() -> query($sql);
        foreach ($result as $record) {
            return true;
        }

        //\Drupal::logger('Authorisation-hasPermission')->notice('public & buddy group: current user ' . $user_id . ' does not have privilege on the module:::' . $InModule);





        if ($ShowWarnMsg)
            drupal_set_message(t('You do not have permission on module "' . $InModule . '"'), 'warning');

        return false;
    }

    static function hasPermissionToAnyOfModules() {
        $sql = 'SELECT DISTINCT module FROM kicp_group_privilege WHERE is_deleted = 0 ORDER BY module';
        //$result = db_query($sql);
        $result = \Drupal::database() -> query($sql);

        foreach ($result as $record) {
            $module = $record->module;
            if (self::hasPermission($module))
                return true;
        }

        drupal_set_message(t('You do not have permission on any modules of KICP'), 'warning');

        return false;
    }

    static function userModulePermissionList() {
        $userModulePermissionList = array();
        $result = self::getAllModules();
        foreach ($result as $record) {
            $module = $record->module_name;
            if (self::hasPermission($module))
                array_push($userModulePermissionList, $module);
        }

        return $userModulePermissionList;
    }

    static function getAllModules() {
        $sql = 'select * from kicp_module order by display_order';
        $result = db_query($sql);

        return $result;
    }

    static function userUnauthorizedModuleList() {
        $userUnauthorizedModuleList = array();
        $result = self::getAllModules();
        foreach ($result as $record) {
            $module = $record->module_name;
            if (!self::hasPermission($module))
                array_push($userUnauthorizedModuleList, $module);
        }

        return $userUnauthorizedModuleList;
    }

    static function gotoPortal() {
        // go to portal web site after time out
        $domain_name = CommonUtil::getSysValue('domain_name');
        $delay = CommonUtil::getSysValue('delay_time_to_portal');
        $output = "";
        // if go back to "Portal", then no need to check the access right of module
        /* 	
          $output =
          '<script type="text/javascript">
          window.onload = function() {';

          $userModulePermissionList = self::userUnauthorizedModuleList();
          foreach ($userModulePermissionList as $module) {
          $output .=
          'jQuery("a[href=\'' . $config->get('app_path') . '/' . $module . '\']").parent().remove();';
          }
          $output .=
          '
          setTimeout(
          function() { window.open(\''.$domain_name.'\',\'_self\'); }, '.
          $delay.
          ');
          };
          </script>';
         */
        $userModulePermissionList = self::userUnauthorizedModuleList();
        header("Location: " . $domain_name);
        exit;
        return $output;
    }

    static function isSiteAdmin($user_id) {
        if (!isset($user_id) or $user_id == "") {
            return false;
        }

        $kicpa_user_id = explode(';', CommonUtil::getSysValue('kicpa_user_id'));
        if (in_array($user_id, $kicpa_user_id)) {
            return true;
        }

        return false;
    }

    static function hasRight($code, $userId, $action, $ShowWarnMsg = \FALSE) {
        if (!isset($userId) or $userId == "") {
            return false;
        }

        $sql = "select * from xoops_users a join kicp_public_user_list b on a.user_id = b.pub_user_id AND b.is_deleted = 0
                    join kicp_public_group c on b.pub_group_id = c.pub_group_id AND c.is_deleted = 0
                    join kicp_permission d on b.pub_group_id = d.group_id AND d.is_deleted =0
                    join kicp_function e on d.function_code = e.function_code 
                    where a.user_is_inactive = 0 and d.type = 'P' and e.status = 'A'
                    and e.function_code = '$code' and a.uid=$userId";
        //$result = db_query($sql);
        $result = \Drupal::database() -> query($sql);
        foreach ($result as $record) {
            if (($action == 'R' and intval($record->is_read) == 1)
                or ( $action == 'W' and intval($record->is_write) == 1)
                or ( $action == 'C' and intval($record->is_change) == 1)
                or ( $action == 'D' and intval($record->is_delete) == 1)) {
                return true;
                break;
            }
        }

        $sql = "select * from xoops_users a join kicp_buddy_user_list b on a.user_id = b.buddy_user_id AND b.is_deleted = 0
    join kicp_buddy_group c on b.buddy_group_id = c.buddy_group_id AND c.is_deleted = 0
    join kicp_permission d on b.buddy_group_id = d.group_id AND d.is_deleted =0
    join kicp_function e on d.function_code = e.function_code 
    WHERE a.user_is_inactive = 0 and d.type = 'B' and e.status = 'A'
    and e.function_code = '$code' and a.uid=$userId";
        //$result = db_query($sql);
        $result = \Drupal::database() -> query($sql);
        foreach ($result as $record) {
            if (($action == 'R' and intval($record->is_read) == 1)
                or ( $action == 'W' and intval($record->is_write) == 1)
                or ( $action == 'C' and intval($record->is_change) == 1)
                or ( $action == 'D' and intval($record->is_delete) == 1)) {
                return true;
                break;
            }
        }

        if ($ShowWarnMsg)
            drupal_set_message(t('You do not have permission on this function.  (' . $code . ')'), 'warning');

        return false;
    }

    public static function GlobalModuleAccessRightChecking($InModule) {

        ### Module access right checking [Start] ###

        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        
        //$domain_name = CommonUtil::getSysValue('domain_name');

        $isSiteAdmin = self::isSiteAdmin($authen->getUserId());  // store "isSiteAdmin" checking in variable
        # has no access right on all modules
        if (!$authen->isAuthenticated) {
            $output = self::removeAllModuleMenuTabs();
	    /*
            return array(
              '#type' => 'markup',
              '#markup' => $this->t($output),
            );
	    */
	    return $output;
        }

        // if current user is not the site admin
        if (!$isSiteAdmin) {

            # get the avaible module list that user has access right to view
            //$output .= implode(',',self::userModulePermissionList());

            if (self::hasPermissionToAnyOfModules()) {

                // user has no premission to access specific module, e.g. blog, ppc, etc...
                if (!self::hasPermission($InModule, TRUE)) {
                    $output = self::setMenuTabsByModulePermission();  // remove other module tab which user does not have access right
                    //$output = $InModule;
                    /*
                      return array(
                      '#type' => 'markup',
                      '#markup' => $this->t($output),
                      );
                     */
                    return $output;
                }
            }
            else {
                //$output = self::gotoPortal();	// no access right to any of the modules, then return to portal
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") ? "https" : "http";
                //$url = $domain_name.CommonUtil::getSysValue('app_path_url');
                $url = $protocol . "://" . $_SERVER['HTTP_HOST'] . CommonUtil::getSysValue('app_path');
                header("Location: " . $url);
                exit;
                /*
                  return array(
                  '#type' => 'markup',
                  '#markup' => $this->t($output),
                  );
                 */
                return $output;
            }
        }

        $output = "true";

        return $output;

        ### Module access right checking [End] ###
    }

}