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

class OC_Share_Backend_Folder extends OC_Share_Backend_File implements OCP\Share_Backend_Collection {

	public function formatItems($items, $format, $parameters = null) {
		if ($format == self::FORMAT_SHARED_STORAGE) {
			// Only 1 item should come through for this format call
			return array('path' => $items[key($items)]['path'], 'permissions' => $items[key($items)]['permissions']);
		} else if ($format == self::FORMAT_FILE_APP && isset($parameters['folder'])) {
			// Only 1 item should come through for this format call
			$folder = $items[key($items)];
			if (isset($parameters['mimetype_filter'])) {
				$mimetype_filter = $parameters['mimetype_filter'];
			} else {
				$mimetype_filter = '';
			}
			$path = $folder['path'].substr($parameters['folder'], 7 + strlen($folder['file_target']));
			$files = OC_FileCache::getFolderContent($path, '', $mimetype_filter);
			foreach ($files as &$file) {
				$file['directory'] = $parameters['folder'];
				$file['type'] = ($file['mimetype'] == 'httpd/unix-directory') ? 'dir' : 'file';
				$file['permissions'] = $folder['permissions'];
				if ($file['type'] == 'file') {
					// Remove Create permission if type is file
					$file['permissions'] &= ~OCP\Share::PERMISSION_CREATE;
				}
			}
			return $files;
		}
		return array();
	}

	public function getChildren($itemSource) {
		$children = array();
		$parents = array($itemSource);
		while (!empty($parents)) {
			$parents = "'".implode("','", $parents)."'";
			$query = OC_DB::prepare('SELECT `id`, `name`, `mimetype` FROM `*PREFIX*fscache` WHERE `parent` IN ('.$parents.')');
			$result = $query->execute();
			$parents = array();
			while ($file = $result->fetchRow()) {
				$children[] = array('source' => $file['id'], 'file_path' => $file['name']);
				// If a child folder is found look inside it 
				if ($file['mimetype'] == 'httpd/unix-directory') {
					$parents[] = $file['id'];
				}
			}
		}
		return $children;
	}

}