<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Archive;

use Icewind\Streams\CallbackWrapper;
use OCP\ITempManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

class ZIP extends Archive {
	/**
	 * @var \ZipArchive zip
	 */
	private $zip;

	public function __construct(
		private string $path,
	) {
		$this->zip = new \ZipArchive();
		if ($this->zip->open($this->path, \ZipArchive::CREATE)) {
		} else {
			Server::get(LoggerInterface::class)->warning('Error while opening archive ' . $this->path, ['app' => 'files_archive']);
		}
	}

	/**
	 * add an empty folder to the archive
	 * @param string $path
	 * @return bool
	 */
	public function addFolder(string $path): bool {
		return $this->zip->addEmptyDir($path);
	}

	/**
	 * add a file to the archive
	 * @param string $source either a local file or string data
	 */
	public function addFile(string $path, string $source = ''): bool {
		if ($source && $source[0] === '/' && file_exists($source)) {
			$result = $this->zip->addFile($source, $path);
		} else {
			$result = $this->zip->addFromString($path, $source);
		}
		if ($result) {
			$this->zip->close();//close and reopen to save the zip
			$this->zip->open($this->path);
		}
		return $result;
	}

	/**
	 * rename a file or folder in the archive
	 */
	public function rename(string $source, string $dest): bool {
		$source = $this->stripPath($source);
		$dest = $this->stripPath($dest);
		return $this->zip->renameName($source, $dest);
	}

	/**
	 * get the uncompressed size of a file in the archive
	 */
	public function filesize(string $path): false|int|float {
		$stat = $this->zip->statName($path);
		return $stat['size'] ?? false;
	}

	/**
	 * get the last modified time of a file in the archive
	 * @return int|false
	 */
	public function mtime(string $path) {
		return filemtime($this->path);
	}

	/**
	 * get the files in a folder
	 */
	public function getFolder(string $path): array {
		// FIXME: multiple calls on getFolder would traverse
		// the whole file list over and over again
		// maybe use a Generator or cache the list ?
		$files = $this->getFiles();
		$folderContent = [];
		$pathLength = strlen($path);
		foreach ($files as $file) {
			if (substr($file, 0, $pathLength) == $path && $file != $path) {
				if (strrpos(substr($file, 0, -1), '/') <= $pathLength) {
					$folderContent[] = substr($file, $pathLength);
				}
			}
		}
		return $folderContent;
	}

	/**
	 * Generator that returns metadata of all files
	 *
	 * @return \Generator<array>
	 */
	public function getAllFilesStat() {
		$fileCount = $this->zip->numFiles;
		for ($i = 0;$i < $fileCount;$i++) {
			yield $this->zip->statIndex($i);
		}
	}

	/**
	 * Return stat information for the given path
	 *
	 * @param string path path to get stat information on
	 * @return ?array stat information or null if not found
	 */
	public function getStat(string $path): ?array {
		$stat = $this->zip->statName($path);
		if (!$stat) {
			return null;
		}
		return $stat;
	}

	/**
	 * get all files in the archive
	 */
	public function getFiles(): array {
		$fileCount = $this->zip->numFiles;
		$files = [];
		for ($i = 0;$i < $fileCount;$i++) {
			$files[] = $this->zip->getNameIndex($i);
		}
		return $files;
	}

	/**
	 * get the content of a file
	 * @return string|false
	 */
	public function getFile(string $path) {
		return $this->zip->getFromName($path);
	}

	/**
	 * extract a single file from the archive
	 */
	public function extractFile(string $path, string $dest): bool {
		$fp = $this->zip->getStream($path);
		if ($fp === false) {
			return false;
		}
		return file_put_contents($dest, $fp) !== false;
	}

	/**
	 * extract the archive
	 */
	public function extract(string $dest): bool {
		return $this->zip->extractTo($dest);
	}

	/**
	 * check if a file or folder exists in the archive
	 */
	public function fileExists(string $path): bool {
		return ($this->zip->locateName($path) !== false) || ($this->zip->locateName($path . '/') !== false);
	}

	/**
	 * remove a file or folder from the archive
	 */
	public function remove(string $path): bool {
		if ($this->fileExists($path . '/')) {
			return $this->zip->deleteName($path . '/');
		} else {
			return $this->zip->deleteName($path);
		}
	}

	/**
	 * get a file handler
	 * @return bool|resource
	 */
	public function getStream(string $path, string $mode) {
		if ($mode === 'r' || $mode === 'rb') {
			return $this->zip->getStream($path);
		} else {
			//since we can't directly get a writable stream,
			//make a temp copy of the file and put it back
			//in the archive when the stream is closed
			$lastPoint = strrpos($path, '.');
			if ($lastPoint !== false) {
				$ext = substr($path, $lastPoint);
			} else {
				$ext = '';
			}
			$tmpFile = Server::get(ITempManager::class)->getTemporaryFile($ext);
			if ($this->fileExists($path)) {
				$this->extractFile($path, $tmpFile);
			}
			$handle = fopen($tmpFile, $mode);
			return CallbackWrapper::wrap($handle, null, null, function () use ($path, $tmpFile): void {
				$this->writeBack($tmpFile, $path);
			});
		}
	}

	/**
	 * write back temporary files
	 */
	public function writeBack(string $tmpFile, string $path): void {
		$this->addFile($path, $tmpFile);
		unlink($tmpFile);
	}

	private function stripPath(string $path): string {
		if (!$path || $path[0] == '/') {
			return substr($path, 1);
		} else {
			return $path;
		}
	}
}
