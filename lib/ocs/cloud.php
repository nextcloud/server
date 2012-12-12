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
		return new OC_OCS_Result($values);
	}
	
	public static function getUserQuota($parameters){
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

				return new OC_OCS_Result($xml);
			}else{
				return new OC_OCS_Result(null, 300);
			}
		}else{
			return new OC_OCS_Result(null, 300);
		}
	}
	
	public static function getUserPublickey($parameters){

		if(OC_User::userExists($parameters['user'])){
			// calculate the disc space
			// TODO
			return new OC_OCS_Result(array());
		}else{
			return new OC_OCS_Result(null, 300);
		}
	}
	
	public static function getUserPrivatekey($parameters){
		$user = OC_User::getUser();
		if(OC_Group::inGroup($user, 'admin') or ($user==$parameters['user'])) {

			if(OC_User::userExists($user)){
				// calculate the disc space
				$txt='this is the private key of '.$parameters['user'];
				echo($txt);
			}else{
				return new OC_OCS_Result(null, 300, 'User does not exist');
			}
		}else{
			return new OC_OCS_Result('null', 300, 'You don´t have permission to access this ressource.');
		}
	}
}
