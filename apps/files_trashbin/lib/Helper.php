<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Trashbin;

use OC\Files\FileInfo;
use OC\Files\View;
use OCP\Constants;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\Mount\IMountPoint;
use OCP\Server;

class Helper {
	/**
	 * Retrieves the contents of a trash bin directory.
	 *
	 * @param string $dir path to the directory inside the trashbin
	 *                    or empty to retrieve the root of the trashbin
	 * @param string $user
	 * @param string $sortAttribute attribute to sort on or empty to disable sorting
	 * @param bool $sortDescending true for descending sort, false otherwise
	 * @return \OCP\Files\FileInfo[]
	 */
	public static function getTrashFiles($dir, $user, $sortAttribute = '', $sortDescending = false) {
		$view = new View('/' . $user . '/files_trashbin/files');

		if (ltrim($dir, '/') !== '' && !$view->is_dir($dir)) {
			throw new \Exception('Directory does not exists');
		}

		$mount = $view->getMount($dir);
		$storage = $mount->getStorage();
		$absoluteDir = $view->getAbsolutePath($dir);
		$internalPath = $mount->getInternalPath($absoluteDir);
		$extraData = Trashbin::getExtraData($user);

		$result = [];
		$timestamp = null;
		$dirContent = $storage->getCache()->getFolderContents($mount->getInternalPath($view->getAbsolutePath($dir)));
		foreach ($dirContent as $entry) {
			$result[] = self::buildFileInfo($entry, $dir, $timestamp, $extraData, $storage, $absoluteDir, $internalPath, $mount);
		}

		if ($sortAttribute !== '') {
			return \OCA\Files\Helper::sortFiles($result, $sortAttribute, $sortDescending);
		}
		return $result;
	}

	public static function getTrashFile(string $dir, string $user, string $name): ?FileInfo {
		$timestamp = null;

		$view = new View('/' . $user . '/files_trashbin/files');

		if (ltrim($dir, '/') !== '' && !$view->is_dir($dir)) {
			throw new \Exception('Directory does not exists');
		}

		$mount = $view->getMount($dir);
		$storage = $mount->getStorage();
		$absoluteDir = $view->getAbsolutePath($dir);
		$internalPath = $mount->getInternalPath($absoluteDir);

		$extraData = Trashbin::getExtraData($user);
		$entry = $storage->getCache()->get($mount->getInternalPath($view->getAbsolutePath($dir . '/' . $name)));
		if ($entry === false) {
			return null;
		}

		$timestamp = null;
		return self::buildFileInfo($entry, $dir, $timestamp, $extraData, $storage, $absoluteDir, $internalPath, $mount);
	}

	private static function buildFileInfo(ICacheEntry $entry, string $dir, ?string &$timestamp, array $extraData, $storage, string $absoluteDir, string $internalPath, IMountPoint $mount): FileInfo {
		$entryName = $entry->getName();
		$name = $entryName;
		if ($dir === '' || $dir === '/') {
			$pathparts = pathinfo($entryName);
			$timestamp = substr($pathparts['extension'], 1);
			$name = $pathparts['filename'];
		} elseif ($timestamp === null) {
			// for subfolders we need to calculate the timestamp only once
			$parts = explode('/', ltrim($dir, '/'));
			$timestamp = substr(pathinfo($parts[0], PATHINFO_EXTENSION), 1);
		}
		$originalPath = '';
		$originalName = substr($entryName, 0, -strlen($timestamp) - 2);
		if (isset($extraData[$originalName][$timestamp]['location'])) {
			$originalPath = $extraData[$originalName][$timestamp]['location'];
			if (substr($originalPath, -1) === '/') {
				$originalPath = substr($originalPath, 0, -1);
			}
		}
		$type = $entry->getMimeType() === ICacheEntry::DIRECTORY_MIMETYPE ? 'dir' : 'file';
		$i = [
			'name' => $name,
			'mtime' => $timestamp,
			'mimetype' => $type === 'dir' ? 'httpd/unix-directory' : Server::get(IMimeTypeDetector::class)->detectPath($name),
			'type' => $type,
			'directory' => ($dir === '/') ? '' : $dir,
			'size' => $entry->getSize(),
			'etag' => '',
			'permissions' => Constants::PERMISSION_ALL - Constants::PERMISSION_SHARE,
			'fileid' => $entry->getId(),
		];
		if ($originalPath) {
			if ($originalPath !== '.') {
				$i['extraData'] = $originalPath . '/' . $originalName;
			} else {
				$i['extraData'] = $originalName;
			}
		}
		$i['deletedBy'] = $extraData[$originalName][$timestamp]['deletedBy'] ?? null;
		return new FileInfo($absoluteDir . '/' . $i['name'], $storage, $internalPath . '/' . $i['name'], $i, $mount);
	}
}
