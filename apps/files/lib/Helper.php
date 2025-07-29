<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files;

use OC\Files\Filesystem;
use OCP\Files\FileInfo;
use OCP\Util;

/**
 * Helper class for manipulating file information
 */
class Helper {
	/**
	 * Comparator function to sort files alphabetically and have
	 * the directories appear first
	 *
	 * @param FileInfo $a file
	 * @param FileInfo $b file
	 * @return int -1 if $a must come before $b, 1 otherwise
	 */
	public static function compareFileNames(FileInfo $a, FileInfo $b) {
		$aType = $a->getType();
		$bType = $b->getType();
		if ($aType === 'dir' and $bType !== 'dir') {
			return -1;
		} elseif ($aType !== 'dir' and $bType === 'dir') {
			return 1;
		} else {
			return Util::naturalSortCompare($a->getName(), $b->getName());
		}
	}

	/**
	 * Comparator function to sort files by date
	 *
	 * @param FileInfo $a file
	 * @param FileInfo $b file
	 * @return int -1 if $a must come before $b, 1 otherwise
	 */
	public static function compareTimestamp(FileInfo $a, FileInfo $b) {
		$aTime = $a->getMTime();
		$bTime = $b->getMTime();
		return ($aTime < $bTime) ? -1 : 1;
	}

	/**
	 * Comparator function to sort files by size
	 *
	 * @param FileInfo $a file
	 * @param FileInfo $b file
	 * @return int -1 if $a must come before $b, 1 otherwise
	 */
	public static function compareSize(FileInfo $a, FileInfo $b) {
		$aSize = $a->getSize();
		$bSize = $b->getSize();
		return ($aSize < $bSize) ? -1 : 1;
	}

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

	/**
	 * Retrieves the contents of the given directory and
	 * returns it as a sorted array of FileInfo.
	 *
	 * @param string $dir path to the directory
	 * @param string $sortAttribute attribute to sort on
	 * @param bool $sortDescending true for descending sort, false otherwise
	 * @param string $mimetypeFilter limit returned content to this mimetype or mimepart
	 * @return FileInfo[] files
	 */
	public static function getFiles($dir, $sortAttribute = 'name', $sortDescending = false, $mimetypeFilter = '') {
		$content = Filesystem::getDirectoryContent($dir, $mimetypeFilter);

		return self::sortFiles($content, $sortAttribute, $sortDescending);
	}

	/**
	 * Sort the given file info array
	 *
	 * @param FileInfo[] $files files to sort
	 * @param string $sortAttribute attribute to sort on
	 * @param bool $sortDescending true for descending sort, false otherwise
	 * @return FileInfo[] sorted files
	 */
	public static function sortFiles($files, $sortAttribute = 'name', $sortDescending = false) {
		$sortFunc = 'compareFileNames';
		if ($sortAttribute === 'mtime') {
			$sortFunc = 'compareTimestamp';
		} elseif ($sortAttribute === 'size') {
			$sortFunc = 'compareSize';
		}
		usort($files, [Helper::class, $sortFunc]);
		if ($sortDescending) {
			$files = array_reverse($files);
		}
		return $files;
	}
}
