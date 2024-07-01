<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Command\Info;

use OCA\Circles\MountManager\CircleMount;
use OCA\Files_External\Config\ExternalMountPoint;
use OCA\Files_Sharing\SharedMount;
use OCA\GroupFolders\Mount\GroupMountPoint;
use OCP\Constants;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IHomeStorage;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Share\IShare;
use OCP\Util;
use Symfony\Component\Console\Output\OutputInterface;

class FileUtils {
	public function __construct(
		private IRootFolder $rootFolder,
		private IUserMountCache $userMountCache,
	) {
	}

	/**
	 * @param FileInfo $file
	 * @return array<string, Node[]>
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OC\User\NoUserException
	 */
	public function getFilesByUser(FileInfo $file): array {
		$id = $file->getId();
		if (!$id) {
			return [];
		}

		$mounts = $this->userMountCache->getMountsForFileId($id);
		$result = [];
		foreach ($mounts as $mount) {
			if (isset($result[$mount->getUser()->getUID()])) {
				continue;
			}

			$userFolder = $this->rootFolder->getUserFolder($mount->getUser()->getUID());
			$result[$mount->getUser()->getUID()] = $userFolder->getById($id);
		}

		return $result;
	}

	/**
	 * Get file by either id of path
	 *
	 * @param string $fileInput
	 * @return Node|null
	 */
	public function getNode(string $fileInput): ?Node {
		if (is_numeric($fileInput)) {
			$mounts = $this->userMountCache->getMountsForFileId((int)$fileInput);
			if (!$mounts) {
				return null;
			}
			$mount = reset($mounts);
			$userFolder = $this->rootFolder->getUserFolder($mount->getUser()->getUID());
			$nodes = $userFolder->getById((int)$fileInput);
			if (!$nodes) {
				return null;
			}
			return $nodes[0];
		} else {
			try {
				return $this->rootFolder->get($fileInput);
			} catch (NotFoundException $e) {
				return null;
			}
		}
	}

	public function formatPermissions(string $type, int $permissions): string {
		if ($permissions == Constants::PERMISSION_ALL || ($type === 'file' && $permissions == (Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE))) {
			return "full permissions";
		}

		$perms = [];
		$allPerms = [Constants::PERMISSION_READ => "read", Constants::PERMISSION_UPDATE => "update", Constants::PERMISSION_CREATE => "create", Constants::PERMISSION_DELETE => "delete", Constants::PERMISSION_SHARE => "share"];
		foreach ($allPerms as $perm => $name) {
			if (($permissions & $perm) === $perm) {
				$perms[] = $name;
			}
		}

		return implode(", ", $perms);
	}

	/**
	 * @psalm-suppress UndefinedClass
	 * @psalm-suppress UndefinedInterfaceMethod
	 */
	public function formatMountType(IMountPoint $mountPoint): string {
		$storage = $mountPoint->getStorage();
		if ($storage && $storage->instanceOfStorage(IHomeStorage::class)) {
			return "home storage";
		} elseif ($mountPoint instanceof SharedMount) {
			$share = $mountPoint->getShare();
			$shares = $mountPoint->getGroupedShares();
			$sharedBy = array_map(function (IShare $share) {
				$shareType = $this->formatShareType($share);
				if ($shareType) {
					return $share->getSharedBy() . " (via " . $shareType . " " . $share->getSharedWith() . ")";
				} else {
					return $share->getSharedBy();
				}
			}, $shares);
			$description = "shared by " . implode(', ', $sharedBy);
			if ($share->getSharedBy() !== $share->getShareOwner()) {
				$description .= " owned by " . $share->getShareOwner();
			}
			return $description;
		} elseif ($mountPoint instanceof GroupMountPoint) {
			return "groupfolder " . $mountPoint->getFolderId();
		} elseif ($mountPoint instanceof ExternalMountPoint) {
			return "external storage " . $mountPoint->getStorageConfig()->getId();
		} elseif ($mountPoint instanceof CircleMount) {
			return "circle";
		}
		return get_class($mountPoint);
	}

	public function formatShareType(IShare $share): ?string {
		switch ($share->getShareType()) {
			case IShare::TYPE_GROUP:
				return "group";
			case IShare::TYPE_CIRCLE:
				return "circle";
			case IShare::TYPE_DECK:
				return "deck";
			case IShare::TYPE_ROOM:
				return "room";
			case IShare::TYPE_USER:
				return null;
			default:
				return "Unknown (" . $share->getShareType() . ")";
		}
	}

	/**
	 * Print out the largest count($sizeLimits) files in the directory tree
	 *
	 * @param OutputInterface $output
	 * @param Folder $node
	 * @param string $prefix
	 * @param array $sizeLimits largest items that are still in the queue to be printed, ordered ascending
	 * @return int how many items we've printed
	 */
	public function outputLargeFilesTree(
		OutputInterface $output,
		Folder $node,
		string $prefix,
		array &$sizeLimits,
		bool $all,
	): int {
		/**
		 * Algorithm to print the N largest items in a folder without requiring to query or sort the entire three
		 *
		 * This is done by keeping a list ($sizeLimits) of size N that contain the largest items outside of this
		 * folders that are could be printed if there aren't enough items in this folder that are larger.
		 *
		 * We loop over the items in this folder by size descending until the size of the item falls before the smallest
		 * size in $sizeLimits (at that point there are enough items outside this folder to complete the N items).
		 *
		 * When encountering a folder, we create an updated $sizeLimits with the largest items in the current folder still
		 * remaining which we pass into the recursion. (We don't update the current $sizeLimits because that should only
		 * hold items *outside* of the current folder.)
		 *
		 * For every item printed we remove the first item of $sizeLimits are there is no longer room in the output to print
		 * items that small.
		 */

		$count = 0;
		$children = $node->getDirectoryListing();
		usort($children, function (Node $a, Node $b) {
			return $b->getSize() <=> $a->getSize();
		});
		foreach ($children as $i => $child) {
			if (!$all) {
				if (count($sizeLimits) === 0 || $child->getSize() < $sizeLimits[0]) {
					return $count;
				}
				array_shift($sizeLimits);
			}
			$count += 1;

			/** @var Node $child */
			$output->writeln("$prefix- " . $child->getName() . ": <info>" . Util::humanFileSize($child->getSize()) . "</info>");
			if ($child instanceof Folder) {
				$recurseSizeLimits = $sizeLimits;
				if (!$all) {
					for ($j = 0; $j < count($recurseSizeLimits); $j++) {
						if (isset($children[$i + $j + 1])) {
							$nextChildSize = $children[$i + $j + 1]->getSize();
							if ($nextChildSize > $recurseSizeLimits[0]) {
								array_shift($recurseSizeLimits);
								$recurseSizeLimits[] = $nextChildSize;
							}
						}
					}
					sort($recurseSizeLimits);
				}
				$recurseCount = $this->outputLargeFilesTree($output, $child, $prefix . "  ", $recurseSizeLimits, $all);
				$sizeLimits = array_slice($sizeLimits, $recurseCount);
				$count += $recurseCount;
			}
		}
		return $count;
	}
}
