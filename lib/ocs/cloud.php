<?php

class OC_OCS_Cloud {

	public static function getSystemWebApps($parameters){
		OC_Util::checkLoggedIn();
		$apps = OC_App::getEnabledApps();
		$values = array();
		foreach($apps as $app) {
			$info = OC_App::getAppInfo($app);
			if(isset($info['standalone'])) {
				$newvalue = array('name'=>$info['name'],'url'=>OC_Helper::linkToAbsolute($app,''),'icon'=>'');
				$values[] = $newvalue;
			}
		}
		return $values;
	}
	
	public static function getUserQuota($parameters){
		OC_Util::checkLoggedIn();
		$user = OC_User::getUser();
		if(OC_Group::inGroup($user, 'admin') or ($user==$parameters['user'])) {

			if(OC_User::userExists($parameters['user'])){
				// calculate the disc space
				$user_dir = '/'.$parameters['user'].'/files';
				OC_Filesystem::init($user_dir);
				$rootInfo=OC_FileCache::get('');
				$sharedInfo=OC_FileCache::get('/Shared');
				$used=$rootInfo['size']-$sharedInfo['size'];
				$free=OC_Filesystem::free_space();
				$total=$free+$used;
				if($total==0) $total=1;  // prevent division by zero
				$relative=round(($used/$total)*10000)/100;

				$xml=array();
				$xml['quota']=$total;
				$xml['free']=$free;
				$xml['used']=$used;
				$xml['relative']=$relative;

				return $xml;
			}else{
				return 300;
			}
		}else{
			return 300;
		}
	}
	
	public static function setUserQuota($parameters){
		OC_Util::checkLoggedIn();
		$user = OC_User::getUser();
		if(OC_Group::inGroup($user, 'admin')) {
		
			// todo
			// not yet implemented
			// add logic here
			error_log('OCS call: user:'.$parameters['user'].' quota:'.$parameters['quota']);
			
			$xml=array();
			return $xml;
		}else{
			return 300;
		}
	}
	
	public static function getUserPublickey($parameters){
		OC_Util::checkLoggedIn();

		if(OC_User::userExists($parameters['user'])){
			// calculate the disc space
			// TODO
			return array();
		}else{
			return 300;
		}
	}
	
	public static function getUserPrivatekey($parameters){
		OC_Util::checkLoggedIn();
		$user = OC_User::getUser();
		if(OC_Group::inGroup($user, 'admin') or ($user==$parameters['user'])) {

			if(OC_User::userExists($user)){
				// calculate the disc space
				$txt='this is the private key of '.$parameters['user'];
				echo($txt);
			}else{
				echo self::generateXml('', 'fail', 300, 'User does not exist');
			}
		}else{
			echo self::generateXml('', 'fail', 300, 'You don´t have permission to access this ressource.');
		}
	}
}
