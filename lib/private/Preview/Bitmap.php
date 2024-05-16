<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Preview;

use Imagick;
use OCP\Files\File;
use OCP\IImage;
use Psr\Log\LoggerInterface;

/**
 * Creates a PNG preview using ImageMagick via the PECL extension
 *
 * @package OC\Preview
 */
abstract class Bitmap extends ProviderV2 {
	/**
	 * List of MIME types that this preview provider is allowed to process.
	 *
	 * These should correspond to the MIME types *identified* by Imagemagick
	 * for files to be processed by this provider. These do / will not
	 * necessarily need to match the MIME types stored in the database
	 * (which are identified by IMimeTypeDetector).
	 *
	 * @return string Regular expression
	 */
	abstract protected function getAllowedMimeTypes(): string;

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		$tmpPath = $this->getLocalFile($file);
		if ($tmpPath === false) {
			\OC::$server->get(LoggerInterface::class)->error(
				'Failed to get thumbnail for: ' . $file->getPath(),
				['app' => 'core']
			);
			return null;
		}

		// Creates \Imagick object from bitmap or vector file
		try {
			$bp = $this->getResizedPreview($tmpPath, $maxX, $maxY);
		} catch (\Exception $e) {
			\OC::$server->get(LoggerInterface::class)->info(
				'File: ' . $file->getPath() . ' Imagick says:',
				[
					'exception' => $e,
					'app' => 'core',
				]
			);
			return null;
		}

		$this->cleanTmpFiles();

		//new bitmap image object
		$image = new \OCP\Image();
		$image->loadFromData((string) $bp);
		//check if image object is valid
		return $image->valid() ? $image : null;
	}

	/**
	 * Returns a preview of maxX times maxY dimensions in PNG format
	 *
	 *    * The default resolution is already 72dpi, no need to change it for a bitmap output
	 *    * It's possible to have proper colour conversion using profileimage().
	 *    ICC profiles are here: http://www.color.org/srgbprofiles.xalter
	 *    * It's possible to Gamma-correct an image via gammaImage()
	 *
	 * @param string $tmpPath the location of the file to convert
	 * @param int $maxX
	 * @param int $maxY
	 *
	 * @return \Imagick
	 *
	 * @throws \Exception
	 */
	private function getResizedPreview($tmpPath, $maxX, $maxY) {
		$bp = new Imagick();

		// Validate mime type
		$bp->pingImage($tmpPath . '[0]');
		$mimeType = $bp->getImageMimeType();
		if (!preg_match($this->getAllowedMimeTypes(), $mimeType)) {
			throw new \Exception('File mime type does not match the preview provider: ' . $mimeType);
		}

		// Layer 0 contains either the bitmap or a flat representation of all vector layers
		$bp->readImage($tmpPath . '[0]');

		$bp = $this->resize($bp, $maxX, $maxY);

		$bp->setImageFormat('png');

		return $bp;
	}

	/**
	 * Returns a resized \Imagick object
	 *
	 * If you want to know more on the various methods available to resize an
	 * image, check out this link : @link https://stackoverflow.com/questions/8517304/what-the-difference-of-sample-resample-scale-resize-adaptive-resize-thumbnail-im
	 *
	 * @param \Imagick $bp
	 * @param int $maxX
	 * @param int $maxY
	 *
	 * @return \Imagick
	 */
	private function resize($bp, $maxX, $maxY) {
		[$previewWidth, $previewHeight] = array_values($bp->getImageGeometry());

		// We only need to resize a preview which doesn't fit in the maximum dimensions
		if ($previewWidth > $maxX || $previewHeight > $maxY) {
			// TODO: LANCZOS is the default filter, CATROM could bring similar results faster
			$bp->resizeImage($maxX, $maxY, imagick::FILTER_LANCZOS, 1, true);
		}

		return $bp;
	}
}
