<?php
/**
 * @copyright Copyright (c) 2016 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Theming;

use Imagick;
use ImagickPixel;

class IconBuilder {
	/** @var ThemingDefaults */
	private $themingDefaults;
	/** @var Util */
	private $util;

	/**
	 * IconBuilder constructor.
	 *
	 * @param ThemingDefaults $themingDefaults
	 * @param Util $util
	 */
	public function __construct(
		ThemingDefaults $themingDefaults,
		Util $util
	) {
		$this->themingDefaults = $themingDefaults;
		$this->util = $util;
	}

	/**
	 * @param $app app name
	 * @return string image blob
	 */
	public function getFavicon($app) {
		$icon = $this->renderAppIcon($app);
		$icon->resizeImage(32, 32, Imagick::FILTER_LANCZOS, 1);
		$icon->setImageFormat("png24");
		$data = $icon->getImageBlob();
		$icon->destroy();
		return $data;
	}

	/**
	 * @param $app app name
	 * @return string image blob
	 */
	public function getTouchIcon($app) {
		$icon = $this->renderAppIcon($app);
		$icon->setImageFormat("png24");
		$data = $icon->getImageBlob();
		$icon->destroy();
		return $data;
	}

	/**
	 * Render app icon on themed background color
	 * fallback to logo
	 *
	 * @param $app app name
	 * @return Imagick
	 */
	public function renderAppIcon($app) {
		$appIcon = $this->util->getAppIcon($app);

		$color = $this->themingDefaults->getMailHeaderColor();
		$mime = mime_content_type($appIcon);
		// generate background image with rounded corners
		$background = '<?xml version="1.0" encoding="UTF-8"?>' .
			'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:cc="http://creativecommons.org/ns#" width="512" height="512" xmlns:xlink="http://www.w3.org/1999/xlink">' .
			'<rect x="0" y="0" rx="75" ry="75" width="512" height="512" style="fill:' . $color . ';" />' .
			'</svg>';

		// resize svg magic as this seems broken in Imagemagick
		if($mime === "image/svg+xml") {
			$svg = file_get_contents($appIcon);

			$tmp = new Imagick();
			$tmp->readImageBlob($svg);
			$x = $tmp->getImageWidth();
			$y = $tmp->getImageHeight();
			$res = $tmp->getImageResolution();
			$tmp->destroy();

			// convert svg to resized image
			$appIconFile = new Imagick();
			$resX = (int)(512 * $res['x'] / $x * 2.53);
			$resY = (int)(512 * $res['y'] / $y * 2.53);
			$appIconFile->setResolution($resX, $resY);
			$appIconFile->setBackgroundColor(new ImagickPixel('transparent'));
			$appIconFile->readImageBlob($svg);
		} else {
			$appIconFile = new Imagick();
			$appIconFile->setBackgroundColor(new ImagickPixel('transparent'));
			$appIconFile->readImageBlob(file_get_contents($appIcon));
			$appIconFile->scaleImage(512, 512, true);
		}

		// offset for icon positioning
		$border_w = (int)($appIconFile->getImageWidth() * 0.05);
		$border_h = (int)($appIconFile->getImageHeight() * 0.05);
		$innerWidth = (int)($appIconFile->getImageWidth() - $border_w * 2);
		$innerHeight = (int)($appIconFile->getImageHeight() - $border_h * 2);
		$appIconFile->adaptiveResizeImage($innerWidth, $innerHeight);
		// center icon
		$offset_w = 512 / 2 - $innerWidth / 2;
		$offset_h = 512 / 2 - $innerHeight / 2;

		$appIconFile->setImageFormat("png24");

		$finalIconFile = new Imagick();
		$finalIconFile->readImageBlob($background);
		$finalIconFile->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
		$finalIconFile->setImageArtifact('compose:args', "1,0,-0.5,0.5");
		$finalIconFile->compositeImage($appIconFile, Imagick::COMPOSITE_ATOP, $offset_w, $offset_h);
		$finalIconFile->resizeImage(512, 512, Imagick::FILTER_LANCZOS, 1);

		$appIconFile->destroy();
		return $finalIconFile;
	}

}