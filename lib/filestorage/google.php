<?php

/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
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

class OC_Filestorage_Google extends OC_Filestorage_Common {
	
	private $auth;

	public function __construct($parameters) {
		
	}
	
	private function connect() {
	  
	}
	public function mkdir($path){}
	public function rmdir($path){}
	public function opendir($path){}
	public function is_dir($path){}
	public function is_file($path){}
	public function stat($path){}
	public function filetype($path){}
	public function is_readable($path){}
	public function is_writable($path){}
	public function file_exists($path){}
	public function unlink($path){}
	public function rename($path1,$path2){}
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