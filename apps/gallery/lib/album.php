<?php

class OC_Gallery_Album {
	public static function create($owner, $name, $path){
		$stmt = OC_DB::prepare('INSERT INTO *PREFIX*gallery_albums (uid_owner, album_name, album_path) VALUES (?, ?, ?)');
		$stmt->execute(array($owner, $name, $path));
	}
	
	public static function rename($oldname, $newname, $owner) {
	    $stmt = OC_DB::prepare('UPDATE OR IGNORE *PREFIX*gallery_albums SET album_name=? WHERE uid_owner=? AND album_name=?');
		$stmt->execute(array($newname, $owner, $oldname));
	}
	
	public static function remove($owner, $name=null) {
		$sql = 'DELETE FROM *PREFIX*gallery_albums WHERE uid_owner = ?';
		$args = array($owner);
		if (!is_null($name)){
			$sql .= ' AND album_name = ?';
			$args[] = $name;
		}
		$stmt = OC_DB::prepare($sql);
		return $stmt->execute($args);
	}
	
  public static function find($owner, $name=null, $path=null){
		$sql = 'SELECT * FROM *PREFIX*gallery_albums WHERE uid_owner = ?';
		$args = array($owner);
		if (!is_null($name)){
			$sql .= ' AND album_name = ?';
			$args[] = $name;
    }
    if (!is_null($path)){
      $sql .= ' AND album_path = ?';
      $args[] = $path;
    }
		$stmt = OC_DB::prepare($sql);
		return $stmt->execute($args);
	}

}

?>
