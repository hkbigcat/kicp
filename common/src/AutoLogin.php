<?php

namespace Drupal\common;

//use function Drupal\common\user_load as user_load;

use Drupal\common\CreateUser;

use \Drupal\user\UserAuth;

class AutoLogin {

	public function __construct() {
		// Nothing
	}

	public static function login($userName, $pwd) {
		
		$uid = \Drupal::service('user.auth')->authenticate(
				$userName, $pwd
		);
		
		// To authenticate a user and return user id
		if ($uid) {
			// To load a new user object
			//$new_user_obj = user_load($uid, TRUE);
			\Drupal::entityTypeManager()->getStorage('user')->resetCache([$uid,]);
			$new_user_obj = \Drupal\user\Entity\User::load($uid);

			// Finalize user login (modify $_SESSION, set login time in db)
			user_login_finalize($new_user_obj);

			return $new_user_obj;
		} else {
			// assume $userName does not exist, then create the user
			new CreateUser($userName, $pwd);
			return self::login($userName, $pwd);
		}
	}

}