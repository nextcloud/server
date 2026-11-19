<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
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
			return $this->createIco($icon);
		} catch (\ImagickException $e) {
			return false;
		}
	}

	/**
	 * Convert the uploaded raster favicon to a square ICO file
	 *
	 * Raster images are not upscaled. Uploaded SVG and ICO files are used as they are,
	 * browsers render SVG favicons better than ImageMagick.
	 *
	 * @return string|false image blob, false for an uploaded SVG or ICO file
	 */
	public function getUploadedFavicon(): string|false {
		$mime = $this->imageManager->getImageMime('favicon');
		if ($mime === 'image/x-icon' || $mime === 'image/vnd.microsoft.icon'
			|| $this->imageManager->isSvgImage('favicon')
			|| !$this->imageManager->canRenderImage('favicon')
			|| !$this->imageManager->canConvert('ICO')) {
			return false;
		}
		$content = $this->getUploadedFaviconContent();
		if ($content === null) {
			return false;
		}
		try {
			$image = $this->readImage($content, false, 128);
			if ($this->isEmpty($image)) {
				return false;
			}
			$size = max($image->getImageWidth(), $image->getImageHeight());
			if ($size > 128) {
				$image->thumbnailImage(128, 128, true);
				$size = 128;
			}
			$icon = new Imagick();
			$icon->newImage($size, $size, new ImagickPixel('transparent'));
			$icon->compositeImage($image, Imagick::COMPOSITE_OVER,
				intdiv($size - $image->getImageWidth(), 2), intdiv($size - $image->getImageHeight(), 2));
			$image->destroy();
			return $this->createIco($icon);
		} catch (\ImagickException $e) {
			return false;
		}
	}

	/**
	 * Create an ICO file with 16, 32, 64 and 128 px images from a square icon,
	 * leaving out sizes larger than the icon, an icon smaller than 16 px is
	 * kept at its size
	 *
	 * @throws \ImagickException
	 */
	private function createIco(Imagick $icon): string {
		$icon->setImageFormat('PNG32');

		$width = $icon->getImageWidth();
		$sizes = array_filter([16, 32, 64, 128], fn (int $size): bool => $size <= $width);
		$favicon = new Imagick();
		$favicon->setFormat('ICO');
		foreach ($sizes === [] ? [$width] : $sizes as $size) {
			$clone = clone $icon;
			if ($size !== $width) {
				$clone->scaleImage($size, 0);
			}
			$favicon->addImage($clone);
			$clone->destroy();
		}

		$data = $favicon->getImagesBlob();
		$favicon->destroy();
		$icon->destroy();
		return $data;
	}

	/** Size of the touch and manifest icons, a raster favicon is not enlarged to it */
	private const TOUCH_ICON_SIZE = 512;
	/** Minimum size of an installable web app icon in Chrome */
	private const TOUCH_ICON_MIN_SIZE = 144;
	/** Part of an app icon covered by the image */
	private const ICON_PADDING = 0.85;

	/**
	 * Render the uploaded favicon as a touch icon
	 *
	 * A square favicon with opaque edges, which is already designed as a tile,
	 * fills the whole icon. Other favicons are rendered like the app icons.
	 * An SVG favicon is rendered at 512 px, a raster favicon is not enlarged.
	 *
	 * @return string|false image blob, false if the generated touch icon has to be used
	 */
	public function getUploadedTouchIcon(): string|false {
		$image = $this->readUploadedFaviconImage();
		if ($image === null) {
			return false;
		}
		try {
			$layout = $this->getTouchIconLayout($image);
			if ($layout === null) {
				return false;
			}
			[$size, $edgeColor] = $layout;
			if ($edgeColor === null) {
				// a raster image is not resampled unless it is too large
				$icon = $this->renderIcon($image, $size, false, $this->imageManager->isSvgImage('favicon'));
				if ($icon === false) {
					return false;
				}
			} else {
				if (max($image->getImageWidth(), $image->getImageHeight()) > $size) {
					$image->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1, true);
				}
				// fill transparent rounded corners with the edge color, as iOS fills them with black
				$icon = new Imagick();
				$icon->newImage($size, $size, $edgeColor);
				$icon->compositeImage($image, Imagick::COMPOSITE_OVER,
					intdiv($size - $image->getImageWidth(), 2), intdiv($size - $image->getImageHeight(), 2));
				$image->destroy();
			}
			// the smallest lossless PNG type, a tile without transparency needs no alpha channel
			$icon->setImageFormat('png');
			$icon->setOption('png:compression-level', '9');
			$data = $icon->getImageBlob();
			$icon->destroy();
			return $data;
		} catch (\ImagickException $e) {
			return false;
		}
	}

	/**
	 * Size of the touch icon rendered from the uploaded favicon
	 *
	 * @return int|null null if the generated touch icon has to be used
	 */
	public function getUploadedTouchIconSize(): ?int {
		$image = $this->readUploadedFaviconImage();
		if ($image === null) {
			return null;
		}
		try {
			return $this->getTouchIconLayout($image)[0] ?? null;
		} catch (\ImagickException $e) {
			return null;
		} finally {
			$image->destroy();
		}
	}

	/**
	 * Render the uploaded favicon as a transparent square PNG image for the web app manifest
	 *
	 * Unlike the touch icon it is not put on a background, as only iOS fills
	 * transparent areas, and iOS uses the touch icon. An SVG favicon is
	 * rendered at 512 px, a raster favicon is not resampled unless it is
	 * larger than 512 px.
	 *
	 * @return string|false image blob, false if the favicon can not be used
	 */
	public function getUploadedManifestIcon(): string|false {
		$image = $this->readUploadedFaviconImage();
		if ($image === null) {
			return false;
		}
		try {
			$size = $this->getManifestIconSize($image);
			if ($size === null) {
				return false;
			}
			if (max($image->getImageWidth(), $image->getImageHeight()) > $size) {
				$image->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1, true);
			}
			$icon = new Imagick();
			$icon->newImage($size, $size, new ImagickPixel('transparent'));
			$icon->compositeImage($image, Imagick::COMPOSITE_OVER,
				intdiv($size - $image->getImageWidth(), 2), intdiv($size - $image->getImageHeight(), 2));
			$icon->setImageFormat('png');
			$icon->setOption('png:compression-level', '9');
			$data = $icon->getImageBlob();
			$icon->destroy();
			return $data;
		} catch (\ImagickException $e) {
			return false;
		} finally {
			$image->destroy();
		}
	}

	/**
	 * Size of the manifest icon rendered from the uploaded favicon
	 *
	 * @return int|null null if the favicon can not be used
	 */
	public function getUploadedManifestIconSize(): ?int {
		$image = $this->readUploadedFaviconImage();
		if ($image === null) {
			return null;
		}
		try {
			return $this->getManifestIconSize($image);
		} catch (\ImagickException $e) {
			return null;
		} finally {
			$image->destroy();
		}
	}

	/**
	 * @return int|null null if the icon would be too small
	 * @throws \ImagickException
	 */
	private function getManifestIconSize(Imagick $image): ?int {
		if ($this->imageManager->isSvgImage('favicon')) {
			return self::TOUCH_ICON_SIZE;
		}
		$size = min(max($image->getImageWidth(), $image->getImageHeight()), self::TOUCH_ICON_SIZE);
		return $size < self::TOUCH_ICON_MIN_SIZE ? null : $size;
	}

	/**
	 * @return Imagick|null null if no favicon was uploaded or it can not be rendered
	 */
	private function readUploadedFaviconImage(): ?Imagick {
		if (!$this->imageManager->canRenderImage('favicon')) {
			return null;
		}
		$content = $this->getUploadedFaviconContent();
		if ($content === null) {
			return null;
		}
		try {
			$image = $this->readImage($content, $this->imageManager->isSvgImage('favicon'), self::TOUCH_ICON_SIZE);
		} catch (\ImagickException $e) {
			return null;
		}
		if ($this->isEmpty($image)) {
			$image->destroy();
			return null;
		}
		return $image;
	}

	/**
	 * Size of the touch icon and the background color of a tile
	 *
	 * A raster image is not enlarged: a tile keeps its size, and the size of
	 * other images is chosen so that they fit into the padding unscaled.
	 *
	 * @return array{0: int, 1: ImagickPixel|null}|null null if the icon would be too small
	 * @throws \ImagickException
	 */
	private function getTouchIconLayout(Imagick $image): ?array {
		$edgeColor = $this->getTileColor($image);
		if ($this->imageManager->isSvgImage('favicon')) {
			return [self::TOUCH_ICON_SIZE, $edgeColor];
		}
		$imageSize = max($image->getImageWidth(), $image->getImageHeight());
		$size = $imageSize;
		if ($edgeColor === null) {
			$size = (int)ceil($imageSize / self::ICON_PADDING);
			while ((int)floor($size * self::ICON_PADDING) < $imageSize) {
				$size++;
			}
		}
		$size = min($size, self::TOUCH_ICON_SIZE);
		if ($size < self::TOUCH_ICON_MIN_SIZE) {
			return null;
		}
		return [$size, $edgeColor];
	}

	/**
	 * @return string|null null if no favicon was uploaded or it can not be read
	 */
	private function getUploadedFaviconContent(): ?string {
		try {
			$content = $this->imageManager->getImage('favicon')->getContent();
		} catch (NotFoundException|NotPermittedException $e) {
			return null;
		}
		return $content === '' ? null : $content;
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

		$appIconIsSvg = ($mime === 'image/svg+xml' || str_starts_with($appIconContent, '<svg') || str_starts_with($appIconContent, '<?xml'));
		// if source image is svg but svg not supported, abort.
		// source images are both user and developer set, and there is guarantees that mime and extension match actual contents type
		if ($appIconIsSvg && !$supportSvg) {
			return false;
		}

		/**
		 * invert app icons for bright primary colors
		 * the default nextcloud logo will not be inverted to black
		 */
		$invert = $this->util->isBrightColor($this->themingDefaults->getColorPrimary())
			&& !$appIcon instanceof ISimpleFile
			&& $app !== 'core';
		try {
			$appIconFile = $this->readImage($appIconContent, $appIconIsSvg, $size);
		} catch (\ImagickException $e) {
			return false;
		}
		return $this->renderIcon($appIconFile, $size, $invert);
	}

	/**
	 * Render an image on themed background color
	 *
	 * @param Imagick $appIconFile image to render
	 * @param int $size size of the icon in px
	 * @param bool $invert whether to invert the colors of the image
	 * @param bool $upscale whether a smaller image may be enlarged
	 * @return Imagick|false
	 */
	private function renderIcon(Imagick $appIconFile, int $size, bool $invert, bool $upscale = true) {
		// calculate final image size and position
		$original_w = $appIconFile->getImageWidth();
		$original_h = $appIconFile->getImageHeight();
		$contentSize = (int)floor($size * self::ICON_PADDING);
		$scale = min($contentSize / $original_w, $contentSize / $original_h);
		if (!$upscale) {
			$scale = min($scale, 1.0);
		}
		$new_w = max(1, (int)floor($original_w * $scale));
		$new_h = max(1, (int)floor($original_h * $scale));
		$offset_w = (int)floor(($size - $new_w) / 2);
		$offset_h = (int)floor(($size - $new_h) / 2);
		$cornerRadius = 0.2 * $size;
		$color = $this->themingDefaults->getColorPrimary();
		// resize original image
		if ($upscale || $new_w !== $original_w || $new_h !== $original_h) {
			$appIconFile->resizeImage($new_w, $new_h, Imagick::FILTER_LANCZOS, 1);
		}
		if ($invert) {
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
			$draw->roundRectangle(0, 0, $size, $size, $cornerRadius, $cornerRadius);
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
			if ($upscale) {
				$finalIconFile->resizeImage($size, $size, $filter, 1, false);
			}

			return $finalIconFile;
		} finally {
			unset($appIconFile);
		}

		return false;
	}

	/**
	 * Background color of an image designed as a tile: square, with opaque edges
	 *
	 * Only the middle 60 % of every edge is checked, so rounded corners do not count.
	 *
	 * @return ImagickPixel|null average color of the edges, null if the image is not a tile
	 */
	private function getTileColor(Imagick $image): ?ImagickPixel {
		$width = $image->getImageWidth();
		$height = $image->getImageHeight();
		if (abs($width / $height - 1) > 0.05) {
			return null;
		}
		$count = 0;
		$opaque = 0;
		$color = [0.0, 0.0, 0.0];
		$edges = [];
		for ($x = (int)($width * 0.2); $x < (int)ceil($width * 0.8); $x++) {
			$edges[] = [$x, 0];
			$edges[] = [$x, $height - 1];
		}
		for ($y = (int)($height * 0.2); $y < (int)ceil($height * 0.8); $y++) {
			$edges[] = [0, $y];
			$edges[] = [$width - 1, $y];
		}
		foreach ($edges as [$x, $y]) {
			$pixel = $image->getImagePixelColor($x, $y);
			$count++;
			if ($pixel->getColorValue(Imagick::COLOR_ALPHA) >= 0.95) {
				$opaque++;
				$color[0] += $pixel->getColorValue(Imagick::COLOR_RED);
				$color[1] += $pixel->getColorValue(Imagick::COLOR_GREEN);
				$color[2] += $pixel->getColorValue(Imagick::COLOR_BLUE);
			}
		}
		if ($count === 0 || $opaque / $count < 0.95) {
			return null;
		}
		return new ImagickPixel(sprintf('rgb(%d,%d,%d)',
			(int)round(255 * $color[0] / $opaque),
			(int)round(255 * $color[1] / $opaque),
			(int)round(255 * $color[2] / $opaque)));
	}

	/**
	 * Whether an image is fully transparent, e.g. an SVG image that ImageMagick could not render
	 */
	private function isEmpty(Imagick $image): bool {
		return $image->getImageAlphaChannel()
			&& $image->getImageChannelRange(Imagick::CHANNEL_ALPHA)['maxima'] <= 0;
	}

	/**
	 * Read an image, rendering an SVG image so that its larger side is $size pixels
	 *
	 * Of a file with several images, the largest one is used, or the first
	 * one of an animation.
	 *
	 * @throws \ImagickException
	 */
	private function readImage(string $content, bool $isSvg, int $size): Imagick {
		$image = new Imagick();
		$image->setBackgroundColor(new ImagickPixel('transparent'));
		if (!$isSvg) {
			// ImageMagick does not detect ICO files by their content
			if (str_starts_with($content, "\x00\x00\x01\x00")) {
				$image->setFormat('ICO');
			}
			$image->readImageBlob($content);
			$index = 0;
			$largest = 0;
			foreach ($image as $i => $frame) {
				$area = $frame->getImageWidth() * $frame->getImageHeight();
				if ($area > $largest) {
					$index = $i;
					$largest = $area;
				}
			}
			$image->setIteratorIndex($index);
			$largestImage = $image->getImage();
			$image->destroy();
			return $largestImage;
		}
		// ensure proper XML declaration
		if (!str_starts_with($content, '<?xml')) {
			$content = '<?xml version="1.0"?>' . $content;
		}
		// get dimensions for resolution calculation
		$tmp = new Imagick();
		$tmp->setBackgroundColor(new ImagickPixel('transparent'));
		$tmp->setResolution(72, 72);
		$tmp->readImageBlob($content);
		$x = $tmp->getImageWidth();
		$y = $tmp->getImageHeight();
		$tmp->destroy();
		// set resolution for proper scaling
		$res = (int)(72 * $size / max($x, $y));
		$image->setResolution($res, $res);
		$image->readImageBlob($content);
		return $image;
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
