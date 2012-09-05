<?php

class OC_OCS_Person {

	public static function check($parameters){
		$login = isset($_POST['login']) ? $_POST['login'] : false;
		$password = isset($_POST['password']) ? $_POST['password'] : false;
		if($login && $password){
			if(OC_User::checkPassword($login,$password)){
				$xml['person']['personid'] = $login;
				return $xml;
			}else{
				return 102;
			}
		}else{
			return 101;
		}
	}
}
