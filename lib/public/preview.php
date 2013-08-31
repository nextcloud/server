<?php
/**
 * Copyright (c) 2013 Frank Karlitschek frank@owncloud.org
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OCP;

/**
 * This class provides functions to render and show thumbnails and previews of files
 */
class Preview {

	/**
	 * @brief return a preview of a file
	 * @param $file The path to the file where you want a thumbnail from
	 * @param $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param $scaleup Scale smaller images up to the thumbnail size or not. Might look ugly
	 * @return image
	 */
	public static function show($file,$maxX=100,$maxY=75,$scaleup=false) {
		return(\OC_Preview::show($file,$maxX,$maxY,$scaleup));
	}



	public static function isMimeSupported($mimetype='*') {
		return \OC\Preview::isMimeSupported($mimetype);
	}

}
