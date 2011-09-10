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
	error_log('openid retured');
	$openid = new SimpleOpenID;
	$openid->SetIdentity($_GET['openid_identity']);
	$openid_validation_result = $openid->ValidateWithServer();
	if ($openid_validation_result == true){         // OK HERE KEY IS VALID
		error_log('auth sucessfull');
		global $WEBROOT;
		$identity=$openid->GetIdentity();
		error_log("auth as $identity");
		$user=OC_USER_OPENID::findUserForIdentity($identity);
		if($user){
			$_SESSION['user_id']=$user;
			header("Location: $WEBROOT");
		}
	}else if($openid->IsError() == true){            // ON THE WAY, WE GOT SOME ERROR
		$error = $openid->GetError();
		error_log("ERROR CODE: " . $error['code']);
		error_log("ERROR DESCRIPTION: " . $error['description']);
	}else{                                            // Signature Verification Failed
		error_log("INVALID AUTHORIZATION");
	}
}else if (isset($_GET['openid_mode']) and $_GET['openid_mode'] == 'cancel'){ // User Canceled your Request
	error_log("USER CANCELED REQUEST");
	return false;
}

?>
