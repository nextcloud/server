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


//class for managing a music collection
class OC_MEDIA_COLLECTION{
	public static $uid;
	private static $artistIdCache=array();
	private static $albumIdCache=array();
	private static $songIdCache=array();
	private static $queries=array();
	
	/**
	* get the id of an artist (case-insensitive)
	* @param string name
	* @return int
	*/
	public static function getArtistId($name){
		if(empty($name)){
			return 0;
		}
		$name=strtolower($name);
		if(isset(self::$artistIdCache[$name])){
			return self::$artistIdCache[$name];
		}else{
			$query=OCP\DB::prepare("SELECT artist_id FROM *PREFIX*media_artists WHERE lower(artist_name) LIKE ?");
			$artists=$query->execute(array($name))->fetchAll();
			if(is_array($artists) and isset($artists[0])){
				self::$artistIdCache[$name]=$artists[0]['artist_id'];
				return $artists[0]['artist_id'];
			}else{
				return 0;
			}
		}
	}

	/**
	* get the id of an album (case-insensitive)
	* @param string name
	* @param int artistId
	* @return int
	*/
	public static function getAlbumId($name,$artistId){
		if(empty($name)){
			return 0;
		}
		$name=strtolower($name);
		if(!isset(self::$albumIdCache[$artistId])){
			self::$albumIdCache[$artistId]=array();
		}
		if(isset(self::$albumIdCache[$artistId][$name])){
			return self::$albumIdCache[$artistId][$name];
		}else{
			$query=OCP\DB::prepare("SELECT album_id FROM *PREFIX*media_albums WHERE lower(album_name) LIKE ? AND album_artist=?");
			$albums=$query->execute(array($name,$artistId))->fetchAll();
			if(is_array($albums) and isset($albums[0])){
				self::$albumIdCache[$artistId][$name]=$albums[0]['album_id'];
				return $albums[0]['album_id'];
			}else{
				return 0;
			}
		}
	}

	/**
	* get the id of an song (case-insensitive)
	* @param string name
	* @param int artistId
	* @param int albumId
	* @return int
	*/
	public static function getSongId($name,$artistId,$albumId){
		if(empty($name)){
			return 0;
		}
		$name=strtolower($name);
		if(!isset(self::$albumIdCache[$artistId])){
			self::$albumIdCache[$artistId]=array();
		}
		if(!isset(self::$albumIdCache[$artistId][$albumId])){
			self::$albumIdCache[$artistId][$albumId]=array();
		}
		if(isset(self::$albumIdCache[$artistId][$albumId][$name])){
			return self::$albumIdCache[$artistId][$albumId][$name];
		}else{
			$uid=$_SESSION['user_id'];
			$query=OCP\DB::prepare("SELECT song_id FROM *PREFIX*media_songs WHERE song_user=? AND lower(song_name) LIKE ? AND song_artist=? AND song_album=?");
			$songs=$query->execute(array($uid,$name,$artistId,$albumId))->fetchAll();
			if(is_array($songs) and isset($songs[0])){
				self::$albumIdCache[$artistId][$albumId][$name]=$songs[0]['song_id'];
				return $songs[0]['song_id'];
			}else{
				return 0;
			}
		}
	}
	
