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

$box_size = 200;
$img = $_GET['img'];

$imagePath = OC_Filesystem::getLocalFile($img);

if(file_exists($imagePath)) {
  $image = new OC_Image($imagePath);
  $image->centerCrop();
  $image->resize($box_size, $box_size);
  $image->fixOrientation();

  $offset = 3600 * 24;
  // calc the string in GMT not localtime and add the offset
  header("Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT");
  header('Cache-Control: max-age='.$offset.', must-revalidate');
  header('Pragma: public');

  $image->show();
}
