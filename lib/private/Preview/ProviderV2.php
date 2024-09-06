<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IImage;
use OCP\Preview\IProviderV2;

abstract class ProviderV2 implements IProviderV2 {
	/** @var array */
	protected $options;

	/** @var array */
	protected $tmpFiles = [];

	/**
	 * Constructor
	 *
	 * @param array $options
	 */
	public function __construct(array $options = []) {
		$this->options = $options;
	}

	abstract public function getMimeType(): string ;

	public function isAvailable(FileInfo $file): bool {
		return true;
	}

	abstract public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage;

	protected function useTempFile(File $file): bool {
		return $file->isEncrypted() || !$file->getStorage()->isLocal();
	}

	/**
	 * Get a path to either the local file or temporary file
	 *
	 * @param File $file
	 * @param int $maxSize maximum size for temporary files
	 * @return string|false
	 */
	protected function getLocalFile(File $file, ?int $maxSize = null) {
		if ($this->useTempFile($file)) {
			$absPath = \OC::$server->getTempManager()->getTemporaryFile();

			$content = $file->fopen('r');

			if ($maxSize) {
				$content = stream_get_contents($content, $maxSize);
			}

			file_put_contents($absPath, $content);
			$this->tmpFiles[] = $absPath;
			return $absPath;
		} else {
			$path = $file->getStorage()->getLocalFile($file->getInternalPath());
			if (is_string($path)) {
				return $path;
			} else {
				return false;
			}
		}
	}

	/**
	 * Clean any generated temporary files
	 */
	protected function cleanTmpFiles(): void {
		foreach ($this->tmpFiles as $tmpFile) {
			unlink($tmpFile);
		}

		$this->tmpFiles = [];
	}
}
