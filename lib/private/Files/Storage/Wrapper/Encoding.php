<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage\Wrapper;

use OC\Files\Filesystem;
use OCP\Cache\CappedMemoryCache;
use OCP\Files\Cache\IScanner;
use OCP\Files\Storage\IStorage;
use OCP\ICache;

/**
 * Encoding wrapper that deals with file names that use unsupported encodings like NFD.
 *
 * When applied and a UTF-8 path name was given, the wrapper will first attempt to access
 * the actual given name and then try its NFD form.
 */
class Encoding extends Wrapper {
	/**
	 * @var ICache
	 */
	private $namesCache;

	/**
	 * @param array $parameters
	 */
	public function __construct($parameters) {
		$this->storage = $parameters['storage'];
		$this->namesCache = new CappedMemoryCache();
	}

	/**
	 * Returns whether the given string is only made of ASCII characters
	 *
	 * @param string $str string
	 */
	private function isAscii($str): bool {
		return !preg_match('/[\\x80-\\xff]+/', $str);
	}

	/**
	 * Checks whether the given path exists in NFC or NFD form after checking
	 * each form for each path section and returns the correct form.
	 * If no existing path found, returns the path as it was given.
	 *
	 * @param string $fullPath path to check
	 *
	 * @return string original or converted path
	 */
	private function findPathToUse($fullPath): string {
		$cachedPath = $this->namesCache[$fullPath];
		if ($cachedPath !== null) {
			return $cachedPath;
		}

		$sections = explode('/', $fullPath);
		$path = '';
		foreach ($sections as $section) {
			$convertedPath = $this->findPathToUseLastSection($path, $section);
			if ($convertedPath === null) {
				// no point in continuing if the section was not found, use original path
				return $fullPath;
			}
			$path = $convertedPath . '/';
		}
		$path = rtrim($path, '/');
		return $path;
	}

	/**
	 * Checks whether the last path section of the given path exists in NFC or NFD form
	 * and returns the correct form. If no existing path found, returns null.
	 *
	 * @param string $basePath base path to check
	 * @param string $lastSection last section of the path to check for NFD/NFC variations
	 *
	 * @return string|null original or converted path, or null if none of the forms was found
	 */
	private function findPathToUseLastSection($basePath, $lastSection): ?string {
		$fullPath = $basePath . $lastSection;
		if ($lastSection === '' || $this->isAscii($lastSection) || $this->storage->file_exists($fullPath)) {
			$this->namesCache[$fullPath] = $fullPath;
			return $fullPath;
		}

		// swap encoding
		if (\Normalizer::isNormalized($lastSection, \Normalizer::FORM_C)) {
			$otherFormPath = \Normalizer::normalize($lastSection, \Normalizer::FORM_D);
		} else {
			$otherFormPath = \Normalizer::normalize($lastSection, \Normalizer::FORM_C);
		}
		$otherFullPath = $basePath . $otherFormPath;
		if ($this->storage->file_exists($otherFullPath)) {
			$this->namesCache[$fullPath] = $otherFullPath;
			return $otherFullPath;
		}

		// return original path, file did not exist at all
		$this->namesCache[$fullPath] = $fullPath;
		return null;
	}

	public function mkdir($path): bool {
		// note: no conversion here, method should not be called with non-NFC names!
		$result = $this->storage->mkdir($path);
		if ($result) {
			$this->namesCache[$path] = $path;
		}
		return $result;
	}

	public function rmdir($path): bool {
		$result = $this->storage->rmdir($this->findPathToUse($path));
		if ($result) {
			unset($this->namesCache[$path]);
		}
		return $result;
	}

	public function opendir($path) {
		$handle = $this->storage->opendir($this->findPathToUse($path));
		return EncodingDirectoryWrapper::wrap($handle);
	}

	public function is_dir($path): bool {
		return $this->storage->is_dir($this->findPathToUse($path));
	}

	public function is_file($path): bool {
		return $this->storage->is_file($this->findPathToUse($path));
	}

	public function stat($path): array|false {
		return $this->storage->stat($this->findPathToUse($path));
	}

	public function filetype($path): string|false {
		return $this->storage->filetype($this->findPathToUse($path));
	}

	public function filesize($path): int|float|false {
		return $this->storage->filesize($this->findPathToUse($path));
	}

	public function isCreatable($path): bool {
		return $this->storage->isCreatable($this->findPathToUse($path));
	}

	public function isReadable($path): bool {
		return $this->storage->isReadable($this->findPathToUse($path));
	}

