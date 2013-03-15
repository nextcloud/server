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

	public function getChildren($itemSource) {
		$children = array();
		$parents = array($itemSource);
		$query = \OC_DB::prepare('SELECT `id` FROM `*PREFIX*mimetypes` WHERE `mimetype` = ?');
		$result = $query->execute(array('httpd/unix-directory'));
		if ($row = $result->fetchRow()) {
			$mimetype = $row['id'];
		} else {
			$mimetype = -1;
		}
		while (!empty($parents)) {
			$parents = "'".implode("','", $parents)."'";
			$query = OC_DB::prepare('SELECT `fileid`, `name`, `mimetype` FROM `*PREFIX*filecache`'
				.' WHERE `parent` IN ('.$parents.')');
			$result = $query->execute();
			$parents = array();
			while ($file = $result->fetchRow()) {
				$children[] = array('source' => $file['fileid'], 'file_path' => $file['name']);
				// If a child folder is found look inside it
				if ($file['mimetype'] == $mimetype) {
					$parents[] = $file['fileid'];
				}
			}
		}
		return $children;
	}

}
