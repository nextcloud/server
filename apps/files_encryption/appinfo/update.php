<?php

use OCA\Files_Encryption\Migration;

$installedVersion=OCP\Config::getAppValue('files_encryption', 'installed_version');

if (version_compare($installedVersion, '0.6', '<')) {
	$m = new Migration();
	$m->dropTableEncryption();
}
