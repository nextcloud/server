<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Sam Tuke <mail@samtuke.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
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
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
OCP\JSON::checkAppEnabled('files_versions');

$source = (string)$_GET['source'];
$start = (int)$_GET['start'];
list ($uid, $filename) = OCA\Files_Versions\Storage::getUidAndFilename($source);
$count = 5; //show the newest revisions
$versions = OCA\Files_Versions\Storage::getVersions($uid, $filename, $source);
if( $versions ) {

	$endReached = false;
	if (count($versions) <= $start+$count) {
		$endReached = true;
	}

	$versions = array_slice($versions, $start, $count);

	// remove owner path from request to not disclose it to the recipient
	foreach ($versions as $version) {
		unset($version['path']);
	}

	\OCP\JSON::success(array('data' => array('versions' => $versions, 'endReached' => $endReached)));

} else {

	\OCP\JSON::success(array('data' => array('versions' => [], 'endReached' => true)));

}
