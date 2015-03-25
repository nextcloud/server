<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Florin Peter <github@florin-peter.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

/**
 * Versions
 *
 * A class to handle the versioning of files.
 */

namespace OCA\Files_Versions;

use OCA\Files_Versions\Command\Expire;

class Storage {

	const DEFAULTENABLED = true;
	const DEFAULTMAXSIZE = 50; // unit: percentage; 50% of available disk space/quota
	const VERSIONS_ROOT = 'files_versions/';

	// files for which we can remove the versions after the delete operation was successful
	private static $deletedFiles = array();

	private static $sourcePathAndUser = array();

	private static $max_versions_per_interval = array(
		//first 10sec, one version every 2sec
		1 => array('intervalEndsAfter' => 10, 'step' => 2),
		//next minute, one version every 10sec
		2 => array('intervalEndsAfter' => 60, 'step' => 10),
		//next hour, one version every minute
		3 => array('intervalEndsAfter' => 3600, 'step' => 60),
		//next 24h, one version every hour
		4 => array('intervalEndsAfter' => 86400, 'step' => 3600),
		//next 30days, one version per day
		5 => array('intervalEndsAfter' => 2592000, 'step' => 86400),
		//until the end one version per week
		6 => array('intervalEndsAfter' => -1, 'step' => 604800),
	);

	public static function getUidAndFilename($filename) {
		$uid = \OC\Files\Filesystem::getOwner($filename);
		\OC\Files\Filesystem::initMountPoints($uid);
		if ($uid != \OCP\User::getUser()) {
			$info = \OC\Files\Filesystem::getFileInfo($filename);
			$ownerView = new \OC\Files\View('/' . $uid . '/files');
			$filename = $ownerView->getPath($info->getId());
		}
		return array($uid, $filename);
	}

	/**
	 * Remember the owner and the owner path of the source file
	 *
	 * @param string $source source path
	 */
	public static function setSourcePathAndUser($source) {
		list($uid, $path) = self::getUidAndFilename($source);
		self::$sourcePathAndUser[$source] = array('uid' => $uid, 'path' => $path);
	}

	/**
	 * Gets the owner and the owner path from the source path
	 *
	 * @param string $source source path
	 * @return array with user id and path
	 */
	public static function getSourcePathAndUser($source) {

		if (isset(self::$sourcePathAndUser[$source])) {
			$uid = self::$sourcePathAndUser[$source]['uid'];
			$path = self::$sourcePathAndUser[$source]['path'];
			unset(self::$sourcePathAndUser[$source]);
		} else {
			$uid = $path = false;
		}
		return array($uid, $path);
	}

}
