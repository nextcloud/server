<?php

// Init owncloud
require_once('../../lib/base.php');

// Check if we are a user
if( !OC_User::isLoggedIn() || (!OC_Group::inGroup( OC_User::getUser(), 'admin' ) && !OC_SubAdmin::isSubAdmin(OC_User::getUser()))){
	OC_JSON::error(array("data" => array( "message" => "Authentication error" )));
	exit();
}
OCP\JSON::callCheck();

$isadmin = OC_Group::inGroup(OC_User::getUser(),'admin')?true:false;

if($isadmin){
	$groups = array();
	if( isset( $_POST["groups"] )){
		$groups = $_POST["groups"];
	}
}else{
	$accessiblegroups = OC_SubAdmin::getSubAdminsGroups(OC_User::getUser());
	$accessiblegroups = array_flip($accessiblegroups);
	if(isset( $_POST["groups"] )){
		$unauditedgroups = $_POST["groups"];
		$groups = array();
		foreach($unauditedgroups as $group){
			if(array_key_exists($group, $accessiblegroups)){
				$groups[] = $group;
			}
		}
		if(count($groups) == 0){
			$groups = array_flip($accessiblegroups);
		}
	}else{
		$groups = array_flip($accessiblegroups);
	}
}
$username = $_POST["username"];
$password = $_POST["password"];

// Does the group exist?
if( in_array( $username, OC_User::getUsers())){
	OC_JSON::error(array("data" => array( "message" => "User already exists" )));
	exit();
}

// Return Success story
try {
	OC_User::createUser($username, $password);
	foreach( $groups as $i ){
		if(!OC_Group::groupExists($i)){
			OC_Group::createGroup($i);
		}
		OC_Group::addToGroup( $username, $i );
	}
	OC_JSON::success(array("data" => array( "username" => $username, "groups" => implode( ", ", OC_Group::getUserGroups( $username )))));
} catch (Exception $exception) {
	OC_JSON::error(array("data" => array( "message" => $exception->getMessage())));
}
