<?php

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
	$userCount = 0;
	foreach ($groupUsers as $user) {
		if ($user != $self) {
			$users[] = "<option value='".$user."'>".$user."</option>";
			$userCount++;
		}
	}
	// Don't include the group if only the current user is a member of it
	if ($userCount > 0) {
		$groups[] = "<option value='".$group."(group)'>".$group." (group) </option>";
	}
}
$users = array_unique($users);
$users[] = "</optgroup>";
$groups[] = "</optgroup>";
$users = array_merge($users, $groups);
OCP\JSON::encodedPrint($users);

?>
