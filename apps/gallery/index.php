<?php

/**
* ownCloud - gallery application
*
* @author Bartek Przybylski
* @copyright 2012 Bartek Przybylski bartek@alefzero.eu
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/



OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('gallery');
OCP\App::setActiveNavigationEntry( 'gallery_index' );

if (!file_exists(OCP\Config::getSystemValue("datadirectory").'/'. OCP\USER::getUser() .'/gallery')) {
  mkdir(OCP\Config::getSystemValue("datadirectory").'/'. OCP\USER::getUser() .'/gallery');
}

if (!isset($_GET['view'])) {
  $result = OC_Gallery_Album::find(OCP\USER::getUser());

  $r = array();
  while ($row = $result->fetchRow())
    $r[] = $row;

  $tmpl = new OCP\Template( 'gallery', 'index', 'user' );
  $tmpl->assign('r', $r);
  $tmpl->printPage();
} else {
  $result = OC_Gallery_Photo::findForAlbum(OCP\USER::getUser(), $_GET['view']);

  $photos = array();
  while ($p = $result->fetchRow())
    $photos[] = $p['file_path'];
  
  $tmpl = new OCP\Template( 'gallery', 'view_album', 'user' );
  $tmpl->assign('photos', $photos);
  $tmpl->assign('albumName', $_GET['view']);
  $tmpl->printPage();
}
?>
