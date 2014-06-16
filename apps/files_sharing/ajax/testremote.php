<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OCP\JSON::checkAppEnabled('files_sharing');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$remote = $_GET['remote'];

if (file_get_contents('https://' . $remote . '/status.php')) {
	echo 'https';
} elseif (file_get_contents('http://' . $remote . '/status.php')) {
	echo 'http';
} else {
	echo 'false';
}
