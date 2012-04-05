<?php
//$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');

OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('files_sharing');

$users = array();
$groups = array();
$self = OC_User::getUser();
$userGroups = OC_Group::getUserGroups($self);
$users[] = "<optgroup label='Users'>";
$groups[] = "<optgroup label='Groups'>";
if(count($userGroups) == 0) {
	$availableUsers = OC_User::getUsers();
        foreach ($availableUsers as $user) {
                if (($user != $self) && (count(OC_Group::getUserGroups($user) == 0))) {
                        $users[] = "<option value='".$user."'>".$user."</option>";
                }
        }
}
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
