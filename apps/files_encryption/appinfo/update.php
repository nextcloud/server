<?php

$installedVersion=OCP\Config::getAppValue('files_encryption', 'installed_version');
// migrate settings from oc_encryption to oc_preferences
if (version_compare($installedVersion, '0.6', '<')) {
	$sql = 'SELECT * FROM `*PREFIX*encryption`';
	$query = \OCP\DB::prepare($sql);
	$result = $query->execute(array())->fetchAll();

	foreach ($result as $row) {
			\OC_Preferences::setValue($row['uid'], 'files_encryption', 'recovery_enabled', $row['recovery_enabled']);
			\OC_Preferences::setValue($row['uid'], 'files_encryption', 'migration_status', $row['migration_status']);
	}

	$deleteOldTable = 'DROP TABLE `*PREFIX*encryption`';
	$query = \OCP\DB::prepare($deleteOldTable);
	$query->execute(array());

}
