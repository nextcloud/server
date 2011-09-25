<?

require_once('../../../lib/base.php');

$album_name = $_GET['album'];

$stmt = OC_DB::prepare('SELECT file_path FROM *PREFIX*gallery_photos,*PREFIX*gallery_albums WHERE *PREFIX*gallery_albums.uid_owner = ? AND album_name = ? AND *PREFIX*gallery_photos.album_id == *PREFIX*gallery_albums.album_id');
$result = $stmt->execute(array(OC_User::getUser(), $album_name));
$images = array();
while ($i = $result->fetchRow()) {
  $images[] = $i['file_path'];
}

echo json_encode(array('status' => 'success', 'imageCount' => $result->numRows(), 'images' => $images));

?>
