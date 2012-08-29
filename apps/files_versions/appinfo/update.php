<?php

$installedVersion=OCP\Config::getAppValue('files_versions', 'installed_version');
// move versions to new directory
if (version_compare($installedVersion, '1.0.2', '<')) {
	$users = \OCP\User::getUsers();
	$datadir =  \OCP\Config::getSystemValue('datadirectory').'/';
	foreach ($users as $user) {
		$oldPath = $datadir.$user.'/versions';
		$newPath = $datadir.$user.'/files_versions';
		if(is_dir($oldPath)) {
			rename($oldPath, $newPath);
		}
	}
}
