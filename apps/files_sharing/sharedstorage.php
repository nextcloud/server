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
	
	public function getInternalPath($path) {
		$mountPoint = OC_FILESYSTEM::getMountPoint($path);
		$internalPath = substr($path,strlen($mountPoint));
		return $internalPath;
	}
	
	// TODO OC_SHARE::getPermissions()
	public function mkdir($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->mkdir(getInternalPath($source));
		}
	}
	
	// TODO OC_SHARE::getPermissions()
	public function rmdir($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->rmdir(getInternalPath($source));
		}
	}
	
	public function opendir($path) {
		global $FAKEDIRS;
		$FAKEDIRS['shared'] = array(0 => 'test.txt');
		return opendir('fakedir://shared');
	}
	
	public function is_dir($path) {
		if ($path == "" || $path == "/") {
			return true;
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->is_dir(getInternalPath($source));
			}
		}
	}
	
	public function is_file($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->is_file(getInternalPath($source));
		}
	}
	public function stat($path) {
		if ($path == "" || $path == "/") {
			$stat["dev"] = "";
			$stat["ino"] = "";
			$stat["mode"] = "";
			$stat["nlink"] = "";
			$stat["uid"] = "";
			$stat["gid"] = "";
			$stat["rdev"] = "";
			$stat["size"] = filesize($path);
			$stat["atime"] = fileatime($path);
			$stat["mtime"] = filemtime($path);
			$stat["ctime"] = filectime($path);
			$stat["blksize"] = "";
			$stat["blocks"] = "";
			return $stat;
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->stat(getInternalPath($source));
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
				return $storage->filetype(getInternalPath($source));
			}
		}

	}
	
	// TODO Get size of shared directory
	public function filesize($path) {
		if ($path == "" || $path == "/") {
			return 10000;
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->filesize(getInternalPath($source));
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
				return $storage->is_readable(getInternalPath($source));
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
				return $storage->is_writeable(getInternalPath($source));
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
				return $storage->file_exists(getInternalPath($source));
			}
		}
	}
	
	public function readfile($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->readfile(getInternalPath($source));
		}	
	}
	
	// TODO Get ctime of last file
	public function filectime($path) {
		if ($path == "" || $path == "/") {
			return 10000003232;
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->filectime(getInternalPath($source));
			}
		}
	}
	
	// TODO Get mtime of last file
	public function filemtime($path) {
		if ($path == "" || $path == "/") {
			return 10000003232;
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->filemtime(getInternalPath($source));
			}
		}
	}
	
	// TODO Get atime of last file
	public function fileatime($path) {
		if ($path == "" || $path == "/") {
			return 10000003232;
		} else {
			$source = OC_SHARE::getSource($path);
			if ($source) {
				$storage = OC_FILESYSTEM::getStorage($source);
				return $storage->fileatime(getInternalPath($source));
			}
		}
	}
	
	public function file_get_contents($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->file_get_contents(getInternalPath($source));
		}
	}
	
	// TODO OC_SHARE::getPermissions()
	public function file_put_contents($path, $data) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->file_put_contents(getInternalPath($source), $data);
		}
	}
	
	public function unlink($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->unlink(getInternalPath($source));
		}		
	}
	
	// TODO OC_SHARE::getPermissions()
	// TODO Update shared item location
	public function rename($path1, $path2) {
		$source = OC_SHARE::getSource($path1);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->rename(getInternalPath($source), $path2);
		}
	}
	
	public function copy($path1, $path2) {
		$source = OC_SHARE::getSource($path1);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->copy(getInternalPath($source), $path2);
		}
	}
	
	public function fopen($path, $mode) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->fopen(getInternalPath($source), $mode);
		}
	}
	
	public function toTmpFile($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->toTmpFile(getInternalPath($source));
		}
	}
	
	public function fromTmpFile($tmpPath, $path) {
		$source = OC_SHARE::getSource($tmpPath);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->fromTmpFile(getInternalPath($source), $path);
		}
	}
	
	public function fromUploadedFile($tmpPath, $path) {
		$source = OC_SHARE::getSource($tmpPath);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->fromUploadedFile(getInternalPath($source), $path);
		}
	}
	
	public function getMimeType($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->getMimeType(getInternalPath($source));
		}
	}
	
	public function delTree($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->delTree(getInternalPath($source));
		}
	}
	
	public function find($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->find(getInternalPath($source));
		}
	}
	
	public function getTree($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->getTree(getInternalPath($source));
		}
	}
	
	public function hash($type, $path, $raw) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->hash($type, getInternalPath($source), $raw);
		}
	}
	
	public function free_space($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->free_space(getInternalPath($source));
		}
	}
	
	// TODO query all shared files?
	public function search($query) { 
		
	}
	
}

?>