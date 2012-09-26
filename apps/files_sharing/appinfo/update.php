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
	while ($row = $result->fetchRow()) {
		$itemSource = OC_FileCache::getId($row['source'], '');
		if ($itemSource != -1) {
			$file = OC_FileCache::get($row['source'], '');
			if ($file['mimetype'] == 'httpd/unix-directory') {
				$itemType = 'folder';
			} else {
				$itemType = 'file';
			}
			if ($row['permissions'] == 0) {
				$permissions = OCP\Share::PERMISSION_READ | OCP\Share::PERMISSION_SHARE;
			} else {
				$permissions = OCP\Share::PERMISSION_READ | OCP\Share::PERMISSION_UPDATE | OCP\Share::PERMISSION_SHARE;
				if ($itemType == 'folder') {
					$permissions |= OCP\Share::PERMISSION_CREATE;
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
				echo 'Skipping sharing "'.$row['source'].'" to "'.$shareWith.'" (error is "'.$e->getMessage().'")<br/>';
			}
			OC_Util::tearDownFS();
		}
	}
	if ($update_error) {
		throw new Exception('There were some problems upgrading the sharing of files');
	}
	// NOTE: Let's drop the table after more testing
// 	$query = OCP\DB::prepare('DROP TABLE `*PREFIX*sharing`');
// 	$query->execute();
}
