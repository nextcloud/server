<?php
$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');

if (!OC_User::isLoggedIn()) {
	echo json_encode(array("status" => "error", "data" => array("message" => "Authentication error")));
	exit();
}
$query = $_GET['term'];
$length = strlen($query);
$query = strtolower($query);
$users = array();
$ocusers = OC_User::getUsers();
$self = OC_User::getUser();
$groups = OC_GROUP::getUserGroups($self);
foreach ($ocusers as $user) {
	if ($user != $self && substr(strtolower($user), 0, $length) == $query) {
		$users[] = (object)array('id' => $user, 'label' => $user, 'name' => $user);
	}
}
foreach ($groups as $group) {
	if (substr(strtolower($group), 0, $length) == $query) {
		$users[] = (object)array('id' => $group, 'label' => $group, 'name' => $group);
	}
}
echo json_encode($users);

?>
