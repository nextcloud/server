<?php

OCP\JSON::callCheck();
OC_JSON::checkSubAdminUser();

if(OC_User::isAdminUser(OC_User::getUser())) {
	$groups = array();
	if (!empty($_POST["groups"])) {
		$groups = $_POST["groups"];
	}
}else{
	if (isset($_POST["groups"])) {
		$groups = array();
		if (!empty($_POST["groups"])) {
			foreach ($_POST["groups"] as $group) {
				if (OC_SubAdmin::isGroupAccessible(OC_User::getUser(), $group)) {
					$groups[] = $group;
				}
			}
		}
		if (empty($groups)) {
			$groups = OC_SubAdmin::getSubAdminsGroups(OC_User::getUser());
		}
	} else {
		$groups = OC_SubAdmin::getSubAdminsGroups(OC_User::getUser());
	}
}
$username = $_POST["username"];
$password = $_POST["password"];

// Return Success story
try {
	// check whether the user's files home exists
	$userDirectory = OC_User::getHome($username) . '/files/';
	$homeExists = file_exists($userDirectory);

	if (!OC_User::createUser($username, $password)) {
		OC_JSON::error(array('data' => array( 'message' => 'User creation failed for '.$username )));
		exit();
	}
	foreach( $groups as $i ) {
		if(!OC_Group::groupExists($i)) {
			OC_Group::createGroup($i);
		}
		OC_Group::addToGroup( $username, $i );
	}

	$userManager = \OC_User::getManager();
	$user = $userManager->get($username);
	OCP\JSON::success(array("data" =>
				array(
					// returns whether the home already existed
					"homeExists" => $homeExists,
					"username" => $username,
					"groups" => OC_Group::getUserGroups( $username ),
					'storageLocation' => $user->getHome())));
} catch (Exception $exception) {
	OCP\JSON::error(array("data" => array( "message" => $exception->getMessage())));
}
