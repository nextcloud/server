<?php
// Init owncloud
require_once('../../lib/base.php');
$connector = new OC_Connector_Sabre_Principal;
$users = OC_User::getUsers();

foreach($users as $user){
	$foo = $connector->getPrincipalByPath('principals/'.$user);
	if(!isset($foo)){
		OC_Connector_Sabre_Principal::addPrincipal(array('uid'=>$user));
	}
}
echo "done";