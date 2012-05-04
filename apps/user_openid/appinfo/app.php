<?php

//check if curl extension installed
if (!in_array ('curl', get_loaded_extensions())){
	return;
}

$userName='';
if(strpos($_SERVER["REQUEST_URI"],'?') and !strpos($_SERVER["REQUEST_URI"],'=')){
	if(strpos($_SERVER["REQUEST_URI"],'/?')){
		$userName=substr($_SERVER["REQUEST_URI"],strpos($_SERVER["REQUEST_URI"],'/?')+2);
	}elseif(strpos($_SERVER["REQUEST_URI"],'.php?')){
		$userName=substr($_SERVER["REQUEST_URI"],strpos($_SERVER["REQUEST_URI"],'.php?')+5);
	}
}

OCP\Util::addHeader('link',array('rel'=>'openid.server', 'href'=>OCP\Util::linkToAbsolute( "user_openid", "user.php" ).'/'.$userName));
OCP\Util::addHeader('link',array('rel'=>'openid.delegate', 'href'=>OCP\Util::linkToAbsolute( "user_openid", "user.php" ).'/'.$userName));

OCP\App::registerPersonal('user_openid','settings');

require_once 'apps/user_openid/user_openid.php';

//active the openid backend
OC_User::useBackend('openid');

//check for results from openid requests
if(isset($_GET['openid_mode']) and $_GET['openid_mode'] == 'id_res'){
	OCP\Util::writeLog('user_openid','openid retured',OCP\Util::DEBUG);
	$openid = new SimpleOpenID;
	$openid->SetIdentity($_GET['openid_identity']);
	$openid_validation_result = $openid->ValidateWithServer();
	if ($openid_validation_result == true){         // OK HERE KEY IS VALID
		OCP\Util::writeLog('user_openid','auth sucessfull',OCP\Util::DEBUG);
		$identity=$openid->GetIdentity();
		OCP\Util::writeLog('user_openid','auth as '.$identity,OCP\Util::DEBUG);
		$user=OC_USER_OPENID::findUserForIdentity($identity);
		if($user){
			$_SESSION['user_id']=$user;
			header("Location: ".OC::$WEBROOT);
		}
	}else if($openid->IsError() == true){            // ON THE WAY, WE GOT SOME ERROR
		$error = $openid->GetError();
		OCP\Util::writeLog('user_openid','ERROR CODE: '. $error['code'],OCP\Util::ERROR);
		OCP\Util::writeLog('user_openid','ERROR DESCRIPTION: '. $error['description'],OCP\Util::ERROR);
	}else{                                            // Signature Verification Failed
		OCP\Util::writeLog('user_openid','INVALID AUTHORIZATION',OCP\Util::ERROR);
	}
}else if (isset($_GET['openid_mode']) and $_GET['openid_mode'] == 'cancel'){ // User Canceled your Request
	OCP\Util::writeLog('user_openid','USER CANCELED REQUEST',OCP\Util::DEBUG);
	return false;
}

?>
