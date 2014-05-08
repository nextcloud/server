<?php

OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::callCheck();

OCP\JSON::checkAdminUser();

$pattern = '';
$limit = null;
$offset = null;
if (isset($_GET['pattern'])) {
	$pattern = $_GET['pattern'];
}
if (isset($_GET['limit'])) {
	$limit = $_GET['limit'];
}
if (isset($_GET['offset'])) {
	$offset = $_GET['offset'];
}

$groups = \OC_Group::getGroups($pattern, $limit, $offset);
$users = \OCP\User::getDisplayNames($pattern, $limit, $offset);

$results = array('groups' => $groups, 'users' => $users);

\OCP\JSON::success($results);
