<?php

$dir = '/';

if (isset($_GET['dir'])) {
	$dir = $_GET['dir'];
}

OCP\JSON::checkLoggedIn();
\OC::$session->close();

// send back json
OCP\JSON::success(array('data' => \OCA\Files\Helper::buildFileStorageStatistics($dir)));
