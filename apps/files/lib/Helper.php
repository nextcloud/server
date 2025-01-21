<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files;

use OC\Files\Filesystem;
use OCP\Files\FileInfo;
use OCP\Files\NotFoundException;
use OCP\ITagManager;
use OCP\Util;

/**
 * Helper class for manipulating file information
 */
class Helper {
	/**
	 * @param string $dir
	 * @return array
	 * @throws NotFoundException
	 */
	public static function buildFileStorageStatistics($dir) {
		// information about storage capacities
		$storageInfo = \OC_Helper::getStorageInfo($dir);
		$l = Util::getL10N('files');
		$maxUploadFileSize = Util::maxUploadFilesize($dir, $storageInfo['free']);
		$maxHumanFileSize = Util::humanFileSize($maxUploadFileSize);
		$maxHumanFileSize = $l->t('Upload (max. %s)', [$maxHumanFileSize]);

		return [
			'uploadMaxFilesize' => $maxUploadFileSize,
			'maxHumanFilesize' => $maxHumanFileSize,
			'freeSpace' => $storageInfo['free'],
			'quota' => $storageInfo['quota'],
			'total' => $storageInfo['total'],
			'used' => $storageInfo['used'],
			'usedSpacePercent' => $storageInfo['relative'],
			'owner' => $storageInfo['owner'],
			'ownerDisplayName' => $storageInfo['ownerDisplayName'],
			'mountType' => $storageInfo['mountType'],
			'mountPoint' => $storageInfo['mountPoint'],
		];
	}

	/**
	 * Determine icon for a given file
	 *
	 * @param FileInfo $file file info
	 * @return string icon URL
	 */
	public static function determineIcon($file) {
		if ($file['type'] === 'dir') {
			$icon = \OC::$server->getMimeTypeDetector()->mimeTypeIcon('dir');
			// TODO: move this part to the client side, using mountType
			if ($file->isShared()) {
				$icon = \OC::$server->getMimeTypeDetector()->mimeTypeIcon('dir-shared');
			} elseif ($file->isMounted()) {
				$icon = \OC::$server->getMimeTypeDetector()->mimeTypeIcon('dir-external');
			}
		} else {
			$icon = \OC::$server->getMimeTypeDetector()->mimeTypeIcon($file->getMimetype());
		}

		return substr($icon, 0, -3) . 'svg';
	}

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
	 * Format file info for JSON
	 * @param FileInfo[] $fileInfos file infos
	 * @return array
	 */
	public static function formatFileInfos($fileInfos) {
		$files = [];
		foreach ($fileInfos as $i) {
			$files[] = self::formatFileInfo($i);
		}

		return $files;
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
	 * Populate the result set with file tags
	 *
	 * @psalm-template T of array{tags?: list<string>, file_source: int, ...array<string, mixed>}
	 * @param list<T> $fileList
	 * @return list<T> file list populated with tags
	 */
	public static function populateTags(array $fileList, ITagManager $tagManager) {
		$tagger = $tagManager->load('files');
		$tags = $tagger->getTagsForObjects(array_map(static fn (array $fileData) => $fileData['file_source'], $fileList));

		if (!is_array($tags)) {
			throw new \UnexpectedValueException('$tags must be an array');
		}

		// Set empty tag array
		foreach ($fileList as &$fileData) {
			$fileData['tags'] = [];
		}
		unset($fileData);

		if (!empty($tags)) {
			foreach ($tags as $fileId => $fileTags) {
				foreach ($fileList as &$fileData) {
					if ($fileId !== $fileData['file_source']) {
						continue;
					}

					$fileData['tags'] = $fileTags;
				}
				unset($fileData);
			}
		}

		return $fileList;
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
