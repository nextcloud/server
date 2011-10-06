<?php
require_once('../../../lib/base.php');

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

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => 'You need to log in.')));
	exit();
}
$box_size = 200;
$album_name= $_GET['album_name'];

$stmt = OC_DB::prepare('SELECT `file_path` FROM *PREFIX*gallery_photos,*PREFIX*gallery_albums WHERE *PREFIX*gallery_albums.`uid_owner` = ? AND `album_name` = ? AND *PREFIX*gallery_photos.`album_id` = *PREFIX*gallery_albums.`album_id`');
$result = $stmt->execute(array(OC_User::getUser(), $album_name));

$numOfItems = min($result->numRows(),10);

$targetImg = imagecreatetruecolor($numOfItems*$box_size, $box_size);
$counter = 0;
while (($i = $result->fetchRow()) && $counter < $numOfItems) {
  $imagePath = OC::$CONFIG_DATADIRECTORY . $i['file_path'];
  CroppedThumbnail($imagePath, $box_size, $box_size, $targetImg, $counter*$box_size);
  $counter++;
}

header('Content-Type: image/png');

imagepng($targetImg);
imagedestroy($targetImg);
?>
