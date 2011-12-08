<?php
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('gallery');

$a = array();
$result = OC_Gallery_Album::find(OC_User::getUser());

while ($r = $result->fetchRow()) {
  $album_name = $r['album_name'];
  $tmp_res = OC_Gallery_Photo::find($r['album_id']);
  $a[] = array('name' => $album_name, 'numOfItems' => min($tmp_res->numRows(), 10));
}

OC_JSON::success(array('albums'=>$a));

?>
