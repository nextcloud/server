<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bartek Przybylski <bart.p.pl@gmail.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Byron Marohn <combustible@live.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author j-ed <juergen@eisfair.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Johannes Willnecker <johannes@willnecker.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Samuel CHEMLA <chemla.samuel@gmail.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
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
namespace OC\Image;

use OCP\IImage;

/**
 * Class for basic image manipulation using GD
 */
class Gd extends Common {

	/**
	 * @inheritDoc
	 */
	public function valid(): bool { // apparently you can't name a method 'empty'...
		if (is_resource($this->resource)) {
			return true;
		}
		if (is_object($this->resource) && $this->$resource instanceof \GdImage) {
			return true;
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function width(): int {
		if ($this->valid()) {
			$width = imagesx($this->resource);
			if ($width !== false) {
				return $width;
			}
		}
		return -1;
	}

	/**
	 * @inheritDoc
	 */
	public function height(): int {
		if ($this->valid()) {
			$height = imagesy($this->resource);
			if ($height !== false) {
				return $height;
			}
		}
		return -1;
	}

	/**
	 * @inheritDoc
	 */
	protected function _write($filePath = null, $mimeType = null): bool {
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

		switch ($imageType) {
			case IMAGETYPE_GIF:
				$retVal = imagegif($this->resource, $filePath);
				break;
			case IMAGETYPE_JPEG:
				$retVal = imagejpeg($this->resource, $filePath, $this->getJpegQuality());
				break;
			case IMAGETYPE_PNG:
				$retVal = imagepng($this->resource, $filePath);
				break;
			case IMAGETYPE_XBM:
				if (function_exists('imagexbm')) {
					$retVal = imagexbm($this->resource, $filePath);
				} else {
					throw new \Exception(__METHOD__ . '(): imagexbm() is not supported.');
				}

				break;
			case IMAGETYPE_WBMP:
				$retVal = imagewbmp($this->resource, $filePath);
				break;
			case IMAGETYPE_BMP:
				$retVal = imagebmp($this->resource, $filePath);
				break;
			default:
				$this->logger->info(__METHOD__ . '(): Could not guess mime-type, defaulting to png', ['app' => 'core']);
				$retVal = imagepng($this->resource, $filePath);
		}
		return $retVal;
	}

	/**
	 * @inheritDoc
	 */
	public function setResource($resource): void {
		// For PHP<8
		if (is_resource($resource) && get_resource_type($resource) === 'gd') {
			$this->resource = $resource;
			return;
		}
		// PHP 8 has real objects for GD stuff
		if (is_object($resource) && $resource instanceof \GdImage) {
			$this->resource = $resource;
			return;
		}
		throw new \InvalidArgumentException('Supplied resource is not of type "gd".');
	}

	/**
	 * @inheritDoc
	 */
	public function data(): ?string {
		if (!$this->valid()) {
			return null;
		}
		ob_start();
		$res = $this->_write(null, $this->mimeType);
		if (!$res) {
			$this->logger->error(__METHOD__ . '(): Error getting image data.', ['app' => 'core']);
		}
		return ob_get_clean();
	}

	/**
	 * @inheritDoc
	 */
	public function fixOrientation(): bool {
		$o = $this->getOrientation();
		$this->logger->debug(__METHOD__ . '() Orientation: ' . $o, ['app' => 'core']);
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
		if ($flip && function_exists('imageflip')) {
			imageflip($this->resource, IMG_FLIP_HORIZONTAL);
		}
		if ($rotate) {
			$res = imagerotate($this->resource, $rotate, 0);
			if ($res) {
				if (imagealphablending($res, true)) {
					if (imagesavealpha($res, true)) {
						imagedestroy($this->resource);
						$this->resource = $res;
						return true;
					} else {
						$this->logger->debug(__METHOD__ . '(): Error during alpha-saving', ['app' => 'core']);
						return false;
					}
				} else {
					$this->logger->debug(__METHOD__ . '(): Error during alpha-blending', ['app' => 'core']);
					return false;
				}
			} else {
				$this->logger->debug(__METHOD__ . '(): Error during orientation fixing', ['app' => 'core']);
				return false;
			}
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function loadFromFile($imagePath = false) {
		// exif_imagetype throws "read error!" if file is less than 12 byte
		if (is_bool($imagePath) || !@is_file($imagePath) || !file_exists($imagePath) || filesize($imagePath) < 12 || !is_readable($imagePath)) {
			return false;
		}
		$iType = exif_imagetype($imagePath);
		switch ($iType) {
			case IMAGETYPE_GIF:
				if (imagetypes() & IMG_GIF) {
					$this->resource = imagecreatefromgif($imagePath);
					// Preserve transparency
					imagealphablending($this->resource, true);
					imagesavealpha($this->resource, true);
				} else {
					$this->logger->debug(__METHOD__ . '(): GIF images not supported: ' . $imagePath, ['app' => 'core']);
				}
				break;
			case IMAGETYPE_JPEG:
				if (imagetypes() & IMG_JPG) {
					if (getimagesize($imagePath) !== false) {
						$this->resource = @imagecreatefromjpeg($imagePath);
					} else {
						$this->logger->debug(__METHOD__ . '(): JPG image not valid: ' . $imagePath, ['app' => 'core']);
					}
				} else {
					$this->logger->debug(__METHOD__ . '(): JPG images not supported: ' . $imagePath, ['app' => 'core']);
				}
				break;
			case IMAGETYPE_PNG:
				if (imagetypes() & IMG_PNG) {
					$this->resource = @imagecreatefrompng($imagePath);
					// Preserve transparency
					imagealphablending($this->resource, true);
					imagesavealpha($this->resource, true);
				} else {
					$this->logger->debug(__METHOD__ . '(): PNG images not supported: ' . $imagePath, ['app' => 'core']);
				}
				break;
			case IMAGETYPE_XBM:
				if (imagetypes() & IMG_XPM) {
					$this->resource = @imagecreatefromxbm($imagePath);
				} else {
					$this->logger->debug(__METHOD__ . '(): XBM/XPM images not supported: ' . $imagePath, ['app' => 'core']);
				}
				break;
			case IMAGETYPE_WBMP:
				if (imagetypes() & IMG_WBMP) {
					$this->resource = @imagecreatefromwbmp($imagePath);
				} else {
					$this->logger->debug(__METHOD__ . '(): WBMP images not supported: ' . $imagePath, ['app' => 'core']);
				}
				break;
			case IMAGETYPE_BMP:
				if (imagetypes() & IMG_BMP) {
					$this->resource = @imagecreatefrombmp($imagePath);
				} else {
					$this->logger->debug(__METHOD__ . '(): BMP images not supported: ' . $imagePath, ['app' => 'core']);
				}
				break;
			case IMAGETYPE_WEBP:
				if (imagetypes() & IMG_WEBP) {
					$this->resource = @imagecreatefromwebp($imagePath);
				} else {
					$this->logger->debug(__METHOD__ . '(): WEBP images not supported: ' . $imagePath, ['app' => 'core']);
				}
				break;
			/*
			case IMAGETYPE_TIFF_II: // (intel byte order)
				break;
			case IMAGETYPE_TIFF_MM: // (motorola byte order)
				break;
			case IMAGETYPE_JPC:
				break;
			case IMAGETYPE_JP2:
				break;
			case IMAGETYPE_JPX:
				break;
			case IMAGETYPE_JB2:
				break;
			case IMAGETYPE_SWC:
				break;
			case IMAGETYPE_IFF:
				break;
			case IMAGETYPE_ICO:
				break;
			case IMAGETYPE_SWF:
				break;
			case IMAGETYPE_PSD:
				break;
			*/
			default:

				// this is mostly file created from encrypted file
				$this->resource = imagecreatefromstring(\OC\Files\Filesystem::file_get_contents(\OC\Files\Filesystem::getLocalPath($imagePath)));
				$iType = IMAGETYPE_PNG;
				$this->logger->debug(__METHOD__ . '(): Default', ['app' => 'core']);
				break;
		}
		if ($this->valid()) {
			$this->imageType = $iType;
			$this->mimeType = image_type_to_mime_type($iType);
			$this->filePath = $imagePath;
		}
		return $this->resource;
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
		$this->resource = @imagecreatefromstring($str);
		if ($this->fileInfo) {
			$this->mimeType = $this->fileInfo->buffer($str);
		}
		if (is_resource($this->resource)) {
			imagealphablending($this->resource, false);
			imagesavealpha($this->resource, true);
		}

		if (!$this->resource) {
			$this->logger->debug(__METHOD__ . '(): Could not load', ['app' => 'core']);
			return false;
		}
		return $this->resource;
	}

	/**
	 * @inheritDoc
	 */
	public function loadFromBase64(string $str) {
		$data = base64_decode($str);
		if ($data) { // try to load from string data
			$this->resource = @imagecreatefromstring($data);
			if ($this->fileInfo) {
				$this->mimeType = $this->fileInfo->buffer($data);
			}
			if (!$this->resource) {
				$this->logger->debug(__METHOD__ . '(): Could not load', ['app' => 'core']);
				return false;
			}
			return $this->resource;
		} else {
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function resize(int $maxSize): bool {
		$result = $this->resizeNew($maxSize);
		imagedestroy($this->resource);
		$this->resource = $result;
		return is_resource($result);
	}

	/**
	 * @inheritDoc
	 */
	public function resizeNew(int $maxSize) {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}
		$widthOrig = $this->width();
		$heightOrig = $this->height();
		$ratioOrig = $widthOrig / $heightOrig;

		if ($ratioOrig > 1) {
			$newHeight = round($maxSize / $ratioOrig);
			$newWidth = $maxSize;
		} else {
			$newWidth = round($maxSize * $ratioOrig);
			$newHeight = $maxSize;
		}

		return $this->preciseResizeNew((int)round($newWidth), (int)round($newHeight));
	}

	/**
	 * @inheritDoc
	 */
	public function preciseResize(int $width, int $height): bool {
		$result = $this->preciseResizeNew($width, $height);
		imagedestroy($this->resource);
		$this->resource = $result;
		return is_resource($result);
	}

	/**
	 * @inheritDoc
	 */
	public function preciseResizeNew(int $width, int $height) {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}
		$widthOrig = $this->width();
		$heightOrig = $this->height();
		$process = imagecreatetruecolor($width, $height);
		if ($process === false) {
			$this->logger->error(__METHOD__ . '(): Error creating true color image', ['app' => 'core']);
			return false;
		}

		// preserve transparency
		if ($this->imageType === IMAGETYPE_GIF or $this->imageType === IMAGETYPE_PNG) {
			imagecolortransparent($process, imagecolorallocatealpha($process, 0, 0, 0, 127));
			imagealphablending($process, false);
			imagesavealpha($process, true);
		}

		$res = imagecopyresampled($process, $this->resource, 0, 0, 0, 0, $width, $height, $widthOrig, $heightOrig);
		if ($res === false) {
			$this->logger->error(__METHOD__ . '(): Error re-sampling process image', ['app' => 'core']);
			imagedestroy($process);
			return false;
		}
		return $process;
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
		$ratioOrig = $widthOrig / $heightOrig;
		$width = $height = min($widthOrig, $heightOrig);

		if ($ratioOrig > 1) {
			$x = (int) (($widthOrig / 2) - ($width / 2));
			$y = 0;
		} else {
			$y = (int) (($heightOrig / 2) - ($height / 2));
			$x = 0;
		}
		if ($size > 0) {
			$targetWidth = $size;
			$targetHeight = $size;
		} else {
			$targetWidth = $width;
			$targetHeight = $height;
		}
		$process = imagecreatetruecolor($targetWidth, $targetHeight);
		if ($process === false) {
			$this->logger->error(__METHOD__ . '(): Error creating true color image', ['app' => 'core']);
			imagedestroy($process);
			return false;
		}

		// preserve transparency
		if ($this->imageType === IMAGETYPE_GIF or $this->imageType === IMAGETYPE_PNG) {
			imagecolortransparent($process, imagecolorallocatealpha($process, 0, 0, 0, 127));
			imagealphablending($process, false);
			imagesavealpha($process, true);
		}

		$res = imagecopyresampled($process, $this->resource, 0, 0, $x, $y, $targetWidth, $targetHeight, $width, $height);
		if ($res === false) {
			$this->logger->error(__METHOD__ . '(): Error re-sampling process image ' . $width . 'x' . $height, ['app' => 'core']);
			imagedestroy($process);
			return false;
		}
		imagedestroy($this->resource);
		$this->resource = $process;
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function crop(int $x, int $y, int $w, int $h): bool {
		$result = $this->cropNew($x, $y, $w, $h);
		imagedestroy($this->resource);
		$this->resource = $result;
		return is_resource($result);
	}

	/**
	 * @inheritDoc
	 */
	public function cropNew(int $x, int $y, int $w, int $h) {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}
		$process = imagecreatetruecolor($w, $h);
		if ($process === false) {
			$this->logger->error(__METHOD__ . '(): Error creating true color image', ['app' => 'core']);
			return false;
		}

		// preserve transparency
		if ($this->imageType === IMAGETYPE_GIF or $this->imageType === IMAGETYPE_PNG) {
			imagecolortransparent($process, imagecolorallocatealpha($process, 0, 0, 0, 127));
			imagealphablending($process, false);
			imagesavealpha($process, true);
		}

		$res = imagecopyresampled($process, $this->resource, 0, 0, $x, $y, $w, $h, $w, $h);
		if ($res === false) {
			$this->logger->error(__METHOD__ . '(): Error re-sampling process image ' . $w . 'x' . $h, ['app' => 'core']);
			return false;
		}
		return $process;
	}

	/**
	 * @inheritDoc
	 */
	public function fitIn(int $maxWidth, int $maxHeight): bool {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}
		$widthOrig = $this->width();
		$heightOrig = $this->height();
		$ratio = $widthOrig / $heightOrig;

		$newWidth = min($maxWidth, $ratio * $maxHeight);
		$newHeight = min($maxHeight, $maxWidth / $ratio);

		$this->preciseResize((int)round($newWidth), (int)round($newHeight));
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function cropCopy(int $x, int $y, int $w, int $h): IImage {
		$image = new \OCP\Image($this->logger, $this->config);
		$image->imageType = $this->imageType;
		$image->mimeType = $this->mimeType;
		$image->resource = $this->cropNew($x, $y, $w, $h);

		return $image;
	}

	/**
	 * @inheritDoc
	 */
	public function preciseResizeCopy(int $width, int $height): IImage {
		$image = new \OCP\Image($this->logger, $this->config);
		$image->imageType = $this->imageType;
		$image->mimeType = $this->mimeType;
		$image->resource = $this->preciseResizeNew($width, $height);

		return $image;
	}

	/**
	 * @inheritDoc
	 */
	public function resizeCopy(int $maxSize): IImage {
		$image = new \OCP\Image($this->logger, $this->config);
		$image->imageType = $this->imageType;
		$image->mimeType = $this->mimeType;
		$image->resource = $this->resizeNew($maxSize);

		return $image;
	}

	/**
	 * @inheritDoc
	 */
	public function destroy(): void {
		if ($this->valid()) {
			imagedestroy($this->resource);
		}
		unset($this->resource);
		unset($this->mimeType);
		unset($this->filePath);
		unset($this->fileInfo);
		unset($this->exif);
	}
}
