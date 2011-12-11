<?php
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('gallery');

OC_Gallery_Album::create(OC_User::getUser(), $_GET['album_name']);

OC_JSON::success(array('name' => $_GET['album_name']));

?>
