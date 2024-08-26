<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021, Nextcloud
 *
 * @license AGPL-3.0-or-later
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
namespace OC\Image;

use OCP\IImage;

/**
 * Class for basic image manipulation using Imagick
 */
class Imagick extends Common {

	/**
	 * Get the corresponding imageType
	 * see \Imagick::queryFormats()
	 */
	private function formatToImageType(string $loader): int {
		switch ($loader) {
			case 'GIF':
			case 'GIF87':
				return IMAGETYPE_GIF;
				break;
			case 'PNG':
			case 'PNG00':
			case 'PNG24':
			case 'PNG32':
			case 'PNG48':
			case 'PNG64':
			case 'PNG8':
				return IMAGETYPE_PNG;
				break;
			case 'JPG':
			case 'JPEG':
				return IMAGETYPE_JPEG;
				break;
			case 'WEBP':
				return IMAGETYPE_WEBP;
				break;
			default:
				throw new \Exception(__METHOD__ . '(): "' . $loader . '" is not supported.');
		}
	}

	/**
	 * @inheritDoc
	 */
	public function valid(): bool {
		if (is_object($this->resource) && $this->resource instanceof \Imagick) {
			return true;
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function width(): int {
		return $this->resource->getImageWidth();
	}

	/**
	 * @inheritDoc
	 */
	public function height(): int {
		return $this->resource->getImageHeight();
	}

	/**
	 * @inheritDoc
	 */
	protected function _write($filePath = null, $mimeType = null): bool {
		try {
			if ($mimeType !== null) {
				$compression = \Imagick::COMPRESSION_UNDEFINED;
				switch ($mimeType) {
					case 'image/gif':
						$imageType = $this->resource->setImageFormat('GIF');
						break;
					case 'image/jpeg':
						$imageType = $this->resource->setImageFormat('JPEG');
						break;
					case 'image/png':
						$imageType = $this->resource->setImageFormat('PNG');
						break;
					case 'image/x-xbitmap':
						$imageType = $this->resource->setImageFormat('XBM');
						break;
					case 'image/bmp':
					case 'image/x-ms-bmp':
						$imageType = $this->resource->setImageFormat('BMP');
						break;
					default:
						throw new \Exception(__METHOD__ . '(): "' . $mimeType . '" is not supported when forcing a specific output format');
				}
			}

			$imageType = $this->formatToImageType($this->resource->getImageFormat());
			switch ($imageType) {
				case IMAGETYPE_GIF:
					$compression = \Imagick::COMPRESSION_LZW;
					break;
				case IMAGETYPE_JPEG:
					$compression = \Imagick::COMPRESSION_JPEG;
					$this->resource->setImageCompressionQuality($this->getJpegQuality());
					break;
				case IMAGETYPE_PNG:
					$compression = \Imagick::COMPRESSION_ZIP;
					break;
			}
			$this->resource->setImageCompression($compression_type);
			return $this->resource->writeImage($filePath);
		} catch (\ImagickException $e) {
			$this->logger->error(__METHOD__ . '(): Error wrtinig image.', ['app' => 'core']);
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function setResource($resource): void {
		if (is_object($resource) && $resource instanceof \Imagick) {
			$this->resource = $resource;
			return;
		}
		throw new \InvalidArgumentException('Supplied resource is not of type "Imagick".');
	}

	/**
	 * @inheritDoc
	 */
	public function data(): ?string {
		if (!$this->valid()) {
			return null;
		}

		try {
			$compression = \Imagick::COMPRESSION_UNDEFINED;
			switch ($this->mimeType) {
				case "image/png":
					$compression = \Imagick::COMPRESSION_ZIP;
					break;
				case "image/jpeg":
					$compression = \Imagick::COMPRESSION_JPEG;
					$this->resource->setImageCompressionQuality($this->getJpegQuality());
					break;
				case "image/gif":
					$compression = \Imagick::COMPRESSION_LZW;
					break;
				default:
					$this->logger->info(__METHOD__ . '(): Could not guess mime-type', ['app' => 'core']);
					break;
			}
			$this->resource->setImageCompression($compression_type);
			return $this->resource->getImageBlob();
		} catch (\ImagickException $e) {
			$this->logger->error(__METHOD__ . '(): Error getting image data.', ['app' => 'core']);
			return null;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getOrientation(): int {
		return $this->resource->getImageOrientation();
	}

	/**
	 * @inheritDoc
	 */
	public function fixOrientation(): bool {
		$o = $this->getOrientation();
		$this->logger->debug(__METHOD__ . '() Orientation: ' . $o, ['app' => 'core']);
		try {
			$filler = new \ImagickPixel('none');
			switch ($o) {
				case \Imagick::ORIENTATION_TOPLEFT:
					break;
				case \Imagick::ORIENTATION_TOPRIGHT:
					$this->resource->flopImage();
					break;
				case \Imagick::ORIENTATION_BOTTOMRIGHT:
					$this->resource->rotateImage($filler, 180);
					break;
				case \Imagick::ORIENTATION_BOTTOMLEFT:
					$this->resource->flopImage();
					$this->resource->rotateImage($filler, 180);
					break;
				case \Imagick::ORIENTATION_LEFTTOP:
					$this->resource->flopImage();
					$this->resource->rotateImage($filler, -90);
					break;
				case \Imagick::ORIENTATION_RIGHTTOP:
					$this->resource->rotateImage($filler, 90);
					break;
				case \Imagick::ORIENTATION_RIGHTBOTTOM:
					$this->resource->flopImage();
					$this->resource->rotateImage($filler, 90);
					break;
				case \Imagick::ORIENTATION_LEFTBOTTOM:
					$this->resource->rotateImage($filler, -90);
					break;
				default: // Invalid orientation
					break;
			}
		} catch (\ImagickException $e) {
			$this->logger->warning(__METHOD__ . '(): ' . $e);
			return false;
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function loadFromFile($imagePath = false) {
		// exif_imagetype throws "read error!" if file is less than 12 byte
		if (is_bool($imagePath) || !@is_file($imagePath) || !file_exists($imagePath) || filesize($imagePath) < 12 || !is_readable($imagePath)) {
			return false;
		}

		try {
			$this->resource = new \Imagick($imagePath);
			if ($this->valid()) {
				$this->imageType = $this->formatToImageType($this->resource->getImageFormat());
				$this->mimeType = $this->resource->getImageMimeType();
				$this->filePath = $imagePath;
			}
			return $this->resource;
		} catch (\ImagickException $e) {
			$this->logger->error(__METHOD__ . '(): Error getting image data.', ['app' => 'core']);
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function loadFromFileHandle($handle) {
		try {
			$this->resource = new \Imagick();
			$this->resource->readImageFile($handle);
			if ($this->valid()) {
				$this->imageType = $this->formatToImageType($this->resource->getImageFormat());
				$this->mimeType = $this->resource->getImageMimeType();
			}
			return $this->resource;
		} catch (\ImagickException $e) {
			$this->logger->error(__METHOD__ . '(): Error getting image data.', ['app' => 'core']);
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function loadFromData(string $str) {
		try {
			$this->resource = new \Imagick();
			$this->resource->readImageBlob($str);
			if ($this->valid()) {
				$this->imageType = $this->formatToImageType($this->resource->getImageFormat());
				$this->mimeType = $this->resource->getImageMimeType();
			}
			return $this->resource;
		} catch (\ImagickException $e) {
			$this->logger->error(__METHOD__ . '(): Error getting image data.', ['app' => 'core']);
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function resize(int $maxSize): bool {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		return $this->fitIn($maxSize, $maxSize);
	}

	/**
	 * @inheritDoc
	 */
	public function resizeNew(int $maxSize) {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		try {
			$image = $this->resource->copy();
			$image->resize($maxSize);
			return $image;
		} catch (\ImagickException $e) {
			$this->logger->warning(__METHOD__ . '(): ' . $e);
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function preciseResize(int $width, int $height): bool {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		try {
			$this->resource->thumbnailimage($maxSize, $maxSize);
		} catch (\ImagickException $e) {
			$this->logger->warning(__METHOD__ . '(): ' . $e);
			return false;
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function preciseResizeNew(int $width, int $height) {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		try {
			$image = $this->resource->copy();
			$image->preciseResize($maxSize);
			return $image;
		} catch (\ImagickException $e) {
			$this->logger->warning(__METHOD__ . '(): ' . $e);
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function centerCrop(int $size = 0): bool {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		$widthOrig = $this->width();
		$heightOrig = $this->height();
		if ($widthOrig === $heightOrig and $size === 0) {
			return true;
		}

		try {
			$this->resource->cropThumbnailImage($size, $size);
		} catch (\ImagickException $e) {
			$this->logger->warning(__METHOD__ . '(): ' . $e);
			return false;
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function crop(int $x, int $y, int $w, int $h): bool {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		try {
			$this->resource->cropImage($w, $h, $x, $y);
		} catch (\ImagickException $e) {
			$this->logger->warning(__METHOD__ . '(): ' . $e);
			return false;
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function cropNew(int $x, int $y, int $w, int $h) {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		try {
			$image = $this->resource->copy();
			$image->crop($x, $y, $w, $h);
			return $image;
		} catch (\ImagickException $e) {
			$this->logger->warning(__METHOD__ . '(): ' . $e);
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function fitIn(int $maxWidth, int $maxHeight): bool {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		try {
			$this->resource->thumbnailimage($maxWidth, $maxHeight, true);
		} catch (\ImagickException $e) {
			$this->logger->warning(__METHOD__ . '(): ' . $e);
			return false;
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function cropCopy(int $x, int $y, int $w, int $h) {
		return $this->cropNew($x, $y, $w, $h);
	}

	/**
	 * @inheritDoc
	 */
	public function preciseResizeCopy(int $width, int $height) {
		return $this->preciseResizeNew($width, $height);
	}

	/**
	 * @inheritDoc
	 */
	public function resizeCopy(int $maxSize) {
		return $this->resizeNew($maxSize, $maxSize);
	}

	/**
	 * @inheritDoc
	 */
	public function destroy(): void {
		if ($this->valid()) {
			$this->resource->clear();
		}
		unset($this->resource);
		unset($this->mimeType);
		unset($this->filePath);
		unset($this->fileInfo);
		unset($this->exif);
	}
}
