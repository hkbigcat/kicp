<?php

namespace Drupal\common;

use Drupal\user\Entity\User;
use Drupal;
use Drupal\common\AutoLogin;
use Drupal\common\CommonUtil;
use Drupal\Core\Database\Database;

class Authentication {

    private $group_id = array();
    public $user_id;
    private $user_name;
    private $uid;
    private $user_email;
    public $isAuthenticated = false;
	
    public function __construct() {

        // check whether 
        $login_bypass_flag = CommonUtil::getSysValue('login_bypass');
        $this->domain_name = CommonUtil::getSysValue('domain_name');

        if ($login_bypass_flag) {

            // assign default values to object (preset as "KICPA Administrator" acount)
            $this->user_id = 'KICPA.OGCIO';
            $this->isAuthenticated = true;
            $this->uid = 2056;
            $this->user_name = "KICPA ADMINISTRATOR";
	        $this->user_email = 'kicpadm@ogcio.gov.hk';

            self::loginDrupal($this->user_id);
        }
        else {

            ///    define("DOMAIN_NAME", $this->domain_name);
            ///    define("APP_PATH", $config->get('app_path'));
            //Get openam userid from header
            $this->user_id = self::getUserIdFromRequestHeader();
            
            //IF openam userid not found or is not valid client IP and is not Active KICP user in Drupal then set isAuthenticated to false
            if (empty($this->user_id) || !self::isValidIP() || !self::isActiveKICPUser($this->user_id))
                $this->isAuthenticated = false;
            else {
                //Get User Info from table xoops_users and kicp_buddy_user_list using openam userid and save to instance variables
                //Also Login Drupal
                $this->isAuthenticated = true;

                $userInfo = self::getKICPUserInfo($this->user_id);

                if (!empty($userInfo)) {
                    $this->uid = $userInfo['uid'];
                    $this->user_name = $userInfo['user_name'];
		            $this->user_email = $userInfo['user_int_email'];
                    self::loginDrupal($this->user_id);
                }
            }
        }

	ini_set('display_errors', 0);
	ini_set('display_startup_errors', 0);

    }

    static function getUserIdFromRequestHeader() {

        $User_Id = '';

        $header_arr = getallheaders();
        
		$User_Id = isset($_SERVER['HTTP_UID']) ? strtoupper($_SERVER['HTTP_UID']) : '';
		//print_r($_SERVER);

		// if fail to get user_id from session, try to retrieve it from another way
        if($User_Id == "") {
			$sessionAry = explode('; ', $_SERVER['HTTP_COOKIE']);
			foreach($sessionAry as $para) {
				if(substr($para, 0, 17) == "kicp_wikiUserName") {
					$User_Id = substr($para,18);
					break;
				}
			}
        }

	
        if (empty($User_Id))
            
	    //drupal_set_message(t('HTTP_UID from request header is empty'), 'error');
        \Drupal::messenger()->addError(t('HTTP_UID from request header is empty'));

        return $User_Id;
    }

    static function getKICPUserInfo($UserId) {

        $userInfo = array();

        $sql = " SELECT u.uid, u.user_name, user_displayname, user_int_email FROM xoops_users u ";
        $sql .= " WHERE u.user_id = '" . $UserId . "' ";

        //$result = db_query($sql);
        $result = \Drupal::database() -> query($sql);

        foreach ($result as $record) {
            return $userInfo = array('uid' => $record->uid,
              'user_name' => $record->user_name,
              'display_name' => $record->user_displayname,
	      'user_int_email' => $record->user_int_email
            );
        }

        return $userInfo;
    }
    
    static function getKICPUserInfoByUId($UId) {

        $userInfo = array();

        $sql = " SELECT u.uid, u.user_name, user_displayname, user_id FROM xoops_users u ";
        $sql .= " WHERE u.uid = '" . $UId . "' ";

        //$result = db_query($sql);
        $result = \Drupal::database() -> query($sql);

        foreach ($result as $record) {
            return $userInfo = array('uid' => $record->uid,
              'user_name' => $record->user_name,
              'display_name' => $record->user_displayname,
              'user_id' => $record->user_id
            );
        }

        return $userInfo;
    }

    static function isValidIP() {

        //If client IP not found in kicp system table kicp_login_ip then return false
        $ip = Drupal::request()->getClientIp();

        $sql = " SELECT 1 FROM kicp_login_ip WHERE ip = '" . $ip . "' ";
        //$result = db_query($sql);
        $result = \Drupal::database() -> query($sql);

        foreach ($result as $record) {
            return true;
        }

        //drupal_set_message(t('Invalid IP "' . $ip . '" is missing in database'), 'error');
        \Drupal::messenger()->addError(t('Invalid IP "' . $ip . '" is missing in database'));

        return false;
    }

    static function isActiveKICPUser($UserId) {

        $sql = "select 1 from xoops_users where user_is_inactive = 0 and user_id = '" . $UserId . "' ";
        //$result = db_query($sql);
        $result = \Drupal::database() -> query($sql);

        foreach ($result as $record) {
            return true;
        }

        //drupal_set_message(t('User "' . $UserId . '" is inactive'), 'error');
        \Drupal::messenger()->addError(t('User "' . $UserId . '" is inactive'));


        return false;
    }

    static function loginDrupal($UserId) {
        
        global $skipDisclaimerCheck;
        
        // skip checking on current page
        if($skipDisclaimerCheck) {
            // do nothing
            //echo "Skip check.<br>";
        } else {
            
            // go check 
            $acceptedDisclaimer = self::isUserAcceptedDisclaimer($UserId);
            //echo "Disclaimer accepted? ".$acceptedDisclaimer;
            if($acceptedDisclaimer) {
                //$_SESSION[$UserId]['accepted_disclaimer'] = true;
            } else {

                $goto = urlencode($_SERVER['REQUEST_URI']);

                CommonUtil::goToUrl('ask_disclaimer?a=1&target='.$goto);
                exit;
            }
        }
		
        if ($UserId != \Drupal::currentUser()->getAccountName()) {

            // login Drupal
            $sql = "SELECT sys_value from kicp_system_config WHERE sys_property = 'drupal_user_pwd'";
			
            //$result = db_query($sql);
            $result = \Drupal::database() -> query($sql);
            $record = $result->fetchObject();
            $pwd = $record->sys_value;
			
            $AutoLogin_obj = new AutoLogin();
            $new_user_obj = $AutoLogin_obj->login($UserId, $pwd);

            if (is_null($new_user_obj) or ! isset($new_user_obj))
                //drupal_set_message(t('Cannot login Drupal'), 'error');
                \Drupal::messenger()->addError(t('Cannot login Drupal'));

        }
    }

    public function getName() {
        return $this->user_name;
    }

    public function getUid() {
        return $this->uid;
    }

    public function getUserId() {
        return $this->user_id;
    }

    public function getUserEmail() {
	return $this->user_email;
    }

    public function isLogin() {
        return $this->uid != 0;
    }

    public function getGroupId() {
        return $this->group_id;
    }
    
    public static function isUserAcceptedDisclaimer($UserId) {
        
        $sql = "SELECT user_is_disclaimer FROM xoops_users WHERE user_id='".$UserId."'";
        //$result = db_query($sql);
        $result = \Drupal::database() -> query($sql);
        $record = $result->fetchObject();
        
        return $record->user_is_disclaimer;
    }
    

}