<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Init owncloud
require_once '../../../lib/base.php';

OC_JSON::checkAdminUser();

$l = OC_L10N::get('settings');

if(OC_Config::getValue('appstoreenabled', true)==false) {
	OCP\JSON::success(array('type' => 'external', 'data' => array()));
}

$enabledApps=OC_App::getEnabledApps();

if(is_null($enabledApps)) {
	OCP\JSON::error(array('data' => array('message' => $l->t('Unable to load list from App Store'))));
}

$apps=array();

// apps from external repo via OCS
$catagoryNames=OC_OCSClient::getCategories();
if(is_array($catagoryNames)) {
	$categories=array_keys($catagoryNames);
	$page=0;
	$filter='approved';
	$externalApps=OC_OCSClient::getApplications($categories, $page, $filter);
	foreach($externalApps as $app) {
		// show only external apps that aren't enabled yet
		$local=false;
		foreach($enabledApps as $a) {
			if($a == $app['name']) {
				$local=true;
			}
		}

		if(!$local) {
			if($app['preview']=='') {
				$pre='trans.png';
			} else {
				$pre=$app['preview'];
			}
			$apps[]=array(
				'name'=>$app['name'],
				'id'=>$app['id'],
				'active'=>false,
				'description'=>$app['description'],
				'author'=>$app['personid'],
				'license'=>$app['license'],
				'preview'=>$pre,
				'internal'=>false,
				'internallabel'=>'3rd Party App',
			);
		}
	}
}

OCP\JSON::success(array('type' => 'external', 'data' => $apps));
