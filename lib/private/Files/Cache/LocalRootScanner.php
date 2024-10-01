<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Cache;

class LocalRootScanner extends Scanner {
	public function scanFile($file, $reuseExisting = 0, $parentId = -1, $cacheData = null, $lock = true, $data = null) {
		if ($this->shouldScanPath($file)) {
			return parent::scanFile($file, $reuseExisting, $parentId, $cacheData, $lock, $data);
		} else {
			return null;
		}
	}

	public function scan($path, $recursive = self::SCAN_RECURSIVE, $reuse = -1, $lock = true) {
		if ($this->shouldScanPath($path)) {
			return parent::scan($path, $recursive, $reuse, $lock);
		} else {
			return null;
		}
	}

	private function shouldScanPath(string $path): bool {
		$path = trim($path, '/');
		return $path === '' || str_starts_with($path, 'appdata_') || str_starts_with($path, '__groupfolders');
	}
}
