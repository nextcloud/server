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

function CroppedThumbnail($imgSrc,$thumbnail_width,$thumbnail_height) { //$imgSrc is a FILE - Returns an image resource.
    //getting the image dimensions  
		if(! function_exists('imagecreatefromjpeg'))
			OC_Log::write('gallery','GD module not installed',OC_Log::ERROR);

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
    $thumb = imagecreatetruecolor($thumbnail_width, $thumbnail_height); 
    imagecopyresampled($thumb, $process, 0, 0, ($x_mid-($thumbnail_width/2)), ($y_mid-($thumbnail_height/2)), $thumbnail_width, $thumbnail_height, $thumbnail_width, $thumbnail_height);

    imagedestroy($process);
    imagedestroy($myImage);
    return $thumb;
}

$box_size = 200;
$img = $_GET['img'];

$imagePath = OC_Filesystem::getLocalFile($img);

if(file_exists($imagePath))
{
	$image = CroppedThumbnail($imagePath, $box_size, $box_size);

	header('Content-Type: image/png');
	$offset = 3600 * 24;
	// calc the string in GMT not localtime and add the offset
	header("Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT");
	header('Cache-Control: max-age='.$offset.', must-revalidate');
	header('Pragma: public');

	imagepng($image);
	imagedestroy($image);
}
