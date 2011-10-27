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

OC_Util::addHeader('link',array('rel'=>'openid.server', 'href'=>OC_Helper::linkTo( "user_openid", "user.php", null, true ).'/'.$userName));
OC_Util::addHeader('link',array('rel'=>'openid.delegate', 'href'=>OC_Helper::linkTo( "user_openid", "user.php", null, true ).'/'.$userName));

OC_APP::registerPersonal('user_openid','settings');

require_once 'apps/user_openid/user_openid.php';

//active the openid backend
OC_User::useBackend('openid');

//check for results from openid requests
if(isset($_GET['openid_mode']) and $_GET['openid_mode'] == 'id_res'){
	OC_Log::write('user_openid','openid retured',OC_Log::DEBUG);
	$openid = new SimpleOpenID;
	$openid->SetIdentity($_GET['openid_identity']);
	$openid_validation_result = $openid->ValidateWithServer();
	if ($openid_validation_result == true){         // OK HERE KEY IS VALID
		OC_Log::write('user_openid','auth sucessfull',OC_Log::DEBUG);
		$identity=$openid->GetIdentity();
		OC_Log::write('user_openid','auth as '.$identity,OC_Log::DEBUG);
		$user=OC_USER_OPENID::findUserForIdentity($identity);
		if($user){
			$_SESSION['user_id']=$user;
			header("Location: ".OC::$WEBROOT);
		}
	}else if($openid->IsError() == true){            // ON THE WAY, WE GOT SOME ERROR
		$error = $openid->GetError();
		OC_Log::write('user_openid','ERROR CODE: '. $error['code'],OC_Log::ERROR);
		OC_Log::write('user_openid','ERROR DESCRIPTION: '. $error['description'],OC_Log::ERROR);
	}else{                                            // Signature Verification Failed
		OC_Log::write('user_openid','INVALID AUTHORIZATION',OC_Log::ERROR);
	}
}else if (isset($_GET['openid_mode']) and $_GET['openid_mode'] == 'cancel'){ // User Canceled your Request
	OC_Log::write('user_openid','USER CANCELED REQUEST',OC_Log::DEBUG);
	return false;
}

?>
