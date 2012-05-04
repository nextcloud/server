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

header('Content-type: text/html; charset=UTF-8') ;
 

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('gallery');

function handleRename($oldname, $newname) {
  OC_Gallery_Album::rename($oldname, $newname, OCP\USER::getUser());
  OC_Gallery_Album::changeThumbnailPath($oldname, $newname);
}

function handleRemove($name) {
  $album_id = OC_Gallery_Album::find(OCP\USER::getUser(), $name);
  $album_id = $album_id->fetchRow();
  $album_id = $album_id['album_id'];
  OC_Gallery_Album::remove(OCP\USER::getUser(), $name);
  OC_Gallery_Photo::removeByAlbumId($album_id);
}

function handleGetThumbnails($albumname) {
  OCP\Response::enableCaching(3600 * 24); // 24 hour
  $thumbnail = OC::$CONFIG_DATADIRECTORY.'/../gallery/'.urldecode($albumname).'.png';
  header('Content-Type: '.OC_Image::getMimeTypeForFile($thumbnail));
  OCP\Response::sendFile($thumbnail);
}

function handleGalleryScanning() {
  OCP\DB::beginTransaction();
  set_time_limit(0);
  OC_Gallery_Album::cleanup();
  $eventSource = new OC_EventSource();
  OC_Gallery_Scanner::scan($eventSource);
  $eventSource->close();
  OCP\DB::commit();
}

function handleFilescan($cleanup) {
  if ($cleanup) OC_Gallery_Album::cleanup();
  $pathlist = OC_Gallery_Scanner::find_paths();
  sort($pathlist);
  OCP\JSON::success(array('paths' => $pathlist));
}

function handleStoreSettings($root, $order) {
  if (!OC_Filesystem::file_exists($root)) {
    OCP\JSON::error(array('cause' => 'No such file or directory'));
    return;
  }
  if (!OC_Filesystem::is_dir($root)) {
    OCP\JSON::error(array('cause' => $root . ' is not a directory'));
    return;
  }

  $current_root = OCP\Config::getUserValue(OCP\USER::getUser(),'gallery', 'root', '/');
  $root = trim($root);
  $root = rtrim($root, '/').'/';
  $rescan = $current_root==$root?'no':'yes';
  OCP\Config::setUserValue(OCP\USER::getUser(), 'gallery', 'root', $root);
  OCP\Config::setUserValue(OCP\USER::getUser(), 'gallery', 'order', $order);
  OCP\JSON::success(array('rescan' => $rescan));
}

function handleGetGallery($path) {
  $a = array();
  $root = OCP\Config::getUserValue(OCP\USER::getUser(),'gallery', 'root', '/');
  $path = utf8_decode(rtrim($root.$path,'/'));
  if($path == '') $path = '/';
  $pathLen = strlen($path);
  $result = OC_Gallery_Album::find(OCP\USER::getUser(), null, $path);
  $album_details = $result->fetchRow();

  $result = OC_Gallery_Album::find(OCP\USER::getUser(), null, null, $path);

  while ($r = $result->fetchRow()) {
    $album_name = $r['album_name'];
    $size=OC_Gallery_Album::getAlbumSize($r['album_id']);
    // this is a fallback mechanism and seems expensive
    if ($size == 0) $size = OC_Gallery_Album::getIntermediateGallerySize($r['album_path']);

    $a[] = array('name' => utf8_encode($album_name), 'numOfItems' => min($size, 10),'path'=>substr($r['album_path'], $pathLen));
  }
  
  $result = OC_Gallery_Photo::find($album_details['album_id']);

  $p = array();

  while ($r = $result->fetchRow()) {
    $p[] = utf8_encode($r['file_path']);
  }

  $r = OC_Gallery_Sharing::getEntryByAlbumId($album_details['album_id']);
  $shared = false;
  $recursive = false;
  $token = '';
  if ($row = $r->fetchRow()) {
    $shared = true;
    $recursive = ($row['recursive'] == 1)? true : false;
    $token = $row['token'];
  }

  OCP\JSON::success(array('albums'=>$a, 'photos'=>$p, 'shared' => $shared, 'recursive' => $recursive, 'token' => $token));
}

function handleShare($path, $share, $recursive) {
  $recursive = $recursive == 'true' ? 1 : 0;
  $owner = OCP\USER::getUser();
  $root = OCP\Config::getUserValue(OCP\USER::getUser(),'gallery', 'root', '/');
  $path = utf8_decode(rtrim($root.$path,'/'));
  if($path == '') $path = '/';
  $r = OC_Gallery_Album::find($owner, null, $path);
  if ($row = $r->fetchRow()) {
    $albumId = $row['album_id'];
  } else {
    OCP\JSON::error(array('cause' => 'Couldn\'t find requested gallery'));
    exit;
  }
    
  if ($share == false) {
      OC_Gallery_Sharing::remove($albumId);
      OCP\JSON::success(array('sharing' => false));
  } else { // share, yeah \o/
    $r = OC_Gallery_Sharing::getEntryByAlbumId($albumId);
    if (($row = $r->fetchRow())) { // update entry
      OC_Gallery_Sharing::updateSharingByToken($row['token'], $recursive);
      OCP\JSON::success(array('sharing' => true, 'token' => $row['token'], 'recursive' => $recursive == 1 ? true : false ));
    } else { // and new sharing entry
      $date = new DateTime();
      $token = md5($owner . $date->getTimestamp());
      OC_Gallery_Sharing::addShared($token, intval($albumId), $recursive);
      OCP\JSON::success(array('sharing' => true, 'token' => $token, 'recursive' => $recursive == 1 ? true : false ));
    }
  }
}


if ($_GET['operation']) {
  switch($_GET['operation']) {
  case 'rename':
	  handleRename($_GET['oldname'], $_GET['newname']);
	  OCP\JSON::success(array('newname' => $_GET['newname']));
	break;
  case 'remove':
	  handleRemove($_GET['name']);
	  OCP\JSON::success();
    break;
  case 'get_covers':
    handleGetThumbnails(urldecode($_GET['albumname']));
    break;
  case 'scan':
    handleGalleryScanning();
    break;
  case 'store_settings':
    handleStoreSettings($_GET['root'], $_GET['order']);
    break;
  case 'get_gallery':
    handleGetGallery($_GET['path']);
    break;
  case 'share':
    handleShare($_GET['path'], $_GET['share'] == 'true' ? true : false, $_GET['recursive']);
    break;
  default:
    OCP\JSON::error(array('cause' => 'Unknown operation'));
  }
}
?>
