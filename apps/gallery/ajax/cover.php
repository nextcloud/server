<?php
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('gallery');

function CroppedThumbnail($imgSrc,$thumbnail_width,$thumbnail_height) { //$imgSrc is a FILE - Returns an image resource.
    //getting the image dimensions  
    list($width_orig, $height_orig) = getimagesize($imgSrc);   
    switch (strtolower(substr($imgSrc, strrpos($imgSrc, '.')+1))) {
      case "jpeg":
      case "jpg":
        $myImage = imagecreatefromjpeg($imgSrc);
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

$box_size = 200;
$album_name = $_GET['album'];
$x = $_GET['x'];

$stmt = OC_DB::prepare('SELECT `file_path` FROM *PREFIX*gallery_photos,*PREFIX*gallery_albums WHERE *PREFIX*gallery_albums.`uid_owner` = ? AND `album_name` = ? AND *PREFIX*gallery_photos.`album_id` == *PREFIX*gallery_albums.`album_id`');
$result = $stmt->execute(array(OC_User::getUser(), $album_name));
$x = min((int)($x/($box_size/$result->numRows())), $result->numRows()-1); // get image to display
$result->seek($x); // never throws
$path = $result->fetchRow();
$path = $path['file_path'];
$tmp = OC::$CONFIG_DATADIRECTORY . $path;
$imagesize = getimagesize($tmp);

header('Content-Type: image/png');
$image = CroppedThumbnail($tmp, $box_size, $box_size);

imagepng($image);
imagedestroy($image);
?>
