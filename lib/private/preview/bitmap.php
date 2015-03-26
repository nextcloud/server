<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Preview;

abstract class Bitmap extends Provider {
	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		$tmpPath = $fileview->toTmpFile($path);

		//create imagick object from bitmap or vector file
		try {
			// Layer 0 contains either the bitmap or
			// a flat representation of all vector layers
			$bp = new \Imagick($tmpPath . '[0]');

			$bp->setImageFormat('png');
		} catch (\Exception $e) {
			\OC_Log::write('core', $e->getmessage(), \OC_Log::ERROR);
			return false;
		}

		unlink($tmpPath);

		//new bitmap image object
		$image = new \OC_Image($bp);
		//check if image object is valid
		return $image->valid() ? $image : false;
	}
}
