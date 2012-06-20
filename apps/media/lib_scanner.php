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

require_once('getid3/getid3.php');

//class for scanning directories for music
class OC_MEDIA_SCANNER{
	static private $getID3=false;
	
	//these are used to store which artists and albums we found, it can save a lot of addArtist/addAlbum calls
	static private $artists=array();
	static private $albums=array();//stored as "$artist/$album" to allow albums with the same name from different artists
	
	/**
	 * scan a folder for music
	 * @param OC_EventSource eventSource (optional)
	 * @return int the number of songs found
	 */
	public static function scanCollection($eventSource=null){
		$music=OC_FileCache::searchByMime('audio');
		$ogg=OC_FileCache::searchByMime('application','ogg');
		$music=array_merge($music,$ogg);
		$eventSource->send('count',count($music));
		$songs=0;
		foreach($music as $file){
			self::scanFile($file);
			$songs++;
			if($eventSource){
				$eventSource->send('scanned',array('file'=>$file,'count'=>$songs));
			}
		}
		if($eventSource){
			$eventSource->send('done',$songs);
		}
		return $songs;
	}

	/**
	 * scan a file for music
	 * @param string $path
	 * @return boolean
	 */
	public static function scanFile($path){
		$file=OC_Filesystem::getLocalFile($path);
		if(!self::isMusic($path)){
			return;
		}
		if(!self::$getID3){
			self::$getID3=@new getID3();
			self::$getID3->encoding='UTF-8';
		}
		$data=@self::$getID3->analyze($file);
		getid3_lib::CopyTagsToComments($data);
		if(!isset($data['comments'])){
			OCP\Util::writeLog('media',"error reading id3 tags in '$file'",OCP\Util::WARN);
			return;
		}
		if(!isset($data['comments']['artist'])){
			OCP\Util::writeLog('media',"error reading artist tag in '$file'",OCP\Util::WARN);
			$artist='unknown';
		}else{
			$artist=OCP\Util::sanitizeHTML(stripslashes($data['comments']['artist'][0]));
		}
		if(!isset($data['comments']['album'])){
			OCP\Util::writeLog('media',"error reading album tag in '$file'",OCP\Util::WARN);
			$album='unknown';
		}else{
			$album=OCP\Util::sanitizeHTML(stripslashes($data['comments']['album'][0]));
		}
		if(!isset($data['comments']['title'])){
			OCP\Util::writeLog('media',"error reading title tag in '$file'",OCP\Util::WARN);
			$title='unknown';
		}else{
			$title=OCP\Util::sanitizeHTML(stripslashes($data['comments']['title'][0]));
		}
		$size=$data['filesize'];
		if (isset($data['comments']['track']))
		{
			$track = $data['comments']['track'][0];
		}
		else if (isset($data['comments']['track_number']))
		{
			$track = $data['comments']['track_number'][0];
			$track = explode('/',$track);
			$track = $track[0];
		}
		else
		{
			$track = 0;
		}
		$length=isset($data['playtime_seconds'])?round($data['playtime_seconds']):0;

		if(!isset(self::$artists[$artist])){
			$artistId=OC_MEDIA_COLLECTION::addArtist($artist);
			self::$artists[$artist]=$artistId;
		}else{
			$artistId=self::$artists[$artist];
		}
		if(!isset(self::$albums[$artist.'/'.$album])){
			$albumId=OC_MEDIA_COLLECTION::addAlbum($album,$artistId);
			self::$albums[$artist.'/'.$album]=$albumId;
		}else{
			$albumId=self::$albums[$artist.'/'.$album];
		}
		$songId=OC_MEDIA_COLLECTION::addSong($title,$path,$artistId,$albumId,$length,$track,$size);
		return (!($title=='unkown' && $artist=='unkown' && $album=='unkown'))?$songId:0;
	}

	/**
	 * quick check if a song is a music file by checking the extension, not as good as a proper mimetype check but way faster
	 * @param string $filename
	 * @return bool
	 */
	public static function isMusic($filename){
		$ext=strtolower(substr($filename,strrpos($filename,'.')+1));
		return $ext=='mp3' || $ext=='flac' || $ext=='m4a' || $ext=='ogg' || $ext=='oga';
	}
}
