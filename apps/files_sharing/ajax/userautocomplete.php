<?php
$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');

if( !OC_USER::isLoggedIn()){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Authentication error" )));
	exit();
}
$query = $_GET['term'];
$length = strlen($query);
$query = strtolower($query);
$users = array();
$ocusers = OC_USER::getUsers();
$self = OC_USER::getUser();
foreach ($ocusers as $user) {
	if ($user != $self && substr(strtolower($user), 0, $length) == $query) {
		$users[] = (object)array('id' => $user, 'label' => $user, 'name' => $user);
	}
}
echo json_encode($users);

?>
