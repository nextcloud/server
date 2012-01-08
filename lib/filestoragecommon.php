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

class OC_Filestorage_Common extends OC_Filestorage {

	public function __construct($parameters){}
	public function mkdir($path){}
	public function rmdir($path){}
	public function opendir($path){}
	public function is_dir($path){}
	public function is_file($path){}
	public function stat($path){}
	public function filetype($path){}
	public function filesize($path) {
		$stat = $this->stat($path);
		return $stat['size'];
	}
	public function is_readable($path){}
	public function is_writeable($path){}
	public function file_exists($path){}
	public function readfile($path) {
		$handle = $this->fopen($path, "r");
		$chunk = 1024;
		while (!feof($handle)) {
			echo fread($handle, $chunk);
		}
		return $this->filesize($path);
	}
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
		return fread($handle, $this->filesize($path));
	}
	public function file_put_contents($path,$data) {
		$handle = $this->fopen($path, "w");
		return fwrite($handle, $data);
	}
	public function unlink($path){}
	public function rename($path1,$path2){}
	public function copy($path1,$path2) {
		$data = $this->file_get_contents($path1);
		return $this->file_put_contents($path2, $data);
	}
	public function fopen($path,$mode){}
	public function toTmpFile($path){}
	public function fromTmpFile($tmpPath,$path){}
	public function fromUploadedFile($tmpPath,$path){}
	public function getMimeType($path){}
	public function hash($type,$path,$raw){}
	public function free_space($path){}
	public function search($query){}
	public function getLocalFile($path){}
}
