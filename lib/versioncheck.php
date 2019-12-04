<?php

// Show warning if a PHP version below 7.2 is used,
if (PHP_VERSION_ID < 70200) {
	http_response_code(500);
	echo 'This version of Nextcloud requires at least PHP 7.2<br/>';
	echo 'You are currently running ' . PHP_VERSION . '. Please update your PHP version.';
	exit(-1);
}

// Show warning if > PHP 7.4 is used as Nextcloud is not compatible with > PHP 7.4 for now
if (PHP_VERSION_ID >= 70500) {
	http_response_code(500);
	echo 'This version of Nextcloud is not compatible with > PHP 7.4.<br/>';
	echo 'You are currently running ' . PHP_VERSION . '.';
	exit(-1);
}
