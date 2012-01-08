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

function CroppedThumbnail($imgSrc,$thumbnail_width,$thumbnail_height, $tgtImg, $shift) { 
    //getting the image dimensions
    list($width_orig, $height_orig) = getimagesize($imgSrc); 
    switch (strtolower(substr($imgSrc, strrpos($imgSrc, '.')+1))) {
      case "jpeg":
      case "jpg":
      case "tiff":
        $myImage = imagecreatefromjpeg($imgSrc);
        break;
      case "png":
        $myImage = imagecreatefrompng($imgSrc);
        break;
      default:
        exit();
    }
    if(!$myImage) exit();
    $ratio_orig = $width_orig/$height_orig;
    
    if ($thumbnail_width/$thumbnail_height > $ratio_orig) {
       $new_height = $thumbnail_width/$ratio_orig;
       $new_width = $thumbnail_width;
    } else {
       $new_width = $thumbnail_height*$ratio_orig;
       $new_height = $thumbnail_height;
    }
    
    $x_mid = $new_width/2;  //horizontal middle
    $y_mid = $new_height/2; //vertical middle
    
    $process = imagecreatetruecolor(round($new_width), round($new_height)); 

    imagecopyresampled($process, $myImage, 0, 0, 0, 0, $new_width, $new_height, $width_orig, $height_orig);
    imagecopyresampled($tgtImg, $process, $shift, 0, ($x_mid-($thumbnail_width/2)), ($y_mid-($thumbnail_height/2)), $thumbnail_width, $thumbnail_height, $thumbnail_width, $thumbnail_height);

    imagedestroy($process);
    imagedestroy($myImage);
}

$box_size = 200;
$album_name= $_GET['album_name'];

$result = OC_Gallery_Photo::findForAlbum(OC_User::getUser(), $album_name);

$numOfItems = min($result->numRows(),10);

if ($numOfItems){
	$targetImg = imagecreatetruecolor($numOfItems*$box_size, $box_size);
}
else{
	$targetImg = imagecreatetruecolor($box_size, $box_size);
}
$counter = 0;
while (($i = $result->fetchRow()) && $counter < $numOfItems) {
	$imagePath = OC_Filesystem::getLocalFile($i['file_path']);
	if(file_exists($imagePath))
	{
		CroppedThumbnail($imagePath, $box_size, $box_size, $targetImg, $counter*$box_size);
		$counter++;
	}
}

header('Content-Type: image/png');

$offset = 3600 * 24;
// calc the string in GMT not localtime and add the offset
header("Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT");
header('Cache-Control: max-age='.$offset.', must-revalidate');
header('Pragma: public');

imagepng($targetImg);
imagedestroy($targetImg);
?>
