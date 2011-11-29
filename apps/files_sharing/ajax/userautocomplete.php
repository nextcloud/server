<?php
//$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');

OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('files_sharing');

$users = array();
$ocusers = OC_User::getUsers();
$self = OC_User::getUser();
$groups = OC_Group::getUserGroups($self);
$users[] = "<optgroup label='Users'>";
foreach ($ocusers as $user) {
	if ($user != $self) {
		$users[] = "<option value='".$user."'>".$user."</option>";
	}
}
$users[] = "</optgroup>";
$users[] = "<optgroup label='Groups'>";
foreach ($groups as $group) {
	$users[] = "<option value='".$group."'>".$group."</option>";
}
$users[] = "</optgroup>";
OC_JSON::encodedPrint($users);

?>
