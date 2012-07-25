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

class OC_Share_Backend_Folder extends OC_Share_Backend_File {

	public function inCollection($collections, $item) {
		// TODO
	}

	public function getChildrenSources($item) {
		return OC_FileCache::getFolderContent($item);
	}

	public function formatItems($items, $format, $parameters = null) {
		if ($format == self::FORMAT_FILE_APP && isset($parameters['folder'])) {
			$folder = $items[key($items)];
			$query = OCP\DB::prepare('SELECT path FROM *PREFIX*fscache WHERE id = ?');
			$result = $query->execute(array($folder['file_source']))->fetchRow();
			if (isset($result['path'])) {
				if (isset($parameters['mimetype_filter'])) {
					$mimetype_filter = $parameters['mimetype_filter'];
				} else {
					$mimetype_filter = '';
				}
				$pos = strpos($result['path'], $folder['item']);
				$path = substr($result['path'], $pos).substr($parameters['folder'], strlen($folder['file_target']));
				$root = substr($result['path'], 0, $pos);
				$files = OC_FileCache::getFolderContent($path, $root, $mimetype_filter);
				foreach ($files as &$file) {
					$file['directory'] = $parameters['folder'];
					$file['type'] = ($file['mimetype'] == 'httpd/unix-directory') ? 'dir' : 'file';
					$permissions = $folder['permissions'];
					if ($file['type'] == 'file') {
						// Remove Create permission if type is file
						$permissions &= ~OCP\Share::PERMISSION_CREATE;
					}
					$file['permissions'] = $permissions;
				}
				return $files;
			}
		}/* else if ($format == self::FORMAT_OPENDIR_ROOT) {
			$query = OCP\DB::prepare('SELECT name FROM *PREFIX*fscache WHERE id IN ('.$ids.')');
			$result = $query->execute();
			$files = array();
			while ($file = $result->fetchRow()) {
				// Set target path
				$files[] = basename($shares[$file['id']]['item_target']);
			}
			return $files;
		}*/
		return array();
	}

}


?>