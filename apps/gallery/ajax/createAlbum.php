<?php
require_once('../../../lib/base.php');

if( !OC_User::isLoggedIn()){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => 'You need to log in.')));
	exit();
}

$stmt = OC_DB::prepare('INSERT INTO *PREFIX*gallery_albums ("uid_owner", "album_name") VALUES ("'.OC_User::getUser().'", "'.$_GET['album_name'].'")');
$stmt->execute(array());

echo json_encode(array( 'status' => 'success', 'name' => $_GET['album_name']));

?>
