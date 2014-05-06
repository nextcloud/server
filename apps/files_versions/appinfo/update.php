<?php

$installedVersion=OCP\Config::getAppValue('files_versions', 'installed_version');
// move versions to new directory
if (version_compare($installedVersion, '1.0.4', '<')) {
	$query = \OCP\DB::prepare("DROP TABLE `*PREFIX*files_versions`");
	$query->execute(array());
}
