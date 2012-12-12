<?php

OC_Util::checkAdminUser();
OCP\JSON::callCheck();

$app = $_GET['app'];

//load the one app and see what it adds to the navigation
OC_App::loadApp($app);

$navigation = OC_App::getNavigation();

$navIds = array();
foreach ($navigation as $nav) {
	$navIds[] = $nav['id'];
}

OCP\JSON::success(array('nav_ids' => array_values($navIds), 'nav_entries' => $navigation));
