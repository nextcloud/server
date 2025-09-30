<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Cache;

use OCP\IConfig;
use OCP\Server;

class LocalRootScanner extends Scanner {
	private string $previewFolder;

	public function __construct(\OC\Files\Storage\Storage $storage) {
		parent::__construct($storage);
		$config = Server::get(IConfig::class);
		$this->previewFolder = 'appdata_' . $config->getSystemValueString('instanceid', '') . '/preview';
	}

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
		if (str_starts_with($path, $this->previewFolder)) {
			return false;
		}
		return $path === '' || str_starts_with($path, 'appdata_') || str_starts_with($path, '__groupfolders');
	}
}
