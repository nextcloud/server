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
							if(self::scanFile($file)){
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
		if(substr($path,-3)=='mp3' and OC_Helper::canExecute("id3info") and OC_Helper::canExecute("mp3info")){//use the command line tool id3info if possible
			$output=array();
			$size=filesize($file);
			$length=0;
			$title='unknown';
			$album='unknown';
			$artist='unknown';
			$track=0;
			exec('id3info "'.$file.'"',$output);
			$data=array();
			foreach($output as $line) {
				switch(substr($line,0,3)){
					case '***'://comments
						break;
					case '==='://tag information
						$key=substr($line,4,4);
						$value=substr($line,strpos($line,':')+2);
						switch(strtolower($key)){
							case 'tit1':
							case 'tit2':
								$title=$value;
								break;
							case 'tpe1':
							case 'tpe2':
								$artist=$value;
								break;
							case 'talb':
								$album=$value;
								break;
							case 'trck':
								$track=$value;
								break;
						}
						break;
				}
			}
			$length=exec('mp3info -p "%S" "'.$file.'"');
		}else{
			if(!self::$getID3){
				self::$getID3=@new getID3();
			}
			$data=@self::$getID3->analyze($file);
			getid3_lib::CopyTagsToComments($data);
			if(!isset($data['comments'])){
				error_log("error reading id3 tags in '$file'");
				return;
			}
			if(!isset($data['comments']['artist'])){
				error_log("error reading artist tag in '$file'");
				$artist='unknown';
			}else{
				$artist=stripslashes($data['comments']['artist'][0]);
				$artist=utf8_encode($artist);
			}
			if(!isset($data['comments']['album'])){
				error_log("error reading album tag in '$file'");
				$album='unknown';
			}else{
				$album=stripslashes($data['comments']['album'][0]);
				$album=utf8_encode($album);
			}
			if(!isset($data['comments']['title'])){
				error_log("error reading title tag in '$file'");
				$title='unknown';
			}else{
				$title=stripslashes($data['comments']['title'][0]);
				$title=utf8_encode($title);
			}
			$size=$data['filesize'];
			$track=(isset($data['comments']['track']))?$data['comments']['track'][0]:0;
			$length=isset($data['playtime_seconds'])?round($data['playtime_seconds']):0;
		}
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
		return !($title=='unkown' && $artist=='unkown' && $album=='unkown');
	}
}