<?php

OC_Hook::connect("OC_Filesystem", "post_write", "OC_Gallery_Hooks_Handlers", "addPhotoFromPath");

require_once(OC::$CLASSPATH['OC_Gallery_Album']);
require_once(OC::$CLASSPATH['OC_Gallery_Photo']);

class OC_Gallery_Hooks_Handlers {
  private static $APP_TAG = "Gallery";

  private static function isPhoto($filename) {
    if (substr(OC_Filesystem::getMimeType($filename), 0, 6) == "image/")
      return 1;
    return 0;
  }

  public static function addPhotoFromPath($params) {
    if (!self::isPhoto($params['path'])) return;
    $fullpath = $params['path'];
    OC_Log::write(self::$APP_TAG, 'Adding file with path '. $fullpath, OC_Log::DEBUG);
    $path = substr($fullpath, 0, strrpos($fullpath, '/'));
    $album = OC_Gallery_Album::find(OC_User::getUser(), null, $path);
    if ($album->numRows() == 0) {
      $new_album_name = trim(str_replace('/', '.', $fullpath));
      if ($new_album_name == '.') $new_album_name = 'main';
      OC_Gallery_Album::create(OC_User::getUser(), $new_album_name, $path);
      $album = OC_Gallery_Album::find(OC_User::getUser(), null, $path);
    }
    $album = $album->fetchRow();
    $albumId = $album['album_id'];
    OC_Gallery_Photo::create($albumId, $fullpath);

  }
}

?>
