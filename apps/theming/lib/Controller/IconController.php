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
use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;
use OCA\Theming\Util;
use OCP\IConfig;

class IconController extends Controller {
	/** @var ThemingDefaults */
	private $themingDefaults;
	/** @var Util */
	private $util;
	/** @var ITimeFactory */
	private $timeFactory;
	/** @var IConfig */
	private $config;
	/** @var IconBuilder */
	private $iconBuilder;
	/** @var ImageManager */
	private $imageManager;

	/**
	 * IconController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param ThemingDefaults $themingDefaults
	 * @param Util $util
	 * @param ITimeFactory $timeFactory
	 * @param IConfig $config
	 * @param IconBuilder $iconBuilder
	 * @param ImageManager $imageManager
	 */
	public function __construct(
		$appName,
		IRequest $request,
		ThemingDefaults $themingDefaults,
		Util $util,
		ITimeFactory $timeFactory,
		IConfig $config,
		IconBuilder $iconBuilder,
		ImageManager $imageManager
	) {
		parent::__construct($appName, $request);

		$this->themingDefaults = $themingDefaults;
		$this->util = $util;
		$this->timeFactory = $timeFactory;
		$this->config = $config;
		$this->iconBuilder = $iconBuilder;
		$this->imageManager = $imageManager;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param $app string app name
	 * @param $image string image file name (svg required)
	 * @return FileDisplayResponse
	 */
	public function getThemedIcon($app, $image) {
		$iconFile = $this->imageManager->getCachedImage("icon-" . $app . '-' . str_replace("/","_",$image));
		if ($iconFile === null) {
			$icon = $this->iconBuilder->colorSvg($app, $image);
			$iconFile = $this->imageManager->setCachedImage("icon-" . $app . '-' . str_replace("/","_",$image), $icon);
		}
		$response = new FileDisplayResponse($iconFile, Http::STATUS_OK, ['Content-Type' => 'image/svg+xml']);
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
	 * @param $app string app name
	 * @return FileDisplayResponse|DataDisplayResponse
	 */
	public function getFavicon($app = "core") {
		$iconFile = $this->imageManager->getCachedImage('favIcon-' . $app);
		if($iconFile === null && $this->themingDefaults->shouldReplaceIcons()) {
			$icon = $this->iconBuilder->getFavicon($app);
			$iconFile = $this->imageManager->setCachedImage('favIcon-' . $app, $icon);
		}
		if ($this->themingDefaults->shouldReplaceIcons()) {
			$response = new FileDisplayResponse($iconFile, Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
		} else {
			$response = new DataDisplayResponse(null, Http::STATUS_NOT_FOUND);
		}
		$response->cacheFor(86400);
		$response->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$response->addHeader('Pragma', 'cache');
		return $response;
	}

	/**
	 * Return a 512x512 icon for touch devices
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param $app string app name
	 * @return FileDisplayResponse|DataDisplayResponse
	 */
	public function getTouchIcon($app = "core") {
		$iconFile = $this->imageManager->getCachedImage('touchIcon-' . $app);
		if ($iconFile === null && $this->themingDefaults->shouldReplaceIcons()) {
			$icon = $this->iconBuilder->getTouchIcon($app);
			$iconFile = $this->imageManager->setCachedImage('touchIcon-' . $app, $icon);
		}
		if ($this->themingDefaults->shouldReplaceIcons()) {
			$response = new FileDisplayResponse($iconFile, Http::STATUS_OK, ['Content-Type' => 'image/png']);
		} else {
			$response = new DataDisplayResponse(null, Http::STATUS_NOT_FOUND);
		}
		$response->cacheFor(86400);
		$response->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$response->addHeader('Pragma', 'cache');
		return $response;
	}
}