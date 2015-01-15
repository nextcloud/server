<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle, Michael Gapczynski
 * @copyright 2011 Michael Gapczynski <mtgap@owncloud.com>
 *            2014 Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Files\Storage;
use OC\Files\Filesystem;
use OCA\Files_Sharing\ISharedStorage;
use OCA\Files_Sharing\SharedMount;

/**
 * Convert target path to source path and pass the function call to the correct storage provider
 */
class Shared extends \OC\Files\Storage\Common implements ISharedStorage {

	private $share;   // the shared resource
	private $files = array();

	public function __construct($arguments) {
		$this->share = $arguments['share'];
	}

	/**
	 * get id of the mount point
	 * @return string
	 */
	public function getId() {
		return 'shared::' . $this->getMountPoint();
	}

	/**
	 * get file cache of the shared item source
	 * @return int
	 */
	public function getSourceId() {
		return (int) $this->share['file_source'];
	}

	/**
	 * Get the source file path, permissions, and owner for a shared file
	 * @param string $target Shared target file path
	 * @return Returns array with the keys path, permissions, and owner or false if not found
	 */
	public function getFile($target) {
		if (!isset($this->files[$target])) {
			// Check for partial files
			if (pathinfo($target, PATHINFO_EXTENSION) === 'part') {
				$source = \OC_Share_Backend_File::getSource(substr($target, 0, -5), $this->getMountPoint(), $this->getItemType());
				if ($source) {
					$source['path'] .= '.part';
					// All partial files have delete permission
					$source['permissions'] |= \OCP\Constants::PERMISSION_DELETE;
				}
			} else {
				$source = \OC_Share_Backend_File::getSource($target, $this->getMountPoint(), $this->getItemType());
			}
			$this->files[$target] = $source;
		}
		return $this->files[$target];
	}

	/**
	 * Get the source file path for a shared file
	 * @param string $target Shared target file path
	 * @return string source file path or false if not found
	 */
	public function getSourcePath($target) {
		$source = $this->getFile($target);
		if ($source) {
			if (!isset($source['fullPath'])) {
				\OC\Files\Filesystem::initMountPoints($source['fileOwner']);
				$mount = \OC\Files\Filesystem::getMountByNumericId($source['storage']);
				if (is_array($mount) && !empty($mount)) {
					$this->files[$target]['fullPath'] = $mount[key($mount)]->getMountPoint() . $source['path'];
				} else {
					$this->files[$target]['fullPath'] = false;
					\OCP\Util::writeLog('files_sharing', "Unable to get mount for shared storage '" . $source['storage'] . "' user '" . $source['fileOwner'] . "'", \OCP\Util::ERROR);
				}
			}
			return $this->files[$target]['fullPath'];
		}
		return false;
	}

	/**
	 * Get the permissions granted for a shared file
	 * @param string $target Shared target file path
	 * @return int CRUDS permissions granted
	 */
	public function getPermissions($target = '') {
		$permissions = $this->share['permissions'];
		// part files and the mount point always have delete permissions
		if ($target === '' || pathinfo($target, PATHINFO_EXTENSION) === 'part') {
			$permissions |= \OCP\Constants::PERMISSION_DELETE;
		}

		if (\OC_Util::isSharingDisabledForUser()) {
			$permissions &= ~\OCP\Constants::PERMISSION_SHARE;
		}

		return $permissions;
	}

