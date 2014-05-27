<?php

$installedVersion = OCP\Config::getAppValue('files_sharing', 'installed_version');

if (version_compare($installedVersion, '0.5', '<')) {
	updateFilePermissions();
}

if (version_compare($installedVersion, '0.4', '<')) {
	removeSharedFolder();
}

// clean up oc_share table from files which are no longer exists
if (version_compare($installedVersion, '0.3.5.6', '<')) {
	\OC\Files\Cache\Shared_Updater::fixBrokenSharesOnAppUpdate();
}


/**
 * it is no longer possible to share single files with delete permissions. User
 * should only be able to unshare single files but never to delete them.
 */
function updateFilePermissions($chunkSize = 99) {
	$query = OCP\DB::prepare('SELECT * FROM `*PREFIX*share` WHERE item_type = ?');
	$result = $query->execute(array('file'));

	$updatedRows = array();

	while ($row = $result->fetchRow()) {
		if ($row['permissions'] & \OCP\PERMISSION_DELETE) {
			$updatedRows[$row['id']] = (int)$row['permissions'] & ~\OCP\PERMISSION_DELETE;
		}
	}

	$chunkedPermissionList = array_chunk($updatedRows, $chunkSize, true);

	foreach ($chunkedPermissionList as $subList) {
		$statement = "UPDATE `*PREFIX*share` SET `permissions` = CASE `id` ";
		//update share table
		$ids = implode(',', array_keys($subList));
		foreach ($subList as $id => $permission) {
			$statement .= "WHEN " . $id . " THEN " . $permission . " ";
		}
		$statement .= ' END WHERE `id` IN (' . $ids . ')';

		$query = OCP\DB::prepare($statement);
		$query->execute();
	}

}

/**
 * update script for the removal of the logical "Shared" folder, we create physical "Shared" folder and
 * update the users file_target so that it doesn't make any difference for the user
 * @note parameters are just for testing, please ignore them
 */
function removeSharedFolder($mkdirs = true, $chunkSize = 99) {
	$query = OCP\DB::prepare('SELECT * FROM `*PREFIX*share`');
	$result = $query->execute();
	$view = new \OC\Files\View('/');
	$users = array();
	$shares = array();
	//we need to set up user backends
	OC_User::useBackend(new OC_User_Database());
	OC_Group::useBackend(new OC_Group_Database());
	OC_App::loadApps(array('authentication'));
	//we need to set up user backends, otherwise creating the shares will fail with "because user does not exist"
	while ($row = $result->fetchRow()) {
		//collect all user shares
		if ((int)$row['share_type'] === 0 && ($row['item_type'] === 'file' || $row['item_type'] === 'folder')) {
			$users[] = $row['share_with'];
			$shares[$row['id']] = $row['file_target'];
		} else if ((int)$row['share_type'] === 1 && ($row['item_type'] === 'file' || $row['item_type'] === 'folder')) {
			//collect all group shares
			$users = array_merge($users, \OC_group::usersInGroup($row['share_with']));
			$shares[$row['id']] = $row['file_target'];
		} else if ((int)$row['share_type'] === 2) {
			$shares[$row['id']] = $row['file_target'];
		}
	}

	$unique_users = array_unique($users);

	if (!empty($unique_users) && !empty($shares)) {

		// create folder Shared for each user

		if ($mkdirs) {
			foreach ($unique_users as $user) {
				\OC\Files\Filesystem::initMountPoints($user);
				if (!$view->file_exists('/' . $user . '/files/Shared')) {
					$view->mkdir('/' . $user . '/files/Shared');
				}
			}
		}

		$chunkedShareList = array_chunk($shares, $chunkSize, true);

		foreach ($chunkedShareList as $subList) {

			$statement = "UPDATE `*PREFIX*share` SET `file_target` = CASE `id` ";
			//update share table
			$ids = implode(',', array_keys($subList));
			foreach ($subList as $id => $target) {
				$statement .= "WHEN " . $id . " THEN '/Shared" . $target . "' ";
			}
			$statement .= ' END WHERE `id` IN (' . $ids . ')';

			$query = OCP\DB::prepare($statement);

			$query->execute(array());
		}

	}
}