	/**
	* Get the list of artists that (optionally) match a search string
	* @param string search optional
	* @return array the list of artists found
	*/
	static public function getArtists($search='%',$exact=false){
		$uid=self::$uid;
		if(empty($uid)){
			$uid=self::$uid=$_SESSION['user_id'];
		}
		if(!$exact and $search!='%'){
			$search="%$search%";
		}elseif($search==''){
			$search='%';
		}
		$query=OCP\DB::prepare("SELECT DISTINCT artist_name, artist_id FROM *PREFIX*media_artists
			INNER JOIN *PREFIX*media_songs ON artist_id=song_artist WHERE artist_name LIKE ? AND song_user=? ORDER BY artist_name");
		$result=$query->execute(array($search,self::$uid));
		return $result->fetchAll();
	}
	
	/**
	* Add an artists to the database
	* @param string name
	* @return integer the artist_id of the added artist
	*/
	static public function addArtist($name){
		$name=trim($name);
		if($name==''){
			return 0;
		}
		//check if the artist is already in the database
		$artistId=self::getArtistId($name);
		if($artistId!=0){
			return $artistId;
		}else{
			$query=OCP\DB::prepare("INSERT INTO `*PREFIX*media_artists` (`artist_name`) VALUES (?)");
			$result=$query->execute(array($name));
			return self::getArtistId($name);;
		}
	}
	
	/**
	* Get the list of albums that (optionally) match an artist and/or search string
	* @param integer artist optional
	* @param string search optional
	* @return array the list of albums found
	*/
	static public function getAlbums($artist=0,$search='%',$exact=false){
		$uid=self::$uid;
		if(empty($uid)){
			$uid=self::$uid=$_SESSION['user_id'];
		}
		$cmd="SELECT DISTINCT album_name, album_artist, album_id
			FROM *PREFIX*media_albums INNER JOIN *PREFIX*media_songs ON album_id=song_album WHERE song_user=? ";
		$params=array(self::$uid);
		if($artist!=0){
			$cmd.="AND album_artist = ? ";
			array_push($params,$artist);
		}
		if($search!='%'){
			$cmd.="AND album_name LIKE ? ";
			if(!$exact){
				$search="%$search%";
			}
			array_push($params,$search);
		}
		$cmd.=' ORDER BY album_name';
		$query=OCP\DB::prepare($cmd);
		return $query->execute($params)->fetchAll();
	}
	
	/**
	* Add an album to the database
	* @param string name
	* @param integer artist
	* @return integer the album_id of the added artist
	*/
	static public function addAlbum($name,$artist){
		$name=trim($name);
		if($name==''){
			return 0;
		}
		//check if the album is already in the database
		$albumId=self::getAlbumId($name,$artist);
		if($albumId!=0){
			return $albumId;
		}else{
			$query=OCP\DB::prepare("INSERT INTO  `*PREFIX*media_albums` (`album_name` ,`album_artist`) VALUES ( ?, ?)");
			$query->execute(array($name,$artist));
			return self::getAlbumId($name,$artist);
		}
	}
	
	/**
	* Get the list of songs that (optionally) match an artist and/or album and/or search string
	* @param integer artist optional
	* @param integer album optional
	* @param string search optional
	* @return array the list of songs found
	*/
	static public function getSongs($artist=0,$album=0,$search='',$exact=false){
		$uid=self::$uid;
		if(empty($uid)){
			$uid=self::$uid=$_SESSION['user_id'];
		}
		$params=array($uid);
		if($artist!=0){
			$artistString="AND song_artist = ?";
			array_push($params,$artist);
		}else{
			$artistString='';
		}
		if($album!=0){
			$albumString="AND song_album = ?";
			array_push($params,$album);
		}else{
			$albumString='';
		}
		if($search){
			if(!$exact){
				$search="%$search%";
			}
			$searchString ="AND song_name LIKE ?";
			array_push($params,$search);
		}else{
			$searchString='';
		}
		$query=OCP\DB::prepare("SELECT * FROM *PREFIX*media_songs WHERE song_user=? $artistString $albumString $searchString ORDER BY song_track, song_name, song_path");
		return $query->execute($params)->fetchAll();
	}
	
	/**
	* Add an song to the database
	* @param string name
	* @param string path
	* @param integer artist
	* @param integer album
	* @return integer the song_id of the added artist
	*/
	static public function addSong($name,$path,$artist,$album,$length,$track,$size){
		$name=trim($name);
		$path=trim($path);
		if($name=='' or $path==''){
			return 0;
		}
		$uid=OCP\USER::getUser();
		//check if the song is already in the database
		$songId=self::getSongId($name,$artist,$album);
		if($songId!=0){
			$songInfo=self::getSong($songId);
			self::moveSong($songInfo['song_path'],$path);
			return $songId;
		}else{
			if(!isset(self::$queries['addsong'])){
				$query=OCP\DB::prepare("INSERT INTO  `*PREFIX*media_songs` (`song_name` ,`song_artist` ,`song_album` ,`song_path` ,`song_user`,`song_length`,`song_track`,`song_size`,`song_playcount`,`song_lastplayed`)
				VALUES (?, ?, ?, ?,?,?,?,?,0,0)");
				self::$queries['addsong']=$query;
			}else{
				$query=self::$queries['addsong'];
			}
			$query->execute(array($name,$artist,$album,$path,$uid,$length,$track,$size));
			$songId=OCP\DB::insertid('*PREFIX*media_songs_song');
// 			self::setLastUpdated();
			return self::getSongId($name,$artist,$album);
		}
	}
	
	public static function getSongCount(){
		$query=OCP\DB::prepare("SELECT COUNT(song_id) AS count FROM *PREFIX*media_songs");
		$result=$query->execute()->fetchAll();
		return $result[0]['count'];
	}
	
	public static function getArtistCount(){
		$query=OCP\DB::prepare("SELECT COUNT(artist_id) AS count FROM *PREFIX*media_artists");
		$result=$query->execute()->fetchAll();
		return $result[0]['count'];
	}
	
	public static function getAlbumCount(){
		$query=OCP\DB::prepare("SELECT COUNT(album_id) AS count FROM *PREFIX*media_albums");
		$result=$query->execute()->fetchAll();
		return $result[0]['count'];
	}
	
	public static function getArtistName($artistId){
		$query=OCP\DB::prepare("SELECT artist_name FROM *PREFIX*media_artists WHERE artist_id=?");
		$artist=$query->execute(array($artistId))->fetchAll();
		if(count($artist)>0){
			return $artist[0]['artist_name'];
		}else{
			return '';
		}
	}
	
	public static function getAlbumName($albumId){
		$query=OCP\DB::prepare("SELECT album_name FROM *PREFIX*media_albums WHERE album_id=?");
		$album=$query->execute(array($albumId))->fetchAll();
		if(count($album)>0){
			return $album[0]['album_name'];
		}else{
			return '';
		}
	}
	
	public static function getSong($id){
		$query=OCP\DB::prepare("SELECT * FROM *PREFIX*media_songs WHERE song_id=?");
		$song=$query->execute(array($id))->fetchAll();
		if(count($song)>0){
			return $song[0];
		}else{
			return '';
		}
	}
	
	/**
	 * get the number of songs in a directory
	 * @param string $path
	 */
	public static function getSongCountByPath($path){
		$query=OCP\DB::prepare("SELECT COUNT(song_id) AS count FROM *PREFIX*media_songs WHERE song_path LIKE ?");
		$result=$query->execute(array("$path%"))->fetchAll();
		return $result[0]['count'];
	}

	/**
	 * remove a song from the database by path
	 * @param string $path the path of the song
	 *
	 * if a path of a folder is passed, all songs stored in the folder will be removed from the database
	 */
	public static function deleteSongByPath($path){
		$query=OCP\DB::prepare("DELETE FROM *PREFIX*media_songs WHERE song_path LIKE ?");
		$query->execute(array("$path%"));
	}

	/**
	 * increase the play count of a song
	 * @param int songId
	 */
	public static function registerPlay($songId){
		$now=time();
		$query=OCP\DB::prepare('UPDATE *PREFIX*media_songs SET song_playcount=song_playcount+1, song_lastplayed=? WHERE song_id=? AND song_lastplayed<?');
		$query->execute(array($now,$songId,$now-60));
	}

	/**
	 * get the id of the song by path
	 * @param string $path
	 * @return int
	 */
	public static function getSongByPath($path){
		$query=OCP\DB::prepare("SELECT song_id FROM *PREFIX*media_songs WHERE song_path = ?");
		$result=$query->execute(array($path));
		if($row=$result->fetchRow()){
			return $row['song_id'];
		}else{
			return 0;
		}
	}
	
	/**
	 * set the path of a song
	 * @param string $oldPath
	 * @param string $newPath
	 */
	public static function moveSong($oldPath,$newPath){
		$query=OCP\DB::prepare("UPDATE *PREFIX*media_songs SET song_path = ? WHERE song_path = ?");
		$query->execute(array($newPath,$oldPath));
	}
}

?>
