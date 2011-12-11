<?php
require_once('../../lib/base.php');

OC_Util::checkLoggedIn();
OC_Util::checkAppEnabled('gallery');
OC_App::setActiveNavigationEntry( 'gallery_index' );


if (!isset($_GET['view'])) {
  $result = OC_Gallery_Album::find(OC_User::getUser());

  $r = array();
  while ($row = $result->fetchRow())
    $r[] = $row;

  $tmpl = new OC_Template( 'gallery', 'index', 'user' );
  $tmpl->assign('r', $r);
  $tmpl->printPage();
} else {
  $result = OC_Gallery_Photo::findForAlbum(OC_User::getUser(), $_GET['view']);

  $photos = array();
  while ($p = $result->fetchRow())
    $photos[] = $p['file_path'];
  
  $tmpl = new OC_Template( 'gallery', 'view_album', 'user' );
  $tmpl->assign('photos', $photos);
  $tmpl->assign('albumName', $_GET['view']);
  $tmpl->printPage();
}
?>
