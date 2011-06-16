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
	
	// TODO OC_SHARE::getPermissions()
	public function mkdir($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->mkdir(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	// TODO OC_SHARE::getPermissions()
	public function rmdir($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->rmdir(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	public function opendir($path) {
		//$source = OC_SHARE::getSource($path);
		//if ($source) {
			//$storage = OC_FILESYSTEM::getStorage($source);
			//return $storage->opendir(OC_FILESYSTEM::getInternalPath($source));
		//}
		global $FAKEDIRS;
		$FAKEDIRS['shared'] = array(0 => 'test.txt');
		return opendir('fakedir://shared');
	}
	
	public function is_dir($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->is_dir(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	public function is_file($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->is_file(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	public function stat($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->stat(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	public function filetype($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->filetype(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	public function filesize($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->filesize(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	// TODO OC_SHARE::getPermissions()
	public function is_readable($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->is_readable(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	// TODO OC_SHARE::getPermissions()
	public function is_writeable($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->is_writeable(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	public function file_exists($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->file_exists(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	public function readfile($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->readfile(OC_FILESYSTEM::getInternalPath($source));
		}	
	}
	
	public function filectime($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->filectime(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	public function filemtime($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->filemtime(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	public function fileatime($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->fileatime(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	public function file_get_contents($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->file_get_contents(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	// TODO OC_SHARE::getPermissions()
	public function file_put_contents($path, $data) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->file_put_contents(OC_FILESYSTEM::getInternalPath($source), $data);
		}
	}
	
	public function unlink($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->unlink(OC_FILESYSTEM::getInternalPath($source));
		}		
	}
	
	// TODO OC_SHARE::getPermissions()
	// TODO Update shared item location
	public function rename($path1, $path2) {
		$source = OC_SHARE::getSource($path1);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->rename(OC_FILESYSTEM::getInternalPath($source), $path2);
		}
	}
	
	public function copy($path1, $path2) {
		$source = OC_SHARE::getSource($path1);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->copy(OC_FILESYSTEM::getInternalPath($source), $path2);
		}
	}
	
	public function fopen($path, $mode) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->fopen(OC_FILESYSTEM::getInternalPath($source), $mode);
		}
	}
	
	public function toTmpFile($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->toTmpFile(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	public function fromTmpFile($tmpPath, $path) {
		$source = OC_SHARE::getSource($tmpPath);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->fromTmpFile(OC_FILESYSTEM::getInternalPath($source), $path);
		}
	}
	
	public function fromUploadedFile($tmpPath, $path) {
		$source = OC_SHARE::getSource($tmpPath);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->fromUploadedFile(OC_FILESYSTEM::getInternalPath($source), $path);
		}
	}
	
	public function getMimeType($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->getMimeType(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	public function delTree($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->delTree(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	public function find($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->find(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	public function getTree($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->getTree(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	public function hash($type, $path, $raw) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->hash($type, OC_FILESYSTEM::getInternalPath($source), $raw);
		}
	}
	
	public function free_space($path) {
		$source = OC_SHARE::getSource($path);
		if ($source) {
			$storage = OC_FILESYSTEM::getStorage($source);
			return $storage->free_space(OC_FILESYSTEM::getInternalPath($source));
		}
	}
	
	// TODO query all shared files?
	public function search($query) { 
		
	}
	
}

?>