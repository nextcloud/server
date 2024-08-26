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
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Direction;
use Jcupitt\Vips\Exception as VipsException;

/**
 * Class for basic image manipulation using libvips
 */
class Vips extends Common {

	/**
	 * Get the corresponding imageType
	 * https://github.com/libvips/php-vips/blob/v1.0.8/src/Image.php#L512
	 */
	private function loaderToImageType(string $loader): int {
		switch ($loader) {
			case 'VipsForeignLoadGifFile':
			case 'VipsForeignLoadGifBuffer':
				return IMAGETYPE_GIF;
				break;
			case 'VipsForeignLoadPng':
			case 'VipsForeignLoadPngBuffer':
				return IMAGETYPE_PNG;
				break;
			case 'VipsForeignLoadJpegFile':
			case 'VipsForeignLoadJpegBuffer':
				return IMAGETYPE_JPEG;
				break;
			case 'VipsForeignLoadWebpFile':
			case 'VipsForeignLoadWebpBuffer':
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
		if (is_object($this->resource) && $this->resource instanceof VipsImage) {
			return true;
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function width(): int {
		return $this->resource->width;
	}

	/**
	 * @inheritDoc
	 */
	public function height(): int {
		return $this->resource->height;
	}

	/**
	 * @inheritDoc
	 */
	protected function _write($filePath = null, $mimeType = null): bool {
		try {
			$imageType = $this->imageType;
			if ($mimeType !== null) {
				switch ($mimeType) {
					case 'image/gif':
						$imageType = IMAGETYPE_GIF;
						break;
					case 'image/jpeg':
						$imageType = IMAGETYPE_JPEG;
						break;
					case 'image/png':
						$imageType = IMAGETYPE_PNG;
						break;
					case 'image/x-xbitmap':
						$imageType = IMAGETYPE_XBM;
						break;
					case 'image/bmp':
					case 'image/x-ms-bmp':
						$imageType = IMAGETYPE_BMP;
						break;
					default:
						throw new \Exception(__METHOD__ . '(): "' . $mimeType . '" is not supported when forcing a specific output format');
				}
			}

			$options = [];
			switch ($imageType) {
				case IMAGETYPE_JPEG:
					$options = ['strip' => true, 'Q' => $this->getJpegQuality(), 'interlace' => true];
					break;
				case IMAGETYPE_PNG:
					$options = ['strip' => true, 'compression' => 7];
					break;
				default:
					break;
			}
			$this->resource->writeToFile($filePath, $options);
			return true;
		} catch (VipsException $e) {
			$this->logger->error(__METHOD__ . '(): Error wrtinig image.', ['app' => 'core']);
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function setResource($resource): void {
		if (is_object($resource) && $resource instanceof VipsImage) {
			$this->resource = $resource;
			return;
		}
		throw new \InvalidArgumentException('Supplied resource is not of type "Vips".');
	}

	/**
	 * @inheritDoc
	 */
	public function data(): ?string {
		if (!$this->valid()) {
			return null;
		}

		try {
			$extension = '.png';
			$options = [];
			switch ($this->mimeType) {
				case "image/gif":
					$extension = '.gif';
					break;
				case 'image/jpeg':
					$extension = '.jpg';
					$options = ['strip' => true, 'Q' => $this->getJpegQuality(), 'interlace' => true];
					break;
				case 'image/png':
					$extension = '.png';
					$options = ['strip' => true, 'compression' => 7];
					break;
				default:
					$this->logger->info(__METHOD__ . '(): Could not guess mime-type', ['app' => 'core']);
					break;
			}
			return $this->resource->writeToBuffer($extension, $options);
		} catch (VipsException $e) {
			$this->logger->error(__METHOD__ . '(): Error getting image data.', ['app' => 'core']);
			return null;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function fixOrientation(): bool {
		$o = $this->getOrientation();
		$this->logger->debug(__METHOD__ . '() Orientation: ' . $o, ['app' => 'core']);
		try {
			$rotate = 0;
			$flip = false;
			switch ($o) {
				case -1:
					return false; //Nothing to fix
				case 1:
					$rotate = 0;
					break;
				case 2:
					$rotate = 0;
					$flip = true;
					break;
				case 3:
					$rotate = 180;
					break;
				case 4:
					$rotate = 180;
					$flip = true;
					break;
				case 5:
					$rotate = 90;
					$flip = true;
					break;
				case 6:
					$rotate = 270;
					break;
				case 7:
					$rotate = 270;
					$flip = true;
					break;
				case 8:
					$rotate = 90;
					break;
			}

			if ($flip) {
				$this->resource = $this->resource->flip(Direction::HORIZONTAL);
			}
			if ($rotate) { // case 0
				switch ($rotate) {
					case 90:
						$this->resource = $this->resource->rot90();
						break;
					case 180:
						$this->resource = $this->resource->rot180();
						break;
					case 270:
						$this->resource = $this->resource->rot270();
						break;
					default:
						assert(false);
				}
			}
		} catch (VipsException $e) {
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
			$loader = VipsImage::findLoad($imagePath);
			$this->resource = VipsImage::newFromFile($imagePath);
			if ($this->valid()) {
				$this->imageType = $this->loaderToImageType($loader);
				// TODO: still depends on GD
				$this->mimeType = image_type_to_mime_type($this->imageType);
				$this->filePath = $imagePath;
			}
			return $this->resource;
		} catch (VipsException $e) {
			$this->logger->error(__METHOD__ . '(): Error getting image data.', ['app' => 'core']);
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function loadFromFileHandle($handle) {
		$contents = stream_get_contents($handle);
		if ($this->loadFromData($contents)) {
			return $this->resource;
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function loadFromData(string $str) {
		try {
			$loader = VipsImage::findLoadBuffer($str);
			$this->resource = VipsImage::newFromBuffer($str);
			if ($this->valid()) {
				$this->imageType = $this->loaderToImageType($loader);
				// TODO: still depends on GD
				$this->mimeType = image_type_to_mime_type($this->imageType);
			}
			return $this->resource;
		} catch (VipsException $e) {
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
	public function resizeNew(int $maxSize): IImage {
		return $this->resource->thumbnail_image($maxSize, ['height' => $maxSize]);
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
			$this->resource = $this->preciseResizeNew($width, $height);
		} catch (VipsException $e) {
			$this->logger->warning(__METHOD__ . '(): ' . $e);
			return false;
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function preciseResizeNew(int $width, int $height): IImage {
		return $this->resource->resize($width / $this->width(), ['vscale' => $height / $this->height()]);
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
		} catch (VipsException $e) {
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
			$this->resource = $this->cropNew($x, $y, $w, $h);
		} catch (VipsException $e) {
			$this->logger->warning(__METHOD__ . '(): ' . $e);
			return false;
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function cropNew(int $x, int $y, int $w, int $h): IImage {
		return $this->resource->crop($x, $y, $w, $h);
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
			$this->resource = $this->resource->thumbnail_image($maxWidth, ['height' => $maxHeight]);
		} catch (VipsException $e) {
			$this->logger->warning(__METHOD__ . '(): ' . $e);
			return false;
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function cropCopy(int $x, int $y, int $w, int $h): IImage {
		return $this->cropNew($x, $y, $w, $h);
	}

	/**
	 * @inheritDoc
	 */
	public function preciseResizeCopy(int $width, int $height): IImage {
		return $this->preciseResizeNew($width, $height);
	}

	/**
	 * @inheritDoc
	 */
	public function resizeCopy(int $maxSize): IImage {
		return $this->resizeNew($width, $height);
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
