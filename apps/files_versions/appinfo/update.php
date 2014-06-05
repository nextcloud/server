<?php

$installedVersion=OCP\Config::getAppValue('files_versions', 'installed_version');
// move versions to new directory
if (version_compare($installedVersion, '1.0.4', '<')) {
	\OC_DB::dropTable("files_versions");
}
