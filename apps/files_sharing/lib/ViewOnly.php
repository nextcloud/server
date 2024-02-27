<?php
/**
 * @author Piotr Mrowczynski piotr@owncloud.com
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Sharing;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;

/**
 * Handles restricting for download of files
 */
class ViewOnly {

	/** @var Folder */
	private $userFolder;

	public function __construct(Folder $userFolder) {
		$this->userFolder = $userFolder;
	}

	/**
	 * @param string[] $pathsToCheck
	 * @return bool
	 */
	public function check(array $pathsToCheck): bool {
		// If any of elements cannot be downloaded, prevent whole download
		foreach ($pathsToCheck as $file) {
			try {
				$info = $this->userFolder->get($file);
				if ($info instanceof File) {
					// access to filecache is expensive in the loop
					if (!$this->checkFileInfo($info)) {
						return false;
					}
				} elseif ($info instanceof Folder) {
					// get directory content is rather cheap query
					if (!$this->dirRecursiveCheck($info)) {
						return false;
					}
				}
			} catch (NotFoundException $e) {
				continue;
			}
		}
		return true;
	}

	/**
	 * @param Folder $dirInfo
	 * @return bool
	 * @throws NotFoundException
	 */
	private function dirRecursiveCheck(Folder $dirInfo): bool {
		if (!$this->checkFileInfo($dirInfo)) {
			return false;
		}
		// If any of elements cannot be downloaded, prevent whole download
		$files = $dirInfo->getDirectoryListing();
		foreach ($files as $file) {
			if ($file instanceof File) {
				if (!$this->checkFileInfo($file)) {
					return false;
				}
			} elseif ($file instanceof Folder) {
				return $this->dirRecursiveCheck($file);
			}
		}

		return true;
	}

	/**
	 * @param Node $fileInfo
	 * @return bool
	 * @throws NotFoundException
	 */
	private function checkFileInfo(Node $fileInfo): bool {
		// Restrict view-only to nodes which are shared
		$storage = $fileInfo->getStorage();
		if (!$storage->instanceOfStorage(SharedStorage::class)) {
			return true;
		}

		// Extract extra permissions
		/** @var \OCA\Files_Sharing\SharedStorage $storage */
		$share = $storage->getShare();

		$canDownload = true;

		// Check if read-only and on whether permission can download is both set and disabled.
		$attributes = $share->getAttributes();
		if ($attributes !== null) {
			$canDownload = $attributes->getAttribute('permissions', 'download');
		}

		if ($canDownload !== null && !$canDownload) {
			return false;
		}
		return true;
	}
}
