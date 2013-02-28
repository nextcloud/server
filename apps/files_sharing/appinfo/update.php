<?php
$installedVersion = OCP\Config::getAppValue('files_sharing', 'installed_version');
if (version_compare($installedVersion, '0.3', '<')) {
	$update_error = false;
	$query = OCP\DB::prepare('SELECT * FROM `*PREFIX*sharing`');
	$result = $query->execute();
	$groupShares = array();
	//we need to set up user backends, otherwise creating the shares will fail with "because user does not exist"
	OC_User::useBackend(new OC_User_Database());
	OC_Group::useBackend(new OC_Group_Database());
	OC_App::loadApps(array('authentication'));
	$rootView = new \OC\Files\View('');
	while ($row = $result->fetchRow()) {
		$meta = $rootView->getFileInfo($$row['source']);
		$itemSource = $meta['fileid'];
		if ($itemSource != -1) {
			$file = $meta;
			if ($file['mimetype'] == 'httpd/unix-directory') {
				$itemType = 'folder';
			} else {
				$itemType = 'file';
			}
			if ($row['permissions'] == 0) {
				$permissions = OCP\PERMISSION_READ | OCP\PERMISSION_SHARE;
			} else {
				$permissions = OCP\PERMISSION_READ | OCP\PERMISSION_UPDATE | OCP\PERMISSION_SHARE;
				if ($itemType == 'folder') {
					$permissions |= OCP\PERMISSION_CREATE;
				}
			}
			$pos = strrpos($row['uid_shared_with'], '@');
			if ($pos !== false && OC_Group::groupExists(substr($row['uid_shared_with'], $pos + 1))) {
				$shareType = OCP\Share::SHARE_TYPE_GROUP;
				$shareWith = substr($row['uid_shared_with'], 0, $pos);
				if (isset($groupShares[$shareWith][$itemSource])) {
					continue;
				} else {
					$groupShares[$shareWith][$itemSource] = true;
				}
			} else if ($row['uid_shared_with'] == 'public') {
				$shareType = OCP\Share::SHARE_TYPE_LINK;
				$shareWith = null;
			} else {
				$shareType = OCP\Share::SHARE_TYPE_USER;
				$shareWith = $row['uid_shared_with'];
			}
			OC_User::setUserId($row['uid_owner']);
			//we need to setup the filesystem for the user, otherwise OC_FileSystem::getRoot will fail and break
			OC_Util::setupFS($row['uid_owner']);
			try {
				OCP\Share::shareItem($itemType, $itemSource, $shareType, $shareWith, $permissions);
			}
			catch (Exception $e) {
				$update_error = true;
				OCP\Util::writeLog('files_sharing',
					'Upgrade Routine: Skipping sharing "'.$row['source'].'" to "'.$shareWith
					.'" (error is "'.$e->getMessage().'")',
					OCP\Util::WARN);
			}
			OC_Util::tearDownFS();
		}
	}
	OC_User::setUserId(null);
	if ($update_error) {
		OCP\Util::writeLog('files_sharing', 'There were some problems upgrading the sharing of files', OCP\Util::ERROR);
	}
	// NOTE: Let's drop the table after more testing
// 	$query = OCP\DB::prepare('DROP TABLE `*PREFIX*sharing`');
// 	$query->execute();
}
if (version_compare($installedVersion, '0.3.3', '<')) {
	OC_User::useBackend(new OC_User_Database());
	OC_App::loadApps(array('authentication'));
	$users = OC_User::getUsers();
	foreach ($users as $user) {
//		OC_FileCache::delete('Shared', '/'.$user.'/files/');
	}
}
