<?php

// touch shared directories to trigger one-time re-scan for all users
$datadir = \OCP\Config::getSystemValue('datadirectory');
$currentVersion=OC_Appconfig::getValue('files_sharing', 'installed_version');
if (version_compare($currentVersion, '0.2.2', '<')) {
	if ($handle = opendir($datadir)) {
		while (false !== ($entry = readdir($handle))) {
			$sharedFolder = $datadir.'/'.$entry.'/files/Shared';
			if ($entry != "." && $entry != ".." && is_dir($sharedFolder)) {
				touch($sharedFolder);
			}
		}
		closedir($handle);
	}
}