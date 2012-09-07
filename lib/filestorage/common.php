<?php

/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski GapczynskiM@gmail.com
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
*/

/**
 * Storage backend class for providing common filesystem operation methods
 * which are not storage-backend specific.
 *
 * OC_Filestorage_Common is never used directly; it is extended by all other
 * storage backends, where its methods may be overridden, and additional
 * (backend-specific) methods are defined.
 *
 * Some OC_Filestorage_Common methods call functions which are first defined
 * in classes which extend it, e.g. $this->stat() .
 */

abstract class OC_Filestorage_Common extends OC_Filestorage {

	public function __construct($parameters) {}
// 	abstract public function mkdir($path);
// 	abstract public function rmdir($path);
// 	abstract public function opendir($path);
	public function is_dir($path) {
		return $this->filetype($path)=='dir';
	}
	public function is_file($path) {
		return $this->filetype($path)=='file';
	}
// 	abstract public function stat($path);
// 	abstract public function filetype($path);
	public function filesize($path) {
		if($this->is_dir($path)) {
			return 0;//by definition
		}else{
			$stat = $this->stat($path);
			return $stat['size'];
		}
	}
	public function isCreatable($path) {
		return $this->isUpdatable($path);
	}
// 	abstract public function isReadable($path);
// 	abstract public function isUpdatable($path);
	public function isDeletable($path) {
		return $this->isUpdatable($path);
	}
	public function isSharable($path) {
		return $this->isReadable($path);
	}
// 	abstract public function file_exists($path);
	public function filectime($path) {
		$stat = $this->stat($path);
		return $stat['ctime'];
	}
	public function filemtime($path) {
		$stat = $this->stat($path);
		return $stat['mtime'];
	}
	public function fileatime($path) {
		$stat = $this->stat($path);
		return $stat['atime'];
	}
	public function file_get_contents($path) {
		$handle = $this->fopen($path, "r");
		if(!$handle) {
			return false;
		}
		$size=$this->filesize($path);
		if($size==0) {
			return '';
		}
		return fread($handle, $size);
	}
	public function file_put_contents($path,$data) {
		$handle = $this->fopen($path, "w");
		return fwrite($handle, $data);
	}
// 	abstract public function unlink($path);
	public function rename($path1,$path2) {
		if($this->copy($path1,$path2)) {
			return $this->unlink($path1);
		}else{
			return false;
		}
	}
	public function copy($path1,$path2) {
		$source=$this->fopen($path1,'r');
		$target=$this->fopen($path2,'w');
		$count=OC_Helper::streamCopy($source,$target);
		return $count>0;
	}
// 	abstract public function fopen($path,$mode);

	/**
	 * @brief Deletes all files and folders recursively within a directory
	 * @param $directory The directory whose contents will be deleted
	 * @param $empty Flag indicating whether directory will be emptied
	 * @returns true/false
	 *
	 * @note By default the directory specified by $directory will be
	 * deleted together with its contents. To avoid this set $empty to true
	 */
	public function deleteAll( $directory, $empty = false ) {

		// strip leading slash
		if( substr( $directory, 0, 1 ) == "/" ) {

			$directory = substr( $directory, 1 );

		}

		// strip trailing slash
		if( substr( $directory, -1) == "/" ) {

			$directory = substr( $directory, 0, -1 );

		}

		if ( !$this->file_exists( \OCP\USER::getUser() . '/' . $directory ) || !$this->is_dir( \OCP\USER::getUser() . '/' . $directory ) ) {

			return false;

		} elseif( !$this->is_readable( \OCP\USER::getUser() . '/' . $directory ) ) {

			return false;

		} else {

			$directoryHandle = $this->opendir( \OCP\USER::getUser() . '/' . $directory );

			while ( $contents = readdir( $directoryHandle ) ) {

				if ( $contents != '.' && $contents != '..') {

					$path = $directory . "/" . $contents;

					if ( $this->is_dir( $path ) ) {

						deleteAll( $path );

					} else {

						$this->unlink( \OCP\USER::getUser() .'/' . $path ); // TODO: make unlink use same system path as is_dir

					}
				}

			}

			//$this->closedir( $directoryHandle ); // TODO: implement closedir in OC_FSV

			if ( $empty == false ) {

				if ( !$this->rmdir( $directory ) ) {

					return false;

				}

			}

			return true;
		}

	}
	public function getMimeType($path) {
		if(!$this->file_exists($path)) {
			return false;
		}
		if($this->is_dir($path)) {
			return 'httpd/unix-directory';
		}
		$source=$this->fopen($path,'r');
		if(!$source) {
			return false;
		}
		$head=fread($source,8192);//8kb should suffice to determine a mimetype
		if($pos=strrpos($path,'.')) {
			$extension=substr($path,$pos);
		}else{
			$extension='';
		}
		$tmpFile=OC_Helper::tmpFile($extension);
		file_put_contents($tmpFile,$head);
		$mime=OC_Helper::getMimeType($tmpFile);
		unlink($tmpFile);
		return $mime;
	}
	public function hash($type,$path,$raw = false) {
		$tmpFile=$this->getLocalFile();
		$hash=hash($type,$tmpFile,$raw);
		unlink($tmpFile);
		return $hash;
	}
// 	abstract public function free_space($path);
	public function search($query) {
		return $this->searchInDir($query);
	}
	public function getLocalFile($path) {
		return $this->toTmpFile($path);
	}
	private function toTmpFile($path) {//no longer in the storage api, still usefull here
		$source=$this->fopen($path,'r');
		if(!$source) {
			return false;
		}
		if($pos=strrpos($path,'.')) {
			$extension=substr($path,$pos);
		}else{
			$extension='';
		}
		$tmpFile=OC_Helper::tmpFile($extension);
		$target=fopen($tmpFile,'w');
		OC_Helper::streamCopy($source,$target);
		return $tmpFile;
	}
	public function getLocalFolder($path) {
		$baseDir=OC_Helper::tmpFolder();
		$this->addLocalFolder($path,$baseDir);
		return $baseDir;
	}
	private function addLocalFolder($path,$target) {
		if($dh=$this->opendir($path)) {
			while($file=readdir($dh)) {
				if($file!=='.' and $file!=='..') {
					if($this->is_dir($path.'/'.$file)) {
						mkdir($target.'/'.$file);
						$this->addLocalFolder($path.'/'.$file,$target.'/'.$file);
					}else{
						$tmp=$this->toTmpFile($path.'/'.$file);
						rename($tmp,$target.'/'.$file);
					}
				}
			}
		}
	}
// 	abstract public function touch($path, $mtime=null);

	protected function searchInDir($query,$dir='') {
		$files=array();
		$dh=$this->opendir($dir);
		if($dh) {
			while($item=readdir($dh)) {
				if ($item == '.' || $item == '..') continue;
				if(strstr(strtolower($item),strtolower($query))!==false) {
					$files[]=$dir.'/'.$item;
				}
				if($this->is_dir($dir.'/'.$item)) {
					$files=array_merge($files,$this->searchInDir($query,$dir.'/'.$item));
				}
			}
		}
		return $files;
	}

	/**
	 * check if a file or folder has been updated since $time
	 * @param int $time
	 * @return bool
	 */
	public function hasUpdated($path,$time) {
		return $this->filemtime($path)>$time;
	}
}
