<?php

class OC_Gallery_Photo{
	public static function create($albumId, $img){
		$stmt = OC_DB::prepare('INSERT INTO *PREFIX*gallery_photos (album_id, file_path) VALUES (?, ?)');
		$stmt->execute(array($albumId, $img));
	}
	public static function find($albumId, $img=null){
		$sql = 'SELECT * FROM *PREFIX*gallery_photos WHERE album_id = ?';
		$args = array($albumId);
		$args = array($albumId);
		if (!is_null($img)){
			$sql .= ' AND file_path = ?';
			$args[] = $img;
		}
		$stmt = OC_DB::prepare($sql);
		return $stmt->execute($args);
	}
	public static function findForAlbum($owner, $album_name){
		$stmt = OC_DB::prepare('SELECT *'
			.' FROM *PREFIX*gallery_photos photos,'
				.' *PREFIX*gallery_albums albums'
			.' WHERE albums.uid_owner = ?'
				.' AND albums.album_name = ?'
				.' AND photos.album_id = albums.album_id');
		return $stmt->execute(array($owner, $album_name));
	}

}

