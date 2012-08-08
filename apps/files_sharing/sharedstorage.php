<?php
/**
 * ownCloud
 *
 * @author Michael Gapczynski
 * @copyright 2011 Michael Gapczynski GapczynskiM@gmail.com
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

require_once( 'lib_share.php' );

/**
 * Convert target path to source path and pass the function call to the correct storage provider
 */
class OC_Filestorage_Shared extends OC_Filestorage {
	
	private $datadir;
	private $sourcePaths = array();
	
	public function __construct($arguments) {
		$this->datadir = $arguments['datadir'];
		$this->datadir .= "/";
	}
	
	public function getInternalPath($path) {
		$mountPoint = OC_Filesystem::getMountPoint($path);
		$internalPath = substr($path, strlen($mountPoint));
		return $internalPath;
	}
	
	public function getSource($target) {
		$target = $this->datadir.$target;
		if (array_key_exists($target, $this->sourcePaths)) {
			return $this->sourcePaths[$target];
		} else {
			$source = OC_Share::getSource($target);
			$this->sourcePaths[$target] = $source;
			return $source;
		}
	}
	
	public function mkdir($path) {
		if ($path == "" || $path == "/" || !$this->is_writable($path)) {
			return false; 
		} else {
			$source = $this->getSource($path);
			if ($source) {
				$storage = OC_Filesystem::getStorage($source);
				return $storage->mkdir($this->getInternalPath($source));
			}
		}
	}
	
	public function rmdir($path) {
		// The folder will be removed from the database, but won't be deleted from the owner's filesystem
		OC_Share::unshareFromMySelf($this->datadir.$path);
		$this->clearFolderSizeCache($path);
	}
	
	public function opendir($path) {
		if ($path == "" || $path == "/") {
			$path = $this->datadir.$path;
			$sharedItems = OC_Share::getItemsInFolder($path);
			$files = array();
			foreach ($sharedItems as $item) {
				// If item is in the root of the shared storage provider and the item exists add it to the fakedirs
				if (dirname($item['target'])."/" == $path && $this->file_exists(basename($item['target']))) {
					$files[] = basename($item['target']);
				}
			}
			OC_FakeDirStream::$dirs['shared'.$path] = $files;
			return opendir('fakedir://shared'.$path);
		} else {
			$source = $this->getSource($path);
			if ($source) {
				$storage = OC_Filesystem::getStorage($source);
				$dh = $storage->opendir($this->getInternalPath($source));
				$modifiedItems = OC_Share::getItemsInFolder($source);
				if ($modifiedItems && $dh) {
					$sources = array();
					$targets = array();
					// Remove any duplicate or trailing '/'
					$path = preg_replace('{(/)\1+}', "/", $path);
					$targetFolder = rtrim($this->datadir.$path, "/");
					foreach ($modifiedItems as $item) {
						// If the item is in the current directory and the item exists add it to the arrays
						if (dirname($item['target']) == $targetFolder && $this->file_exists($path."/".basename($item['target']))) {
							// If the item was unshared from self, add it it to the arrays
							if ($item['permissions'] == OC_Share::UNSHARED) {
								$sources[] = basename($item['source']);
								$targets[] = "";
							} else {
								$sources[] = basename($item['source']);
								$targets[] = basename($item['target']);
							}
						}
					}
					// Don't waste time if there aren't any modified items in the current directory
					if (empty($sources)) {
						return $dh;
					} else {
						global $FAKEDIRS;
						$files = array();
						while (($filename = readdir($dh)) !== false) {
							if ($filename != "." && $filename != "..") {
								// If the file isn't in the sources array it isn't modified and can be added as is
								if (!in_array($filename, $sources)) {
									$files[] = $filename;
								// The file has a different name than the source and is added to the fakedirs
								} else {
									$target = $targets[array_search($filename, $sources)];
									// Don't add the file if it was unshared from self by the user
									if ($target != "") {
										$files[] = $target;
									}
								}
							}
						}
						$FAKEDIRS['shared'] = $files;
						return opendir('fakedir://shared');
					}
				} else {
					return $dh;
				}
			}
		}
	}
	
	public function is_dir($path) {
		if ($path == "" || $path == "/") {
			return true;
		} else {
			$source = $this->getSource($path);
			if ($source) {
				$storage = OC_Filesystem::getStorage($source);
				return $storage->is_dir($this->getInternalPath($source));
			}
		}
	}
	
