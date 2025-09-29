<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming;

use Imagick;
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
		if (!$this->imageManager->shouldReplaceIcons()) {
			return false;
		}
		try {
			$favicon = new Imagick();
			$favicon->setFormat('ico');
			$icon = $this->renderAppIcon($app, 128);
			if ($icon === false) {
				return false;
			}
			$icon->setImageFormat('png32');

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
		$appIcon = $this->util->getAppIcon($app);
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

		$color = $this->themingDefaults->getColorPrimary();

		// generate background image with rounded corners
		$cornerRadius = 0.2 * $size;
		$background = '<?xml version="1.0" encoding="UTF-8"?>'
			. '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:cc="http://creativecommons.org/ns#" width="' . $size . '" height="' . $size . '" xmlns:xlink="http://www.w3.org/1999/xlink">'
			. '<rect x="0" y="0" rx="' . $cornerRadius . '" ry="' . $cornerRadius . '" width="' . $size . '" height="' . $size . '" style="fill:' . $color . ';" />'
			. '</svg>';
		// resize svg magic as this seems broken in Imagemagick
		if ($mime === 'image/svg+xml' || substr($appIconContent, 0, 4) === '<svg') {
			if (substr($appIconContent, 0, 5) !== '<?xml') {
				$svg = '<?xml version="1.0"?>' . $appIconContent;
			} else {
				$svg = $appIconContent;
			}
			$tmp = new Imagick();
			$tmp->setBackgroundColor(new ImagickPixel('transparent'));
			$tmp->setResolution(72, 72);
			$tmp->readImageBlob($svg);
			$x = $tmp->getImageWidth();
			$y = $tmp->getImageHeight();
			$tmp->destroy();

			// convert svg to resized image
			$appIconFile = new Imagick();
			$resX = (int)(72 * $size / $x);
			$resY = (int)(72 * $size / $y);
			$appIconFile->setResolution($resX, $resY);
			$appIconFile->setBackgroundColor(new ImagickPixel('transparent'));
			$appIconFile->readImageBlob($svg);

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
		} else {
			$appIconFile = new Imagick();
			$appIconFile->setBackgroundColor(new ImagickPixel('transparent'));
			$appIconFile->readImageBlob($appIconContent);
		}
		// offset for icon positioning
		$padding = 0.15;
		$border_w = (int)($appIconFile->getImageWidth() * $padding);
		$border_h = (int)($appIconFile->getImageHeight() * $padding);
		$innerWidth = ($appIconFile->getImageWidth() - $border_w * 2);
		$innerHeight = ($appIconFile->getImageHeight() - $border_h * 2);
		$appIconFile->adaptiveResizeImage($innerWidth, $innerHeight);
		// center icon
		$offset_w = (int)($size / 2 - $innerWidth / 2);
		$offset_h = (int)($size / 2 - $innerHeight / 2);

		$finalIconFile = new Imagick();
		$finalIconFile->setBackgroundColor(new ImagickPixel('transparent'));
		$finalIconFile->readImageBlob($background);
		$finalIconFile->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
		$finalIconFile->setImageArtifact('compose:args', '1,0,-0.5,0.5');
		$finalIconFile->compositeImage($appIconFile, Imagick::COMPOSITE_ATOP, $offset_w, $offset_h);
		$finalIconFile->setImageFormat('png24');
		if (defined('Imagick::INTERPOLATE_BICUBIC') === true) {
			$filter = Imagick::INTERPOLATE_BICUBIC;
		} else {
			$filter = Imagick::FILTER_LANCZOS;
		}
		$finalIconFile->resizeImage($size, $size, $filter, 1, false);

		$appIconFile->destroy();
		return $finalIconFile;
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
