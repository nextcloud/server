<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2018 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IImage;
use Psr\Log\LoggerInterface;

/**
 * Creates a JPG preview using ImageMagick via the PECL extension
 *
 * @package OC\Preview
 */
class HEIC extends ProviderV2 {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return '/image\/(x-)?hei(f|c)/';
	}

	/**
	 * {@inheritDoc}
	 */
	public function isAvailable(FileInfo $file): bool {
		return in_array('HEIC', \Imagick::queryFormats("HEI*"));
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		if (!$this->isAvailable($file)) {
			return null;
		}

		$tmpPath = $this->getLocalFile($file);
		if ($tmpPath === false) {
			\OC::$server->get(LoggerInterface::class)->error(
				'Failed to get thumbnail for: ' . $file->getPath(),
				['app' => 'core']
			);
			return null;
		}

		// Creates \Imagick object from the heic file
		try {
			$bp = $this->getResizedPreview($tmpPath, $maxX, $maxY);
			$bp->setFormat('jpg');
		} catch (\Exception $e) {
			\OC::$server->get(LoggerInterface::class)->error(
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
	 * Returns a preview of maxX times maxY dimensions in JPG format
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
		$bp = new \Imagick();

		// Some HEIC files just contain (or at least are identified as) other formats
		// like JPEG. We just need to check if the image is safe to process.
		$bp->pingImage($tmpPath . '[0]');
		$mimeType = $bp->getImageMimeType();
		if (!preg_match('/^image\/(x-)?(png|jpeg|gif|bmp|tiff|webp|hei(f|c)|avif)$/', $mimeType)) {
			throw new \Exception('File mime type does not match the preview provider: ' . $mimeType);
		}

		// Layer 0 contains either the bitmap or a flat representation of all vector layers
		$bp->readImage($tmpPath . '[0]');

		// Fix orientation from EXIF
		$bp->autoOrient();

		$bp->setImageFormat('jpg');

		$bp = $this->resize($bp, $maxX, $maxY);

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
			// If we want a small image (thumbnail) let's be most space- and time-efficient
			if ($maxX <= 500 && $maxY <= 500) {
				$bp->thumbnailImage($maxY, $maxX, true);
				$bp->stripImage();
			} else {
				// A bigger image calls for some better resizing algorithm
				// According to http://www.imagemagick.org/Usage/filter/#lanczos
				// the catrom filter is almost identical to Lanczos2, but according
				// to https://www.php.net/manual/en/imagick.resizeimage.php it is
				// significantly faster
				$bp->resizeImage($maxX, $maxY, \Imagick::FILTER_CATROM, 1, true);
			}
		}

		return $bp;
	}
}
