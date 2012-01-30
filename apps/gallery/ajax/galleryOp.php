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
require_once(OC::$CLASSPATH['OC_Gallery_Album']);
require_once(OC::$CLASSPATH['OC_Gallery_Scanner']);
OC_JSON::checkAppEnabled('gallery');

function handleRename($oldname, $newname) {
  OC_JSON::checkLoggedIn();
  OC_Gallery_Album::rename($oldname, $newname, OC_User::getUser());
  OC_Gallery_Album::changeThumbnailPath($oldname, $newname);
}

function handleRemove($name) {
  OC_JSON::checkLoggedIn();
  $album_id = OC_Gallery_Album::find(OC_User::getUser(), $name);
  $album_id = $album_id->fetchRow();
  $album_id = $album_id['album_id'];
  OC_Gallery_Album::remove(OC_User::getUser(), $name);
  OC_Gallery_Photo::removeByAlbumId($album_id);
}

function handleGetThumbnails($albumname) {
  OC_JSON::checkLoggedIn();
  $photo = new OC_Image();
  $photo->loadFromFile(OC::$CONFIG_DATADIRECTORY.'/../gallery/'.$albumname.'.png');
  $photo->show();
}

function handleGalleryScanning() {
  OC_JSON::checkLoggedIn();
  OC_Gallery_Scanner::cleanup();
  OC_JSON::success(array('albums' => OC_Gallery_Scanner::scan('/')));
}

function handleFilescan() {
  OC_JSON::checkLoggedIn();
  $pathlist = OC_Gallery_Scanner::find_paths('/');
  sort($pathlist);
  OC_JSON::success(array('paths' => $pathlist));
}

function handlePartialCreate($path) {
  OC_JSON::checkLoggedIn();
  if (empty($path)) OC_JSON::error(array('cause' => 'No path specified'));
  if (!OC_Filesystem::is_dir($path)) OC_JSON::error(array('cause' => 'Invalid path given'));

  $album = OC_Gallery_Album::find(OC_User::getUser(), null, $path);
  $albums;
  OC_Gallery_Scanner::scanDir($path, $albums);
  OC_JSON::success(array('album_details' => $albums));
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
    handleGetThumbnails($_GET['albumname']);
    break;
  case 'scan':
    handleGalleryScanning();
    break;
  case 'filescan':
    handleFilescan();
    break;
  case 'partial_create':
    handlePartialCreate($_GET['path']);
    break;
  default:
    OC_JSON::error(array('cause' => 'Unknown operation'));
  }
}
?>
