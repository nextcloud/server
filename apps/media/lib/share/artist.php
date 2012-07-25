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

class OC_Share_Backend_Artist extends OCP\Share_Backend {

	public function getSource($item, $uid) {
		$query = OCP\DB::prepare('SELECT artist_id FROM *PREFIX*media_artists WHERE artist_id = ? AND song_user = ?');
		$result = $query->execute(array($item, $uid))->fetchRow();
		if (is_array($result)) {
			return array('item' => $item, 'file' => $result['song_path']);
		}
		return false;
	}

	public function generateTarget($item, $uid, $exclude = null) {
		// TODO Make sure target path doesn't exist already
		return '/Shared'.$item;
	}

	public function formatItems($items, $format) {
		$ids = array();
		foreach ($items as $id => $info) {
			$ids[] = $id;
		}
		$ids = "'".implode("','", $ids)."'";
		switch ($format) {
			case self::FORMAT_SOURCE_PATH:
				$query = OCP\DB::prepare('SELECT path FROM *PREFIX*fscache WHERE id IN ('.$ids.')');
				return $query->execute()->fetchAll();
			case self::FORMAT_FILE_APP:
				$query = OCP\DB::prepare('SELECT id, path, name, ctime, mtime, mimetype, size, encrypted, versioned, writable FROM *PREFIX*fscache WHERE id IN ('.$ids.')');
				$result = $query->execute();
				$files = array();
				while ($file = $result->fetchRow()) {
					// Set target path
					$file['path'] = $items[$file['id']]['item_target'];
					$file['name'] = basename($file['path']);
					// TODO Set permissions: $file['writable']
					$files[] = $file;
				}
				return $files;
		}
	}

}

?>