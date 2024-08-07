<?php

namespace Drupal\common;

use Drupal\user\Entity\User;
use Drupal;
use Drupal\common\AutoLogin;
use Drupal\common\CommonUtil;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Authentication {

    private $group_id = array();
    public $user_id;
    private $user_name;
    private $uid;
    private $user_email;
    public $isAuthenticated = false;
	
    public function __construct() {

        $login_bypass_flag = CommonUtil::getSysValue('login_bypass');

        if ($login_bypass_flag) {
                // assign default values to object (preset as "KICPA Administrator" acount)
                $this->user_id = 'KICPA.OGCIO';
                $this->isAuthenticated = true;
                $this->uid = 2056;
                $this->user_name = "KICPA ADMINISTRATOR";
                $this->user_email = 'kicpadm@ogcio.gov.hk';

                self::loginDrupal($this->user_id);
                
        } else {

            $this->user_id = self::getUserIdFromRequestHeader();
            
            //IF openam userid not found or is not valid client IP and is not Active KICP user in Drupal then set isAuthenticated to false
            if (empty($this->user_id) || !self::isValidIP() || !self::isActiveKICPUser($this->user_id)) {
                $this->isAuthenticated = false;
                
            }
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
            $User_Id = (isset($_COOKIE['wiki_login_user']) && $_COOKIE['wiki_login_user'] != "") ? $_COOKIE['wiki_login_user'] : "";
        }

        if (empty($User_Id)) {           
            \Drupal::logger('common')->error('HTTP_UID from request header is empty');                              
            \Drupal::messenger()->addError(t('HTTP_UID from request header is empty'));
        }

        return $User_Id;
    }

    static function getKICPUserInfo($UserId) {

        $userInfo = array();
        $sql = " SELECT u.uid, u.user_name, user_displayname as display_name, user_int_email FROM xoops_users u WHERE u.user_id = '" . $UserId . "' ";
        $database = \Drupal::database();
        $userInfo = $database-> query($sql)->fetchAssoc();
        return $userInfo;
    }
    
    static function getKICPUserInfoByUId($UId) {

        $userInfo = array();
        $sql = " SELECT u.uid, u.user_name, user_displayname as display_name, user_id FROM xoops_users u WHERE u.uid = '" . $UId . "' ";
        $database = \Drupal::database();
        $userInfo = $database-> query($sql)->fetchAssoc();
        return $userInfo;
    }

    static function isValidIP() {

        //If client IP not found in kicp system table kicp_login_ip then return false
        $ip = Drupal::request()->getClientIp();

        $sql = " SELECT 1 FROM kicp_login_ip WHERE ip = '" . $ip . "' ";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        if ($result) 
            return true;

        \Drupal::messenger()->addError(t('Invalid IP "' . $ip . '" is not in allowed list.'));
        return false;
    }

    static function isActiveKICPUser($UserId) {

        $sql = "select user_is_inactive from xoops_users where user_id = '" . $UserId . "' ";
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchObject();
        if (!$result) {
            \Drupal::messenger()->addError(t('User "' . $UserId . '" is not an OGCIO Portal user.'));
            return false;
        }
        if ($result->user_is_inactive==1) {
            \Drupal::messenger()->addError(t('User "' . $UserId . '" is inactive'));
            return false;
        }

        return true;
    }

    static function loginDrupal($UserId) {
        
        //global $skipDisclaimerCheck;
        $request = \Drupal::request();
        $session = $request->getSession();
        $skipDisclaimerCheck = $session->get('skipDisclaimerCheck');
       
                // skip checking on current page
        if(!$skipDisclaimerCheck) {
            // go check 
            $acceptedDisclaimer = self::isUserAcceptedDisclaimer($UserId);

            //echo "Disclaimer accepted? ".$acceptedDisclaimer;
            $routeName = \Drupal::routeMatch()->getRouteName();
            $node = \Drupal::routeMatch()->getParameter('node');
            if(!$acceptedDisclaimer && $routeName!="mainpage.mainpage_ask_disclaimer" && $routeName!='mainpage.mainpage_go_accept_disclaimer' && $routeName!='mainpage.mainpage_ask_disclaimer' && $node!=1) {
                $goto = urlencode($_SERVER['REQUEST_URI']);
                $url = Url::fromUri('base:/ask_disclaimer');
                $response = new RedirectResponse($url->toString().'?a=1&target='.$goto);
                $response->send();

                return new RedirectResponse(urldecode($url));

                //CommonUtil::goToUrl('ask_disclaimer?a=1&target='.$goto);
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

            if (is_null($new_user_obj) or ! isset($new_user_obj)) {
                \Drupal::logger('common')->error('login error: '.$UserId);                              
                \Drupal::messenger()->addError(t('Cannot login Drupal'));
            }
            else 
                \Drupal::logger('common')->info('Login: '.$UserId);              

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


    function checkAccessRight() {

        $url = Url::fromUri('base:/no_access');
        if (!$this->isAuthenticated) {
            return new RedirectResponse($url->toString());
        }

    }

}