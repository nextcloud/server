<?php
/**
 * Copyright (c) 2014 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OC_JSON::checkAdminUser();

$l = OC_L10N::get('settings');

$category = intval($_GET['category']);
$apps = array();

switch($category) {
	// installed apps
	case 0:
		$apps = \OC_App::listAllApps(true);
		$apps = array_filter($apps, function($app) {
			return $app['active'];
		});
		break;
	// not-installed apps
	case 1:
		$apps = \OC_App::listAllApps(true);
		$apps = array_filter($apps, function($app) {
			return !$app['active'];
		});
		break;
	default:
		if ($category === 2) {
			$apps = \OC_App::getAppstoreApps('approved');
			$apps = array_filter($apps, function($app) {
				return isset($app['internalclass']) && $app['internalclass'] === 'recommendedapp';
			});
		} else {
			$apps = \OC_App::getAppstoreApps('approved', $category);
		}
		if (!$apps) {
			$apps = array();
		}
		usort($apps, function ($a, $b) {
			$a = (int)$a['score'];
			$b = (int)$b['score'];
			if ($a === $b) {
				return 0;
			}
			return ($a > $b) ? -1 : 1;
		});
		break;
}

// fix groups to be an array
$apps = array_map(function($app){
	$groups = array();
	if (is_string($app['groups'])) {
		$groups = json_decode($app['groups']);
	}
	$app['groups'] = $groups;
	$app['canUnInstall'] = !$app['active'] && $app['removable'];
	return $app;
}, $apps);

OCP\JSON::success(array("apps" => $apps));
