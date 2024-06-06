<?php
// using by: 

namespace Drupal\common;

use Drupal;
use Drupal\common\CommonUtil;
use Drupal\Core\Database\Database;

class Authorisation {

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

}