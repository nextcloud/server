<?php

$dir = '/';

if (isset($_GET['dir'])) {
	$dir = $_GET['dir'];
}

OCP\JSON::checkLoggedIn();
\OC::$server->getSession()->close();

// send back json
try {
	OCP\JSON::success(array('data' => \OCA\Files\Helper::buildFileStorageStatistics($dir)));
} catch (\OCP\Files\NotFoundException $e) {
	OCP\JSON::error(['data' => ['message' => 'Folder not found']]);
}
