<?php
/**
 * Copyright (c) 2014 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Share;

class Controller {
	public static function showShare($args) {
		\OC_Util::checkAppEnabled('files_sharing');

		$token = $args['token'];

		\OC_App::loadApp('files_sharing');
		\OC_User::setIncognitoMode(true);

		require_once \OC_App::getAppPath('files_sharing') .'/public.php';
	}
}
?>
