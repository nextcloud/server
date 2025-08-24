<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
// Show warning if a PHP version below 8.1 is used,
if (PHP_VERSION_ID < 80100) {
	http_response_code(500);
	echo 'This version of Nextcloud requires at least PHP 8.1<br/>';
	echo 'You are currently running ' . PHP_VERSION . '. Please update your PHP version.';
	exit(1);
}

// Show warning if >= PHP 8.5 is used as Nextcloud is not compatible with >= PHP 8.5 for now
if (PHP_VERSION_ID >= 80500) {
	http_response_code(500);
	echo 'This version of Nextcloud is not compatible with PHP>=8.5.<br/>';
	echo 'You are currently running ' . PHP_VERSION . '.';
	exit(1);
}
