<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_sharing');

$users = array();
$groups = array();
$self = OCP\USER::getUser();
$users[] = "<optgroup label='Users'>";
$groups[] = "<optgroup label='Groups'>";
if(OCP\Config::getAppValue('files_sharing', 'allowSharingWithEveryone', 'no') == 'yes') {
	$allGroups = OC_Group::getGroups();
	foreach($allGroups as $group) {
	    $groups[] = "<option value='".$group."(group)'>".$group." (group) </option>";
	}
	$allUsers = OC_User::getUsers();
	foreach($allUsers as $user) {
		if($user != $self) {
			$users[] = "<option value='".$user."'>".$user."</option>";
	    }
	}
} else {
	$userGroups = OC_Group::getUserGroups($self);
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
}
$users[] = "</optgroup>";
$groups[] = "</optgroup>";
$users = array_merge($users, $groups);
OCP\JSON::encodedPrint($users);

?>
