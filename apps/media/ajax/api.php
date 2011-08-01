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

//no apps
$RUNTIME_NOAPPS=true;

require_once('../../../lib/base.php');
require_once('../lib_collection.php');
require_once('../lib_scanner.php');

error_reporting(E_ALL); //no script error reporting because of getID3

$arguments=$_POST;

if(!isset($_POST['action']) and isset($_GET['action'])){
	$arguments=$_GET;
}

foreach($arguments as &$argument){
	$argument=stripslashes($argument);
}
global $CONFIG_DATADIRECTORY;
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
OC_MEDIA_COLLECTION::$uid=OC_User::getUser();
unset($_SESSION['collection']);
if($arguments['action']){
	switch($arguments['action']){
		case 'delete':
			unset($_SESSION['collection']);
			$path=$arguments['path'];
			OC_MEDIA_COLLECTION::deleteSongByPath($path);
			$paths=explode(PATH_SEPARATOR,OC_Preferences::getValue(OC_User::getUser(),'media','paths',''));
			if(array_search($path,$paths)!==false){
				unset($paths[array_search($path,$paths)]);
				OC_Preferences::setValue(OC_User::getUser(),'media','paths',implode(PATH_SEPARATOR,$paths));
			}
		case 'get_collection':
			if(!isset($_SESSION['collection'])){
				$artists=OC_MEDIA_COLLECTION::getArtists();
				foreach($artists as &$artist){
					$artist['albums']=OC_MEDIA_COLLECTION::getAlbums($artist['artist_id']);
					foreach($artist['albums'] as &$album){
						$album['songs']=OC_MEDIA_COLLECTION::getSongs($artist['artist_id'],$album['album_id']);
					}
				}

				$_SESSION['collection']=json_encode($artists);
			}
			echo $_SESSION['collection'];
			break;
		case 'scan':
			unset($_SESSION['collection']);
			OC_DB::beginTransaction();
			set_time_limit(0); //recursive scan can take a while
			$path=$arguments['path'];
			if(OC_Filesystem::is_dir($path)){
				$paths=explode(PATH_SEPARATOR,OC_Preferences::getValue(OC_User::getUser(),'media','paths',''));
				if(array_search($path,$paths)===false){
					$paths[]=$path;
					OC_Preferences::setValue(OC_User::getUser(),'media','paths',implode(PATH_SEPARATOR,$paths));
				}
			}
			echo OC_MEDIA_SCANNER::scanFolder($path);
			OC_DB::commit();
			flush();
			break;
		case 'scanFile':
			unset($_SESSION['collection']);
			echo (OC_MEDIA_SCANNER::scanFile($arguments['path']))?'true':'false';
			break;
		case 'get_artists':
			echo json_encode(OC_MEDIA_COLLECTION::getArtists($arguments['search']));
			break;
		case 'get_albums':
			echo json_encode(OC_MEDIA_COLLECTION::getAlbums($arguments['artist'],$arguments['search']));
			break;
		case 'get_songs':
			echo json_encode(OC_MEDIA_COLLECTION::getSongs($arguments['artist'],$arguments['album'],$arguments['search']));
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
					echo json_encode($song);
				}
			}
			break;
		case 'play':
			ob_end_clean();
			
			$ftype=OC_Filesystem::getMimeType( $arguments['path'] );
			
			$songId=OC_MEDIA_COLLECTION::getSongByPath($arguments['path']);
			OC_MEDIA_COLLECTION::registerPlay($songId);
			
			header('Content-Type:'.$ftype);
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Accept-Ranges: bytes');
			header('Content-Length: '.OC_Filesystem::filesize($arguments['path']));
			
			OC_Filesystem::readfile($arguments['path']);
			exit;
		case 'find_music':
			echo json_encode(findMusic());
			exit;
	}
}

function findMusic($path='/'){
	$music=array();
	$dh=OC_Filesystem::opendir($path);
	if($dh){
		while($filename=readdir($dh)){
			if($filename[0]!='.'){
				$file=$path.'/'.$filename;
				if(OC_Filesystem::is_dir($file)){
					$music=array_merge($music,findMusic($file));
				}else{
					if(OC_MEDIA_SCANNER::isMusic($filename)){
						$music[]=$file;
					}
				}
			}
		}
	}
	return $music;
}

?>