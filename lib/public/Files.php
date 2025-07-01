<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP;

use OCP\Files\IMimeTypeDetector;

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

	/**
	 * Get the mimetype form a local file
	 * @param string $path
	 * @return string
	 *                does NOT work for ownClouds filesystem, use OC_FileSystem::getMimeType instead
	 * @since 5.0.0
	 * @deprecated 14.0.0
	 */
	public static function getMimeType($path) {
		return Server::get(IMimeTypeDetector::class)->detect($path);
	}

	/**
	 * Search for files by mimetype
	 * @param string $mimetype
	 * @return array
	 * @since 6.0.0
	 * @deprecated 14.0.0
	 */
	public static function searchByMime($mimetype) {
		return \OC\Files\Filesystem::searchByMime($mimetype);
	}

	/**
	 * Copy the contents of one stream to another
	 *
	 * @template T of null|true
	 * @param resource $source
	 * @param resource $target
	 * @param T $includeResult
	 * @return int|array
	 * @psalm-return (T is true ? array{0: int, 1: bool} : int)
	 * @since 5.0.0
	 * @since 32.0.0 added $includeResult parameter
	 * @deprecated 14.0.0
	 */
	public static function streamCopy($source, $target, ?bool $includeResult = null) {
		if (!$source or !$target) {
			return $includeResult ? [0, false] : 0;
		}

		$bufSize = 8192;
		$count = 0;
		$result = true;
		while (!feof($source)) {
			$buf = fread($source, $bufSize);
			if ($buf === false) {
				$result = false;
				break;
			}

			$bytesWritten = fwrite($target, $buf);
			if ($bytesWritten !== false) {
				$count += $bytesWritten;
			}

			if ($bytesWritten === false
				|| ($bytesWritten < $bufSize && $bytesWritten < strlen($buf))
			) {
				$result = false;
				break;
			}
		}
		return $includeResult ? [$count, $result] : $count;
	}

	/**
	 * Adds a suffix to the name in case the file exists
	 * @param string $path
	 * @param string $filename
	 * @return string
	 * @since 5.0.0
	 * @deprecated 14.0.0 use getNonExistingName of the OCP\Files\Folder object
	 */
	public static function buildNotExistingFileName($path, $filename) {
		return \OC_Helper::buildNotExistingFileName($path, $filename);
	}
}
