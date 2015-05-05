<?php
/**
 * Copyright (c) 2014 Jörn Dreyer <jfd@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\User;

class Controller {
	public static function getDisplayNames($args) {
		\OC_JSON::checkLoggedIn();
		\OC_JSON::callCheck();

		$users = $_GET['users'];
		$result = array();
		$userManager = \OC::$server->getUserManager();

		foreach ($users as $user) {
			$userObject = $userManager->get($user);
			if (is_object($userObject)) {
				$result[$user] = $userObject->getDisplayName();
			} else {
				$result[$user] = false;
			}
		}

		\OC_JSON::success(array('users'=>$result));
	}
}
