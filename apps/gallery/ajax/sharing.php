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

 
OCP\JSON::callCheck();

if (!isset($_GET['token']) || !isset($_GET['operation'])) {
  OCP\JSON::error(array('cause' => 'Not enought arguments'));
  exit;
}

$operation = $_GET['operation'];
$token = $_GET['token'];

if (!OC_Gallery_Sharing::isTokenValid($token)) {
  OCP\JSON::error(array('cause' => 'Given token is not valid'));
  exit;
}

function handleGetGallery($token, $path) {
  $owner = OC_Gallery_Sharing::getTokenOwner($token);
  $apath = OC_Gallery_Sharing::getPath($token);

  if ($path == false)
    $root = $apath;
  else
    $root =  rtrim($apath,'/').$path;

  $r = OC_Gallery_Album::find($owner, null, $root);
  $albums = array();
  $photos = array();
  $albumId = -1;
  if ($row = $r->fetchRow()) {
    $albumId = $row['album_id'];
  }
  if ($albumId != -1) {

    if (OC_Gallery_Sharing::isRecursive($token)) {
      $r = OC_Gallery_Album::find($owner, null, null, $root);
      while ($row = $r->fetchRow())
        $albums[] = $row['album_name'];
    }

    $r = OC_Gallery_Photo::find($albumId);
    while ($row = $r->fetchRow())
      $photos[] = $row['file_path'];
  }

  OCP\JSON::success(array('albums' => $albums, 'photos' => $photos));
}

function handleGetThumbnail($token, $imgpath) {
  $owner = OC_Gallery_Sharing::getTokenOwner($token);
  $image = OC_Gallery_Photo::getThumbnail($imgpath, $owner);
  if ($image) {
    OCP\Response::enableCaching(3600 * 24); // 24 hour
    $image->show();
  }
}

function handleGetAlbumThumbnail($token, $albumname)
{
  $owner = OC_Gallery_Sharing::getTokenOwner($token);
  $file = OCP\Config::getSystemValue("datadirectory").'/'. $owner .'/gallery/'.$albumname.'.png';
  $image = new OC_Image($file);
  if ($image->valid()) {
    $image->centerCrop();
    $image->resize(200);
    $image->fixOrientation();
    OCP\Response::enableCaching(3600 * 24); // 24 hour
    $image->show();
  }
}

function handleGetPhoto($token, $photo) {
  $owner = OC_Gallery_Sharing::getTokenOwner($token);
  $file = OCP\Config::getSystemValue( "datadirectory", OC::$SERVERROOT."/data" ).'/'.$owner.'/files'.urldecode($photo);
  header('Content-Type: '.OC_Image::getMimeTypeForFile($file));
  OCP\Response::sendFile($file);
}

switch ($operation) {
  case 'get_gallery':
    handleGetGallery($token, isset($_GET['path'])? $_GET['path'] : false);
    break;
  case 'get_thumbnail':
    handleGetThumbnail($token, urldecode($_GET['img']));
    break;
  case 'get_album_thumbnail':
    handleGetAlbumThumbnail($token, urldecode($_GET['albumname']));
    break;
  case 'get_photo':
    handleGetPhoto($token, urldecode($_GET['photo']));
    break;
}

