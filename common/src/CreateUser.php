<?php

namespace Drupal\common;

//use function Drupal\common\entity_create as entity_create;
use Drupal\Core\Database\Database;

class CreateUser {

	public function __construct($userName, $pwd) {

		$user_surname = "Last";
		$user_forename = "First";
		$email = "dummy@test.com";

		$sql = "select user_surname, user_name, email from xoops_users where user_id='$userName'";
		//$result = db_query($sql);
		$result = \Drupal::database() -> query($sql);

		foreach ($result as $record) {
			$user_surname = $record->user_surname;
			$user_forename = $record->user_name;
			$pos = strpos($user_forename, ',');
			if ($pos !== false) {
				$pos++;
				$user_forename = substr($user_forename, $pos);
				$user_forename = trim($user_forename);
			}
			$email = $record->email;
		}

		$values = array(
			'field_first_name' => $user_forename,
			'fieldt_last_name' => $user_surname,
			'name' => $userName,
			'mail' => $email,
			'roles' => array(),
			'pass' => $pwd,
			'status' => 1,
		);

		//$account = entity_create('user', $values);
		$account = \Drupal::entityTypeManager()->getStorage('user')->create($values);
		
		$account->save();
	}

}