	public function mkdir($path) {
		if ($path == '' || $path == '/' || !$this->isCreatable(dirname($path))) {
			return false;
		} else if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->mkdir($internalPath);
		}
		return false;
	}

	/**
	 * Delete the directory if DELETE permission is granted
	 * @param string $path
	 * @return boolean
	 */
	public function rmdir($path) {

		// never delete a share mount point
		if(empty($path)) {
			return false;
		}

		if (($source = $this->getSourcePath($path)) && $this->isDeletable($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->rmdir($internalPath);
		}
		return false;
	}

	public function opendir($path) {
		$source = $this->getSourcePath($path);
		list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
		return $storage->opendir($internalPath);
	}

	public function is_dir($path) {
		$source = $this->getSourcePath($path);
		list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
		return $storage->is_dir($internalPath);
	}

	public function is_file($path) {
		if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->is_file($internalPath);
		}
		return false;
	}

	public function stat($path) {
		if ($path == '' || $path == '/') {
			$stat['size'] = $this->filesize($path);
			$stat['mtime'] = $this->filemtime($path);
			return $stat;
		} else if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->stat($internalPath);
		}
		return false;
	}

	public function filetype($path) {
		if ($path == '' || $path == '/') {
			return 'dir';
		} else if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->filetype($internalPath);
		}
		return false;
	}

	public function filesize($path) {
		$source = $this->getSourcePath($path);
		list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
		return $storage->filesize($internalPath);
	}

	public function isCreatable($path) {
		return ($this->getPermissions($path) & \OCP\Constants::PERMISSION_CREATE);
	}

	public function isReadable($path) {
		return $this->file_exists($path);
	}

	public function isUpdatable($path) {
		return ($this->getPermissions($path) & \OCP\Constants::PERMISSION_UPDATE);
	}

	public function isDeletable($path) {
		return ($this->getPermissions($path) & \OCP\Constants::PERMISSION_DELETE);
	}

	public function isSharable($path) {
		if (\OCP\Util::isSharingDisabledForUser()) {
			return false;
		}
		return ($this->getPermissions($path) & \OCP\Constants::PERMISSION_SHARE);
	}

	public function file_exists($path) {
		if ($path == '' || $path == '/') {
			return true;
		} else if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->file_exists($internalPath);
		}
		return false;
	}

	public function filemtime($path) {
		$source = $this->getSourcePath($path);
		list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
		return $storage->filemtime($internalPath);
	}

	public function file_get_contents($path) {
		$source = $this->getSourcePath($path);
		if ($source) {
			$info = array(
				'target' => $this->getMountPoint() . $path,
				'source' => $source,
			);
			\OCP\Util::emitHook('\OC\Files\Storage\Shared', 'file_get_contents', $info);
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->file_get_contents($internalPath);
		}
	}

	public function file_put_contents($path, $data) {
		if ($source = $this->getSourcePath($path)) {
			// Check if permission is granted
			if (($this->file_exists($path) && !$this->isUpdatable($path))
				|| ($this->is_dir($path) && !$this->isCreatable($path))
			) {
				return false;
			}
			$info = array(
				'target' => $this->getMountPoint() . '/' . $path,
				'source' => $source,
			);
			\OCP\Util::emitHook('\OC\Files\Storage\Shared', 'file_put_contents', $info);
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			$result = $storage->file_put_contents($internalPath, $data);
			return $result;
		}
		return false;
	}

	/**
	 * Delete the file if DELETE permission is granted
	 * @param string $path
	 * @return boolean
	 */
	public function unlink($path) {

		// never delete a share mount point
		if (empty($path)) {
			return false;
		}
		if ($source = $this->getSourcePath($path)) {
			if ($this->isDeletable($path)) {
				list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
				return $storage->unlink($internalPath);
			}
		}
		return false;
	}

	public function rename($path1, $path2) {

		// we need the paths relative to data/user/files
		$relPath1 = $this->getMountPoint() . '/' . $path1;
		$relPath2 = $this->getMountPoint() . '/' . $path2;

		// check for update permissions on the share
		if ($this->isUpdatable('')) {

			$pathinfo = pathinfo($relPath1);
			// for part files we need to ask for the owner and path from the parent directory because
			// the file cache doesn't return any results for part files
			if (isset($pathinfo['extension']) && $pathinfo['extension'] === 'part') {
				list($user1, $path1) = \OCA\Files_Sharing\Helper::getUidAndFilename($pathinfo['dirname']);
				$path1 = $path1 . '/' . $pathinfo['basename'];
			} else {
				list($user1, $path1) = \OCA\Files_Sharing\Helper::getUidAndFilename($relPath1);
			}
			$targetFilename = basename($relPath2);
			list($user2, $path2) = \OCA\Files_Sharing\Helper::getUidAndFilename(dirname($relPath2));
			$rootView = new \OC\Files\View('');
			return $rootView->rename('/' . $user1 . '/files/' . $path1, '/' . $user2 . '/files/' . $path2 . '/' . $targetFilename);
		}

		return false;
	}

	public function copy($path1, $path2) {
		// Copy the file if CREATE permission is granted
		if ($this->isCreatable(dirname($path2))) {
			$oldSource = $this->getSourcePath($path1);
			$newSource = $this->getSourcePath(dirname($path2)) . '/' . basename($path2);
			$rootView = new \OC\Files\View('');
			return $rootView->copy($oldSource, $newSource);
		}
		return false;
	}

	public function fopen($path, $mode) {
		if ($source = $this->getSourcePath($path)) {
			switch ($mode) {
				case 'r+':
				case 'rb+':
				case 'w+':
				case 'wb+':
				case 'x+':
				case 'xb+':
				case 'a+':
				case 'ab+':
				case 'w':
				case 'wb':
				case 'x':
				case 'xb':
				case 'a':
				case 'ab':
					$exists = $this->file_exists($path);
					if ($exists && !$this->isUpdatable($path)) {
						return false;
					}
					if (!$exists && !$this->isCreatable(dirname($path))) {
						return false;
					}
			}
			$info = array(
				'target' => $this->getMountPoint() . $path,
				'source' => $source,
				'mode' => $mode,
			);
			\OCP\Util::emitHook('\OC\Files\Storage\Shared', 'fopen', $info);
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->fopen($internalPath, $mode);
		}
		return false;
	}

	public function getMimeType($path) {
		if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->getMimeType($internalPath);
		}
		return false;
	}

	public function free_space($path) {
		$source = $this->getSourcePath($path);
		if ($source) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->free_space($internalPath);
		}
		return \OCP\Files\FileInfo::SPACE_UNKNOWN;
	}

	public function getLocalFile($path) {
		if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->getLocalFile($internalPath);
		}
		return false;
	}

	public function touch($path, $mtime = null) {
		if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->touch($internalPath, $mtime);
		}
		return false;
	}

	public static function setup($options) {
		$shares = \OCP\Share::getItemsSharedWithUser('file', $options['user']);
		$manager = Filesystem::getMountManager();
		$loader = Filesystem::getLoader();
		if (!\OCP\User::isLoggedIn() || \OCP\User::getUser() != $options['user']
			|| $shares
		) {
			foreach ($shares as $share) {
				// don't mount shares where we have no permissions
				if ($share['permissions'] > 0) {
					$mount = new SharedMount(
							'\OC\Files\Storage\Shared',
							$options['user_dir'] . '/' . $share['file_target'],
							array(
								'share' => $share,
								'user' => $options['user']
							),
							$loader
							);
					$manager->addMount($mount);
				}
			}
		}
	}

	/**
	 * return mount point of share, relative to data/user/files
	 *
	 * @return string
	 */
	public function getMountPoint() {
		return $this->share['file_target'];
	}

	public function setMountPoint($path) {
		$this->share['file_target'] = $path;
	}

	public function getShareType() {
		return $this->share['share_type'];
	}

	/**
	 * does the group share already has a user specific unique name
	 * @return bool
	 */
	public function uniqueNameSet() {
		return (isset($this->share['unique_name']) && $this->share['unique_name']);
	}

	/**
	 * the share now uses a unique name of this user
	 *
	 * @brief the share now uses a unique name of this user
	 */
	public function setUniqueName() {
		$this->share['unique_name'] = true;
	}

	/**
	 * get share ID
	 * @return integer unique share ID
	 */
	public function getShareId() {
		return $this->share['id'];
	}

	/**
	 * get the user who shared the file
	 * @return string
	 */
	public function getSharedFrom() {
		return $this->share['uid_owner'];
	}

	/**
	 * @return array
	 */
	public function getShare() {
		return $this->share;
	}

	/**
	 * return share type, can be "file" or "folder"
	 * @return string
	 */
	public function getItemType() {
		return $this->share['item_type'];
	}

	public function hasUpdated($path, $time) {
		return $this->filemtime($path) > $time;
	}

	public function getCache($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		return new \OC\Files\Cache\Shared_Cache($storage);
	}

	public function getScanner($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		return new \OC\Files\Cache\SharedScanner($storage);
	}

	public function getWatcher($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		return new \OC\Files\Cache\Shared_Watcher($storage);
	}

	public function getOwner($path) {
		if ($path == '') {
			$path = $this->getMountPoint();
		}
		$source = $this->getFile($path);
		if ($source) {
			return $source['fileOwner'];
		}
		return false;
	}

	public function getETag($path) {
		if ($path == '') {
			$path = $this->getMountPoint();
		}
		if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->getETag($internalPath);
		}
		return null;
	}

	/**
	 * unshare complete storage, also the grouped shares
	 *
	 * @return bool
	 */
	public function unshareStorage() {
		$result = true;
		if (!empty($this->share['grouped'])) {
			foreach ($this->share['grouped'] as $share) {
				$result = $result && \OCP\Share::unshareFromSelf($share['item_type'], $share['file_target']);
			}
		}
		$result = $result && \OCP\Share::unshareFromSelf($this->getItemType(), $this->getMountPoint());

		return $result;
	}

}
