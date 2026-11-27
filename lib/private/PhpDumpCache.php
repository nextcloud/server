<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2004 David Grudl (https://davidgrudl.com)
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace OC;

// TODO: cleanup? TTL?

class PhpDumpCache {

	public function __construct(
		private string $tempDirectory,
	) {
		$this->setTempDirectory($tempDirectory);
	}

	/**
	 * Sets path to temporary directory.
	 */
	public function setTempDirectory(string $dir): static {
		if (!is_dir($dir) && !@mkdir($dir, recursive: true) && !is_dir($dir)) { // @ - dir may already exist
			throw new \RuntimeException("Unable to create directory '$dir'");
		}
		$this->tempDirectory = $dir;
		return $this;
	}

	/**
	 * Loads class list from cache.
	 */
	public function loadCache(array $cacheKey): ?array {
		$file = $this->generateCacheFileName($cacheKey);

		$data = @include $file; // @ file may not exist
		if (is_array($data)) {
			return $data;
		}

		return null;
	}

	/**
	 * Writes class list to cache.
	 * @param ?resource $lock
	 */
	public function saveCache(array $cacheKey, array $data): void {
		// we have to acquire a lock to be able safely rename file
		// on Linux: that another thread does not rename the same named file earlier
		// on Windows: that the file is not read by another thread
		$file = $this->generateCacheFileName($cacheKey);
		$lock = $this->acquireLock("$file.lock", LOCK_EX);
		$code = "<?php\nreturn " . var_export($data, true) . ";\n";

		if (file_put_contents("$file.tmp", $code) !== strlen($code) || !rename("$file.tmp", $file)) {
			@unlink("$file.tmp"); // @ file may not exist
			throw new \RuntimeException(sprintf("Unable to create '%s'.", $file));
		}

		if (function_exists('opcache_invalidate')) {
			@opcache_invalidate($file, force: true); // @ can be restricted
		}
	}

	/** @return resource */
	private function acquireLock(string $file, int $mode) {
		$handle = @fopen($file, 'w'); // @ is escalated to exception
		if (!$handle) {
			throw new \RuntimeException(sprintf(
				"Unable to create file '%s'. %s",
				$file,
				\error_get_last()['message'] ?? ''
			));
		} elseif (!@flock($handle, $mode)) {
			// @ is escalated to exception
			throw new \RuntimeException(sprintf(
				"Unable to acquire %s lock on file '%s'. %s",
				$mode & LOCK_EX ? 'exclusive' : 'shared',
				$file,
				\error_get_last()['message'] ?? '',
			));
		}

		return $handle;
	}

	private function generateCacheFileName(array $cacheKey): string {
		return $this->tempDirectory . '/' . hash('xxh3', serialize($cacheKey)) . '.php';
	}
}
