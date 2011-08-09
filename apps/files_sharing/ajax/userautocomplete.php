<?php
$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');

if (!OC_User::isLoggedIn()) {
	echo json_encode(array("status" => "error", "data" => array("message" => "Authentication error")));
	exit();
}
$users = array();
$ocusers = OC_User::getUsers();
$self = OC_User::getUser();
$groups = OC_GROUP::getUserGroups($self);
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
echo json_encode($users);

?>
