<?php

OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::callCheck();

OCP\JSON::checkAdminUser();

$pattern = '';
$limit = null;
$offset = null;
if (isset($_GET['pattern'])) {
	$pattern = (string)$_GET['pattern'];
}
if (isset($_GET['limit'])) {
	$limit = (int)$_GET['limit'];
}
if (isset($_GET['offset'])) {
	$offset = (int)$_GET['offset'];
}

$groups = \OC_Group::getGroups($pattern, $limit, $offset);
$users = \OCP\User::getDisplayNames($pattern, $limit, $offset);

$results = array('groups' => $groups, 'users' => $users);

\OCP\JSON::success($results);
