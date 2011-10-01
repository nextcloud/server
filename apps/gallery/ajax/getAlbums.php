<?
require_once('../../../lib/base.php');

if (!OC_User::IsLoggedIn()) {
  echo json_encode(array('status' => 'error', 'message' => 'You need to log in'));
  exit();
}

$a = array();
$stmt = OC_DB::prepare('SELECT * FROM *PREFIX*gallery_albums WHERE uid_owner = ?');
$result = $stmt->execute(array(OC_User::getUser()));

while ($r = $result->fetchRow()) {
  $album_name = $r['album_name'];
  $stmt = OC_DB::prepare('SELECT * FROM *PREFIX*gallery_photos WHERE album_id = ?');
  $tmp_res = $stmt->execute(array($r['album_id']));
  $a[] = array('name' => $album_name, 'numOfItems' => min($tmp_res->numRows(), 10));
}

echo json_encode(array('status'=>'success', 'albums'=>$a));

?>
