<?php
/**
 * @copyright Copyright (c) 2016 Julius Haertl <jus@bitgrid.net>
 *
 * @author Julius Haertl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Theming\Controller;

use OCA\Theming\Template;
use OCA\Theming\ThemingDefaults;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCA\Theming\Util;
use OCP\IURLGenerator;
use Imagick;
use ImagickPixel;

class IconController extends Controller {
	/** @var ThemingDefaults */
	private $themingDefaults;
	/** @var Util */
	private $util;
	/** @var ITimeFactory */
	private $timeFactory;
	/** @var IL10N */
	private $l;
	/** @var IConfig */
	private $config;
	/** @var IRootFolder */
	private $rootFolder;


	/**
	 * IconController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param ThemingDefaults $themingDefaults
	 * @param Util $util
	 * @param ITimeFactory $timeFactory
	 * @param IL10N $l
	 * @param IRootFolder $rootFolder
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IConfig $config,
		ThemingDefaults $themingDefaults,
		Util $util,
		ITimeFactory $timeFactory,
		IL10N $l,
		IRootFolder $rootFolder
	) {
		parent::__construct($appName, $request);

		$this->themingDefaults = $themingDefaults;
		$this->util = $util;
		$this->timeFactory = $timeFactory;
		$this->l = $l;
		$this->config = $config;
		$this->rootFolder = $rootFolder;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param $app app name
	 * @param $image image file name
	 * @return StreamResponse|DataResponse
	 */
	public function getThemedIcon($app, $image) {
		$image = $this->util->getAppImage($app, $image);
		$svg = file_get_contents($image);
		$color = $this->util->elementColor($this->themingDefaults->getMailHeaderColor());
		$svg = $this->util->colorizeSvg($svg, $color);

		$response = new DataDisplayResponse($svg, Http::STATUS_OK, ['Content-Type' => 'image/svg+xml']);
		$response->cacheFor(86400);
		$response->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		return $response;
	}

	/**
	 * Return a 32x32 favicon as png
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param $app app name
	 * @return StreamResponse|DataResponse
	 */
	public function getFavicon($app="core") {
		$icon = $this->renderAppIcon($app);
		$icon->resizeImage(32, 32, Imagick::FILTER_LANCZOS, 1);
		$icon->setImageFormat("png24");

		$response = new DataDisplayResponse($icon, Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
		$response->cacheFor(86400);
		$response->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		return $response;
	}

	/**
	 * Return a 512x512 icon for touch devices
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param $app app name
	 * @return StreamResponse|DataResponse
	 */
	public function getTouchIcon($app="core") {
		$icon = $this->renderAppIcon($app);
		$icon->setImageFormat("png24");

		$response = new DataDisplayResponse($icon, Http::STATUS_OK, ['Content-Type' => 'image/png']);
		$response->cacheFor(86400);
		$response->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		return $response;
	}

	/**
	 * Render app icon on themed background color
	 * fallback to logo
	 *
	 * @param $app app name
	 * @return Imagick
	 */
	private function renderAppIcon($app) {
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