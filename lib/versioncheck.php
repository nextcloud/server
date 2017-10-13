<?php

// Show warning if a PHP version below 5.6.0 is used, this has to happen here
// because base.php will already use 5.6 syntax.
if (version_compare(PHP_VERSION, '5.6.0', '<')) {
	http_response_code(500);
	echo 'This version of Nextcloud requires at least PHP 5.6.0<br/>';
	echo 'You are currently running ' . PHP_VERSION . '. Please update your PHP version.';
	exit(-1);
}

// Show warning if > PHP 7.1 is used as Nextcloud 12 is not compatible with > PHP 7.1
if (version_compare(PHP_VERSION, '7.2.0', '>=')) {
	http_response_code(500);
	echo 'This version of Nextcloud is not compatible with > PHP 7.2.<br/>';
	echo 'You are currently running ' . PHP_VERSION . '.';
	exit(-1);
}
