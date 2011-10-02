<?php
require_once('../../../lib/base.php');

function CroppedThumbnail($imgSrc,$thumbnail_width,$thumbnail_height) { //$imgSrc is a FILE - Returns an image resource.
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

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => 'You need to log in.')));
	exit();
}
$box_size = 200;
$img = $_GET['img'];

$tmp = OC::$CONFIG_DATADIRECTORY . $img;

header('Content-Type: image/png');
$image = CroppedThumbnail($tmp, $box_size, $box_size);

imagepng($image);
imagedestroy($image);
?>
