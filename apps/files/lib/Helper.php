<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files;

use OCP\Files\FileInfo;

/**
 * Helper class for manipulating file information
 */
class Helper {
	/**
	 * Formats the file info to be returned as JSON to the client.
	 *
	 * @param FileInfo $i
	 * @return array formatted file info
	 */
	public static function formatFileInfo(FileInfo $i) {
		$entry = [];

		$entry['id'] = $i->getId();
		$entry['parentId'] = $i->getParentId();
		$entry['mtime'] = $i->getMtime() * 1000;
		// only pick out the needed attributes
		$entry['name'] = $i->getName();
		$entry['permissions'] = $i->getPermissions();
		$entry['mimetype'] = $i->getMimetype();
		$entry['size'] = $i->getSize();
		$entry['type'] = $i->getType();
		$entry['etag'] = $i->getEtag();
		// TODO: this is using the private implementation of FileInfo
		// the array access is not part of the public interface
		if (isset($i['tags'])) {
			$entry['tags'] = $i['tags'];
		}
		if (isset($i['displayname_owner'])) {
			$entry['shareOwner'] = $i['displayname_owner'];
		}
		if (isset($i['is_share_mount_point'])) {
			$entry['isShareMountPoint'] = $i['is_share_mount_point'];
		}
		if (isset($i['extraData'])) {
			$entry['extraData'] = $i['extraData'];
		}

		$mountType = null;
		$mount = $i->getMountPoint();
		$mountType = $mount->getMountType();
		if ($mountType !== '') {
			if ($i->getInternalPath() === '') {
				$mountType .= '-root';
			}
			$entry['mountType'] = $mountType;
		}
		return $entry;
	}
}
