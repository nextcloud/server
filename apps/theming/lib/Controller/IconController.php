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

use OCA\Theming\IconBuilder;
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
	/** @var IconBuilder */
	private $iconBuilder;

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
		if(extension_loaded('imagick')) {
			$this->iconBuilder = new IconBuilder($this->themingDefaults, $this->util);
		}
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param $app app name
	 * @param $image image file name (svg required)
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
		$response->addHeader('Pragma', 'cache');
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
		if($this->themingDefaults->shouldReplaceIcons()) {
			$icon = $this->iconBuilder->getFavicon($app);
			$response = new DataDisplayResponse($icon, Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
			$response->cacheFor(86400);
			$response->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
			$response->addHeader('Pragma', 'cache');
			return $response;
		} else {
			$response = new DataDisplayResponse(null, Http::STATUS_NOT_FOUND);
			$response->cacheFor(86400);
			$response->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
			return $response;
		}


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
		if($this->themingDefaults->shouldReplaceIcons()) {
			$icon = $this->iconBuilder->getTouchIcon($app);
			$response = new DataDisplayResponse($icon, Http::STATUS_OK, ['Content-Type' => 'image/png']);
			$response->cacheFor(86400);
			$response->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
			$response->addHeader('Pragma', 'cache');
			return $response;
		} else {
			$response = new DataDisplayResponse(null, Http::STATUS_NOT_FOUND);
			$response->cacheFor(86400);
			$response->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
			return $response;
		}
	}


}