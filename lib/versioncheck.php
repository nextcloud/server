<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
// Show warning if a PHP version below 8.2 is used,
if (PHP_VERSION_ID < 80200) {
	http_response_code(500);
	echo 'This version of Nextcloud requires at least PHP 8.2<br/>';
	echo 'You are currently running ' . PHP_VERSION . '. Please update your PHP version.';
	exit(1);
}

// Show warning if >= PHP 8.6 is used as Nextcloud is not compatible with >= PHP 8.6 for now
if (PHP_VERSION_ID >= 80600) {
	http_response_code(500);
	echo 'This version of Nextcloud is not compatible with PHP>=8.6.<br/>';
	echo 'You are currently running ' . PHP_VERSION . '.';
	exit(1);
}
