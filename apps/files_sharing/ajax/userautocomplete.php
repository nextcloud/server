<?php
//$RUNTIME_NOAPPS = true;

 

OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('files_sharing');

$users = array();
$groups = array();
$self = OC_User::getUser();
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
OC_JSON::encodedPrint($users);

?>
