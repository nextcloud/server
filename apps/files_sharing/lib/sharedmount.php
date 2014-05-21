<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing;

use OC\Files\Filesystem;
use OC\Files\Mount\Mount;
use OC\Files\Mount\MoveableMount;
use OC\Files\Storage\Shared;

/**
 * Person mount points can be moved by the user
 */
class SharedMount extends Mount implements MoveableMount {
	/**
	 * @var \OC\Files\Storage\Shared $storage
	 */
	protected $storage = null;

	/**
	 * Format a path to be relative to the /user/files/ directory
	 *
	 * @param string $path the absolute path
	 * @return string e.g. turns '/admin/files/test.txt' into '/test.txt'
	 */
	private function stripUserFilesPath($path) {
		$trimmed = ltrim($path, '/');
		$split = explode('/', $trimmed);

		// it is not a file relative to data/user/files
		if (count($split) < 3 || $split[1] !== 'files') {
			\OCP\Util::writeLog('file sharing',
				'Can not strip userid and "files/" from path: ' . $path,
				\OCP\Util::DEBUG);
			return false;
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
		// it shouldn't be possible to move a Shared storage into another one
		list($targetStorage,) = Filesystem::resolvePath($target);
		if ($targetStorage instanceof Shared) {
			\OCP\Util::writeLog('file sharing',
				'It is not allowed to move one mount point into another one',
				\OCP\Util::DEBUG);
			return false;
		}

		$relTargetPath = $this->stripUserFilesPath($target);
		$share = $this->storage->getShare();

		// if the user renames a mount point from a group share we need to create a new db entry
		// for the unique name
		if ($this->storage->getShareType() === \OCP\Share::SHARE_TYPE_GROUP && $this->storage->uniqueNameSet() === false) {
			$query = \OC_DB::prepare('INSERT INTO `*PREFIX*share` (`item_type`, `item_source`, `item_target`,'
				. ' `share_type`, `share_with`, `uid_owner`, `permissions`, `stime`, `file_source`,'
				. ' `file_target`, `token`, `parent`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
			$arguments = array($share['item_type'], $share['item_source'], $share['item_target'],
				2, \OCP\User::getUser(), $share['uid_owner'], $share['permissions'], $share['stime'], $share['file_source'],
				$relTargetPath, $share['token'], $share['id']);

		} else {
			// rename mount point
			$query = \OC_DB::prepare(
				'UPDATE `*PREFIX*share`
					SET `file_target` = ?
					WHERE `id` = ?'
			);
			$arguments = array($relTargetPath, $this->storage->getShareId());
		}

		$result = $query->execute($arguments);

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
	 * @return mixed
	 * @return bool
	 */
	public function removeMount() {
	}
}
