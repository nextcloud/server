<?php
/**
 * @copyright Copyright (c) 2019, Michael Bonfils (<bonfils.michael@protonmail.com>)
 *
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

use OC\Archive\ZIP;
use OCP\Files\File;
use OCP\IImage;

class CBZ extends ProviderV2 {

	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return '/application\/comicbook+zip/';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		$fileName = $this->getLocalFile($file);

		$zp = new ZIP($fileName);

		$name = null;
		$names = $zp->getFiles();
		foreach($names as $name) {
			$size = $zp->filesize($name);
			/* skip empty file and directory entry */
			if ($size > 0) {
				break;
			}
		}
		if (!$name) {
			return null;
		}

		$maxSizeForImages = \OC::$server->getConfig()->getSystemValue('preview_max_filesize_image', 50);

		if ($maxSizeForImages !== -1 && $size > ($maxSizeForImages * 1024 * 1024)) {
			return null;
		}

		
		$image = new \OC_Image();
		$image->loadFromData($zp->getFile($name));
		$image->fixOrientation();
		$this->cleanTmpFiles();

		if ($image->valid()) {
			$image->scaleDownToFit($maxX, $maxY);
			return $image;
		}
		return null;
	}
}
