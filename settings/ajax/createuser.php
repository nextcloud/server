<?php

OCP\JSON::callCheck();
OC_JSON::checkSubAdminUser();

if(OC_User::isAdminUser(OC_User::getUser())) {
	$groups = array();
	if( isset( $_POST["groups"] )) {
		$groups = $_POST["groups"];
	}
}else{
	if(isset( $_POST["groups"] )) {
		$groups = array();
		foreach($_POST["groups"] as $group) {
			if(OC_SubAdmin::isGroupAccessible(OC_User::getUser(), $group)) {
				$groups[] = $group;
			}
		}
		if(count($groups) == 0) {
			$groups = OC_SubAdmin::getSubAdminsGroups(OC_User::getUser());
		}
	}else{
		$groups = OC_SubAdmin::getSubAdminsGroups(OC_User::getUser());
	}
}
$username = $_POST["username"];
$password = $_POST["password"];

// Return Success story
try {
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
	OC_JSON::success(array("data" =>
				array(
					"username" => $username,
					"groups" => implode( ", ", OC_Group::getUserGroups( $username )))));
} catch (Exception $exception) {
	OC_JSON::error(array("data" => array( "message" => $exception->getMessage())));
}
