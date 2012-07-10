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
	private $sourcePaths = array();
	
	public function __construct($arguments) {
		$this->sharedFolder = $arguments['sharedFolder'];
	}

	private function getSourcePath($target) {
		$target = $this->sharedFolder.'/'.$target;
		$target = rtrim($target, '/');
		if (isset($this->sourcePaths[$target])) {
			return $this->sourcePaths[$target];
		} else {
			$pos = strpos($target, '/', 8);
			// Get shared folder name
			if ($pos !== false) {
				$itemTarget = substr($target, 0, $pos);
			} else {
				$itemTarget = $target;
			}
			$sourcePath = OCP\Share::getItemSharedWith('file', $itemTarget, OC_Share_Backend_File::FORMAT_SOURCE_PATH);
			if ($sourcePath) {
				$this->sourcePaths[$target] = $sourcePath.substr($target, strlen($itemTarget));
				return $this->sourcePaths[$target];
			}
			OCP\Util::writeLog('files_sharing', 'File source path not found for: '.$target, OCP\Util::ERROR);
			return false;
		}
	}

	private function getInternalPath($path) {
		$mountPoint = OC_Filesystem::getMountPoint($path);
		$internalPath = substr($path, strlen($mountPoint));
		return $internalPath;
	}
	
	public function mkdir($path) {
		if ($path == "" || $path == "/" || !$this->is_writable($path)) {
			return false; 
		} else {
			if ($source = $this->getSourcePath($path)) {
				$storage = OC_Filesystem::getStorage($source);
				return $storage->mkdir($this->getInternalPath($source));
			}
		}
	}
	
	public function rmdir($path) {
		// The folder will be removed from the database, but won't be deleted from the owner's filesystem
		// TODO
	}
	
	public function opendir($path) {
		if ($path == '' || $path == '/') {
			$files = OCP\Share::getItemsSharedWith('file', OC_Share_Backend_Folder::FORMAT_OPENDIR);
			OC_FakeDirStream::$dirs['shared'] = $files;
			return opendir('fakedir://shared');
		} else {
			if ($source = $this->getSourcePath($path)) {
				$storage = OC_Filesystem::getStorage($source);
				return $storage->opendir($this->getInternalPath($source));
			}
		}
	}

	public function is_dir($path) {
		if ($path == '' || $path == '/') {
			return true;
		} else {
			if ($source = $this->getSourcePath($path)) {
				$storage = OC_Filesystem::getStorage($source);
				return $storage->is_dir($this->getInternalPath($source));
			}
		}
	}

	public function is_file($path) {
		if ($source = $this->getSourcePath($path)) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->is_file($this->getInternalPath($source));
		}
	}

	public function stat($path) {
		if ($path == '' || $path == '/') {
			$stat['size'] = $this->filesize($path);
			$stat['mtime'] = $this->filemtime($path);
			$stat['ctime'] = $this->filectime($path);
			return $stat;
		} else {
			if ($source = $this->getSourcePath($path)) {
				$storage = OC_Filesystem::getStorage($source);
				return $storage->stat($this->getInternalPath($source));
			}
		}
	}

	public function filetype($path) {
		if ($path == "" || $path == "/") {
			return "dir";
		} else {
			$source = $this->getSourcePath($path);
			if ($source) {
				$storage = OC_Filesystem::getStorage($source);
				return $storage->filetype($this->getInternalPath($source));
			}
		}

	}

	public function filesize($path) {
		if ($path == "" || $path == "/" || $this->is_dir($path)) {
			return $this->getFolderSize($path);
		} else {
			$source = $this->getSourcePath($path);
			if ($source) {
				$storage = OC_Filesystem::getStorage($source);
				return $storage->filesize($this->getInternalPath($source));
			}
		}
	}

	public function is_readable($path) {
		return true;
	}
	
	public function is_writable($path) {
		if($path == "" || $path == "/"){
			return false;
		}elseif (OC_Share::getPermissions($this->sharedFolder.$path) & OC_Share::WRITE) {
			return true;
		} else {
			return false;
		}
	}
	
	public function file_exists($path) {
		if ($path == "" || $path == "/") {
			return true;
		} else {
			$source = $this->getSourcePath($path);
			if ($source) {
				$storage = OC_Filesystem::getStorage($source);
				return $storage->file_exists($this->getInternalPath($source));
			}
		}
	}
	
	public function filectime($path) {
		if ($path == "" || $path == "/") {
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
		if ($path == "" || $path == "/") {
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
		if ($this->is_writable($path)) {
			$source = $this->getSourcePath($path);
			if ($source) {
				$info = array(
						'target' => $this->sharedFolder.$path,
						'source' => $source,
					     );
				OCP\Util::emitHook('OC_Filestorage_Shared', 'file_put_contents', $info);
				$storage = OC_Filesystem::getStorage($source);
				$result = $storage->file_put_contents($this->getInternalPath($source), $data);
				if ($result) {
					$this->clearFolderSizeCache($path);
				}
				return $result;
			}
		}
	}
	
	public function unlink($path) {
		// The item will be removed from the database, but won't be touched on the owner's filesystem
		$target = $this->sharedFolder.$path;
		// Check if the item is inside a shared folder
		if (OC_Share::getParentFolders($target)) {
			// If entry for item already exists
			if (OC_Share::getItem($target)) {
				OC_Share::unshareFromMySelf($target, false);
			} else {
				OC_Share::pullOutOfFolder($target, $target);
				OC_Share::unshareFromMySelf($target, false);
			}
		// Delete the database entry
		} else {
			OC_Share::unshareFromMySelf($target);
		}
		$this->clearFolderSizeCache($this->getInternalPath($target));
		return true;
	}
	
	public function rename($path1, $path2) {
		$oldTarget = $this->sharedFolder.$path1;
		$newTarget = $this->sharedFolder.$path2;
		// Check if the item is inside a shared folder
		if ($folders = OC_Share::getParentFolders($oldTarget)) {
			$root1 = substr($path1, 0, strpos($path1, "/"));
			$root2 = substr($path1, 0, strpos($path2, "/"));
			// Prevent items from being moved into different shared folders until versioning (cut and paste) and prevent items going into 'Shared'
			if ($root1 !== $root2) {
				return false;
			// Check if both paths have write permission
			} else if ($this->is_writable($path1) && $this->is_writable($path2)) {
				$oldSource = $this->getSourcePath($path1);
				$newSource = $folders['source'].substr($newTarget, strlen($folders['target']));
				if ($oldSource) {
					$storage = OC_Filesystem::getStorage($oldSource);
					return $storage->rename($this->getInternalPath($oldSource), $this->getInternalPath($newSource));
				}
			// If the user doesn't have write permission, items can only be renamed and not moved
			} else if (dirname($path1) !== dirname($path2)) {
				return false;
			// The item will be renamed in the database, but won't be touched on the owner's filesystem
			} else {
				OC_Share::pullOutOfFolder($oldTarget, $newTarget);
				// If this is a folder being renamed, call setTarget in case there are any database entries inside the folder
				if (self::is_dir($path1)) {
					OC_Share::setTarget($oldTarget, $newTarget);
				}
			}
		} else {
			OC_Share::setTarget($oldTarget, $newTarget);
		}
		$this->clearFolderSizeCache($this->getInternalPath($oldTarget));
		$this->clearFolderSizeCache($this->getInternalPath($newTarget));
		return true;
	}
	
	public function copy($path1, $path2) {
		if ($path2 == "" || $path2 == "/") {
			// TODO Construct new shared item or should this not be allowed?
		} else {
			if ($this->is_writable($path2)) {
				$tmpFile = $this->toTmpFile($path1);
				$result = $this->fromTmpFile($tmpFile, $path2);
				if ($result) {
					$this->clearFolderSizeCache($path2);
				}
				return $result;
			} else {
				return false;
			}
		}
	}
	
	public function fopen($path, $mode) {
		$source = $this->getSourcePath($path);
		if ($source) {
			$info = array(
				'target' => $this->sharedFolder.$path,
				'source' => $source,
				'mode' => $mode,
			);
			OCP\Util::emitHook('OC_Filestorage_Shared', 'fopen', $info);
			$storage = OC_Filesystem::getStorage($source);
			return $storage->fopen($this->getInternalPath($source), $mode);
		}
	}
	
	public function toTmpFile($path) {
		$source = $this->getSourcePath($path);
		if ($source) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->toTmpFile($this->getInternalPath($source));
		}
	}
	
	public function fromTmpFile($tmpFile, $path) {
		if ($this->is_writable($path)) {
			$source = $this->getSourcePath($path);
			if ($source) {
				$storage = OC_Filesystem::getStorage($source);
				$result = $storage->fromTmpFile($tmpFile, $this->getInternalPath($source));
				if ($result) {
					$this->clearFolderSizeCache($path);
				}
				return $result;
			}
		} else {
			return false;
		}
	}
	
	public function getMimeType($path) {
		if ($path == "" || $path == "/") {
			return 'httpd/unix-directory';
		}
		$source = $this->getSourcePath($path);
		if ($source) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->getMimeType($this->getInternalPath($source));
		}
	}
	
	public function hash($type, $path, $raw) {
		$source = $this->getSourcePath($path);
		if ($source) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->hash($type, $this->getInternalPath($source), $raw);
		}
	}
	
	public function free_space($path) {
		$source = $this->getSourcePath($path);
		if ($source) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->free_space($this->getInternalPath($source));
		}
	}
	
	public function search($query) {
		return $this->searchInDir($query);
	}

	protected function searchInDir($query, $path = "") {
		$files = array();
		if ($dh = $this->opendir($path)) {
			while (($filename = readdir($dh)) !== false) {
				if ($filename != "." && $filename != "..") {
					if (strstr(strtolower($filename), strtolower($query))) {
						$files[] = $path."/".$filename;
					}
					if ($this->is_dir($path."/".$filename)) {
						$files = array_merge($files, $this->searchInDir($query, $path."/".$filename));
					}
				}
			}
		}
		return $files;
	}

	public function getLocalFile($path) {
		$source = $this->getSourcePath($path);
		if ($source) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->getLocalFile($this->getInternalPath($source));
		}
	}
	public function touch($path, $mtime=null){
		$source = $this->getSourcePath($path);
		if ($source) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->touch($this->getInternalPath($source),$time);
		}
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
	public function hasUpdated($path,$time){
		//TODO
		return false;
	}
}
