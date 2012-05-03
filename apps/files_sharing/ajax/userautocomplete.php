<?php
//$RUNTIME_NOAPPS = true;

 

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_sharing');

$users = array();
$groups = array();
$self = OCP\USER::getUser();
$userGroups = OC_Group::getUserGroups($self);
$users[] = "<optgroup label='Users'>";
$groups[] = "<optgroup label='Groups'>";
foreach ($userGroups as $group) {
	$groupUsers = OC_Group::usersInGroup($group);
	foreach ($groupUsers as $user) {
		if ($user != $self) {
			$users[] = "<option value='".$user."'>".$user."</option>";
		}
	}
	$groups[] = "<option value='".$group."'>".$group."</option>";
}
$users[] = "</optgroup>";
$groups[] = "</optgroup>";
$users = array_merge($users, $groups);
OCP\JSON::encodedPrint($users);

?>
