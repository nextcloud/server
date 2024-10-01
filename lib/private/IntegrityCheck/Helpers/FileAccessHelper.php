<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\IntegrityCheck\Helpers;

/**
 * Class FileAccessHelper provides a helper around file_get_contents and
 * file_put_contents
 *
 * @package OC\IntegrityCheck\Helpers
 */
class FileAccessHelper {
	/**
	 * Wrapper around file_get_contents($filename, $data)
	 *
	 * @param string $filename
	 * @return string|false
	 */
	public function file_get_contents(string $filename) {
		return file_get_contents($filename);
	}

	/**
	 * Wrapper around file_exists($filename)
	 *
	 * @param string $filename
	 * @return bool
	 */
	public function file_exists(string $filename): bool {
		return file_exists($filename);
	}

	/**
	 * Wrapper around file_put_contents($filename, $data)
	 *
	 * @param string $filename
	 * @param string $data
	 * @return int
	 * @throws \Exception
	 */
	public function file_put_contents(string $filename, string $data): int {
		$bytesWritten = @file_put_contents($filename, $data);
		if ($bytesWritten === false || $bytesWritten !== \strlen($data)) {
			throw new \Exception('Failed to write into ' . $filename);
		}
		return $bytesWritten;
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	public function is_writable(string $path): bool {
		return is_writable($path);
	}

	/**
	 * @param string $path
	 * @throws \Exception
	 */
	public function assertDirectoryExists(string $path) {
		if (!is_dir($path)) {
			throw new \Exception('Directory ' . $path . ' does not exist.');
		}
	}
}
