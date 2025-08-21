<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Storage;

use LogicException;
use OC;
use OC\Files\SimpleFS\SimpleFile;
use OC\Preview\Db\Preview;
use OCP\IConfig;

class LocalPreviewStorage implements IPreviewStorage {
	private const PREVIEW_DIRECTORY = '__preview';

	private readonly string $rootFolder;

	public function __construct(
		private readonly IConfig $config,
	) {
		$this->rootFolder = $this->config->getSystemValue('datadirectory', OC::$SERVERROOT . '/data');
	}

	public function writePreview(Preview $preview, $stream): false|int {
		$previewPath = $this->constructPath($preview);
		$this->createParentFiles($previewPath);
		$file = @fopen($this->rootFolder . '/' . self::PREVIEW_DIRECTORY . '/' . $previewPath, 'w');
		return fwrite($file, $stream);
	}

	public function readPreview(Preview $preview) {
		$previewPath = $this->constructPath($preview);
		return @fopen($this->rootFolder . '/' . self::PREVIEW_DIRECTORY . '/' . $previewPath, 'r');
	}

	public function deletePreview(Preview $preview) {
		$previewPath = $this->constructPath($preview);
		@unlink($this->rootFolder . '/' . self::PREVIEW_DIRECTORY . '/' . $previewPath);
	}

	private function constructPath(Preview $preview): string {
		return implode('/', str_split(substr(md5((string)$preview->getFileId()), 0, 7))) . '/' . $preview->getFileId() . '/' . $preview->getName();
	}

	private function createParentFiles($path) {
		['basename' => $basename, 'dirname' => $dirname] = pathinfo($path);
		$currentDir = $this->rootFolder . '/' . self::PREVIEW_DIRECTORY;
		mkdir($currentDir);
		foreach (explode('/', $dirname) as $suffix) {
			$currentDir .= "/$suffix";
			mkdir($currentDir);
		}
	}

	public function migratePreview(Preview $preview, SimpleFile $file): void {
		$instanceId = $this->config->getSystemValueString('instanceid');
		$previewPath = $this->constructPath($preview);
		$sourcePath = $this->rootFolder . '/appdata_' . $instanceId . '/preview/' . $previewPath;
		$destinationPath = $this->rootFolder . '/' . self::PREVIEW_DIRECTORY . '/' . $previewPath;
		if (!file_exists($sourcePath)) {
			// legacy flat directory
			$sourcePath = $this->rootFolder . '/appdata_' . $instanceId . '/preview/' . $preview->getFileId() . '/' . $preview->getName();
		}
		if (file_exists($destinationPath)) {
			return;
		}
		$this->createParentFiles($previewPath);
		echo 'Copying ' . $sourcePath . ' to ' . $destinationPath . PHP_EOL;
		$ok = copy($sourcePath, $destinationPath);
		if (!$ok) {
			throw new LogicException('Failed to copy ' . $sourcePath . ' to ' . $destinationPath);
		}
	}
}
