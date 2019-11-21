<?php

// Show warning if a PHP version below 7.2 is used,
if (version_compare(PHP_VERSION, '7.2') === -1) {
	http_response_code(500);
	echo 'This version of Nextcloud requires at least PHP 7.2<br/>';
	echo 'You are currently running ' . PHP_VERSION . '. Please update your PHP version.';
	exit(-1);
}

// Show warning if > PHP 7.4 is used as Nextcloud is not compatible with > PHP 7.4 for now
if (version_compare(PHP_VERSION, '7.5.0') !== -1) {
	http_response_code(500);
	echo 'This version of Nextcloud is not compatible with > PHP 7.4.<br/>';
	echo 'You are currently running ' . PHP_VERSION . '.';
	exit(-1);
}
