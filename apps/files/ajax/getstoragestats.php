<?php

// only need filesystem apps
$RUNTIME_APPTYPES = array('filesystem');

$dir = '/';

if (isset($_GET['dir'])) {
	$dir = $_GET['dir'];
}

OCP\JSON::checkLoggedIn();

// send back json
OCP\JSON::success(array('data' => \OCA\Files\Helper::buildFileStorageStatistics($dir)));
