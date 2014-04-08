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

/**
 * Convert target path to source path and pass the function call to the correct storage provider
 */
class Shared extends \OC\Files\Storage\Common {

	private $mountPoint;   // mount point relative to data/user/files
	private $type;         // can be "file" or "folder"
	private $shareId;      // share Id to identify the share in the database
	private $fileSource;   // file cache ID of the shared item
	private $sharedFrom;   // the user who shared the file
	private $files = array();

	public function __construct($arguments) {
		$this->mountPoint = $arguments['shareTarget'];
		$this->type = $arguments['shareType'];
		$this->shareId = $arguments['shareId'];
		$this->fileSource = $arguments['fileSource'];
		$this->sharedFrom = $arguments['sharedFrom'];
	}

	/**
	 * @breif get id of the mount point
	 * @return string
	 */
	public function getId() {
		return 'shared::' . $this->mountPoint;
	}

	/**
	 * @breif get file cache of the shared item source
	 * @return string
	 */
	public function getSourceId() {
		return $this->fileSource;
	}

	/**
	 * @brief Get the source file path, permissions, and owner for a shared file
	 * @param string Shared target file path
	 * @param string $target
	 * @return Returns array with the keys path, permissions, and owner or false if not found
	 */
	public function getFile($target) {
		if (!isset($this->files[$target])) {
			// Check for partial files
			if (pathinfo($target, PATHINFO_EXTENSION) === 'part') {
				$source = \OC_Share_Backend_File::getSource(substr($target, 0, -5), $this->getMountPoint(), $this->getShareType());
				if ($source) {
					$source['path'] .= '.part';
					// All partial files have delete permission
					$source['permissions'] |= \OCP\PERMISSION_DELETE;
				}
			} else {
				$source = \OC_Share_Backend_File::getSource($target, $this->getMountPoint(), $this->getShareType());
			}
			$this->files[$target] = $source;
		}
		return $this->files[$target];
	}

	/**
	 * @brief Get the source file path for a shared file
	 * @param string Shared target file path
	 * @param string $target
	 * @return string source file path or false if not found
	 */
	public function getSourcePath($target) {
		$source = $this->getFile($target);
		if ($source) {
			if (!isset($source['fullPath'])) {
				\OC\Files\Filesystem::initMountPoints($source['fileOwner']);
				$mount = \OC\Files\Filesystem::getMountByNumericId($source['storage']);
				if (is_array($mount)) {
					$this->files[$target]['fullPath'] = $mount[key($mount)]->getMountPoint() . $source['path'];
				} else {
					$this->files[$target]['fullPath'] = false;
				}
			}
			return $this->files[$target]['fullPath'];
		}
		return false;
	}

