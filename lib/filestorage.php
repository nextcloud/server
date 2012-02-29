<?php

/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
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
 * Privde a common interface to all different storage options
 */
class OC_Filestorage{
	public function __construct($parameters){}
	public function mkdir($path){}
	public function rmdir($path){}
	public function opendir($path){}
	public function is_dir($path){}
	public function is_file($path){}
	public function stat($path){}
	public function filetype($path){}
	public function filesize($path){}
	public function is_readable($path){}
	public function is_writeable($path){}
	public function file_exists($path){}
	public function readfile($path){}
	public function filectime($path){}
	public function filemtime($path){}
	public function fileatime($path){}
	public function file_get_contents($path){}
	public function file_put_contents($path,$data){}
	public function unlink($path){}
	public function rename($path1,$path2){}
	public function copy($path1,$path2){}
	public function fopen($path,$mode){}
	public function toTmpFile($path){}//copy the file to a temporary file, used for cross-storage file actions
	public function fromTmpFile($tmpPath,$path){}//copy a file from a temporary file, used for cross-storage file actions
	public function fromUploadedFile($tmpPath,$path){}//copy a file from a temporary file, used for cross-storage file actions
	public function getMimeType($path){}
	public function hash($type,$path,$raw){}
	public function free_space($path){}
	public function search($query){}
	public function touch($path, $mtime=null){}
	public function getLocalFile($path){}// get a path to a local version of the file, whether the original file is local or remote
}
