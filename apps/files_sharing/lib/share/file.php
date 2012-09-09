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

class OC_Share_Backend_File implements OCP\Share_Backend_File_Dependent {

	const FORMAT_SHARED_STORAGE = 0;
	const FORMAT_FILE_APP = 1;
	const FORMAT_FILE_APP_ROOT = 2;
	const FORMAT_OPENDIR = 3;

	private $path;

	public function isValidSource($itemSource, $uidOwner) {
		$path = OC_FileCache::getPath($itemSource, $uidOwner);
		if ($path) {
			$this->path = $path;
			return true;
		}
		return false;
	}

	public function getFilePath($itemSource, $uidOwner) {
		if (isset($this->path)) {
			$path = $this->path;
			$this->path = null;
			return $path;
		}
		return false;
	}

	public function generateTarget($filePath, $shareWith, $exclude = null) {
		$target = $filePath;
		if (isset($exclude)) {
			if ($pos = strrpos($target, '.')) {
				$name = substr($target, 0, $pos);
				$ext = substr($target, $pos);
			} else {
				$name = $filePath;
				$ext = '';
			}
			$i = 2;
			$append = '';
			while (in_array($name.$append.$ext, $exclude)) {
				$append = ' ('.$i.')';
				$i++;
			}
			$target = $name.$append.$ext;
		}
		return $target;
	}

	public function formatItems($items, $format, $parameters = null) {
		if ($format == self::FORMAT_SHARED_STORAGE) {
			// Only 1 item should come through for this format call
			return array('path' => $items[key($items)]['path'], 'permissions' => $items[key($items)]['permissions']);
		} else if ($format == self::FORMAT_FILE_APP) {
			$files = array();
			foreach ($items as $item) {
				$file = array();
				$file['id'] = $item['file_source'];
				$file['path'] = $item['file_target'];
				$file['name'] = basename($item['file_target']);
				$file['ctime'] = $item['ctime'];
				$file['mtime'] = $item['mtime'];
				$file['mimetype'] = $item['mimetype'];
				$file['size'] = $item['size'];
				$file['encrypted'] = $item['encrypted'];
				$file['versioned'] = $item['versioned'];
				$file['directory'] = $parameters['folder'];
				$file['type'] = ($item['mimetype'] == 'httpd/unix-directory') ? 'dir' : 'file';
				$file['permissions'] = $item['permissions'];
				if ($file['type'] == 'file') {
					// Remove Create permission if type is file
					$file['permissions'] &= ~OCP\Share::PERMISSION_CREATE;
				}
				// NOTE: Temporary fix to allow unsharing of files in root of Shared directory
				$file['permissions'] |= OCP\Share::PERMISSION_DELETE;
				$files[] = $file;
			}
			return $files;
		} else if ($format == self::FORMAT_FILE_APP_ROOT) {
			$mtime = 0;
			$size = 0;
			foreach ($items as $item) {
				if ($item['mtime'] > $mtime) {
					$mtime = $item['mtime'];
				}
				$size += $item['size'];
			}
			return array(0 => array('id' => -1, 'name' => 'Shared', 'mtime' => $mtime, 'mimetype' => 'httpd/unix-directory', 'size' => $size, 'writable' => false, 'type' => 'dir', 'directory' => '', 'permissions' => OCP\Share::PERMISSION_READ));
		} else if ($format == self::FORMAT_OPENDIR) {
			$files = array();
			foreach ($items as $item) {
				$files[] = basename($item['file_target']);
			}
			return $files;
		}
		return array();
	}

}