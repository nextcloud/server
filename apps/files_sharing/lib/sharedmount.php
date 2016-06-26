<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

use OC\Files\Filesystem;
use OC\Files\Mount\MountPoint;
use OC\Files\Mount\MoveableMount;
use OC\Files\View;

/**
 * Shared mount points can be moved by the user
 */
class SharedMount extends MountPoint implements MoveableMount {
	/**
	 * @var \OC\Files\Storage\Shared $storage
	 */
	protected $storage = null;

	/**
	 * @var \OC\Files\View
	 */
	private $recipientView;

	/**
	 * @var string
	 */
	private $user;

	/**
	 * @param string $storage
	 * @param SharedMount[] $mountpoints
	 * @param array|null $arguments
	 * @param \OCP\Files\Storage\IStorageFactory $loader
	 */
	public function __construct($storage, array $mountpoints, $arguments = null, $loader = null) {
		$this->user = $arguments['user'];
		$this->recipientView = new View('/' . $this->user . '/files');
		$newMountPoint = $this->verifyMountPoint($arguments['share'], $mountpoints);
		$absMountPoint = '/' . $this->user . '/files' . $newMountPoint;
		$arguments['ownerView'] = new View('/' . $arguments['share']['uid_owner'] . '/files');
		parent::__construct($storage, $absMountPoint, $arguments, $loader);
	}

	/**
	 * check if the parent folder exists otherwise move the mount point up
	 *
	 * @param array $share
	 * @param SharedMount[] $mountpoints
	 * @return string
	 */
	private function verifyMountPoint(&$share, array $mountpoints) {

		$mountPoint = basename($share['file_target']);
		$parent = dirname($share['file_target']);

		if (!$this->recipientView->is_dir($parent)) {
			$parent = Helper::getShareFolder($this->recipientView);
		}

		$newMountPoint = $this->generateUniqueTarget(
			\OC\Files\Filesystem::normalizePath($parent . '/' . $mountPoint),
			$this->recipientView,
			$mountpoints
		);

		if ($newMountPoint !== $share['file_target']) {
			$this->updateFileTarget($newMountPoint, $share);
			$share['file_target'] = $newMountPoint;
			$share['unique_name'] = true;
		}

		return $newMountPoint;
	}

	/**
	 * @param string $path
	 * @param View $view
	 * @param SharedMount[] $mountpoints
	 * @return mixed
	 */
	private function generateUniqueTarget($path, $view, array $mountpoints) {
		$pathinfo = pathinfo($path);
		$ext = (isset($pathinfo['extension'])) ? '.'.$pathinfo['extension'] : '';
		$name = $pathinfo['filename'];
		$dir = $pathinfo['dirname'];

		// Helper function to find existing mount points
		$mountpointExists = function($path) use ($mountpoints) {
			foreach ($mountpoints as $mountpoint) {
				if ($mountpoint->getShare()['file_target'] === $path) {
					return true;
				}
			}
			return false;
		};

		$i = 2;
		while ($view->file_exists($path) || $mountpointExists($path)) {
			$path = Filesystem::normalizePath($dir . '/' . $name . ' ('.$i.')' . $ext);
			$i++;
		}

		return $path;
	}

	/**
	 * update fileTarget in the database if the mount point changed
	 *
	 * @param string $newPath
	 * @param array $share reference to the share which should be modified
	 * @return bool
	 */
	private function updateFileTarget($newPath, &$share) {
		// if the user renames a mount point from a group share we need to create a new db entry
		// for the unique name
		if ($share['share_type'] === \OCP\Share::SHARE_TYPE_GROUP && empty($share['unique_name'])) {
			$query = \OCP\DB::prepare('INSERT INTO `*PREFIX*share` (`item_type`, `item_source`, `item_target`,'
			.' `share_type`, `share_with`, `uid_owner`, `permissions`, `stime`, `file_source`,'
			.' `file_target`, `token`, `parent`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
			$arguments = array($share['item_type'], $share['item_source'], $share['item_target'],
				2, $this->user, $share['uid_owner'], $share['permissions'], $share['stime'], $share['file_source'],
				$newPath, $share['token'], $share['id']);
		} else {
			// rename mount point
			$query = \OCP\DB::prepare(
					'Update `*PREFIX*share`
						SET `file_target` = ?
						WHERE `id` = ?'
			);
			$arguments = array($newPath, $share['id']);
		}

		$result = $query->execute($arguments);

		return $result === 1 ? true : false;
	}

	/**
	 * Format a path to be relative to the /user/files/ directory
	 *
	 * @param string $path the absolute path
	 * @return string e.g. turns '/admin/files/test.txt' into '/test.txt'
	 * @throws \OCA\Files_Sharing\Exceptions\BrokenPath
	 */
	protected function stripUserFilesPath($path) {
		$trimmed = ltrim($path, '/');
		$split = explode('/', $trimmed);

		// it is not a file relative to data/user/files
		if (count($split) < 3 || $split[1] !== 'files') {
			\OCP\Util::writeLog('file sharing',
				'Can not strip userid and "files/" from path: ' . $path,
				\OCP\Util::ERROR);
			throw new \OCA\Files_Sharing\Exceptions\BrokenPath('Path does not start with /user/files', 10);
		}

		// skip 'user' and 'files'
		$sliced = array_slice($split, 2);
		$relPath = implode('/', $sliced);

		return '/' . $relPath;
	}

	/**
	 * Move the mount point to $target
	 *
	 * @param string $target the target mount point
	 * @return bool
	 */
	public function moveMount($target) {

		$relTargetPath = $this->stripUserFilesPath($target);
		$share = $this->storage->getShare();

		$result = true;

		if (!empty($share['grouped'])) {
			foreach ($share['grouped'] as $s) {
				$result = $this->updateFileTarget($relTargetPath, $s) && $result;
			}
		} else {
			$result = $this->updateFileTarget($relTargetPath, $share) && $result;
		}

		if ($result) {
			$this->setMountPoint($target);
			$this->storage->setUniqueName();
			$this->storage->setMountPoint($relTargetPath);

		} else {
			\OCP\Util::writeLog('file sharing',
				'Could not rename mount point for shared folder "' . $this->getMountPoint() . '" to "' . $target . '"',
				\OCP\Util::ERROR);
		}

		return $result;
	}

	/**
	 * Remove the mount points
	 *
	 * @return bool
	 */
	public function removeMount() {
		$mountManager = \OC\Files\Filesystem::getMountManager();
		/** @var $storage \OC\Files\Storage\Shared */
		$storage = $this->getStorage();
		$result = $storage->unshareStorage();
		$mountManager->removeMount($this->mountPoint);

		return $result;
	}

	/**
	 * @return array
	 */
	public function getShare() {
		/** @var $storage \OC\Files\Storage\Shared */
		$storage = $this->getStorage();
		return $storage->getShare();
	}
}
