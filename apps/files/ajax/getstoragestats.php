<?php

// only need filesystem apps
$RUNTIME_APPTYPES = array('filesystem');

OCP\JSON::checkLoggedIn();

// send back json
OCP\JSON::success(array('data' => \OCA\files\lib\Helper::buildFileStorageStatistics('/')));
