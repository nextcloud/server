<?php

$installedVersion=OCP\Config::getAppValue('files_trashbin', 'installed_version');
if (version_compare($installedVersion, '0.5', '<=')) {
	$connection = OC_DB::getConnection();
	$platform = $connection->getDatabasePlatform();
	if ($platform->getName() === 'oracle') {
		try {
			$connection->beginTransaction();
			$sql1 = 'ALTER TABLE `*PREFIX*files_trash` ADD `auto_id` NUMBER(10) DEFAULT NULL';
			\OC_DB::executeAudited($sql1, array());
			$sql2 = 'CREATE SEQUENCE `*PREFIX*files_trash_seq` start with 1 increment by 1 nomaxvalue';
			\OC_DB::executeAudited($sql2, array());
			$sql3 = 'UPDATE `*PREFIX*files_trash` SET `auto_id` = `*PREFIX*files_trash_seq`.nextval';
			\OC_DB::executeAudited($sql3, array());
			$connection->commit();
		} catch (\DatabaseException $e) {
			\OCP\Util::writeLog('files_trashbin', "Oracle upgrade fixup failed: " . $e->getMessage(), \OCP\Util::WARN);
		}
	}
}
