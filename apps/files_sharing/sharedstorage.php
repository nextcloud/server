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
	
	// TODO uh... I don't know what to do here
	public function __construct($parameters) {
		
	}
	
	public static function getInternalPath($path) {
		$mountPoint = OC_FILESYSTEM::getMountPoint($path);
		$internalPath = substr($path,strlen($mountPoint));
		return $internalPath;
	}
	
	// TODO OC_SHARE::getPermissions()
	public function mkdir($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->mkdir(self::getInternalPath($source));
		}
	}
	
	// TODO OC_SHARE::getPermissions()
	public function rmdir($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->rmdir(self::getInternalPath($source));
		}
	}
	
	// TODO add all files from db in array
	public function opendir($path) {
		global $FAKEDIRS;
		$sharedItems = OC_SHARE::getItemsSharedWith();
		foreach ($sharedItems as $item) {
			$files[] = $item['target'];
		}
		$FAKEDIRS['shared'] = $files;
		return opendir('fakedir://shared');
	}
	
	public function is_dir($path) {
		if ($path == "" || $path == "/") {
			return true;
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->is_dir(self::getInternalPath($source));
			}
		}
	}
	
	public function is_file($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->is_file(self::getInternalPath($source));
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
			$stat["size"] = OC_FILESTORAGE_SHARED::filesize($path);
			$stat["atime"] = OC_FILESTORAGE_SHARED::fileatime($path);
			$stat["mtime"] = OC_FILESTORAGE_SHARED::filemtime($path);
			$stat["ctime"] = OC_FILESTORAGE_SHARED::filectime($path);
			$stat["blksize"] = "";
			$stat["blocks"] = "";
			return $stat;
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->stat(self::getInternalPath($source));
			}
		}
	}
	
	public function filetype($path) {
		if ($path == "" || $path == "/") {
			return "dir";
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->filetype(self::getInternalPath($source));
			}
		}

	}
	
	public function filesize($path) {
		if ($path == "" || $path == "/") {
			$size = 0;
			$dir = OC_FILESTORAGE_SHARED::opendir($path);
			while (($filename = readdir($dir)) != false) {
				$size += OC_FILESTORAGE_SHARED::filesize($filename);
			}
			return $size;
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->filesize(self::getInternalPath($source));
			}
		}
	}
	
	// TODO OC_SHARE::getPermissions()
	public function is_readable($path) {
		if ($path == "" || $path == "/") {
			return true;
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->is_readable(self::getInternalPath($source));
			}
		}
	}
	
	// TODO OC_SHARE::getPermissions()
	public function is_writeable($path) {
		if ($path == "" || $path == "/") {
			return true;
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->is_writeable(self::getInternalPath($source));
			}
		}
	}
	
	public function file_exists($path) {
		if ($path == "" || $path == "/") {
			return true;
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->file_exists(self::getInternalPath($source));
			}
		}
	}
	
	public function readfile($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->readfile(self::getInternalPath($source));
		}	
	}
	
	public function filectime($path) {
		if ($path == "" || $path == "/") {
			$ctime = 0; 
			$dir = OC_FILESTORAGE_SHARED::opendir($path);
			while (($filename = readdir($dir)) != false) {
				$tempctime = OC_FILESTORAGE_SHARED::filectime($filename);
				if ($tempctime > $ctime) {
					$ctime = $tempctime;
				}
			}
			return $ctime;
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->filectime(self::getInternalPath($source));
			}
		}
	}
	
	public function filemtime($path) {
		if ($path == "" || $path == "/") {
			$mtime = 0; 
			$dir = OC_FILESTORAGE_SHARED::opendir($path);
			while (($filename = readdir($dir)) != false) {
				$tempmtime = OC_FILESTORAGE_SHARED::filemtime($filename);
				if ($tempmtime > $mtime) {
					$mtime = $tempmtime;
				}
			}
			return $mtime;
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->filemtime(self::getInternalPath($source));
			}
		}
	}
	
	public function fileatime($path) {
		if ($path == "" || $path == "/") {
			$atime = 0; 
			$dir = OC_FILESTORAGE_SHARED::opendir($path);
			while (($filename = readdir($dir)) != false) {
				$tempatime = OC_FILESTORAGE_SHARED::fileatime($filename);
				if ($tempatime > $atime) {
					$atime = $tempatime;
				}
			}
			return $atime;
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->fileatime(self::getInternalPath($source));
			}
		}
	}
	
	public function file_get_contents($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->file_get_contents(self::getInternalPath($source));
		}
	}
	
	// TODO OC_SHARE::getPermissions()
	public function file_put_contents($path, $data) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->file_put_contents(self::getInternalPath($source), $data);
		}
	}
	
	public function unlink($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->unlink(self::getInternalPath($source));
		}		
	}
	
	// TODO OC_SHARE::getPermissions()
	// TODO Update shared item location
	public function rename($path1, $path2) {
		$source = OC_SHARE::getSource($path1);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->rename(self::getInternalPath($source), $path2);
		}
	}
	
	public function copy($path1, $path2) {
		$source = OC_SHARE::getSource($path1);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->copy(self::getInternalPath($source), $path2);
		}
	}
	
	public function fopen($path, $mode) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->fopen(self::getInternalPath($source), $mode);
		}
	}
	
	public function toTmpFile($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->toTmpFile(self::getInternalPath($source));
		}
	}
	
	public function fromTmpFile($tmpPath, $path) {
		$source = OC_SHARE::getSource($tmpPath);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->fromTmpFile(self::getInternalPath($source), $path);
		}
	}
	
	public function fromUploadedFile($tmpPath, $path) {
		$source = OC_SHARE::getSource($tmpPath);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->fromUploadedFile(self::getInternalPath($source), $path);
		}
	}
	
	public function getMimeType($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->getMimeType(self::getInternalPath($source));
		}
	}
	
	public function delTree($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->delTree(self::getInternalPath($source));
		}
	}
	
	public function find($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->find(self::getInternalPath($source));
		}
	}
	
	public function getTree($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->getTree(self::getInternalPath($source));
		}
	}
	
	public function hash($type, $path, $raw) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->hash($type, self::getInternalPath($source), $raw);
		}
	}
	
	public function free_space($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->free_space(self::getInternalPath($source));
		}
	}
	
	// TODO query all shared files?
	public function search($query) { 
		
	}
	
}

?>