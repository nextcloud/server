<?php

/**
* ownCloud - gallery application
*
* @author Bartek Przybylski
* @copyright 2012 Bartek Przybylski <bartek@alefzero.eu>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Lesser General Public 
* License along with this library. If not, see <http://www.gnu.org/licenses/>.
*
*/

class OC_Gallery_Album {
	public static function create($owner, $name, $path){
		$stmt = OCP\DB::prepare('INSERT INTO *PREFIX*gallery_albums (uid_owner, album_name, album_path, parent_path) VALUES (?, ?, ?, ?)');
		$stmt->execute(array($owner, $name, $path, self::getParentPath($path)));
	}

	public static function cleanup() {
		$albums = self::find(OCP\USER::getUser());
		while ($r = $albums->fetchRow()) {
			OC_Gallery_Photo::removeByAlbumId($r['album_id']);
			self::remove(OCP\USER::getUser(), $r['album_name']);
		}
	}

	public static function getParentPath($path) {
		return $path === '/' ? '' : dirname($path);
	}

	public static function remove($owner, $name=null, $path=null, $parent=null) {
		$sql = 'DELETE FROM *PREFIX*gallery_albums WHERE uid_owner LIKE ?';
		$args = array($owner);
		if (!is_null($name)){
			$sql .= ' AND album_name LIKE ?';
			$args[] = $name;
		}
		if (!is_null($path)){
			$sql .= ' AND album_path LIKE ?';
			$args[] = $path;
		}
		if (!is_null($parent)){
			$sql .= ' AND parent_path LIKE ?';
			$args[] = $parent;
		}
		$stmt = OCP\DB::prepare($sql);
		return $stmt->execute($args);
	}

	public static function removeByName($owner, $name) { self::remove($ownmer, $name); }
	public static function removeByPath($owner, $path) { self::remove($owner, null, $path); }
	public static function removeByParentPath($owner, $parent) { self::remove($owner, null, null, $parent); }
	
	public static function find($owner, $name=null, $path=null, $parent=null){
		$sql = 'SELECT * FROM *PREFIX*gallery_albums WHERE uid_owner = ?';
		$args = array($owner);
		if (!is_null($name)){
			$sql .= ' AND album_name = ?';
			$args[] = $name;
		}
		if (!is_null($path)){
			$sql .= ' AND album_path = ?';
			$args[] = $path;
		}
		if (!is_null($parent)){
			$sql .= ' AND parent_path = ?';
			$args[] = $parent;
		}
    $order = OCP\Config::getUserValue($owner, 'gallery', 'order', 'ASC');
		$sql .= ' ORDER BY album_name ' . $order;

		$stmt = OCP\DB::prepare($sql);
		return $stmt->execute($args);
	}

	public static function changePath($oldname, $newname, $owner) {
		$stmt = OCP\DB::prepare('UPDATE *PREFIX*gallery_albums SET album_path=? WHERE uid_owner=? AND album_path=?');
		$stmt->execute(array($newname, $owner, $oldname));
	}

	public static function changeThumbnailPath($oldname, $newname) {
		 
		$thumbpath = OC::$CONFIG_DATADIRECTORY.'/../gallery/';
		rename($thumbpath.$oldname.'.png', $thumbpath.$newname.'.png');
	}

	public static function getAlbumSize($id){
    $sql = 'SELECT COUNT(*) as size FROM *PREFIX*gallery_photos WHERE album_id = ?';
    $stmt = OCP\DB::prepare($sql);
    $result=$stmt->execute(array($id))->fetchRow();
    return $result['size'];
	}

  public static function getIntermediateGallerySize($path) {
    $path .= '%';
    $sql = 'SELECT COUNT(*) as size FROM *PREFIX*gallery_photos photos, *PREFIX*gallery_albums albums WHERE photos.album_id = albums.album_id AND uid_owner = ? AND file_path LIKE ?';
    $stmt = OCP\DB::prepare($sql);
    $result = $stmt->execute(array(OCP\USER::getUser(), $path))->fetchRow();
    return $result['size'];
  }
}

?>
