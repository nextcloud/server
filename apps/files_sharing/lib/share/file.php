
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

class OC_Share_Backend_File extends OCP\Share_Backend {

	const FORMAT_SHARED_STORAGE = 0;
	const FORMAT_FILE_APP = 1;
	const FORMAT_FILE_APP_ROOT = 2;
	const FORMAT_OPENDIR = 3;

	public function getSource($item, $uid) {
		if (OC_Filesystem::file_exists($item)) {
			return array('item' => null, 'file' => $item);
		}
		return false;
	}

	public function generateTarget($item, $uid, $exclude = null) {
		// TODO Make sure target path doesn't exist already
		return '/Shared'.$item;
	}

	public function formatItems($items, $format, $parameters = null) {
		if ($format == self::FORMAT_OPENDIR) {
			$files = array();
			foreach ($items as $file) {
				$files[] = basename($file['file_target']);
			}
			return $files;
		} else if ($format == self::FORMAT_SHARED_STORAGE) {
			$id = $items[key($items)]['file_source'];
			$query = OCP\DB::prepare('SELECT path FROM *PREFIX*fscache WHERE id = ?');
			$result = $query->execute(array($id))->fetchAll();
			if (isset($result[0]['path'])) {
				return array('path' => $result[0]['path'], 'permissions' => $items[key($items)]['permissions']);
			}
			return false;
		} else {
			$shares = array();
			$ids = array();
			foreach ($items as $item) {
				$shares[$item['file_source']] = $item;
				$ids[] = $item['file_source'];
			}
			$ids = "'".implode("','", $ids)."'";
			if ($format == self::FORMAT_FILE_APP) {
				$query = OCP\DB::prepare('SELECT id, path, name, ctime, mtime, mimetype, size, encrypted, versioned, writable FROM *PREFIX*fscache WHERE id IN ('.$ids.')');
				$result = $query->execute();
				$files = array();
				while ($file = $result->fetchRow()) {
					// Set target path
					$file['path'] = $shares[$file['id']]['file_target'];
					$file['name'] = basename($file['path']);
					// TODO Set permissions: $file['writable']
					$files[] = $file;
				}
				return $files;
			} else if ($format == self::FORMAT_FILE_APP_ROOT) {
				$query = OCP\DB::prepare('SELECT id, path, name, ctime, mtime, mimetype, size, encrypted, versioned, writable FROM *PREFIX*fscache WHERE id IN ('.$ids.')');
				$result = $query->execute();
				$mtime = 0;
				$size = 0;
				while ($file = $result->fetchRow()) {
					if ($file['mtime'] > $mtime) {
						$mtime = $file['mtime'];
					}
					$size += $file['size'];
				}
				return array(0 => array('name' => 'Shared', 'mtime' => $mtime, 'mimetype' => 'httpd/unix-directory', 'size' => $size, 'writable' => false));
			}
		}
		return array();
	}

}