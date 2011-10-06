<?php

/**
* ownCloud - media plugin
*
* @author Robin Appelman
* @copyright 2010 Robin Appelman icewind1991@gmail.com
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

$_POST=$_GET; //debug

require_once('../../lib/base.php');
OC_JSON::checkAppEnabled('media');
require_once('lib_collection.php');

$user=isset($_POST['user'])?$_POST['user']:'';
$pass=isset($_POST['pass'])?$_POST['pass']:'';
if(OC_User::checkPassword($user,$pass)){
	OC_Util::setupFS($user);
	OC_MEDIA_COLLECTION::$uid=$user;
}else{
	exit;
}

if(isset($_POST['play']) and $_POST['play']=='true'){
	if(!isset($_POST['song'])){
		exit;
	}
	$song=OC_MEDIA_COLLECTION::getSong($_POST['song']);
	$ftype=OC_Filesystem::getMimeType( $song['song_path'] );
	header('Content-Type:'.$ftype);
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: '.OC_Filesystem::filesize($song['song_path']));

	OC_Filesystem::readfile($song['song_path']);
}

$artist=isset($_POST['artist'])?'%'.$_POST['artist'].'%':'';
$album=isset($_POST['album'])?'%'.$_POST['album'].'%':'';
$song=isset($_POST['song'])?$_POST['song']:'';

$artist=OC_MEDIA_COLLECTION::getArtistId($artist);
$album=OC_MEDIA_COLLECTION::getAlbumId($album,$artist);

$songs=OC_MEDIA_COLLECTION::getSongs($artist,$album,$song);

$baseUrl=OC_Util::getServerURL().OC_Helper::linkTo('media','tomahawk.php');

$results=array();
foreach($songs as $song) {
	$results[] = (Object) array(
		'artist' => OC_MEDIA_COLLECTION::getArtistName($song['song_artist']),
		'album' => OC_MEDIA_COLLECTION::getAlbumName($song['song_album']),
		'track' => $song['song_name'],
		'source' => 'ownCloud',
		'mimetype' => OC_Filesystem::getMimeType($song['song_path']),
		'extension' => substr($song['song_path'],strrpos($song['song_path'],'.')),
		'url' => $baseUrl.'?play=true&song='.$song['song_id'],
		'bitrate' => round($song['song_id']/$song['song_length'],0),
		'duration' => round($song['song_length'],0),
		'size' => $song['song_size'],
		'score' => (float)1.0
	);
}
OC_JSON::encodedPrint($results);
?>
