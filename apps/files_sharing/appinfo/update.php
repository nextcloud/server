<?php

$installedVersion = OCP\Config::getAppValue('files_sharing', 'installed_version');
if (version_compare($installedVersion, '0.4', '<')) {
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
		if ($row['share_type'] === "0" && ($row['item_type'] === 'file' || $row['item_type'] === 'folder')) {
			$users[] = $row['share_with'];
			$shares[$row['id']] = $row['file_target'];
		} else if ($row['share_type'] === "1" && ($row['item_type'] === 'file' || $row['item_type'] === 'folder')) {
			//collect all group shares
			$users = array_merge($users, \OC_group::usersInGroup($row['share_with']));
			$shares[$row['id']] = $row['file_target'];
		} else if ($row['share_type'] === "2") {
			$shares[$row['id']] = $row['file_target'];
		}
	}

	$unique_users = array_unique($users);

	if (!empty($unique_users) && !empty($shares)) {

		// create folder Shared for each user

		foreach ($unique_users as $user) {
			\OC\Files\Filesystem::initMountPoints($user);
			if (!$view->file_exists('/' . $user . '/files/Shared')) {
				$view->mkdir('/' . $user . '/files/Shared');
			}
		}

		$statement = "UPDATE `*PREFIX*share` SET `file_target` = CASE id ";
		//update share table
		$ids = implode(',', array_keys($shares));
		foreach ($shares as $id => $target) {
			$statement .= "WHEN " . $id . " THEN '/Shared" . $target . "' ";
		}
		$statement .= ' END WHERE `id` IN (' . $ids . ')';

		$query = OCP\DB::prepare($statement);

		$query->execute(array());

	}

}

// clean up oc_share table from files which are no longer exists
if (version_compare($installedVersion, '0.3.5.6', '<')) {
	\OC\Files\Cache\Shared_Updater::fixBrokenSharesOnAppUpdate();
}
