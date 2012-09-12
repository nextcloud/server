<?php
/**
 * ownCloud
 *
 * @author Michael Gapczynski
 * @copyright 2011 Michael Gapczynski mtgap@owncloud.com
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

/**
 * Convert target path to source path and pass the function call to the correct storage provider
 */
class OC_Filestorage_Shared extends OC_Filestorage_Common {

	private $sharedFolder;
	private $files = array();

	public function __construct($arguments) {
		$this->sharedFolder = $arguments['sharedFolder'];
	}

	/**
	* @brief Get the source file path and the permissions granted for a shared file
	* @param string Shared target file path
	* @return Returns array with the keys path and permissions or false if not found
	*/
	private function getFile($target) {
		$target = '/'.$target;
		$target = rtrim($target, '/');
		if (isset($this->files[$target])) {
			return $this->files[$target];
		} else {
			$pos = strpos($target, '/', 1);
			// Get shared folder name
			if ($pos !== false) {
				$folder = substr($target, 0, $pos);
				if (isset($this->files[$folder])) {
					$file = $this->files[$folder];
				} else {
					$file = OCP\Share::getItemSharedWith('folder', $folder, OC_Share_Backend_File::FORMAT_SHARED_STORAGE);
				}
				if ($file) {
					$this->files[$target]['path'] = $file['path'].substr($target, strlen($folder));
					$this->files[$target]['permissions'] = $file['permissions'];
					return $this->files[$target];
				}
			} else {
				$file = OCP\Share::getItemSharedWith('file', $target, OC_Share_Backend_File::FORMAT_SHARED_STORAGE);
				if ($file) {
					$this->files[$target] = $file;
					return $this->files[$target];
				}
			}
			OCP\Util::writeLog('files_sharing', 'File source not found for: '.$target, OCP\Util::ERROR);
			return false;
		}
	}

	/**
	* @brief Get the source file path for a shared file
	* @param string Shared target file path
	* @return Returns source file path or false if not found
	*/
	private function getSourcePath($target) {
		$file = $this->getFile($target);
		if (isset($file['path'])) {
			$uid = substr($file['path'], 1, strpos($file['path'], '/', 1) - 1);
			OC_Filesystem::mount('OC_Filestorage_Local', array('datadir' => OC_User::getHome($uid)), $uid);
			return $file['path'];
		}
		return false;
	}

	/**
	* @brief Get the permissions granted for a shared file
	* @param string Shared target file path
	* @return Returns CRUDS permissions granted or false if not found
	*/
	private function getPermissions($target) {
		$file = $this->getFile($target);
		if (isset($file['permissions'])) {
			return $file['permissions'];
		}
		return false;
	}

	/**
	* @brief Get the internal path to pass to the storage filesystem call
	* @param string Source file path
	* @return Source file path with mount point stripped out
	*/
	private function getInternalPath($path) {
		$mountPoint = OC_Filesystem::getMountPoint($path);
		$internalPath = substr($path, strlen($mountPoint));
		return $internalPath;
	}

