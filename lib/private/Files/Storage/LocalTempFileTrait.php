<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage;

/**
 * Storage backend class for providing common filesystem operation methods
 * which are not storage-backend specific.
 *
 * \OC\Files\Storage\Common is never used directly; it is extended by all other
 * storage backends, where its methods may be overridden, and additional
 * (backend-specific) methods are defined.
 *
 * Some \OC\Files\Storage\Common methods call functions which are first defined
 * in classes which extend it, e.g. $this->stat() .
 */
trait LocalTempFileTrait {
	/** @var array<string,string|false> */
	protected array $cachedFiles = [];

	protected function getCachedFile(string $path): string|false {
		if (!isset($this->cachedFiles[$path])) {
			$this->cachedFiles[$path] = $this->toTmpFile($path);
		}
		return $this->cachedFiles[$path];
	}

	protected function removeCachedFile(string $path): void {
		unset($this->cachedFiles[$path]);
	}

	protected function toTmpFile(string $path): string|false { //no longer in the storage api, still useful here
		$source = $this->fopen($path, 'r');
		if (!$source) {
			return false;
		}
		if ($pos = strrpos($path, '.')) {
			$extension = substr($path, $pos);
		} else {
			$extension = '';
		}
		$tmpFile = \OC::$server->getTempManager()->getTemporaryFile($extension);
		$target = fopen($tmpFile, 'w');
		\OC_Helper::streamCopy($source, $target);
		fclose($target);
		return $tmpFile;
	}
}
