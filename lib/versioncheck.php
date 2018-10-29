<?php

// Show warning if a PHP version below 7.0 is used, this has to happen here
// because base.php will already use 7.0 syntax.
if (version_compare(PHP_VERSION, '7.0') === -1) {
	http_response_code(500);
	echo 'This version of Nextcloud requires at least PHP 7.0<br/>';
	echo 'You are currently running ' . PHP_VERSION . '. Please update your PHP version.';
	exit(-1);
}

// Show warning if > PHP 7.3 is used as Nextcloud is not compatible with > PHP 7.3 for now
if (version_compare(PHP_VERSION, '7.4.0') !== -1) {
	http_response_code(500);
	echo 'This version of Nextcloud is not compatible with > PHP 7.3.<br/>';
	echo 'You are currently running ' . PHP_VERSION . '.';
	exit(-1);
}
