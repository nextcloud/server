<?php

/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2012 Frank Karlitschek frank@owncloud.org
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
 * Provde a common interface to all different storage options
 */
abstract class OC_Filestorage{
	abstract public function __construct($parameters);
	abstract public function mkdir($path);
	abstract public function rmdir($path);
	abstract public function opendir($path);
	abstract public function is_dir($path);
	abstract public function is_file($path);
	abstract public function stat($path);
	abstract public function filetype($path);
	abstract public function filesize($path);
	abstract public function isCreatable($path);
	abstract public function isReadable($path);
	abstract public function isUpdatable($path);
	abstract public function isDeletable($path);
	abstract public function isSharable($path);
	abstract public function file_exists($path);
	abstract public function filectime($path);
	abstract public function filemtime($path);
	abstract public function file_get_contents($path);
	abstract public function file_put_contents($path,$data);
	abstract public function unlink($path);
	abstract public function rename($path1,$path2);
	abstract public function copy($path1,$path2);
	abstract public function fopen($path,$mode);
	abstract public function getMimeType($path);
	abstract public function hash($type,$path,$raw = false);
	abstract public function free_space($path);
	abstract public function search($query);
	abstract public function touch($path, $mtime=null);
	abstract public function getLocalFile($path);// get a path to a local version of the file, whether the original file is local or remote
	abstract public function getLocalFolder($path);// get a path to a local version of the folder, whether the original file is local or remote
	/**
	 * check if a file or folder has been updated since $time
	 * @param int $time
	 * @return bool
	 *
	 * hasUpdated for folders should return at least true if a file inside the folder is add, removed or renamed.
	 * returning true for other changes in the folder is optional
	 */
	abstract public function hasUpdated($path,$time);
}
