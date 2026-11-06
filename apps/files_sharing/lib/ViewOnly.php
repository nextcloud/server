<?php

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2019 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Share\IShare;

/**
 * Handles restricting for download of files
 */
class ViewOnly {

	public function __construct(
		private ?Folder $folder,
	) {
	}

	/**
	 * @param string[] $pathsToCheck paths to check, relative to the given folder
	 * @return bool
	 */
	public function check(array $pathsToCheck): bool {
		if ($this->folder === null) {
			throw new \LogicException('Folder is not set but this check requires it.');
		}

		if (empty($pathsToCheck)) {
			return $this->dirRecursiveCheck($this->folder);
		}

		// If any of elements cannot be downloaded, prevent whole download
		foreach ($pathsToCheck as $file) {
			try {
				$info = $this->folder->get($file);
				if ($info instanceof File) {
					// access to filecache is expensive in the loop
					if (!$this->isDownloadable($info)) {
						return false;
					}
				} elseif ($info instanceof Folder) {
					// get directory content is rather cheap query
					if (!$this->dirRecursiveCheck($info)) {
						return false;
					}
				}
			} catch (NotFoundException) {
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
		if (!$this->isDownloadable($dirInfo)) {
			return false;
		}
		// If any of elements cannot be downloaded, prevent whole download
		$files = $dirInfo->getDirectoryListing();
		foreach ($files as $file) {
			if ($file instanceof File) {
				if (!$this->isDownloadable($file)) {
					return false;
				}
			} elseif ($file instanceof Folder) {
				if (!$this->dirRecursiveCheck($file)) {
					return false;
				}
			}
		}

		return true;
	}

	public function isDownloadable(Node $fileInfo): bool {
		try {
			return self::canDownloadThroughMount($fileInfo);
		} catch (NotFoundException) {
			return true;
		}
	}

	/**
	 * Whether the node can be downloaded through the mount it is accessed by,
	 * which for share mounts requires one of the received shares to allow it.
	 *
	 * @param ?callable(IShare): bool $allowsDownload whether a received share allows downloads, defaults to its download attribute
	 * @throws NotFoundException
	 */
	public static function canDownloadThroughMount(Node $node, ?callable $allowsDownload = null): bool {
		$mount = $node->getMountPoint();
		if (!$mount instanceof SharedMount) {
			return true;
		}

		$allowsDownload ??= static fn (IShare $share): bool => $share->getAttributes()?->getAttribute('permissions', 'download') !== false;
		foreach ($mount->getGroupedShares() as $share) {
			if ($allowsDownload($share)) {
				return true;
			}
		}

		return false;
	}
}
