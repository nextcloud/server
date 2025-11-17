<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Cache;

use OC\Files\Storage\Storage;
use OCP\IConfig;
use OCP\Server;
use Override;

class LocalRootScanner extends Scanner {
	private string $previewFolder;

	public function __construct(Storage $storage) {
		parent::__construct($storage);
		$config = Server::get(IConfig::class);
		$this->previewFolder = 'appdata_' . $config->getSystemValueString('instanceid', '') . '/preview';
	}

	#[Override]
	public function scanFile($file, $reuseExisting = 0, $parentId = -1, $cacheData = null, $lock = true, $data = null) {
		if ($this->shouldScanPath($file)) {
			return parent::scanFile($file, $reuseExisting, $parentId, $cacheData, $lock, $data);
		} else {
			return null;
		}
	}

	#[Override]
	public function scan($path, $recursive = self::SCAN_RECURSIVE, $reuse = -1, $lock = true) {
		if ($this->shouldScanPath($path)) {
			return parent::scan($path, $recursive, $reuse, $lock);
		} else {
			return null;
		}
	}

	#[Override]
	protected function scanChildren(string $path, $recursive, int $reuse, int $folderId, bool $lock, int|float $oldSize, &$etagChanged = false) {
		if (str_starts_with($path, $this->previewFolder)) {
			return 0;
		}
		return parent::scanChildren($path, $recursive, $reuse, $folderId, $lock, $oldSize, $etagChanged);
	}

	private function shouldScanPath(string $path): bool {
		$path = trim($path, '/');
		return $path === '' || str_starts_with($path, 'appdata_') || str_starts_with($path, '__groupfolders');
	}
}
