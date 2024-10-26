<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Archive;

abstract class Archive {
	abstract public function __construct(string $source);

	/**
	 * add an empty folder to the archive
	 */
	abstract public function addFolder(string $path): bool;

	/**
	 * add a file to the archive
	 * @param string $source either a local file or string data
	 */
	abstract public function addFile(string $path, string $source = ''): bool;

	/**
	 * rename a file or folder in the archive
	 */
	abstract public function rename(string $source, string $dest): bool;

	/**
	 * get the uncompressed size of a file in the archive
	 */
	abstract public function filesize(string $path): false|int|float;

	/**
	 * get the last modified time of a file in the archive
	 * @return int|false
	 */
	abstract public function mtime(string $path);

	/**
	 * get the files in a folder
	 * @param string $path
	 * @return array
	 */
	abstract public function getFolder(string $path): array;

	/**
	 * get all files in the archive
	 */
	abstract public function getFiles(): array;

	/**
	 * get the content of a file
	 * @return string|false
	 */
	abstract public function getFile(string $path);

	/**
	 * extract a single file from the archive
	 */
	abstract public function extractFile(string $path, string $dest): bool;

	/**
	 * extract the archive
	 */
	abstract public function extract(string $dest): bool;

	/**
	 * check if a file or folder exists in the archive
	 */
	abstract public function fileExists(string $path): bool;

	/**
	 * remove a file or folder from the archive
	 */
	abstract public function remove(string $path): bool;

	/**
	 * get a file handler
	 * @return bool|resource
	 */
	abstract public function getStream(string $path, string $mode);

	/**
	 * add a folder and all its content
	 */
	public function addRecursive(string $path, string $source): void {
		$dh = opendir($source);
		if (is_resource($dh)) {
			$this->addFolder($path);
			while (($file = readdir($dh)) !== false) {
				if ($file === '.' || $file === '..') {
					continue;
				}
				if (is_dir($source . '/' . $file)) {
					$this->addRecursive($path . '/' . $file, $source . '/' . $file);
				} else {
					$this->addFile($path . '/' . $file, $source . '/' . $file);
				}
			}
		}
	}
}
