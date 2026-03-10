<?php

/**
 * SPDX-FileCopyrightText: 2016-2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage;

use OCP\ITempManager;
use OCP\Server;

/**
 * Helper methods for temporary local file handling for storage paths.
 *
 * Intended for use by storage implementations such as \OC\Files\Storage\Common.
 *
 * This trait caches per-path temporary local copies created from storage streams,
 * so repeated local-file access can reuse the same temp file during the instance
 * lifetime.
 */
trait LocalTempFileTrait {
	/** @var array<string,string|false> */
	protected array $cachedFiles = [];

	/**
	 * Returns the temporary local file path associated with the specified storage file.
	 *
	 * Creates a temp file on first use if necessary. Caches for repeated (instance-level) access.
	 *
	 * @param string $path Storage-internal path
	 * @return string|false Local temp file path, or false if the source cannot be opened/copied
	 */
	protected function getCachedFile(string $path): string|false {
		if (!isset($this->cachedFiles[$path])) {
			$this->cachedFiles[$path] = $this->toTmpFile($path);
		}
		return $this->cachedFiles[$path];
	}

	/**
	 * Invalidate the cached temp local file entry for the specified storage file.
	 *
	 * @param string $path Storage-internal path
	 */
	protected function removeCachedFile(string $path): void {
		unset($this->cachedFiles[$path]);
	}

	/**
	 * Copies a storage file stream into a temporary local file.
	 *
	 * The temporary local file keeps the same extension (if any) as the source file.
	 *
	 * @param string $path Storage-internal path
	 * @return string|false Local temp file path, or false on failure
	 */
	protected function toTmpFile(string $path): string|false {
		$source = $this->fopen($path, 'r');
		if (!$source) {
			return false;
		}

		$ext = pathinfo($path, PATHINFO_EXTENSION);
		$extension = $ext !== '' ? '.' . $ext : '';

		$tmpFile = Server::get(ITempManager::class)->getTemporaryFile($extension);
		$target = fopen($tmpFile, 'w');
		if ($target === false) {
			fclose($source);
			return false;
		}

		try {
			$result = stream_copy_to_stream($source, $target);
			if ($result === false) {
				return false;
			}
		} finally {
			fclose($target);
			fclose($source);
		}

		return $tmpFile;
	}
}
