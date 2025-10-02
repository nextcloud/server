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

/**
 * Preview storage root for Nextcloud.
 *
 * Implements preview folder access using all supported folder structures.
 */
class Root extends AppData {
	/**
	 * @param IRootFolder $rootFolder
	 * @param SystemConfig $systemConfig
	 */
	public function __construct(
		IRootFolder $rootFolder,
		private SystemConfig $systemConfig,
	) {
		parent::__construct($rootFolder, $systemConfig, 'preview');
	}

	/**
	 * Retrieve the preview folder for a specified file.
	 *
	 * Search order:
	 *  - Hashed folder structure
	 *  - Legacy flat folder structure
	 *  - Legacy single bucket folder structure (if enabled)
	 *
	 * If none of these locations exist, throws NotFoundException.
	 *
	 * @param string $name File identifier (fileId) as a string.
	 * @return ISimpleFolder Preview folder instance.
	 * @throws NotFoundException If no folder is found.
	 */
	public function getFolder(string $name): ISimpleFolder {
		$searchTargets = $this->buildSearchTargets($name);

		foreach ($searchTargets as $target) {
			try {
				return parent::getFolder($target);
			} catch (NotFoundException $e) {
				// Optional: Add debug logging here
				// Note: there is no point in rethrowing; don't know which structure should succeed.
				continue;
			}
		}
		throw new NotFoundException("Preview folder not found for name: $name");
	}

	/**
	 * Build a list of candidate folder paths to search for a preview folder.
	 *
	 * The order is:
	 *  - Hashed folder structure (modern, default)
	 *  - Legacy flat folder structure
	 *  - Legacy single bucket folder structure (if enabled)
	 *
	 * @param string $name File identifier (fileId) as a string.
	 * @return string[] List of folder structure candidates to check for previews.
	 */
	private function buildSearchTargets(string $name): array {
		$internalFolder = self::getInternalFolder($name);
		$searchTargets = [ $internalFolder, $name ];
		if ($this->systemConfig->getValue('objectstore.multibucket.preview-distribution', false)) {
			$searchTargets[] = 'old-multibucket/' . $internalFolder;
			// TODO: Consider moving legacy bucket fallback earlier as an optimization.
		}
		// TODO: Consider adding config flag to disable using legacy fallback strategies as an optimization.
		return $searchTargets;
	}

	/**
	 * Create a new preview folder, using the default modern structure, for a specified file.
	 *
	 * @param string $name File identifier (fileId) as a string.
	 * @return ISimpleFolder Preview folder instance.
	 */
	public function newFolder(string $name): ISimpleFolder {
		$internalFolder = self::getInternalFolder($name);
		return parent::newFolder($internalFolder);
	}

	/**
	 * Directory listing is disallowed for this root due to performance.
	 *
	 * @return array<ISimpleFolder> An empty array.
	 */
	public function getDirectoryListing(): array {
		return [];
	}

	/**
	 * Utility function to get the internal (hashed) folder path for a given name.
	 * Uses the first 7 chars of the md5 hash as folder distribution.
	 *
	 * @param string $name File identifier (fileId) as a string.
	 * @return string Preview folder path for the specified file.
	 */
	public static function getInternalFolder(string $name): string {
		$hash = substr(md5($name), 0, 7);
		$folderPath = implode('/', str_split($hash)) . '/' . $name;
		return $folderPath;
	}

	/**
	 * Get the numeric storage ID for this preview storage.
	 *
	 * @return int
	 */
	public function getStorageId(): int {
		return $this->getAppDataRootFolder()
			->getStorage()
			->getCache()
			->getNumericStorageId();
	}
}
