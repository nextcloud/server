<?php

require_once 'apps/user_openid/user_openid.php';

OC_APP::addSettingsPage( array( "id" => "user_openid_settings", 'order'=>1, "href" => OC_HELPER::linkTo( "user_openid", "settings.php" ), "name" => "OpenID"));

//active the openid backend
OC_USER::useBackend('openid');

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
