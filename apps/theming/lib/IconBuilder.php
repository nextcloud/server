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
use OCP\App\AppPathNotFoundException;

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
	 * @param $app string app name
	 * @return string|false image blob
	 */
	public function getFavicon($app) {
		$icon = $this->renderAppIcon($app, 32);
		if($icon === false) {
			return false;
		}
		$icon->setImageFormat("png24");
		$data = $icon->getImageBlob();
		$icon->destroy();
		return $data;
	}

	/**
	 * @param $app string app name
	 * @return string|false image blob
	 */
	public function getTouchIcon($app) {
		$icon = $this->renderAppIcon($app, 512);
		if($icon === false) {
			return false;
		}
		$icon->setImageFormat("png24");
		$data = $icon->getImageBlob();
		$icon->destroy();
		return $data;
	}

	/**
	 * Render app icon on themed background color
	 * fallback to logo
	 *
	 * @param $app string app name
	 * @param $size int size of the icon in px
	 * @return Imagick|false
	 */
	public function renderAppIcon($app, $size) {
		try {
			$appIcon = $this->util->getAppIcon($app);
			$appIconContent = file_get_contents($appIcon);
		} catch (AppPathNotFoundException $e) {
			return false;
		}

		if($appIconContent === false) {
			return false;
		}

		$color = $this->themingDefaults->getMailHeaderColor();
		$mime = mime_content_type($appIcon);

		// generate background image with rounded corners
		$background = '<?xml version="1.0" encoding="UTF-8"?>' .
			'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:cc="http://creativecommons.org/ns#" width="512" height="512" xmlns:xlink="http://www.w3.org/1999/xlink">' .
			'<rect x="0" y="0" rx="100" ry="100" width="512" height="512" style="fill:' . $color . ';" />' .
			'</svg>';
		// resize svg magic as this seems broken in Imagemagick
		if($mime === "image/svg+xml" || substr($appIconContent, 0, 4) === "<svg") {
			if(substr($appIconContent, 0, 5) !== "<?xml") {
				$svg = "<?xml version=\"1.0\"?>".$appIconContent;
			} else {
				$svg = $appIconContent;
			}
			$tmp = new Imagick();
			$tmp->readImageBlob($svg);
			$x = $tmp->getImageWidth();
			$y = $tmp->getImageHeight();
			$res = $tmp->getImageResolution();
			$tmp->destroy();

			if($x>$y) {
				$max = $x;
			} else {
				$max = $y;
			}

			// convert svg to resized image
			$appIconFile = new Imagick();
			$resX = (int)(512 * $res['x'] / $max * 2.53);
			$resY = (int)(512 * $res['y'] / $max * 2.53);
			$appIconFile->setResolution($resX, $resY);
			$appIconFile->setBackgroundColor(new ImagickPixel('transparent'));
			$appIconFile->readImageBlob($svg);
			$appIconFile->scaleImage(512, 512, true);
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
		$finalIconFile->setBackgroundColor(new ImagickPixel('transparent'));
		$finalIconFile->readImageBlob($background);
		$finalIconFile->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
		$finalIconFile->setImageArtifact('compose:args', "1,0,-0.5,0.5");
		$finalIconFile->compositeImage($appIconFile, Imagick::COMPOSITE_ATOP, $offset_w, $offset_h);
		$finalIconFile->setImageFormat('png24');
		if (defined("Imagick::INTERPOLATE_BICUBIC") === true) {
			$filter = Imagick::INTERPOLATE_BICUBIC;
		} else {
			$filter = Imagick::FILTER_LANCZOS;
		}
		$finalIconFile->resizeImage($size, $size, $filter, 1, false);

		$appIconFile->destroy();
		return $finalIconFile;
	}

	public function colorSvg($app, $image) {
		try {
			$imageFile = $this->util->getAppImage($app, $image);
		} catch (AppPathNotFoundException $e) {
			return false;
		}
		$svg = file_get_contents($imageFile);
		if ($svg !== false && $svg !== "") {
			$color = $this->util->elementColor($this->themingDefaults->getMailHeaderColor());
			$svg = $this->util->colorizeSvg($svg, $color);
			return $svg;
		} else {
			return false;
		}
	}

}
