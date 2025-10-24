<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use OCP\Files\SimpleFS\ISimpleFile;

class IconBuilder {
	/**
	 * IconBuilder constructor.
	 *
	 * @param ThemingDefaults $themingDefaults
	 * @param Util $util
	 * @param ImageManager $imageManager
	 */
	public function __construct(
		private ThemingDefaults $themingDefaults,
		private Util $util,
		private ImageManager $imageManager,
	) {
	}

	/**
	 * @param $app string app name
	 * @return string|false image blob
	 */
	public function getFavicon($app) {
		if (!$this->imageManager->canConvert('PNG')) {
			return false;
		}
		try {
			$icon = $this->renderAppIcon($app, 128);
			if ($icon === false) {
				return false;
			}
			$icon->setImageFormat('PNG32');

			$favicon = new Imagick();
			$favicon->setFormat('ICO');

			$clone = clone $icon;
			$clone->scaleImage(16, 0);
			$favicon->addImage($clone);

			$clone = clone $icon;
			$clone->scaleImage(32, 0);
			$favicon->addImage($clone);

			$clone = clone $icon;
			$clone->scaleImage(64, 0);
			$favicon->addImage($clone);

			$clone = clone $icon;
			$clone->scaleImage(128, 0);
			$favicon->addImage($clone);

			$data = $favicon->getImagesBlob();
			$favicon->destroy();
			$icon->destroy();
			$clone->destroy();
			return $data;
		} catch (\ImagickException $e) {
			return false;
		}
	}

	/**
	 * @param $app string app name
	 * @return string|false image blob
	 */
	public function getTouchIcon($app) {
		try {
			$icon = $this->renderAppIcon($app, 512);
			if ($icon === false) {
				return false;
			}
			$icon->setImageFormat('png32');
			$data = $icon->getImageBlob();
			$icon->destroy();
			return $data;
		} catch (\ImagickException $e) {
			return false;
		}
	}

	/**
	 * Render app icon on themed background color
	 * fallback to logo
	 *
	 * @param string $app app name
	 * @param int $size size of the icon in px
	 * @return Imagick|false
	 */
	public function renderAppIcon($app, $size) {
		$supportSvg = $this->imageManager->canConvert('SVG');
		// retrieve app icon
		$appIcon = $this->util->getAppIcon($app, $supportSvg);
		if ($appIcon instanceof ISimpleFile) {
			$appIconContent = $appIcon->getContent();
			$mime = $appIcon->getMimeType();
		} elseif (!file_exists($appIcon)) {
			return false;
		} else {
			$appIconContent = file_get_contents($appIcon);
			$mime = mime_content_type($appIcon);
		}

		if ($appIconContent === false || $appIconContent === '') {
			return false;
		}

		$appIconFile = null;
		$appIconIsSvg = ($mime === 'image/svg+xml' || substr($appIconContent, 0, 4) === '<svg');

		// if source image is svg but svg not supported, abort
		if ($appIconIsSvg && !$supportSvg) {
			return false;
		}

		try {
			// construct original image object
			$appIconFile = new Imagick();
			$appIconFile->setBackgroundColor(new ImagickPixel('transparent'));

			if ($appIconIsSvg) {
				// handle SVG images
				// ensure proper XML declaration
				if (substr($appIconContent, 0, 5) !== '<?xml') {
					$svg = '<?xml version="1.0"?>' . $appIconContent;
				} else {
					$svg = $appIconContent;
				}
				// get dimensions for resolution calculation
				$tmp = new Imagick();
				$tmp->setBackgroundColor(new ImagickPixel('transparent'));
				$tmp->setResolution(72, 72);
				$tmp->readImageBlob($svg);
				$x = $tmp->getImageWidth();
				$y = $tmp->getImageHeight();
				$tmp->destroy();
				// set resolution for proper scaling
				$resX = (int)(72 * $size / $x);
				$resY = (int)(72 * $size / $y);
				$appIconFile->setResolution($resX, $resY);
				$appIconFile->readImageBlob($svg);
			} else {
				// handle non-SVG images
				$appIconFile->readImageBlob($appIconContent);
			}
		} catch (\ImagickException $e) {
			return false;
		}
		// calculate final image size and position
		$padding = 0.85;
		$original_w = $appIconFile->getImageWidth();
		$original_h = $appIconFile->getImageHeight();
		$contentSize = (int)floor($size * $padding);
		$scale = min($contentSize / $original_w, $contentSize / $original_h);
		$new_w = max(1, (int)floor($original_w * $scale));
		$new_h = max(1, (int)floor($original_h * $scale));
		$offset_w = (int)floor(($size - $new_w) / 2);
		$offset_h = (int)floor(($size - $new_h) / 2);
		$cornerRadius = 0.2 * $size;
		$color = $this->themingDefaults->getColorPrimary();
		// resize original image
		$appIconFile->resizeImage($new_w, $new_h, Imagick::FILTER_LANCZOS, 1);
		/**
		 * invert app icons for bright primary colors
		 * the default nextcloud logo will not be inverted to black
		 */
		if ($this->util->isBrightColor($color)
			&& !$appIcon instanceof ISimpleFile
			&& $app !== 'core'
		) {
			$appIconFile->negateImage(false);
		}
		// construct final image object
		try {
			// image background
			$finalIconFile = new Imagick();
			$finalIconFile->setBackgroundColor(new ImagickPixel('transparent'));
			// icon background
			$finalIconFile->newImage($size, $size, new ImagickPixel('transparent'));
			$draw = new ImagickDraw();
			$draw->setFillColor($color);
			$draw->roundRectangle(0, 0, $size - 1, $size - 1, $cornerRadius, $cornerRadius);
			$finalIconFile->drawImage($draw);
			$draw->destroy();
			// overlay icon
			$finalIconFile->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
			$finalIconFile->setImageArtifact('compose:args', '1,0,-0.5,0.5');
			$finalIconFile->compositeImage($appIconFile, Imagick::COMPOSITE_ATOP, $offset_w, $offset_h);
			$finalIconFile->setImageFormat('PNG32');
			if (defined('Imagick::INTERPOLATE_BICUBIC') === true) {
				$filter = Imagick::INTERPOLATE_BICUBIC;
			} else {
				$filter = Imagick::FILTER_LANCZOS;
			}
			$finalIconFile->resizeImage($size, $size, $filter, 1, false);

			return $finalIconFile;
		} finally {
			unset($appIconFile);
		}

		return false;
	}

	/**
	 * @param string $app app name
	 * @param string $image relative path to svg file in app directory
	 * @return string|false content of a colorized svg file
	 */
	public function colorSvg($app, $image) {
		$imageFile = $this->util->getAppImage($app, $image);
		if ($imageFile === false || $imageFile === '' || !file_exists($imageFile)) {
			return false;
		}
		$svg = file_get_contents($imageFile);
		if ($svg !== false && $svg !== '') {
			$color = $this->util->elementColor($this->themingDefaults->getColorPrimary());
			$svg = $this->util->colorizeSvg($svg, $color);
			return $svg;
		} else {
			return false;
		}
	}
}
