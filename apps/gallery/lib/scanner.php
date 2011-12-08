<?php

class OC_Gallery_Scanner {
  public static function scan($root) {
    $albums = array();
    self::scanDir($root, $albums);
    return $albums;
  }

  public static function scanDir($path, &$albums) {
    $current_album = array('name'=> $path, 'imagesCount' => 0, 'images' => array());
    $current_album['name'] = str_replace('/', '.', str_replace(OC::$CONFIG_DATADIRECTORY, '', $current_album['name']));
    $current_album['name'] = ($current_album['name']==='')?'main':$current_album['name'];

    if ($dh = OC_Filesystem::opendir($path)) {
      while (($filename = readdir($dh)) !== false) {
        $filepath = $path.'/'.$filename;
        if (substr($filename, 0, 1) == '.') continue;
        if (OC_Filesystem::is_dir($filepath)) {
          self::scanDir($filepath, $albums);
        } elseif (self::isPhoto($path.'/'.$filename)) {
          $current_album['images'][] = $filepath;
        }
      }
    }
    $current_album['imagesCount'] = count($current_album['images']);
    $albums[] = $current_album;
    $result = OC_Gallery_Album::find(OC_User::getUser(), $current_album['name']);
    if ($result->numRows() == 0 && count($current_album['images'])) {
	    OC_Gallery_Album::create(OC_User::getUser(), $current_album['name']);
	    $result = OC_Gallery_Album::find(OC_User::getUser(), $current_album['name']);
    }
    $albumId = $result->fetchRow();
    $albumId = $albumId['album_id'];
    foreach ($current_album['images'] as $img) {
      $result = OC_Gallery_Photo::find($albumId, $img);
      if ($result->numRows() == 0) {
	      OC_Gallery_Photo::create($albumId, $img);
      }
    }
  }

  public static function isPhoto($filename) {
    if (substr(OC_Filesystem::getMimeType($filename), 0, 6) == "image/")
      return 1;
    return 0;
  }
}
?>
