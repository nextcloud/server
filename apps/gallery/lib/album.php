<?php

class OC_Gallery_Album{
	public static function create($owner, $name){
		$stmt = OC_DB::prepare('INSERT INTO *PREFIX*gallery_albums (uid_owner, album_name) VALUES (?, ?)');
		$stmt->execute(array($owner, $name));
	}
	public static function find($owner, $name=null){
		$sql = 'SELECT * FROM *PREFIX*gallery_albums WHERE uid_owner = ?';
		$args = array($owner);
		if (!is_null($name)){
			$sql .= ' AND album_name = ?';
			$args[] = $name;
		}
		$stmt = OC_DB::prepare($sql);
		return $stmt->execute($args);
	}
}
