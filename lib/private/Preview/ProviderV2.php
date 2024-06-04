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

	/**
	 * @return string Regex with the mimetypes that are supported by this provider
	 */
	abstract public function getMimeType(): string ;

	/**
	 * Check if a preview can be generated for $path
	 *
	 * @param FileInfo $file
	 * @return bool
	 */
	public function isAvailable(FileInfo $file): bool {
		return true;
	}

	/**
	 * get thumbnail for file at path $path
	 *
	 * @param File $file
	 * @param int $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param int $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @return null|\OCP\IImage false if no preview was generated
	 * @since 17.0.0
	 */
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
