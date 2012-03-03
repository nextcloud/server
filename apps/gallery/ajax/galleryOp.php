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

require_once('../../../lib/base.php');

OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('gallery');

function handleRename($oldname, $newname) {
  OC_Gallery_Album::rename($oldname, $newname, OC_User::getUser());
  OC_Gallery_Album::changeThumbnailPath($oldname, $newname);
}

function handleRemove($name) {
  $album_id = OC_Gallery_Album::find(OC_User::getUser(), $name);
  $album_id = $album_id->fetchRow();
  $album_id = $album_id['album_id'];
  OC_Gallery_Album::remove(OC_User::getUser(), $name);
  OC_Gallery_Photo::removeByAlbumId($album_id);
}

function handleGetThumbnails($albumname) {
  OC_Response::enableCaching(3600 * 24); // 24 hour
  error_log(htmlentities($albumname));
  $thumbnail = OC::$CONFIG_DATADIRECTORY.'/../gallery/'.urldecode($albumname).'.png';
  header('Content-Type: '.OC_Image::getMimeTypeForFile($thumbnail));
  OC_Response::sendFile($thumbnail);
}

function handleGalleryScanning() {
  OC_Gallery_Scanner::cleanup();
  OC_JSON::success(array('albums' => OC_Gallery_Scanner::scan('/')));
}

function handleFilescan($cleanup) {
  if ($cleanup) OC_Gallery_Album::cleanup();
  $root = OC_Preferences::getValue(OC_User::getUser(), 'gallery', 'root', '').'/';
  $pathlist = OC_Gallery_Scanner::find_paths($root);
  sort($pathlist);
  OC_JSON::success(array('paths' => $pathlist));
}

function handlePartialCreate($path) {
  if (empty($path)) OC_JSON::error(array('cause' => 'No path specified'));
  if (!OC_Filesystem::is_dir($path.'/')) OC_JSON::error(array('cause' => 'Invalid path given'));

  $album = OC_Gallery_Album::find(OC_User::getUser(), null, $path);
  $albums = array();
  OC_Gallery_Scanner::scanDir($path, $albums);
  OC_JSON::success(array('album_details' => $albums));
}

function handleStoreSettings($root, $order) {
  if (!OC_Filesystem::file_exists($root)) {
    OC_JSON::error(array('cause' => 'No such file or directory'));
    return;
  }
  if (!OC_Filesystem::is_dir($root)) {
    OC_JSON::error(array('cause' => $root . ' is not a directory'));
    return;
  }

  $current_root = OC_Preferences::getValue(OC_User::getUser(),'gallery', 'root', '/');
  $root = trim(rtrim($root, '/'));
  $rescan = $current_root==$root?'no':'yes';
  OC_Preferences::setValue(OC_User::getUser(), 'gallery', 'root', $root);
  OC_Preferences::setValue(OC_User::getUser(), 'gallery', 'order', $order);
  OC_JSON::success(array('rescan' => $rescan));
}


function handleGetGalleries() {
  $a = array();

  $result = OC_Gallery_Album::find(OC_User::getUser());

  while ($r = $result->fetchRow()) {
    $album_name = $r['album_name'];
    $tmp_res = OC_Gallery_Photo::find($r['album_id']);

    $a[] = array('name' => utf8_encode($album_name), 'numOfItems' => min($tmp_res->numRows(), 10), 'bgPath' => OC::$WEBROOT.'/data/'.OC_User::getUser().'/gallery/'.$album_name.'.png');
  }

  OC_JSON::success(array('albums'=>$a));
}

if ($_GET['operation']) {
  switch($_GET['operation']) {
  case 'rename':
	  handleRename($_GET['oldname'], $_GET['newname']);
	  OC_JSON::success(array('newname' => $_GET['newname']));
	break;
  case 'remove':
	  handleRemove($_GET['name']);
	  OC_JSON::success();
    break;
  case 'get_covers':
    handleGetThumbnails(urldecode($_GET['albumname']));
    break;
  case 'scan':
    handleGalleryScanning();
    break;
  case 'filescan':
    handleFilescan($_GET['cleanup']);
    break;
  case 'partial_create':
    handlePartialCreate(urldecode($_GET['path']));
    break;
  case 'store_settings':
    handleStoreSettings($_GET['root'], $_GET['order']);
    break;
  case 'get_galleries':
    handleGetGalleries();
    break;
  default:
    OC_JSON::error(array('cause' => 'Unknown operation'));
  }
}
?>
