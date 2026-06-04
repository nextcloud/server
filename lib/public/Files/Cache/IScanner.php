<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\Cache;

/**
 * Scan files from the storage and save to the cache
 *
 * @since 9.0.0
 */
interface IScanner {
	/**
	 * @since 9.0.0
	 */
	public const SCAN_RECURSIVE_INCOMPLETE = 2; // only recursive into not fully scanned folders

	/**
	 * @since 9.0.0
	 */
	public const SCAN_RECURSIVE = true;

	/**
	 * @since 9.0.0
	 */
	public const SCAN_SHALLOW = false;

	/**
	 * @since 12.0.0
	 */
	public const REUSE_NONE = 0;

	/**
	 * @since 9.0.0
	 */
	public const REUSE_ETAG = 1;

	/**
	 * @since 9.0.0
	 */
	public const REUSE_SIZE = 2;

	/**
	 * scan a single file and store it in the cache
	 *
	 * @param string $file
	 * @param int $reuseExisting
	 * @param int $parentId
	 * @param array | null $cacheData existing data in the cache for the file to be scanned
	 * @param bool $lock set to false to disable getting an additional read lock during scanning
	 * @return array | null an array of metadata of the scanned file
	 * @throws \OC\ServerNotAvailableException
	 * @throws \OCP\Lock\LockedException
	 * @since 9.0.0
	 */
	public function scanFile($file, $reuseExisting = 0, $parentId = -1, $cacheData = null, $lock = true);

	/**
	 * scan a folder and all its children
	 *
	 * @param string $path
	 * @param bool $recursive
	 * @param int $reuse
	 * @param bool $lock set to false to disable getting an additional read lock during scanning
	 * @return array | null an array of the meta data of the scanned file or folder
	 * @since 9.0.0
	 */
	public function scan($path, $recursive = self::SCAN_RECURSIVE, $reuse = -1, $lock = true);

	/**
	 * check if the file should be ignored when scanning
	 * NOTE: files with a '.part' extension are ignored as well!
	 *       prevents unfinished put requests to be scanned
	 *
	 * @param string $file
	 * @return boolean
	 * @since 9.0.0
	 */
	public static function isPartialFile($file);

	/**
	 * walk over any folders that are not fully scanned yet and scan them
	 *
	 * @since 9.0.0
	 */
	public function backgroundScan();
}
