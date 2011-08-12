<?php

// Init owncloud
require_once('../../lib/base.php');

$l=new OC_L10N('settings');

// We send json data
header("Content-Type: application/jsonrequest");

// Check if we are a user
if(!OC_User::isLoggedIn()){
	echo json_encode(array("status" => "error", "data" => array("message" => $l->t("Authentication error"))));
	exit();
}

// Get data
if(!isset($_POST["password"]) && !isset($_POST["oldpassword"])){
	echo json_encode(array("status" => "error", "data" => array("message" => $l->t("You have to enter the old and the new password!"))));
	exit();
}

// Check if the old password is correct
if(!OC_User::checkPassword($_SESSION["user_id"], $_POST["oldpassword"])){
	echo json_encode(array("status" => "error", "data" => array("message" => $l->t("Your old password is wrong!"))));
	exit();
}

// Change password
if(OC_User::setPassword($_SESSION["user_id"], $_POST["password"])){
	echo json_encode(array("status" => "success", "data" => array("message" => $l->t("Password changed"))));
	OC_Crypt::changekeypasscode($_POST["password"]);
}else{
	echo json_encode(array("status" => "error", "data" => array("message" => $l->t("Unable to change password"))));
}

?>