	public function is_file($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->is_file($this->getInternalPath($source));
		}
	}
	
	// TODO fill in other components of array
	public function stat($path) {
		if ($path == "" || $path == "/") {
			$stat["size"] = $this->filesize($path);
			$stat["mtime"] = $this->filemtime($path);
			$stat["ctime"] = $this->filectime($path);
			return $stat;
		} else {
			$source = $this->getSource($path);
			if ($source) {
				$storage = OC_Filesystem::getStorage($source);
				return $storage->stat($this->getInternalPath($source));
			}
		}
	}
	
	public function filetype($path) {
		if ($path == "" || $path == "/") {
			return "dir";
		} else {
			$source = $this->getSource($path);
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
			$source = $this->getSource($path);
			if ($source) {
				$storage = OC_Filesystem::getStorage($source);
				return $storage->filesize($this->getInternalPath($source));
			}
		}
	}

	public function getFolderSize($path) {
		return 0; //depricated
	}
	
	private function calculateFolderSize($path) {
		if ($this->is_file($path)) {
			$path = dirname($path);
		}
		$size = 0;
		if ($dh = $this->opendir($path)) {
			while (($filename = readdir($dh)) !== false) {
				if ($filename != "." && $filename != "..") {
					$subFile = $path."/".$filename;
					if ($this->is_file($subFile)) {
						$size += $this->filesize($subFile);
					} else {
						$size += $this->getFolderSize($subFile);
					}
				}
			}
			if ($size > 0) {
				$dbpath = rtrim($this->datadir.$path, "/");
// 				$query = OCP\DB::prepare("INSERT INTO *PREFIX*foldersize VALUES(?,?)");
// 				$result = $query->execute(array($dbpath, $size));
			}
		}
		return $size;
	}

	private function clearFolderSizeCache($path) {
		$path = rtrim($path, "/");
		$path = preg_replace('{(/)\1+}', "/", $path);
		if ($this->is_file($path)) {
			$path = dirname($path);
		}
		$dbpath = rtrim($this->datadir.$path, "/");
// 		$query = OCP\DB::prepare("DELETE FROM *PREFIX*/*foldersize*/ WHERE path = ?");
// 		$result = $query->execute(array($dbpath));
		if ($path != "/" && $path != "") {
			$parts = explode("/", $path);
			$part = array_pop($parts);
			if (empty($part)) {
				array_pop($parts);
			}
			$parent = implode("/", $parts);
			$this->clearFolderSizeCache($parent);
		}
	}

	public function is_readable($path) {
		return true;
	}
	
	public function is_writable($path) {
		if($path == "" || $path == "/"){
			return false;
		}elseif (OC_Share::getPermissions($this->datadir.$path) & OC_Share::WRITE) {
			return true;
		} else {
			return false;
		}
	}
	
	public function file_exists($path) {
		if ($path == "" || $path == "/") {
			return true;
		} else {
			$source = $this->getSource($path);
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
			$source = $this->getSource($path);
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
			$source = $this->getSource($path);
			if ($source) {
				$storage = OC_Filesystem::getStorage($source);
				return $storage->filemtime($this->getInternalPath($source));
			}
		}
	}
	
	public function file_get_contents($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->file_get_contents($this->getInternalPath($source));
		}
	}
	
	public function file_put_contents($path, $data) {
		if ($this->is_writable($path)) {
			$source = $this->getSource($path);
			if ($source) {
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
		$target = $this->datadir.$path;
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
		$oldTarget = $this->datadir.$path1;
		$newTarget = $this->datadir.$path2;
		// Check if the item is inside a shared folder
		if ($folders = OC_Share::getParentFolders($oldTarget)) {
			$root1 = substr($path1, 0, strpos($path1, "/"));
			$root2 = substr($path1, 0, strpos($path2, "/"));
			// Prevent items from being moved into different shared folders until versioning (cut and paste) and prevent items going into 'Shared'
			if ($root1 !== $root2) {
				return false;
			// Check if both paths have write permission
			} else if ($this->is_writable($path1) && $this->is_writable($path2)) {
				$oldSource = $this->getSource($path1);
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
		$source = $this->getSource($path);
		if ($source) {
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
					if (!$this->is_writable($path)) {
						return false;
					}
			}
			$storage = OC_Filesystem::getStorage($source);
			return $storage->fopen($this->getInternalPath($source), $mode);
		}
	}
	
	public function toTmpFile($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->toTmpFile($this->getInternalPath($source));
		}
	}
	
	public function fromTmpFile($tmpFile, $path) {
		if ($this->is_writable($path)) {
			$source = $this->getSource($path);
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
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->getMimeType($this->getInternalPath($source));
		}
	}
	
	public function hash($type, $path, $raw = false) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->hash($type, $this->getInternalPath($source), $raw);
		}
	}
	
	public function free_space($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->free_space($this->getInternalPath($source));
		}
	}
	
	public function search($query) {
		return $this->searchInDir($query);
	}

	private function searchInDir($query, $path = "") {
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
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->getLocalFile($this->getInternalPath($source));
		}
	}
	public function touch($path, $mtime=null){
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_Filesystem::getStorage($source);
			return $storage->touch($this->getInternalPath($source),$time);
		}
	}

	public static function setup() {
		OC_Filesystem::mount('OC_Filestorage_Shared', array('datadir' => '/'.OCP\USER::getUser().'/files/Shared'), '/'.OCP\USER::getUser().'/files/Shared/');
	}

}

if (OCP\USER::isLoggedIn()) {
	OC_Filestorage_Shared::setup();
} else {
	OCP\Util::connectHook('OC_User', 'post_login', 'OC_Filestorage_Shared', 'setup');
}

?>
