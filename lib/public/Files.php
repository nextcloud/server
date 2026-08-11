<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP;

/**
 * Legacy helper for direct, local/host filesystem operations (not Nextcloud's
 * virtual filesystem). For VFS access, use
 * \OCP\Files\IRootFolder and \OCP\Files\Folder / \OCP\Files\Node instead.
 *
 * @since 5.0.0
 * @deprecated 14.0.0 No direct replacement for rmdirr(); apps needing this
 *			   should implement their own recursive delete, use a well-tested
 *			   library, or open a GitHub Issue (enhancement request).
 */
class Files {

	/**
	 * Recursively delete a local filesystem file or directory.
	 *
	 * This operates on paths understood by PHP's native filesystem
	 * functions; it does not operate on Nextcloud VFS paths.
	 *
	 * Symbolic links are deleted as links and are not traversed.
	 *
	 * @param string $dir Local filesystem path to delete
	 * @param bool $deleteSelf Whether to delete the supplied path itself.
	 *                          If false, only its contents are deleted.
	 * @return bool True when the requested path no longer exists, or when
	 *              only its contents were requested to be deleted.
	 *
	 * @since 5.0.0
	 * @since 32.0.0 Added the $deleteSelf parameter
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
