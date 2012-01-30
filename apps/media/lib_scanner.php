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

require_once('getID3/getid3/getid3.php');

//class for scanning directories for music
class OC_MEDIA_SCANNER{
	static private $getID3=false;
	
	//these are used to store which artists and albums we found, it can save a lot of addArtist/addAlbum calls
	static private $artists=array();
	static private $albums=array();//stored as "$artist/$album" to allow albums with the same name from different artists
	
	/**
	 * scan a folder for music
	 * @param string $path
	 * @return int the number of songs found
	 */
	public static function scanFolder($path){
		if (OC_Filesystem::is_dir($path)) {
			$songs=0;
			if ($dh = OC_Filesystem::opendir($path)) {
				while (($filename = readdir($dh)) !== false) {
					if($filename<>'.' and $filename<>'..' and substr($filename,0,1)!='.'){
						$file=$path.'/'.$filename;
						if(OC_Filesystem::is_dir($file)){
							$songs+=self::scanFolder($file);
						}elseif(OC_Filesystem::is_file($file)){
							$data=self::scanFile($file);
							if($data){
								$songs++;
							}
						}
					}
				}
			}
		}elseif(OC_Filesystem::is_file($path)){
			$songs=1;
			self::scanFile($path);
		}else{
			$songs=0;
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
			OC_Log::write('media',"error reading id3 tags in '$file'",OC_Log::WARN);
			return;
		}
		if(!isset($data['comments']['artist'])){
			OC_Log::write('media',"error reading artist tag in '$file'",OC_Log::WARN);
			$artist='unknown';
		}else{
			$artist=stripslashes($data['comments']['artist'][0]);
		}
		if(!isset($data['comments']['album'])){
			OC_Log::write('media',"error reading album tag in '$file'",OC_Log::WARN);
			$album='unknown';
		}else{
			$album=stripslashes($data['comments']['album'][0]);
		}
		if(!isset($data['comments']['title'])){
			OC_Log::write('media',"error reading title tag in '$file'",OC_Log::WARN);
			$title='unknown';
		}else{
			$title=stripslashes($data['comments']['title'][0]);
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
	 * quick check if a song is a music file by checking the extention, not as good as a proper mimetype check but way faster
	 * @param string $filename
	 * @return bool
	 */
	public static function isMusic($filename){
		$ext=strtolower(substr($filename,strrpos($filename,'.')+1));
		return $ext=='mp3' || $ext=='flac' || $ext=='m4a' || $ext=='ogg' || $ext=='oga';
	}
}
