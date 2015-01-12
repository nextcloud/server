<?php

use OCA\Files_Sharing\Migration;

$installedVersion = \OC::$server->getConfig()->getAppValue('files_sharing', 'installed_version');

// Migration OC7 -> OC8
if (version_compare($installedVersion, '0.6.0', '<')) {
	$m = new Migration();
	$m->addAcceptRow();
}

