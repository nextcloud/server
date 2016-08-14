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
	/** @var Template */
	private $template;
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
	 * @param Template $template
	 * @param Util $util
	 * @param ITimeFactory $timeFactory
	 * @param IL10N $l
	 * @param IRootFolder $rootFolder
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IConfig $config,
		Template $template,
		Util $util,
		ITimeFactory $timeFactory,
		IL10N $l,
		IRootFolder $rootFolder
	) {
		parent::__construct($appName, $request);

		$this->template = $template;
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
		$image = $this->getAppImage($app, $image);
		$svg = file_get_contents($image);
		$color = $this->template->getMailHeaderColor();
		$svg = $this->colorizeSvg($svg, $color);
		return new DataDisplayResponse($svg, Http::STATUS_OK, ['Content-Type' => 'image/svg+xml']);
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
		// TODO: we need caching here
		$icon = $this->renderAppIcon($app);
		$icon->resizeImage(32, 32, Imagick::FILTER_LANCZOS, 1);
		$icon->setImageFormat("png24");

		$response = new DataDisplayResponse($icon, Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
		$response->cacheFor(3600);
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
		// TODO: we need caching here
		$icon = $this->renderAppIcon($app);
		$icon->resizeImage(512, 512, Imagick::FILTER_LANCZOS, 1);
		$icon->setImageFormat("png24");

		$response = new DataDisplayResponse($icon, Http::STATUS_OK, ['Content-Type' => 'image/png']);
		$response->cacheFor(3600);
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
		$appIcon = $this->getAppIcon($app);
		$color = $this->config->getAppValue($this->appName, 'color');
		$mime = mime_content_type($appIcon);
		if ($color === "") {
			$color = '#0082c9';
		}
		// generate background image with rounded corners
		$background = '<?xml version="1.0" encoding="UTF-8"?>' .
			'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:cc="http://creativecommons.org/ns#" width="512" height="512" xmlns:xlink="http://www.w3.org/1999/xlink">' .
			'<rect x="0" y="0" rx="75" ry="75" width="512" height="512" style="fill:' . $color . ';" />' .
			'</svg>';


		// resize svg magic as this seems broken in Imagemagick
		if($mime === "image/svg+xml") {
			$svg = file_get_contents($appIcon);
			if ($this->util->invertTextColor($color)) {
				$svg = $this->svgInvert($svg);
			}

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
		$appIconFile->destroy();
		return $finalIconFile;
	}

	/**
	 * @param $app app name
	 * @return string path to app icon / logo
	 */
	private function getAppIcon($app) {
		$appPath = \OC_App::getAppPath($app);

		$icon = $appPath . '/img/' . $app . '.svg';
		if(file_exists($icon)) {
			return $icon;
		}
		$icon = $appPath . '/img/app.svg';
		if(file_exists($icon)) {
			return $icon;
		}

		$icon = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data/') . '/themedinstancelogo';
		if(file_exists($icon)) {
			return $icon;
		}
		return \OC::$SERVERROOT . '/core/img/logo.svg';
	}

	/**
	 * @param $app app name
	 * @param $image relative path to image in app folder
	 * @return string absolute path to image
	 */
	private function getAppImage($app, $image) {
		$appPath = \OC_App::getAppPath($app);

		if($app==="core") {
			$icon = \OC::$SERVERROOT . '/core/img/' . $image;
			if(file_exists($icon)) {
				return $icon;
			}
		}

		$icon = $appPath . '/img/' . $image;
		if(file_exists($icon)) {
			return $icon;
		}
		$icon = $appPath . '/img/' . $image . '.svg';
		if(file_exists($icon)) {
			return $icon;
		}
		$icon = $appPath . '/img/' . $image . '.png';
		if(file_exists($icon)) {
			return $icon;
		}
		$icon = $appPath . '/img/' . $image . '.gif';
		if(file_exists($icon)) {
			return $icon;
		}
		$icon = $appPath . '/img/' . $image . '.jpg';
		if(file_exists($icon)) {
			return $icon;
		}
	}

	/**
	 * replace black with white and white with black
	 *
	 * @param $svg content of a svg file
	 * @return string
	 */
	private function svgInvert($svg) {
		$svg = preg_replace('/#(f{3,6})/i', '#REPLACECOLOR', $svg);
		$svg = preg_replace('/#(0{3,6})/i', '#ffffff', $svg);
		$svg = preg_replace('/#(REPLACECOLOR)/i', '#000000', $svg);
		return $svg;
	}

	/**
	 * replace default color with a custom one
	 *
	 * @param $svg content of a svg file
	 * @param $color color to match
	 * @return string
	 */
	private function colorizeSvg($svg, $color) {
		$svg = preg_replace('/#0082c9/i', $color, $svg);
		return $svg;
	}
	
}