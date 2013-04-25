<?php
/**
 * Copyright (c) 2013 Frank Karlitschek frank@owncloud.org
 * Copyrigjt (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class OC_Preview_Image extends OC_Preview_Provider{

	public function getMimeType(){
		return '/image\/.*/';
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

OC_Preview::registerProvider('OC_Preview_Image');