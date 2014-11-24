<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing;

use OC\Files\Mount\MountPoint;
use OC\Files\Mount\MoveableMount;

/**
 * Shared mount points can be moved by the user
 */
class SharedMount extends MountPoint implements MoveableMount {
	/**
	 * @var \OC\Files\Storage\Shared $storage
	 */
	protected $storage = null;

	public function __construct($storage, $mountpoint, $arguments = null, $loader = null) {
		// first update the mount point before creating the parent
		$newMountPoint = $this->verifyMountPoint($arguments['share'], $arguments['user']);
		$absMountPoint = '/' . $arguments['user'] . '/files' . $newMountPoint;
		parent::__construct($storage, $absMountPoint, $arguments, $loader);
	}

	/**
	 * check if the parent folder exists otherwise move the mount point up
	 */
	private function verifyMountPoint(&$share, $user) {

		$mountPoint = basename($share['file_target']);
		$parent = dirname($share['file_target']);

		while (!\OC\Files\Filesystem::is_dir($parent)) {
			$parent = dirname($parent);
		}

		$newMountPoint = \OCA\Files_Sharing\Helper::generateUniqueTarget(
				\OC\Files\Filesystem::normalizePath($parent . '/' . $mountPoint),
				array(),
				new \OC\Files\View('/' . $user . '/files')
				);

		if($newMountPoint !== $share['file_target']) {
			self::updateFileTarget($newMountPoint, $share);
			$share['file_target'] = $newMountPoint;
			$share['unique_name'] = true;
		}

		return $newMountPoint;
	}

	/**
	 * update fileTarget in the database if the mount point changed
	 * @param string $newPath
	 * @param array $share reference to the share which should be modified
	 * @return bool
	 */
	private static function updateFileTarget($newPath, &$share) {
		// if the user renames a mount point from a group share we need to create a new db entry
		// for the unique name
		if ($share['share_type'] === \OCP\Share::SHARE_TYPE_GROUP && empty($share['unique_name'])) {
			$query = \OC_DB::prepare('INSERT INTO `*PREFIX*share` (`item_type`, `item_source`, `item_target`,'
			.' `share_type`, `share_with`, `uid_owner`, `permissions`, `stime`, `file_source`,'
			.' `file_target`, `token`, `parent`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
			$arguments = array($share['item_type'], $share['item_source'], $share['item_target'],
				2, \OCP\User::getUser(), $share['uid_owner'], $share['permissions'], $share['stime'], $share['file_source'],
				$newPath, $share['token'], $share['id']);
		} else {
			// rename mount point
			$query = \OC_DB::prepare(
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
}
