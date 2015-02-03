<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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
