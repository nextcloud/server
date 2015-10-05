<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
	 * @var \OC\Files\Cache\ChangePropagator
	 */
	protected $ownerPropagator;

	/**
	 * @var \OC\Files\View
	 */
	private $recipientView;

	/**
	 * @var string
	 */
	private $user;

	public function __construct($storage, $mountpoint, $arguments = null, $loader = null) {
		// first update the mount point before creating the parent
		$this->ownerPropagator = $arguments['propagator'];
		$this->user = $arguments['user'];
		$this->recipientView = new View('/' . $this->user . '/files');
		$newMountPoint = $this->verifyMountPoint($arguments['share']);
		$absMountPoint = '/' . $this->user . '/files' . $newMountPoint;
		$arguments['ownerView'] = new View('/' . $arguments['share']['uid_owner'] . '/files');
		parent::__construct($storage, $absMountPoint, $arguments, $loader);
	}

	/**
	 * check if the parent folder exists otherwise move the mount point up
	 */
	private function verifyMountPoint(&$share) {

		$mountPoint = basename($share['file_target']);
		$parent = dirname($share['file_target']);

		if (!$this->recipientView->is_dir($parent)) {
			$parent = Helper::getShareFolder();
		}

		$newMountPoint = \OCA\Files_Sharing\Helper::generateUniqueTarget(
			\OC\Files\Filesystem::normalizePath($parent . '/' . $mountPoint),
			[],
			$this->recipientView
		);

		if ($newMountPoint !== $share['file_target']) {
			$this->updateFileTarget($newMountPoint, $share);
			$share['file_target'] = $newMountPoint;
			$share['unique_name'] = true;
		}

		return $newMountPoint;
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
		/** @var \OC\Files\Storage\Shared */
		$storage = $this->getStorage();
		$result = $storage->unshareStorage();
		$mountManager->removeMount($this->mountPoint);

		return $result;
	}

	public function getShare() {
		return $this->getStorage()->getShare();
	}

	/**
	 * @return \OC\Files\Cache\ChangePropagator
	 */
	public function getOwnerPropagator() {
		return $this->ownerPropagator;
	}
}
