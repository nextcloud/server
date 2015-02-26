<?php

use OCA\Files_Encryption\Migration;

$installedVersion=OCP\Config::getAppValue('files_encryption', 'installed_version');

// Migration OC7 -> OC8
if (version_compare($installedVersion, '0.7', '<')) {
	$m = new Migration();
	$m->reorganizeFolderStructure();
}
