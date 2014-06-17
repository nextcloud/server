<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OCP\JSON::checkAppEnabled('files_sharing');

$remote = $_GET['remote'];

function testUrl($url) {
	try {
		$result = file_get_contents($url);
		$data = json_decode($result);
		return is_object($data) and !empty($data->version);
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
