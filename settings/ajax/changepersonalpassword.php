<?php

// Check if we are an user
OC_JSON::callCheck();
OC_JSON::checkLoggedIn();

// Manually load apps to ensure hooks work correctly (workaround for issue 1503)
OC_App::loadApps();

$username = OC_User::getUser();
$password = isset($_POST['personal-password']) ? $_POST['personal-password'] : null;
$oldPassword = isset($_POST['oldpassword']) ? $_POST['oldpassword'] : '';

if (!OC_User::checkPassword($username, $oldPassword)) {
	$l = new \OC_L10n('settings');
	OC_JSON::error(array("data" => array("message" => $l->t("Wrong password")) ));
	exit();
}
if (!is_null($password) && OC_User::setPassword($username, $password)) {
	OC_JSON::success();
} else {
	OC_JSON::error();
}
