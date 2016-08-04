<?php

\OC_Util::checkLoggedIn();
\OC::$server->getSession()->close();

$file = array_key_exists('file', $_GET) ? (string)$_GET['file'] : '';
$maxX = array_key_exists('x', $_GET) ? (int)$_GET['x'] : '32';
$maxY = array_key_exists('y', $_GET) ? (int)$_GET['y'] : '32';
$keepAspect = array_key_exists('a', $_GET) ? true : false;
$always = array_key_exists('forceIcon', $_GET) ? (bool)$_GET['forceIcon'] : true;
$mode = array_key_exists('mode', $_GET) ? $_GET['mode'] : 'fill';

if ($file === '') {
	//400 Bad Request
	\OC_Response::setStatus(400);
	\OCP\Util::writeLog('core-preview', 'No file parameter was passed', \OCP\Util::DEBUG);
	exit;
}

if ($maxX === 0 || $maxY === 0) {
	//400 Bad Request
	\OC_Response::setStatus(400);
	\OCP\Util::writeLog('core-preview', 'x and/or y set to 0', \OCP\Util::DEBUG);
	exit;
}

$userFolder = \OC::$server->getUserFolder();
$file = $userFolder->get($file);

$p = new \OC\Preview2(\OC::$server->getRootFolder(),
	\OC::$server->getConfig(),
	\OC::$server->getPreviewManager(),
	$file);

$p->getPreview($maxX, $maxY, !$keepAspect, $mode);

