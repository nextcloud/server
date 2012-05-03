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

header('Content-type: text/html; charset=UTF-8') ;

OCP\JSON::checkAppEnabled('media');
require_once(OC::$APPSROOT . '/apps/media/lib_collection.php');
require_once(OC::$APPSROOT . '/apps/media/lib_scanner.php');

error_reporting(E_ALL); //no script error reporting because of getID3

$arguments=$_POST;

if(!isset($_POST['action']) and isset($_GET['action'])){
	$arguments=$_GET;
}

foreach($arguments as &$argument){
	$argument=stripslashes($argument);
}
@ob_clean();
if(!isset($arguments['artist'])){
	$arguments['artist']=0;
}
if(!isset($arguments['album'])){
	$arguments['album']=0;
}
if(!isset($arguments['search'])){
	$arguments['search']='';
}
OC_MEDIA_COLLECTION::$uid=OCP\USER::getUser();
if($arguments['action']){
	switch($arguments['action']){
		case 'delete':
			$path=$arguments['path'];
			OC_MEDIA_COLLECTION::deleteSongByPath($path);
			$paths=explode(PATH_SEPARATOR,OCP\Config::getUserValue(OCP\USER::getUser(),'media','paths',''));
			if(array_search($path,$paths)!==false){
				unset($paths[array_search($path,$paths)]);
				OCP\Config::setUserValue(OCP\USER::getUser(),'media','paths',implode(PATH_SEPARATOR,$paths));
			}
		case 'get_collection':
			$data=array();
			$data['artists']=OC_MEDIA_COLLECTION::getArtists();
			$data['albums']=OC_MEDIA_COLLECTION::getAlbums();
			$data['songs']=OC_MEDIA_COLLECTION::getSongs();
			OCP\JSON::encodedPrint($data);
			break;
		case 'scan':
			OCP\DB::beginTransaction();
			set_time_limit(0); //recursive scan can take a while
			$eventSource=new OC_EventSource();
			OC_MEDIA_SCANNER::scanCollection($eventSource);
			$eventSource->close();
			OCP\DB::commit();
			break;
		case 'scanFile':
			echo (OC_MEDIA_SCANNER::scanFile($arguments['path']))?'true':'false';
			break;
		case 'get_artists':
			OCP\JSON::encodedPrint(OC_MEDIA_COLLECTION::getArtists($arguments['search']));
			break;
		case 'get_albums':
			OCP\JSON::encodedPrint(OC_MEDIA_COLLECTION::getAlbums($arguments['artist'],$arguments['search']));
			break;
		case 'get_songs':
			OCP\JSON::encodedPrint(OC_MEDIA_COLLECTION::getSongs($arguments['artist'],$arguments['album'],$arguments['search']));
			break;
		case 'get_path_info':
			if(OC_Filesystem::file_exists($arguments['path'])){
				$songId=OC_MEDIA_COLLECTION::getSongByPath($arguments['path']);
				if($songId==0){
					unset($_SESSION['collection']);
					$songId= OC_MEDIA_SCANNER::scanFile($arguments['path']);
				}
				if($songId>0){
					$song=OC_MEDIA_COLLECTION::getSong($songId);
					$song['artist']=OC_MEDIA_COLLECTION::getArtistName($song['song_artist']);
					$song['album']=OC_MEDIA_COLLECTION::getAlbumName($song['song_album']);
					OCP\JSON::encodedPrint($song);
				}
			}
			break;
		case 'play':
			@ob_end_clean();
			
			$ftype=OC_Filesystem::getMimeType( $arguments['path'] );
			
			$songId=OC_MEDIA_COLLECTION::getSongByPath($arguments['path']);
			OC_MEDIA_COLLECTION::registerPlay($songId);
			
			header('Content-Type:'.$ftype);
			OCP\Response::enableCaching(3600 * 24); // 24 hour
			header('Accept-Ranges: bytes');
			header('Content-Length: '.OC_Filesystem::filesize($arguments['path']));
			$mtime = OC_Filesystem::filemtime($arguments['path']);
			OCP\Response::setLastModifiedHeader($mtime);
			
			OC_Filesystem::readfile($arguments['path']);
			exit;
		case 'find_music':
			$music=OC_FileCache::searchByMime('audio');
			$ogg=OC_FileCache::searchByMime('application','ogg');
			$music=array_merge($music,$ogg);
			OCP\JSON::encodedPrint($music);
			exit;
	}
}
?>