	public function isUpdatable($path): bool {
		return $this->storage->isUpdatable($this->findPathToUse($path));
	}

	public function isDeletable($path): bool {
		return $this->storage->isDeletable($this->findPathToUse($path));
	}

	public function isSharable($path): bool {
		return $this->storage->isSharable($this->findPathToUse($path));
	}

	public function getPermissions($path): int {
		return $this->storage->getPermissions($this->findPathToUse($path));
	}

	public function file_exists($path): bool {
		return $this->storage->file_exists($this->findPathToUse($path));
	}

	public function filemtime($path): int|false {
		return $this->storage->filemtime($this->findPathToUse($path));
	}

	public function file_get_contents($path): string|false {
		return $this->storage->file_get_contents($this->findPathToUse($path));
	}

	public function file_put_contents($path, $data): int|float|false {
		return $this->storage->file_put_contents($this->findPathToUse($path), $data);
	}

	public function unlink($path): bool {
		$result = $this->storage->unlink($this->findPathToUse($path));
		if ($result) {
			unset($this->namesCache[$path]);
		}
		return $result;
	}

	public function rename($source, $target): bool {
		// second name always NFC
		return $this->storage->rename($this->findPathToUse($source), $this->findPathToUse($target));
	}

	public function copy($source, $target): bool {
		return $this->storage->copy($this->findPathToUse($source), $this->findPathToUse($target));
	}

	public function fopen($path, $mode) {
		$result = $this->storage->fopen($this->findPathToUse($path), $mode);
		if ($result && $mode !== 'r' && $mode !== 'rb') {
			unset($this->namesCache[$path]);
		}
		return $result;
	}

	public function getMimeType($path): string|false {
		return $this->storage->getMimeType($this->findPathToUse($path));
	}

	public function hash($type, $path, $raw = false): string|false {
		return $this->storage->hash($type, $this->findPathToUse($path), $raw);
	}

	public function free_space($path): int|float|false {
		return $this->storage->free_space($this->findPathToUse($path));
	}

	public function touch($path, $mtime = null): bool {
		return $this->storage->touch($this->findPathToUse($path), $mtime);
	}

	public function getLocalFile($path): string|false {
		return $this->storage->getLocalFile($this->findPathToUse($path));
	}

	public function hasUpdated($path, $time): bool {
		return $this->storage->hasUpdated($this->findPathToUse($path), $time);
	}

	public function getCache($path = '', $storage = null): \OCP\Files\Cache\ICache {
		if (!$storage) {
			$storage = $this;
		}
		return $this->storage->getCache($this->findPathToUse($path), $storage);
	}

	public function getScanner($path = '', $storage = null): IScanner {
		if (!$storage) {
			$storage = $this;
		}
		return $this->storage->getScanner($this->findPathToUse($path), $storage);
	}

	public function getETag($path): string|false {
		return $this->storage->getETag($this->findPathToUse($path));
	}

	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath): bool {
		if ($sourceStorage === $this) {
			return $this->copy($sourceInternalPath, $this->findPathToUse($targetInternalPath));
		}

		$result = $this->storage->copyFromStorage($sourceStorage, $sourceInternalPath, $this->findPathToUse($targetInternalPath));
		if ($result) {
			unset($this->namesCache[$targetInternalPath]);
		}
		return $result;
	}

	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath): bool {
		if ($sourceStorage === $this) {
			$result = $this->rename($sourceInternalPath, $this->findPathToUse($targetInternalPath));
			if ($result) {
				unset($this->namesCache[$sourceInternalPath]);
				unset($this->namesCache[$targetInternalPath]);
			}
			return $result;
		}

		$result = $this->storage->moveFromStorage($sourceStorage, $sourceInternalPath, $this->findPathToUse($targetInternalPath));
		if ($result) {
			unset($this->namesCache[$sourceInternalPath]);
			unset($this->namesCache[$targetInternalPath]);
		}
		return $result;
	}

	public function getMetaData($path): ?array {
		$entry = $this->storage->getMetaData($this->findPathToUse($path));
		$entry['name'] = trim(Filesystem::normalizePath($entry['name']), '/');
		return $entry;
	}

	public function getDirectoryContent($directory): \Traversable {
		$entries = $this->storage->getDirectoryContent($this->findPathToUse($directory));
		foreach ($entries as $entry) {
			$entry['name'] = trim(Filesystem::normalizePath($entry['name']), '/');
			yield $entry;
		}
	}
}
