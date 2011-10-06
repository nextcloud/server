<?php
require_once('../../lib/base.php');

OC_Util::checkLoggedIn();
OC_App::setActiveNavigationEntry( 'gallery_index' );


if (!isset($_GET['view'])) {
  $stmt = OC_DB::prepare('SELECT * FROM *PREFIX*gallery_albums WHERE uid_owner = ?');
  $result = $stmt->execute(array(OC_User::getUser()));

  $r = array();
  while ($row = $result->fetchRow())
    $r[] = $row;

  $tmpl = new OC_Template( 'gallery', 'index', 'user' );
  $tmpl->assign('r', $r);
  $tmpl->printPage();
} else {
  $stmt = OC_DB::prepare('SELECT * FROM *PREFIX*gallery_photos, *PREFIX*gallery_albums WHERE uid_owner = ? AND album_name = ? AND *PREFIX*gallery_albums.album_id = *PREFIX*gallery_photos.album_id');
  
  $result = $stmt->execute(array(OC_User::getUser(), $_GET['view']));

  $photos = array();
  while ($p = $result->fetchRow())
    $photos[] = $p['file_path'];
  
  $tmpl = new OC_Template( 'gallery', 'view_album', 'user' );
  $tmpl->assign('photos', $photos);
  $tmpl->assign('albumName', $_GET['view']);
  $tmpl->printPage();
}
?>
