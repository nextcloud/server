<?php

/**
* ownCloud - gallery application
*
* @author Bartek Przybylski
* @copyright 2012 Bartek Przybylski bart.p.pl@gmail.com
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

OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_post_write, "OC_Gallery_Hooks_Handlers", "addPhotoFromPath");
OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_delete, "OC_Gallery_Hooks_Handlers", "removePhoto");
//OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_post_rename, "OC_Gallery_Hooks_Handlers", "renamePhoto");

require_once(OC::$CLASSPATH['OC_Gallery_Album']);
require_once(OC::$CLASSPATH['OC_Gallery_Photo']);

class OC_Gallery_Hooks_Handlers {
  private static $APP_TAG = "Gallery";

  private static function isPhoto($filename) {
    $ext = strtolower(substr($filename, strrpos($filename, '.')+1));
    return $ext=='png' || $ext=='jpeg' || $ext=='jpg' || $ext=='gif';
  }

  private static function directoryContainsPhotos($dirpath) {
    $dirhandle = OC_Filesystem::opendir($dirpath.'/');
    if ($dirhandle != FALSE) {
      while (($filename = readdir($dirhandle)) != FALSE) {
        if ($filename[0] == '.') continue;
        if (self::isPhoto($dirpath.'/'.$filename)) return true;
      }
    }
    return false;
  }

  private static function createAlbum($path) {
    $new_album_name = trim(str_replace('/', '.', $path), '.');
    if ($new_album_name == '')
      $new_album_name = 'main';

    OCP\Util::writeLog(self::$APP_TAG, 'Creating new album '.$new_album_name, OCP\Util::DEBUG);
    OC_Gallery_Album::create(OCP\USER::getUser(), $new_album_name, $path);

    return OC_Gallery_Album::find(OCP\USER::getUser(), null, $path);
  }

  public static function pathInRoot($path) {
    $root = OCP\Config::getUserValue(OCP\USER::getUser(), 'gallery', 'root', '/');
    return substr($path, 0, strlen($path)>strlen($root)?strlen($root):strlen($path)) == $root;
  }

  public static function addPhotoFromPath($params) {
    $fullpath = $params[OC_Filesystem::signal_param_path];
    $fullpath = rtrim(dirname($fullpath),'/').'/'.basename($fullpath);

    if (!self::isPhoto($fullpath)) return;

    $a = OC_Gallery_Album::find(OCP\USER::getUser(), null, dirname($fullpath));
    if (!($r = $a->fetchRow())) {
      OC_Gallery_Album::create(OCP\USER::getUser(), basename(dirname($fullpath)), dirname($fullpath));
      $a = OC_Gallery_Album::find(OCP\USER::getUser(), null, dirname($fullpath));
      $r = $a->fetchRow();
    }
    $albumId = $r['album_id'];
    $p = OC_Gallery_Album::find($albumId, $fullpath);
    if (!($p->fetchRow()))
      OC_Gallery_Photo::create($albumId, $fullpath);
  }

  public static function removePhoto($params) {
    $fullpath = $params[OC_Filesystem::signal_param_path];
    $fullpath = rtrim(dirname($fullpath),'/').'/'.basename($fullpath);

    if (OC_Filesystem::is_dir($fullpath)) {
      OC_Gallery_Album::remove(OCP\USER::getUser(), null, $fullpath);
    } elseif (self::isPhoto($fullpath)) {
      $a = OC_Gallery_Album::find(OCP\USER::getUser(), null, rtrim(dirname($fullpath),'/'));
      if (($r = $a->fetchRow())) {
        OC_Gallery_Photo::removeByPath($fullpath, $r['album_id']);
        $p = OC_Gallery_Photo::findForAlbum(OCP\USER::getUser(), $r['album_name']);
        if (!($p->fetchRow())) {
          OC_Gallery_Album::remove(OCP\USER::getUser(), null, dirname($fullpath));
        }
      }
    }
  }

  public static function renamePhoto($params) {
    $oldpath = $params[OC_Filesystem::signal_param_oldpath];
    $newpath = $params[OC_Filesystem::signal_param_newpath];
    if (OC_Filesystem::is_dir($newpath.'/') && self::directoryContainsPhotos($newpath)) {
      OC_Gallery_Album::changePath($oldpath, $newpath, OCP\USER::getUser());
    } elseif (self::isPhoto($newpath)) {
      $olddir = dirname($oldpath);
      $newdir = dirname($newpath);
      if ($olddir == '') $olddir = '/';
      if ($newdir == '') $newdir = '/';
      if (!self::isPhoto($newpath)) return;
      OCP\Util::writeLog(self::$APP_TAG, 'Moving photo from '.$oldpath.' to '.$newpath, OCP\Util::DEBUG);
      $album;
      $newAlbumId;
      $oldAlbumId;
      if ($olddir == $newdir) {
        // album changing is not needed
        $albums = OC_Gallery_Album::find(OCP\USER::getUser(), null, $olddir);
        $album = $albums->fetchRow();
        if (!$album) {
          $albums = self::createAlbum($newdir);
          $album = $albums->fetchRow();
        }
        $newAlbumId = $oldAlbumId = $album['album_id'];
      } else {
        $newalbum = OC_Gallery_Album::find(OCP\USER::getUser(), null, $newdir);
        $oldalbum = OC_Gallery_Album::find(OCP\USER::getUser(), null, $olddir);

        if (!($newalbum = $newalbum->fetchRow())) {
          $newalbum = self::createAlbum($newdir);
          $newalbum = $newalbum->fetchRow();
        }
        $oldalbum = $oldalbum->fetchRow();
        if (!$oldalbum) {
          OC_Gallery_Photo::create($newalbum['album_id'], $newpath);
          return;
        }
        $newAlbumId = $newalbum['album_id'];
        $oldAlbumId = $oldalbum['album_id'];

      }
      OC_Gallery_Photo::changePath($oldAlbumId, $newAlbumId, $oldpath, $newpath);
    }
  }
}

?>
