<?php

/**
* ownCloud - gallery application
*
* @author Bartek Przybylski
* @copyright 2012 Bartek Przybylski bart.p.pl@gmail.com
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

require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('gallery');

$a = array();

$result = OC_Gallery_Album::find(OC_User::getUser());

while ($r = $result->fetchRow()) {
  $album_name = $r['album_name'];
  $tmp_res = OC_Gallery_Photo::find($r['album_id']);

  $a[] = array('name' => $album_name, 'numOfItems' => min($tmp_res->numRows(), 10), 'bgPath' => OC::$WEBROOT.'/data/'.OC_User::getUser().'/gallery/'.$album_name.'.png');
}

OC_JSON::success(array('albums'=>$a));

?>