	/**
	 * @brief Get the permissions granted for a shared file
	 * @param string Shared target file path
	 * @return int CRUDS permissions granted or false if not found
	 */
	public function getPermissions($target) {
		$source = $this->getFile($target);
		if ($source) {
			return $source['permissions'];
		}
		return false;
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

	public function rmdir($path) {
		if (($source = $this->getSourcePath($path)) && $this->isDeletable($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->rmdir($internalPath);
		}
		return false;
	}

	public function opendir($path) {
		if ($path == '' || $path == '/') {
			$files = \OCP\Share::getItemsSharedWith('file', \OC_Share_Backend_Folder::FORMAT_OPENDIR);
			\OC\Files\Stream\Dir::register($this->mountPoint, $files);
			return opendir('fakedir://' . $this->mountPoint);
		} else if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->opendir($internalPath);
		}
		return false;
	}

	public function is_dir($path) {
		if ($path == '' || $path == '/') {
			return true;
		} else if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->is_dir($internalPath);
		}
		return false;
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
		if ($path == '' || $path == '/' || $this->is_dir($path)) {
			return 0;
		} else if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->filesize($internalPath);
		}
		return false;
	}

	public function isCreatable($path) {
		if ($path == '') {
			return ($this->getPermissions($this->getMountPoint()) & \OCP\PERMISSION_CREATE);
		}
		return ($this->getPermissions($path) & \OCP\PERMISSION_CREATE);
	}

	public function isReadable($path) {
		return $this->file_exists($path);
	}

	public function isUpdatable($path) {
		if ($path == '') {
			return false;
		}
		return ($this->getPermissions($path) & \OCP\PERMISSION_UPDATE);
	}

	public function isDeletable($path) {
		if ($path == '') {
			return true;
		}
		return ($this->getPermissions($path) & \OCP\PERMISSION_DELETE);
	}

	public function isSharable($path) {
		if ($path == '') {
			return false;
		}
		return ($this->getPermissions($path) & \OCP\PERMISSION_SHARE);
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
		if ($path == '' || $path == '/') {
			$mtime = 0;
			$dh = $this->opendir($path);
			if (is_resource($dh)) {
				while (($filename = readdir($dh)) !== false) {
					$tempmtime = $this->filemtime($filename);
					if ($tempmtime > $mtime) {
						$mtime = $tempmtime;
					}
				}
			}
			return $mtime;
		} else {
			$source = $this->getSourcePath($path);
			if ($source) {
				list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
				return $storage->filemtime($internalPath);
			}
		}
	}

	public function file_get_contents($path) {
		$source = $this->getSourcePath($path);
		if ($source) {
			$info = array(
				'target' => $this->mountPoint . $path,
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
				'target' => $this->mountPoint . $path,
				'source' => $source,
			);
			\OCP\Util::emitHook('\OC\Files\Storage\Shared', 'file_put_contents', $info);
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			$result = $storage->file_put_contents($internalPath, $data);
			return $result;
		}
		return false;
	}

	public function unlink($path) {
		// Delete the file if DELETE permission is granted
		if ($source = $this->getSourcePath($path)) {
			if ($this->isDeletable($path)) {
				list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
				return $storage->unlink($internalPath);
			}
		}
		return false;
	}

	/**
	 * @brief Format a path to be relative to the /user/files/ directory
	 * @param string $path the absolute path
	 * @return string e.g. turns '/admin/files/test.txt' into '/test.txt'
	 */
	private static function stripUserFilesPath($path) {
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
	 * @brief rename a shared foder/file
	 * @param string $sourcePath
	 * @param string $targetPath
	 * @return bool
	 */
	private function renameMountPoint($sourcePath, $targetPath) {

		// it shoulbn't be possible to move a Shared storage into another one
		list($targetStorage, ) = \OC\Files\Filesystem::resolvePath($targetPath);
		if ($targetStorage instanceof \OC\Files\Storage\Shared) {
			\OCP\Util::writeLog('file sharing',
					'It is not allowed to move one mount point into another one',
					\OCP\Util::DEBUG);
			return false;
		}

		$relTargetPath = $this->stripUserFilesPath($targetPath);

		// rename mount point
		$query = \OC_DB::prepare(
				'Update `*PREFIX*share`
					SET `file_target` = ?
					WHERE `id` = ?'
				);

		$result = $query->execute(array($relTargetPath, $this->shareId));

		if ($result) {
			// update the mount manager with the new paths
			$mountManager = \OC\Files\Filesystem::getMountManager();
			$mount = $mountManager->find($sourcePath);
			$mount->setMountPoint($targetPath . '/');
			$mountManager->addMount($mount);
			$mountManager->removeMount($sourcePath . '/');

		} else {
			\OCP\Util::writeLog('file sharing',
					'Could not rename mount point for shared folder "' . $sourcePath . '" to "' . $targetPath . '"',
					\OCP\Util::ERROR);
		}

		return $result;
	}


	public function rename($path1, $path2) {

		$sourceMountPoint = \OC\Files\Filesystem::getMountPoint($path1);

		// if we renamed the mount point we need to adjust the file_target in the
		// database
		if (strlen($sourceMountPoint) >= strlen($path1)) {
			return $this->renameMountPoint($path1, $path2);
		}

		// Renaming/moving is only allowed within shared folders
		$oldSource = $this->getSourcePath($path1);
		if ($oldSource) {
			$newSource = $this->getSourcePath(dirname($path2)) . '/' . basename($path2);
			// Within the same folder, we only need UPDATE permissions
			if (dirname($path1) == dirname($path2) and $this->isUpdatable($path1)) {
				list($storage, $oldInternalPath) = \OC\Files\Filesystem::resolvePath($oldSource);
				list(, $newInternalPath) = \OC\Files\Filesystem::resolvePath($newSource);
				return $storage->rename($oldInternalPath, $newInternalPath);
				// otherwise DELETE and CREATE permissions required
			} elseif ($this->isDeletable($path1) && $this->isCreatable(dirname($path2))) {
				$rootView = new \OC\Files\View('');
				return $rootView->rename($oldSource, $newSource);
			}
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
				'target' => $this->mountPoint . $path,
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
		if ($path == '') {
			return \OC\Files\SPACE_UNKNOWN;
		}
		$source = $this->getSourcePath($path);
		if ($source) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->free_space($internalPath);
		}
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
		$shares = \OCP\Share::getItemsSharedWith('file');
		if (!\OCP\User::isLoggedIn() || \OCP\User::getUser() != $options['user']
			|| $shares
		) {
			foreach ($shares as $share) {
				\OC\Files\Filesystem::mount('\OC\Files\Storage\Shared',
						array(
							'shareTarget' => $share['file_target'],
							'shareType' => $share['item_type'],
							'shareId' => $share['id'],
							'fileSource' => $share['file_source'],
							'sharedFrom' => $share['uid_owner'],
							),
						$options['user_dir'] . '/' . $share['file_target']);
			}
		}
	}

	/**
	 * @brief return mount point of share, relative to data/user/files
	 * @return string
	 */
	public function getMountPoint() {
		return ltrim($this->mountPoint, '/');
	}

	/**
	 * @brief get the user who shared the file
	 * @return string
	 */
	public function getSharedFrom() {
		return $this->sharedFrom;
	}

	/**
	 * @brief return share type, can be "file" or "folder"
	 * @return string
	 */
	public function getShareType() {
		return $this->type;
	}

	public function hasUpdated($path, $time) {
		if ($path == '') {
			return false;
		}
		return $this->filemtime($path) > $time;
	}

	public function getCache($path = '') {
		return new \OC\Files\Cache\Shared_Cache($this);
	}

	public function getScanner($path = '') {
		return new \OC\Files\Cache\Scanner($this);
	}

	public function getPermissionsCache($path = '') {
		return new \OC\Files\Cache\Shared_Permissions($this);
	}

	public function getWatcher($path = '') {
		return new \OC\Files\Cache\Shared_Watcher($this);
	}

	public function getOwner($path) {
		if ($path == '') {
			return false;
		}
		$source = $this->getFile($path);
		if ($source) {
			return $source['fileOwner'];
		}
		return false;
	}

	public function getETag($path) {
		if ($path == '') {
			return parent::getETag($path);
		}
		if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->getETag($internalPath);
		}
		return null;
	}

}
