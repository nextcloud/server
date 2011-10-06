<?php
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('gallery');

$a = array();
$stmt = OC_DB::prepare('SELECT * FROM *PREFIX*gallery_albums WHERE `uid_owner` = ?');
$result = $stmt->execute(array(OC_User::getUser()));

while ($r = $result->fetchRow()) {
  $album_name = $r['album_name'];
  $stmt = OC_DB::prepare('SELECT * FROM *PREFIX*gallery_photos WHERE `album_id` = ?');
  $tmp_res = $stmt->execute(array($r['album_id']));
  $a[] = array('name' => $album_name, 'numOfItems' => min($tmp_res->numRows(), 10));
}

OC_JSON::success(array('albums'=>$a));

?>
