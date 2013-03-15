<?php

OC_Util::checkAdminUser();
OCP\JSON::callCheck();

$app = $_GET['app'];
$app = OC_App::cleanAppId($app);

$navigation = OC_App::getAppNavigationEntries($app);

$navIds = array();
foreach ($navigation as $nav) {
	$navIds[] = $nav['id'];
}

OCP\JSON::success(array('nav_ids' => array_values($navIds), 'nav_entries' => $navigation));
