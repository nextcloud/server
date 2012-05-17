<?php

/**
* ownCloud - gallery application
*
* @author Bartek Przybylski
* @copyright 2012 Bartek Przybylski bartek@alefzero.eu
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
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

class OC_Gallery_Sharing {
  private static function getEntries($token) {
    $sql = 'SELECT * FROM *PREFIX*gallery_sharing WHERE token = ?';
    $stmt = OCP\DB::prepare($sql);
    return $stmt->execute(array($token));
  }

  public static function isTokenValid($token) {
    $r = self::getEntries($token);
    $row = $r->fetchRow();
    return $row != null;
  }

  public static function isRecursive($token) {
    $r = self::getEntries($token);
    if ($row = $r->fetchRow()) return $row['recursive'] == 1;
    return false;
  }

  public static function getTokenOwner($token) {
    $r = self::getEntries($token);
    if ($row = $r->fetchRow()) {
      $galleryId = $row['gallery_id'];
      $sql = 'SELECT * FROM *PREFIX*gallery_albums WHERE album_id = ?';
      $stmt = OCP\DB::prepare($sql);
      $r = $stmt->execute(array($galleryId));
      if ($row = $r->fetchRow())
       return $row['uid_owner'];
     }
    return false;
  }

  public static function getPath($token) {
    $r = self::getEntries($token);
    if ($row = $r->fetchRow()) {
      $galleryId = $row['gallery_id'];
      $sql = 'SELECT * FROM *PREFIX*gallery_albums WHERE album_id = ?';
      $stmt = OCP\DB::prepare($sql);
      $r = $stmt->execute(array($galleryId));
      if ($row = $r->fetchRow())
        return $row['album_path'];
    }
  }

  public static function updateSharingByToken($token, $recursive) {
    $stmt = OCP\DB::prepare('UPDATE *PREFIX*gallery_sharing SET recursive = ? WHERE token = ?');
    $stmt->execute(array($recursive, $token));
  }

  public static function getEntryByAlbumId($album_id) {
    $stmt = OCP\DB::prepare('SELECT * FROM *PREFIX*gallery_sharing WHERE gallery_id = ?');
    return $stmt->execute(array($album_id));
  }

  public static function addShared($token, $albumId, $recursive) {
    $sql = 'INSERT INTO *PREFIX*gallery_sharing (token, gallery_id, recursive) VALUES (?, ?, ?)';
    $stmt = OCP\DB::prepare($sql);
    $stmt->execute(array($token, $albumId, $recursive));
  }

  public static function remove($albumId) {
    $stmt = OCP\DB::prepare('DELETE FROM *PREFIX*gallery_sharing WHERE gallery_id = ?');
    $stmt->execute(array($albumId));
  }
}

