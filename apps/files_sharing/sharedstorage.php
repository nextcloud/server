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

OC_FILESYSTEM::registerStorageType('shared','OC_FILESTORAGE_SHARED',array('datadir'=>'string'));

/**
 * Convert target path to source path and pass the function call to the correct storage provider
 */
class OC_FILESTORAGE_SHARED {
	
	private $sourcePaths = array();
	
	// TODO uh... I don't know what to do here
	public function __construct($parameters) {
		
	}
	
	public function getInternalPath($path) {
		$mountPoint = OC_FILESYSTEM::getMountPoint($path);
		$internalPath = substr($path, strlen($mountPoint));
		return $internalPath;
	}
	
	public function getSource($target) {
		$target = OC_FILESYSTEM::getStorageMountPoint($this).$target;
		if (array_key_exists($target, $this->sourcePaths)) {
			return $this->sourcePaths[$target];
		} else {
			$source = OC_SHARE::getSource($target);
			$this->sourcePaths[$target] = $source;
			return $source;
		}
	}
	
	// TODO OC_SHARE::getPermissions()
	public function mkdir($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->mkdir($this->getInternalPath($source));
		}
	}
	
	// TODO OC_SHARE::getPermissions()
	public function rmdir($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->rmdir($this->getInternalPath($source));
		}
	}
	
	public function opendir($path) {
		if ($path == "" || $path == "/") {
			global $FAKEDIRS;
			$sharedItems = OC_SHARE::getItemsSharedWith();
			foreach ($sharedItems as $item) {
				$files[] = $item['target'];
			}
			$FAKEDIRS['shared'] = $files;
			return opendir('fakedir://shared');
		} else {
			$source = $this->getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->opendir($this->getInternalPath($source));
			}
		}
	}
	
	public function is_dir($path) {
		if ($path == "" || $path == "/") {
			return true;
		} else {
			$source = $this->getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->is_dir($this->getInternalPath($source));
			}
		}
	}
	
	public function is_file($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->is_file($this->getInternalPath($source));
		}
	}
	
	// TODO fill in other components of array
	public function stat($path) {
		if ($path == "" || $path == "/") {
			$stat["dev"] = "";
			$stat["ino"] = "";
			$stat["mode"] = "";
			$stat["nlink"] = "";
			$stat["uid"] = "";
			$stat["gid"] = "";
			$stat["rdev"] = "";
			$stat["size"] = $this->filesize($path);
			$stat["atime"] = $this->fileatime($path);
			$stat["mtime"] = $this->filemtime($path);
			$stat["ctime"] = $this->filectime($path);
			$stat["blksize"] = "";
			$stat["blocks"] = "";
			return $stat;
		} else {
			$source = $this->getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
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
				$storage = OC_FILESYSTEM::getStorage($source);
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
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->filesize($this->getInternalPath($source));
			}
		}
	}
	
	public function getFolderSize($path) {
		return 10000;
		if ($path == "" || $path == "/") {
			$dbpath = $_SESSION['user_id']."/files/Share/";
		} else {
			$source = $this->getSource($path);
			$dbpath = $this->getInternalPath($source);
		}
		$query = OC_DB::prepare("SELECT size FROM *PREFIX*foldersize WHERE path=?");
		$size = $query->execute(array($dbpath))->fetchAll();
		if (count($size) > 0) {
			return $size[0]['size'];
		} else {
			return $this->calculateFolderSize($path);
		}
	}
	
	public function calculateFolderSize($path) {
		if ($this->is_file($path)) {
			$path = dirname($path);
		}
		$path = str_replace("//", "/", $path);
		if ($this->is_dir($path) && substr($path, -1) != "/") {
			$path .= "/";
		}
		$size = 0;
		if ($dh = $this->opendir($path)) {
			while (($filename = readdir($dh)) !== false) {
				if ($filename != "." && $filename != "..") {
					$subFile = $path.$filename;
					if ($this->is_file($subFile)) {
						$size += $this->filesize($subFile);
					} else {
						$size += $this->getFolderSize($subFile);
					}
				}
			}
			if ($size > 0) {
				if ($path == "" || $path == "/") {
					$dbpath = $_SESSION['user_id']."/files/Share/";
				} else {
					$source = $this->getSource($path);
					$dbpath = $this->getInternalPath($source);
				}
				$query = OC_DB::prepare("INSERT INTO *PREFIX*foldersize VALUES(?,?)");
				$result = $query->execute(array($dbpath, $size));
			}
		}
		return $size;
	}
	
	// TODO OC_SHARE::getPermissions()
	public function is_readable($path) {
		if ($path == "" || $path == "/") {
			return true;
		} else {
			$source = $this->getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->is_readable($this->getInternalPath($source));
			}
		}
	}
	
	// TODO OC_SHARE::getPermissions()
	public function is_writeable($path) {
		if ($path == "" || $path == "/") {
			return true;
		} else {
			$source = $this->getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->is_writeable($this->getInternalPath($source));
			}
		}
	}
	
	public function file_exists($path) {
		if ($path == "" || $path == "/") {
			return true;
		} else {
			$source = $this->getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->file_exists($this->getInternalPath($source));
			}
		}
	}
	
	public function readfile($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->readfile($this->getInternalPath($source));
		}	
	}
	
	public function filectime($path) {
		if ($path == "" || $path == "/") {
			$ctime = 0; 
			$dir = $this->opendir($path);
			while (($filename = readdir($dir)) != false) {
				$tempctime = $this->filectime($filename);
				if ($tempctime < $ctime) {
					$ctime = $tempctime;
				}
			}
			return $ctime;
		} else {
			$source = $this->getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->filectime($this->getInternalPath($source));
			}
		}
	}
	
	public function filemtime($path) {
		if ($path == "" || $path == "/") {
			$mtime = 0; 
			$dir = $this->opendir($path);
			while (($filename = readdir($dir)) != false) {
				$tempmtime = $this->filemtime($filename);
				if ($tempmtime > $mtime) {
					$mtime = $tempmtime;
				}
			}
			return $mtime;
		} else {
			$source = $this->getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->filemtime($this->getInternalPath($source));
			}
		}
	}
	
	public function fileatime($path) {
		if ($path == "" || $path == "/") {
			$atime = 0; 
			$dir = $this->opendir($path);
			while (($filename = readdir($dir)) != false) {
				$tempatime = $this->fileatime($filename);
				if ($tempatime > $atime) {
					$atime = $tempatime;
				}
			}
			return $atime;
		} else {
			$source = $this->getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->fileatime($this->getInternalPath($source));
			}
		}
	}
	
	public function file_get_contents($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->file_get_contents($this->getInternalPath($source));
		}
	}
	
	// TODO OC_SHARE::getPermissions()
	public function file_put_contents($path, $data) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->file_put_contents($this->getInternalPath($source), $data);
		}
	}
	
	public function unlink($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->unlink($this->getInternalPath($source));
		}		
	}
	
	// TODO OC_SHARE::getPermissions()
	// TODO Update shared item location
	public function rename($path1, $path2) {
		$source = $this->getSource($path1);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->rename($this->getInternalPath($source), $path2);
		}
	}
	
	public function copy($path1, $path2) {
		$source = $this->getSource($path1);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->copy($this->getInternalPath($source), $path2);
		}
	}
	
	public function fopen($path, $mode) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->fopen($this->getInternalPath($source), $mode);
		}
	}
	
	public function toTmpFile($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->toTmpFile($this->getInternalPath($source));
		}
	}
	
	public function fromTmpFile($tmpPath, $path) {
		$source = $this->getSource($tmpPath);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->fromTmpFile($this->getInternalPath($source), $path);
		}
	}
	
	public function fromUploadedFile($tmpPath, $path) {
		$source = $this->getSource($tmpPath);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->fromUploadedFile($this->getInternalPath($source), $path);
		}
	}
	
	public function getMimeType($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->getMimeType($this->getInternalPath($source));
		}
	}
	
	public function delTree($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->delTree($this->getInternalPath($source));
		}
	}
	
	public function find($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->find($this->getInternalPath($source));
		}
	}
	
	public function getTree($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->getTree($this->getInternalPath($source));
		}
	}
	
	public function hash($type, $path, $raw) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->hash($type, $this->getInternalPath($source), $raw);
		}
	}
	
	public function free_space($path) {
		$source = $this->getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->free_space($this->getInternalPath($source));
		}
	}
	
	// TODO query all shared files?
	public function search($query) { 
		
	}
	
}

?>