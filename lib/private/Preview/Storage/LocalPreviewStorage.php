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
	private readonly string $rootFolder;
	private readonly string $instanceId;

	public function __construct(
		private readonly IConfig $config,
	) {
		$this->instanceId = $this->config->getSystemValueString('instanceid');
		$this->rootFolder = $this->config->getSystemValue('datadirectory', OC::$SERVERROOT . '/data');
	}

	public function writePreview(Preview $preview, mixed $stream): false|int {
		$previewPath = $this->constructPath($preview);
		if (!$this->createParentFiles($previewPath)) {
			return false;
		}
		return file_put_contents($previewPath, $stream);
	}

	public function readPreview(Preview $preview): mixed {
		return @fopen($this->constructPath($preview), 'r');
	}

	public function deletePreview(Preview $preview): void {
		@unlink($this->constructPath($preview));
	}

	public function getPreviewRootFolder(): string {
		return $this->rootFolder . '/appdata_' . $this->instanceId . '/preview/';
	}

	private function constructPath(Preview $preview): string {
		return $this->getPreviewRootFolder() . implode('/', str_split(substr(md5((string)$preview->getFileId()), 0, 7))) . '/' . $preview->getFileId() . '/' . $preview->getName();
	}

	private function createParentFiles(string $path): bool {
		['dirname' => $dirname] = pathinfo($path);
		return mkdir($dirname, recursive: true);
	}

	public function migratePreview(Preview $preview, SimpleFile $file): void {
		// legacy flat directory
		$sourcePath = $this->getPreviewRootFolder() . $preview->getFileId() . '/' . $preview->getName();
		if (!file_exists($sourcePath)) {
			return;
		}

		$destinationPath = $this->constructPath($preview);
		if (file_exists($destinationPath)) {
			@unlink($sourcePath); // We already have a new preview, just delete the old one
			return;
		}

		$this->createParentFiles($destinationPath);
		$ok = rename($sourcePath, $destinationPath);
		if (!$ok) {
			throw new LogicException('Failed to copy ' . $sourcePath . ' to ' . $destinationPath);
		}
	}
}
