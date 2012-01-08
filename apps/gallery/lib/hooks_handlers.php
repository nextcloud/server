<?php

OC_Hook::connect("OC_Filesystem", "post_write", "OC_Gallery_Hooks_Handlers", "addPhotoFromPath");
OC_Hook::connect("OC_Filesystem", "delete", "OC_Gallery_Hooks_Handlers", "removePhoto");
OC_Hook::connect("OC_Filesystem", "post_rename", "OC_Gallery_Hooks_Handlers", "renamePhoto");

require_once(OC::$CLASSPATH['OC_Gallery_Album']);
require_once(OC::$CLASSPATH['OC_Gallery_Photo']);

class OC_Gallery_Hooks_Handlers {
  private static $APP_TAG = "Gallery";

  private static function isPhoto($filename) {
    if (substr(OC_Filesystem::getMimeType($filename), 0, 6) == "image/")
      return 1;
    return 0;
  }

  private static function createAlbum($path) {
    $new_album_name = trim(str_replace('/', '.', $path), '.');
    if ($new_album_name == '') $new_album_name = 'main';

    OC_Log::write(self::$APP_TAG, 'Creating new album '.$new_album_name, OC_Log::DEBUG);
    OC_Gallery_Album::create(OC_User::getUser(), $new_album_name, $path);

    return OC_Gallery_Album::find(OC_User::getUser(), null, $path);
  }

  public static function addPhotoFromPath($params) {
    if (!self::isPhoto($params['path'])) return;
    $fullpath = $params['path'];
    OC_Log::write(self::$APP_TAG, 'Adding file with path '. $fullpath, OC_Log::DEBUG);
    $path = substr($fullpath, 0, strrpos($fullpath, '/'));
    $album = OC_Gallery_Album::find(OC_User::getUser(), null, $path);

    if ($album->numRows() == 0) {
      $album = self::createAlbum($path);
    }
    $album = $album->fetchRow();
    $albumId = $album['album_id'];
    $photo = OC_Gallery_Photo::find($albumId, $fullpath);
    if ($photo->numRows() == 0) { // don't duplicate photo entries
      OC_Log::write(self::$APP_TAG, 'Adding new photo to album', OC_Log::DEBUG);
      OC_Gallery_Photo::create($albumId, $fullpath);
    }

  }

  public static function removePhoto($params) {
    $path = $params['path'];
    if (!self::isPhoto($path)) return;
    OC_Gallery_Photo::removeByPath($path);
  }

  public static function renamePhoto($params) {
    $olddir = substr($params['oldpath'], 0, strrpos($params['oldpath'], '/'));
    $newdir = substr($params['newpath'], 0, strrpos($params['newpath'], '/'));
    if (!self::isPhoto($params['newpath'])) return;
    $album;
    $newAlbumId;
    $oldAlbumId;
    if ($olddir == $newdir) {
      // album changing is not needed
      $album = OC_Gallery_Album::find(OC_User::getUser(), null, $olddir);
      if ($album->numRows() == 0) {
        $album = self::createAlbum($newdir);
      }
      $album = $album->fetchRow();
      $newAlbumId = $oldAlbumId = $album['album_id'];
    } else {
      $newalbum = OC_Gallery_Album::find(OC_User::getUser(), null, $newdir);
      $oldalbum = OC_Gallery_Album::find(OC_User::getUser(), null, $olddir);

      if ($newalbum->numRows() == 0) {
        $newalbum = self::createAlbum($newdir);
      }
      $newalbum = $newalbum->fetchRow();
      if ($oldalbum->numRows() == 0) {
        OC_Gallery_Photo::create($newalbum['album_id'], $params['newpath']);
        return;
      }
      $oldalbum = $oldalbum->fetchRow();
      $newAlbumId = $newalbum['album_id'];
      $oldAlbumId = $oldalbum['album_id'];

    }
    OC_Gallery_Photo::changePath($oldAlbumId, $newAlbumId, $params['oldpath'], $params['newpath']);
  }
}

?>
