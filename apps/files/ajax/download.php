<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// Check if we are a user
OC_Util::checkLoggedIn();
\OC::$server->getSession()->close();

$files = isset($_GET['files']) ? (string)$_GET['files'] : '';
$dir = isset($_GET['dir']) ? (string)$_GET['dir'] : '';

$files_list = json_decode($files);
// in case we get only a single file
if (!is_array($files_list)) {
	$files_list = [$files];
}

/**
 * @psalm-taint-escape cookie
 */
function cleanCookieInput(string $value): string {
	if (strlen($value) > 32) {
		return '';
	}
	if (preg_match('!^[a-zA-Z0-9]+$!', $_GET['downloadStartSecret']) !== 1) {
		return '';
	}
	return $value;
}

/**
 * this sets a cookie to be able to recognize the start of the download
 * the content must not be longer than 32 characters and must only contain
 * alphanumeric characters
 */
if (isset($_GET['downloadStartSecret'])) {
	$value = cleanCookieInput($_GET['downloadStartSecret']);
	if ($value !== '') {
		setcookie('ocDownloadStarted', $value, time() + 20, '/');
	}
}

$server_params = [ 'head' => \OC::$server->getRequest()->getMethod() === 'HEAD' ];

/**
 * Http range requests support
 */
if (isset($_SERVER['HTTP_RANGE'])) {
	$server_params['range'] = \OC::$server->getRequest()->getHeader('Range');
}

OC_Files::get($dir, $files_list, $server_params);
