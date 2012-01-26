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

require_once('base.php'); // base lib
require_once('images_utils.php');

class OC_Gallery_Scanner {

  public static function scan($root) {
    $albums = array();
    self::scanDir($root, $albums);
    return $albums;
  }

  public static function cleanUp() {
    $stmt = OC_DB::prepare('DELETE FROM *PREFIX*gallery_albums');
    $stmt->execute(array());
    $stmt = OC_DB::prepare('DELETE FROM *PREFIX*gallery_photos');
    $stmt->execute(array());
  }

  public static function scanDir($path, &$albums) {
    $current_album = array('name'=> $path, 'imagesCount' => 0, 'images' => array());
    $current_album['name'] = str_replace('/', '.', str_replace(OC::$CONFIG_DATADIRECTORY, '', $current_album['name']));
    $current_album['name'] = ($current_album['name']==='.') ?
                             'main' :
                             trim($current_album['name'],'.');

    if ($dh = OC_Filesystem::opendir($path)) {
      while (($filename = readdir($dh)) !== false) {
        $filepath = ($path[strlen($path)-1]=='/'?$path:$path.'/').$filename;
        if (substr($filename, 0, 1) == '.') continue;
        if (self::isPhoto($path.'/'.$filename)) {
          $current_album['images'][] = $filepath;
        }
      }
    }
    $current_album['imagesCount'] = count($current_album['images']);
    $albums['imagesCount'] = $current_album['imagesCount'];
    $albums['albumName'] = $current_album['name'];

    $result = OC_Gallery_Album::find(OC_User::getUser(), /*$current_album['name']*/ null, $path);
    // don't duplicate galleries with same path (bug oc-33)
    if ($result->numRows() == 0 && count($current_album['images'])) {
      OC_Gallery_Album::create(OC_User::getUser(), $current_album['name'], $path);
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
    if (count($current_album['images'])) {
      self::createThumbnail($current_album['name'],$current_album['images']);
    }
  }

  public static function createThumbnail($albumName, $files) {
    $file_count = min(count($files), 10);
    $thumbnail = imagecreatetruecolor($file_count*200, 200);
    for ($i = 0; $i < $file_count; $i++) {
		$imagePath = OC_Filesystem::getLocalFile($files[$i]);
      CroppedThumbnail($imagePath, 200, 200, $thumbnail, $i*200);
    }
    imagepng($thumbnail, OC_Config::getValue("datadirectory").'/'. OC_User::getUser() .'/gallery/' . $albumName.'.png');
  }

  public static function isPhoto($filename) {
    $ext = strtolower(substr($filename, strrpos($filename, '.')+1));
    return $ext=='png' || $ext=='jpeg' || $ext=='jpg' || $ext=='gif';
  }

  public static function find_paths($path) {
    $ret = array();
    $dirres;
    $addpath = FALSE;
    if (($dirres = OC_Filesystem::opendir($path)) == FALSE) return $ret;

    while (($file = readdir($dirres)) != FALSE) {
      if ($file[0] == '.') continue;
      if (OC_Filesystem::is_dir($path.$file))
        $ret = array_merge($ret, self::find_paths($path.$file.'/'));
      if (self::isPhoto($path.$file)) $addpath = TRUE;
    }

    if ($addpath) $ret[] = $path;

    return $ret;
  }
}
?>
