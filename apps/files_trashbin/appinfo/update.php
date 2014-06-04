<?php

$installedVersion=OCP\Config::getAppValue('files_trashbin', 'installed_version');

if (version_compare($installedVersion, '0.6', '<')) {
	//size of the trash bin could be incorrect, remove it for all users to
	//enforce a recalculation during next usage.
	\OC_DB::dropTable('files_trashsize');
}
