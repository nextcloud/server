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
* This library is diconnectstributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

//we need to have the sha256 hash of passwords for ampache
OCP\Util::connectHook('OC_User','post_login','OC_MEDIA','loginListener');

//connect to the filesystem for auto updating
OCP\Util::connectHook('OC_Filesystem','post_write','OC_MEDIA','updateFile');

//listen for file deletions to clean the database if a song is deleted
OCP\Util::connectHook('OC_Filesystem','post_delete','OC_MEDIA','deleteFile');

//list for file moves to update the database
OCP\Util::connectHook('OC_Filesystem','post_rename','OC_MEDIA','moveFile');

class OC_MEDIA{
	/**
	 * get the sha256 hash of the password needed for ampache
	 * @param array $params, parameters passed from OC_Hook
	 */
	public static function loginListener($params){
		if(isset($_POST['user']) and $_POST['password']){
			$name=$_POST['user'];
			$query=OCP\DB::prepare("SELECT user_id from *PREFIX*media_users WHERE user_id LIKE ?");
			$uid=$query->execute(array($name))->fetchAll();
			if(count($uid)==0){
				$password=hash('sha256',$_POST['password']);
				$query=OCP\DB::prepare("INSERT INTO *PREFIX*media_users (user_id, user_password_sha256) VALUES (?, ?);");
				$query->execute(array($name,$password));
			}
		}
	}
	
	/**
	 *
	 */
	public static function updateFile($params){
		$path=$params['path'];
		if(!$path) return;
		require_once 'lib_scanner.php';
		require_once 'lib_collection.php';
		//fix a bug where there were multiply '/' in front of the path, it should only be one
		while($path[0]=='/'){
			$path=substr($path,1);
		}
		$path='/'.$path;
		OC_MEDIA_SCANNER::scanFile($path);
	}

	/**
	 *
	 */
	public static function deleteFile($params){
		$path=$params['path'];
		require_once 'lib_collection.php';
		OC_MEDIA_COLLECTION::deleteSongByPath($path);
	}

	public static function moveFile($params){
		require_once 'lib_collection.php';
		OC_MEDIA_COLLECTION::moveSong($params['oldpath'],$params['newpath']);
	}
}

class OC_MediaSearchProvider extends OC_Search_Provider{
	function search($query){
		require_once('lib_collection.php');
		$artists=OC_MEDIA_COLLECTION::getArtists($query);
		$albums=OC_MEDIA_COLLECTION::getAlbums(0,$query);
		$songs=OC_MEDIA_COLLECTION::getSongs(0,0,$query);
		$results=array();
		foreach($artists as $artist){
			$results[]=new OC_Search_Result($artist['artist_name'],'',OCP\Util::linkTo( 'media', 'index.php').'#artist='.urlencode($artist['artist_name']),'Music');
		}
		foreach($albums as $album){
			$artist=OC_MEDIA_COLLECTION::getArtistName($album['album_artist']);
			$results[]=new OC_Search_Result($album['album_name'],'by '.$artist,OCP\Util::linkTo( 'media', 'index.php').'#artist='.urlencode($artist).'&album='.urlencode($album['album_name']),'Music');
		}
		foreach($songs as $song){
			$minutes=floor($song['song_length']/60);
			$secconds=$song['song_length']%60;
			$artist=OC_MEDIA_COLLECTION::getArtistName($song['song_artist']);
			$album=OC_MEDIA_COLLECTION::getalbumName($song['song_album']);
			$results[]=new OC_Search_Result($song['song_name'],"by $artist, in $album $minutes:$secconds",OCP\Util::linkTo( 'media', 'index.php').'#artist='.urlencode($artist).'&album='.urlencode($album).'&song='.urlencode($song['song_name']),'Music');
		}
		return $results;
	}
}