	public function mkdir($path) {
		if ($path == '' || $path == '/' || !$this->isCreatable(dirname($path))) {
			return false;
		} else if ($source = $this->getSourcePath($path)) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->mkdir($this->getInternalPath($source));
		}
		return false;
	}

	public function rmdir($path) {
		if (($source = $this->getSourcePath($path)) && $this->isDeletable($path)) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->rmdir($this->getInternalPath($source));
		}
		return false;
	}

	public function opendir($path) {
		if ($path == '' || $path == '/') {
			$files = OCP\Share::getItemsSharedWith('file', OC_Share_Backend_Folder::FORMAT_OPENDIR);
			OC_FakeDirStream::$dirs['shared'] = $files;
			return opendir('fakedir://shared');
		} else if ($source = $this->getSourcePath($path)) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->opendir($this->getInternalPath($source));
		}
		return false;
	}

	public function is_dir($path) {
		if ($path == '' || $path == '/') {
			return true;
		} else if ($source = $this->getSourcePath($path)) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->is_dir($this->getInternalPath($source));
		}
		return false;
	}

	public function is_file($path) {
		if ($source = $this->getSourcePath($path)) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->is_file($this->getInternalPath($source));
		}
		return false;
	}

	public function stat($path) {
		if ($path == '' || $path == '/') {
			$stat['size'] = $this->filesize($path);
			$stat['mtime'] = $this->filemtime($path);
			$stat['ctime'] = $this->filectime($path);
			return $stat;
		} else if ($source = $this->getSourcePath($path)) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->stat($this->getInternalPath($source));
		}
		return false;
	}

	public function filetype($path) {
		if ($path == '' || $path == '/') {
			return 'dir';
		} else if ($source = $this->getSourcePath($path)) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->filetype($this->getInternalPath($source));
		}
		return false;
	}

	public function filesize($path) {
		if ($path == '' || $path == '/' || $this->is_dir($path)) {
			return 0;
		} else if ($source = $this->getSourcePath($path)) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->filesize($this->getInternalPath($source));
		}
		return false;
	}

	public function isCreatable($path) {
		if ($path == '') {
			return false;
		}
		return ($this->getPermissions($path) & OCP\Share::PERMISSION_CREATE);
	}

	public function isReadable($path) {
		return $this->file_exists($path);
	}

	public function isUpdatable($path) {
		if ($path == '') {
			return false;
		}
		return ($this->getPermissions($path) & OCP\Share::PERMISSION_UPDATE);
	}

	public function isDeletable($path) {
		if ($path == '') {
			return true;
		}
		return ($this->getPermissions($path) & OCP\Share::PERMISSION_DELETE);
	}

	public function isSharable($path) {
		if ($path == '') {
			return false;
		}
		return ($this->getPermissions($path) & OCP\Share::PERMISSION_SHARE);
	}

	public function file_exists($path) {
		if ($path == '' || $path == '/') {
			return true;
		} else if ($source = $this->getSourcePath($path)) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->file_exists($this->getInternalPath($source));
		}
		return false;
	}

	public function filectime($path) {
		if ($path == '' || $path == '/') {
			$ctime = 0;
			if ($dh = $this->opendir($path)) {
				while (($filename = readdir($dh)) !== false) {
					$tempctime = $this->filectime($filename);
					if ($tempctime < $ctime) {
						$ctime = $tempctime;
					}
				}
			}
			return $ctime;
		} else {
			$source = $this->getSourcePath($path);
			if ($source) {
				$storage = OC_Filesystem::getStorage($source);
				return $storage->filectime($this->getInternalPath($source));
			}
		}
	}

	public function filemtime($path) {
		if ($path == '' || $path == '/') {
			$mtime = 0;
			if ($dh = $this->opendir($path)) {
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
				$storage = OC_Filesystem::getStorage($source);
				return $storage->filemtime($this->getInternalPath($source));
			}
		}
	}

	public function file_get_contents($path) {
		$source = $this->getSourcePath($path);
		if ($source) {
			$info = array(
				'target' => $this->sharedFolder.$path,
				'source' => $source,
			);
			OCP\Util::emitHook('OC_Filestorage_Shared', 'file_get_contents', $info);
			$storage = OC_Filesystem::getStorage($source);
			return $storage->file_get_contents($this->getInternalPath($source));
		}
	}

	public function file_put_contents($path, $data) {
		if ($source = $this->getSourcePath($path)) {
			// Check if permission is granted
			if (($this->file_exists($path) && !$this->isUpdatable($path)) || ($this->is_dir($path) && !$this->isCreatable($path))) {
				return false;
			}
			$info = array(
					'target' => $this->sharedFolder.$path,
					'source' => $source,
				);
			OCP\Util::emitHook('OC_Filestorage_Shared', 'file_put_contents', $info);
			$storage = OC_Filesystem::getStorage($source);
			$result = $storage->file_put_contents($this->getInternalPath($source), $data);
			return $result;
		}
		return false;
	}

	public function unlink($path) {
		// Delete the file if DELETE permission is granted
		if ($source = $this->getSourcePath($path)) {
			if ($this->isDeletable($path)) {
				$storage = OC_Filesystem::getStorage($source);
				return $storage->unlink($this->getInternalPath($source));
			} else if (dirname($path) == '/' || dirname($path) == '.') {
				// Unshare the file from the user if in the root of the Shared folder
				if ($this->is_dir($path)) {
					$itemType = 'folder';
				} else {
					$itemType = 'file';
				}
				return OCP\Share::unshareFromSelf($itemType, $path);
			}
		}
		return false;
	}

	public function rename($path1, $path2) {
		// Renaming/moving is only allowed within shared folders
		$pos1 = strpos($path1, '/', 1);
		$pos2 = strpos($path2, '/', 1);
		if ($pos1 !== false && $pos2 !== false && ($oldSource = $this->getSourcePath($path1))) {
			$newSource = $this->getSourcePath(dirname($path2)).'/'.basename($path2);
			if (dirname($path1) == dirname($path2)) {
				// Rename the file if UPDATE permission is granted
				if ($this->isUpdatable($path1)) {
					$storage = OC_Filesystem::getStorage($oldSource);
					return $storage->rename($this->getInternalPath($oldSource), $this->getInternalPath($newSource));
				}
			} else {
				// Move the file if DELETE and CREATE permissions are granted
				if ($this->isDeletable($path1) && $this->isCreatable(dirname($path2))) {
					// Get the root shared folder
					$folder1 = substr($path1, 0, $pos1);
					$folder2 = substr($path2, 0, $pos2);
					// Copy and unlink the file if it exists in a different shared folder
					if ($folder1 != $folder2) {
						if ($this->copy($path1, $path2)) {
							return $this->unlink($path1);
						}
					} else {
						$storage = OC_Filesystem::getStorage($oldSource);
						return $storage->rename($this->getInternalPath($oldSource), $this->getInternalPath($newSource));
					}
				}
			}
		}
		return false;
	}

	public function copy($path1, $path2) {
		// Copy the file if CREATE permission is granted
		if ($this->isCreatable(dirname($path2))) {
			$source = $this->fopen($path1, 'r');
			$target = $this->fopen($path2, 'w');
			return OC_Helper::streamCopy($source, $target);
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
					if (!$this->isUpdatable($path)) {
						return false;
					}
			}
			$info = array(
				'target' => $this->sharedFolder.$path,
				'source' => $source,
				'mode' => $mode,
			);
			OCP\Util::emitHook('OC_Filestorage_Shared', 'fopen', $info);
			$storage = OC_Filesystem::getStorage($source);
			return $storage->fopen($this->getInternalPath($source), $mode);
		}
		return false;
	}

	public function getMimeType($path) {
		if ($path == '' || $path == '/') {
			return 'httpd/unix-directory';
		}
		if ($source = $this->getSourcePath($path)) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->getMimeType($this->getInternalPath($source));
		}
		return false;
	}

	public function free_space($path) {
		$source = $this->getSourcePath($path);
		if ($source) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->free_space($this->getInternalPath($source));
		}
	}

	public function getLocalFile($path) {
		if ($source = $this->getSourcePath($path)) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->getLocalFile($this->getInternalPath($source));
		}
		return false;
	}
	public function touch($path, $mtime = null) {
		if ($source = $this->getSourcePath($path)) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->touch($this->getInternalPath($source), $mtime);
		}
		return false;
	}

	public static function setup($options) {
		$user_dir = $options['user_dir'];
		OC_Filesystem::mount('OC_Filestorage_Shared', array('sharedFolder' => '/Shared'), $user_dir.'/Shared/');
	}

	/**
	 * check if a file or folder has been updated since $time
	 * @param int $time
	 * @return bool
	 */
	public function hasUpdated($path,$time) {
		//TODO
		return false;
	}
}
