<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview\Storage;

use OC\Files\AppData\AppData;
use OC\SystemConfig;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;

class Root extends AppData {
	private $isMultibucketPreviewDistributionEnabled = false;
	public function __construct(IRootFolder $rootFolder, SystemConfig $systemConfig) {
		parent::__construct($rootFolder, $systemConfig, 'preview');

		$this->isMultibucketPreviewDistributionEnabled = $systemConfig->getValue('objectstore.multibucket.preview-distribution', false) === true;
	}


	public function getFolder(string $name): ISimpleFolder {
		$internalFolder = self::getInternalFolder($name);

		try {
			return parent::getFolder($internalFolder);
		} catch (NotFoundException $e) {
			/*
			 * The new folder structure is not found.
			 * Lets try the old one
			 */
		}

		try {
			return parent::getFolder($name);
		} catch (NotFoundException $e) {
			/*
			 * The old folder structure is not found.
			 * Lets try the multibucket fallback if available
			 */
			if ($this->isMultibucketPreviewDistributionEnabled) {
				return parent::getFolder('old-multibucket/' . $internalFolder);
			}

			// when there is no further fallback just throw the exception
			throw $e;
		}
	}

	public function newFolder(string $name): ISimpleFolder {
		$internalFolder = self::getInternalFolder($name);
		return parent::newFolder($internalFolder);
	}

	/*
	 * Do not allow directory listing on this special root
	 * since it gets to big and time consuming
	 */
	public function getDirectoryListing(): array {
		return [];
	}

	public static function getInternalFolder(string $name): string {
		return implode('/', str_split(substr(md5($name), 0, 7))) . '/' . $name;
	}

	public function getStorageId(): int {
		return $this->getAppDataRootFolder()->getStorage()->getCache()->getNumericStorageId();
	}
}
