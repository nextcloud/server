<?php
/**
 * Copyright (c) 2012 Frank Karlitschek frank@owncloud.org
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/*
TODO:
  - delete thumbnails if files change. 
  - make it work with external filesystem files. 
  - movies support
  - pdf support
  - mp3/id3 support
  - more file types

*/



class OC_Preview {

	// the thumbnail cache folder
	const THUMBNAILS_FOLDER = 'thumbnails';

	/**
	 * @brief return a preview of a file
	 * @param $file The path to the file where you want a thumbnail from
	 * @param $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param $scaleup Scale smaller images up to the thumbnail size or not. Might look ugly
	 * @return image
	*/
	static public function show($file,$maxX,$maxY,$scalingup) {
		// get the mimetype of the file
		$mimetype=explode('/',OC_FileSystem::getMimeType($file));
		// it´s an image
		if($mimetype[0]=='image'){
			OCP\Response::enableCaching(3600 * 24); // 24 hour
			$image=OC_PreviewImage::getThumbnail($file,$maxX,$maxY,$scalingup);
			$image->show();

		// it´s a video
		}elseif($mimetype[0]=='video'){
			OCP\Response::enableCaching(3600 * 24); // 24 hour
			OC_PreviewMovie::getThumbnail($file,$maxX,$maxY,$scalingup);

		// it´s something else. Let´s create a dummy preview
		}else{
			header('Content-type: image/png');
			OC_PreviewUnknown::getThumbnail($maxX,$maxY);
		}
	}


}



class OC_PreviewImage {

        public static function getThumbnail($path,$maxX,$maxY,$scalingup) {

                $thumbnails_view = new \OC_FilesystemView('/'.\OCP\User::getUser() .'/'.OC_Preview::THUMBNAILS_FOLDER);

                // is a preview already in the cache?
                if ($thumbnails_view->file_exists($path.'-'.$maxX.'-'.$maxY.'-'.$scalingup)) {
                        return new \OC_Image($thumbnails_view->getLocalFile($path.'-'.$maxX.'-'.$maxY.'-'.$scalingup));
                }

                // does the sourcefile exist?
                if (!\OC_Filesystem::file_exists($path)) {
                        \OC_Log::write('Preview', 'File '.$path.' don\'t exists', \OC_Log::WARN);
                        return false;
                }

                // open the source image
                $image = new \OC_Image();
                $image->loadFromFile(\OC_Filesystem::getLocalFile($path));
                if (!$image->valid()) return false;

                // fix the orientation
                $image->fixOrientation();

                // calculate the right preview size
                $Xsize=$image->width();
                $Ysize=$image->height();
                if (($Xsize/$Ysize)>($maxX/$maxY)) {
                        $factor=$maxX/$Xsize;
                } else {
                        $factor=$maxY/$Ysize;
                }

                // only scale up if requested
                if($scalingup==false) {
                        if($factor>1) $factor=1;
                }
                $newXsize=$Xsize*$factor;
                $newYsize=$Ysize*$factor;

                // resize
                $ret = $image->preciseResize($newXsize, $newYsize);
                if (!$ret) {
                        \OC_Log::write('Preview', 'Couldn\'t resize image', \OC_Log::ERROR);
                        unset($image);
                        return false;
                }

                // store in cache
                $l = $thumbnails_view->getLocalFile($path.'-'.$maxX.'-'.$maxY.'-'.$scalingup);
                $image->save($l);

                return $image;


        }



}


class OC_PreviewMovie {

        public static function isAvailable() {
		$check=shell_exec('ffmpeg');
		if($check==NULL) return(false); else return(true);
        }

        public static function getThumbnail($path,$maxX,$maxY,$scalingup) {
                $thumbnails_view = new \OC_FilesystemView('/'.\OCP\User::getUser() .'/'.OC_Preview::THUMBNAILS_FOLDER);

                // is a preview already in the cache?
                if ($thumbnails_view->file_exists($path.'-'.$maxX.'-'.$maxY.'-'.$scalingup)) {
                        return new \OC_Image($thumbnails_view->getLocalFile($path.'-'.$maxX.'-'.$maxY.'-'.$scalingup));
                }

                // does the sourcefile exist?
                if (!\OC_Filesystem::file_exists($path)) {
                        \OC_Log::write('Preview', 'File '.$path.' don\'t exists', \OC_Log::WARN);
                        return false;
                }

                // call ffmpeg to do the screenshot
                shell_exec('ffmpeg -y  -i {'.escapeshellarg($path).'} -f mjpeg -vframes 1 -ss 1 -s {'.escapeshellarg($maxX).'}x{'.escapeshellarg($maxY).'} {.'.$thumbnails_view->getLocalFile($path.'-'.$maxX.'-'.$maxY.'-'.$scalingup).'}');

                // output the generated Preview
                $thumbnails_view->getLocalFile($path.'-'.$maxX.'-'.$maxY.'-'.$scalingup);
                unset($thumbnails_view);
        }


}


class OC_PreviewUnknown {
        public static function getThumbnail($maxX,$maxY) {
			
		// check if GD is installed
		if(!extension_loaded('gd') || !function_exists('gd_info')) {
			OC_Log::write('preview', __METHOD__.'(): GD module not installed', OC_Log::ERROR);
			return false;
		}

		// create a white image
		$image = imagecreatetruecolor($maxX, $maxY);
		$color = imagecolorallocate($image, 255, 255, 255);
		imagefill($image, 0, 0, $color);

		// output the image
		imagepng($image);
		imagedestroy($image);
        }

}

