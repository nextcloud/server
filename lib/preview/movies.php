<?php
/**
 * Copyright (c) 2013 Frank Karlitschek frank@owncloud.org
 * Copyrigjt (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
if(!is_null(shell_exec('ffmpeg -version'))){

	class OC_Preview_Movie extends OC_Preview_Provider{

		public function getMimeType(){
			return '/video\/.*/';
		}
		
		public function getThumbnail($path,$maxX,$maxY,$scalingup,$fileview) {
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

	OC_Preview::registerProvider('OC_Preview_Movie');
}