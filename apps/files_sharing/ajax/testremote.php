<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
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

OCP\JSON::callCheck();
OCP\JSON::checkAppEnabled('files_sharing');

$remote = $_GET['remote'];

function testUrl($url) {
	try {
		$result = file_get_contents($url);
		$data = json_decode($result);
		// public link mount is only supported in ownCloud 7+
		return is_object($data) and !empty($data->version) and version_compare($data->version, '7.0.0', '>=');
	} catch (Exception $e) {
		return false;
	}
}

if (testUrl('https://' . $remote . '/status.php')) {
	echo 'https';
} elseif (testUrl('http://' . $remote . '/status.php')) {
	echo 'http';
} else {
	echo 'false';
}
