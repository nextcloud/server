<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP;

/**
 * This class provides access to the internal filesystem abstraction layer. Use
 * this class exclusively if you want to access files
 * @since 5.0.0
 * @deprecated 14.0.0
 */
class Files {
	/**
	 * Recursive deletion of folders
	 *
	 * @param string $dir path to the folder
	 * @param bool $deleteSelf if set to false only the content of the folder will be deleted
	 * @return bool
	 * @since 5.0.0
	 * @since 32.0.0 added the $deleteSelf parameter
	 * @deprecated 14.0.0
	 */
	public static function rmdirr($dir, bool $deleteSelf = true) {
		if (is_dir($dir)) {
			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ($files as $fileInfo) {
				/** @var \SplFileInfo $fileInfo */
				if ($fileInfo->isLink()) {
					unlink($fileInfo->getPathname());
				} elseif ($fileInfo->isDir()) {
					rmdir($fileInfo->getRealPath());
				} else {
					unlink($fileInfo->getRealPath());
				}
			}
			if ($deleteSelf) {
				rmdir($dir);
			}
		} elseif (file_exists($dir)) {
			if ($deleteSelf) {
				unlink($dir);
			}
		}
		if (!$deleteSelf) {
			return true;
		}

		return !file_exists($dir);
	}
}
