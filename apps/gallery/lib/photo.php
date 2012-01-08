<?php

class OC_Gallery_Photo{
	public static function create($albumId, $img){
		$stmt = OC_DB::prepare('INSERT INTO *PREFIX*gallery_photos (album_id, file_path) VALUES (?, ?)');
		$stmt->execute(array($albumId, $img));
	}
	public static function find($albumId, $img=null){
		$sql = 'SELECT * FROM *PREFIX*gallery_photos WHERE album_id = ?';
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

  public static function removeByPath($path) {
    $stmt = OC_DB::prepare('DELETE FROM *PREFIX*gallery_photos WHERE file_path = ?');
    $stmt->execute(array($path));
  }

  public static function removeById($id) {
    $stmt = OC_DB::prepare('DELETE FROM *PREFIX*gallery_photos WHERE photo_id = ?');
    $stmt->execute(array($id));
  }

  public static function changePath($oldAlbumId, $newAlbumId, $oldpath, $newpath) {
    $stmt = OC_DB::prepare("UPDATE *PREFIX*gallery_photos SET file_path = ?, album_id = ? WHERE album_id = ? and file_path = ?");
    $stmt->execute(array($newpath, $newAlbumId, $oldAlbumId, $oldpath));
  }
}

