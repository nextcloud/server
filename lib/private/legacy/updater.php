<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * provides an interface to all search providers
 *
 * @deprecated use \OC\Updater instead
 */
class OC_Updater {
	public static function check() {
		$updater = new \OC\Updater();
		return $updater->check('http://apps.owncloud.com/updater.php');
	}
}
