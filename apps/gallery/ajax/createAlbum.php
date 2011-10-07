<?php
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('gallery');

$stmt = OC_DB::prepare('INSERT INTO *PREFIX*gallery_albums ("uid_owner", "album_name") VALUES ("'.OC_User::getUser().'", "'.$_GET['album_name'].'")');
$stmt->execute(array());

OC_JSON::success(array('name' => $_GET['album_name']));

?>
