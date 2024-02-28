<?php
/**
 * @copyright Copyright (c) 2016 Julius Haertl <jus@bitgrid.net>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Michael Weimann <mail@michael-weimann.eu>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Theming\Controller;

use OC\IntegrityCheck\Helpers\FileAccessHelper;
use OCA\Theming\IconBuilder;
use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\Files\NotFoundException;
use OCP\IRequest;

class IconController extends Controller {
	/** @var ThemingDefaults */
	private $themingDefaults;
	/** @var IconBuilder */
	private $iconBuilder;
	/** @var ImageManager */
	private $imageManager;
	/** @var FileAccessHelper */
	private $fileAccessHelper;
	/** @var IAppManager */
	private $appManager;

	public function __construct(
		$appName,
		IRequest $request,
		ThemingDefaults $themingDefaults,
		IconBuilder $iconBuilder,
		ImageManager $imageManager,
		FileAccessHelper $fileAccessHelper,
		IAppManager $appManager
	) {
		parent::__construct($appName, $request);

		$this->themingDefaults = $themingDefaults;
		$this->iconBuilder = $iconBuilder;
		$this->imageManager = $imageManager;
		$this->fileAccessHelper = $fileAccessHelper;
		$this->appManager = $appManager;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * Get a themed icon
	 *
	 * @param string $app ID of the app
	 * @param string $image image file name (svg required)
	 * @return FileDisplayResponse<Http::STATUS_OK, array{Content-Type: 'image/svg+xml'}>|NotFoundResponse<Http::STATUS_NOT_FOUND, array{}>
	 * @throws \Exception
	 *
	 * 200: Themed icon returned
	 * 404: Themed icon not found
	 */
	public function getThemedIcon(string $app, string $image): Response {
		if ($app !== 'core' && !$this->appManager->isEnabledForUser($app)) {
			$app = 'core';
			$image = 'favicon.png';
		}

		$color = $this->themingDefaults->getColorPrimary();
		try {
			$iconFileName = $this->imageManager->getCachedImage('icon-' . $app . '-' . $color . str_replace('/', '_', $image));
		} catch (NotFoundException $exception) {
			$icon = $this->iconBuilder->colorSvg($app, $image);
			if ($icon === false || $icon === '') {
				return new NotFoundResponse();
			}
			$iconFileName = $this->imageManager->setCachedImage('icon-' . $app . '-' . $color . str_replace('/', '_', $image), $icon);
		}
		$response = new FileDisplayResponse($iconFileName, Http::STATUS_OK, ['Content-Type' => 'image/svg+xml']);
		$response->cacheFor(86400, false, true);
		return $response;
	}

	/**
	 * Return a 32x32 favicon as png
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $app ID of the app
	 * @return DataDisplayResponse<Http::STATUS_OK, array{Content-Type: 'image/x-icon'}>|FileDisplayResponse<Http::STATUS_OK, array{Content-Type: 'image/x-icon'}>|NotFoundResponse<Http::STATUS_NOT_FOUND, array{}>
	 * @throws \Exception
	 *
	 * 200: Favicon returned
	 * 404: Favicon not found
	 */
	public function getFavicon(string $app = 'core'): Response {
		if ($app !== 'core' && !$this->appManager->isEnabledForUser($app)) {
			$app = 'core';
		}

		$response = null;
		$iconFile = null;
		try {
			$iconFile = $this->imageManager->getImage('favicon', false);
			$response = new FileDisplayResponse($iconFile, Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
		} catch (NotFoundException $e) {
		}
		if ($iconFile === null && $this->imageManager->shouldReplaceIcons()) {
			$color = $this->themingDefaults->getColorPrimary();
			try {
				$iconFile = $this->imageManager->getCachedImage('favIcon-' . $app . $color);
			} catch (NotFoundException $exception) {
				$icon = $this->iconBuilder->getFavicon($app);
				if ($icon === false || $icon === '') {
					return new NotFoundResponse();
				}
				$iconFile = $this->imageManager->setCachedImage('favIcon-' . $app . $color, $icon);
			}
			$response = new FileDisplayResponse($iconFile, Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
		}
		if ($response === null) {
			$fallbackLogo = \OC::$SERVERROOT . '/core/img/favicon.png';
			$response = new DataDisplayResponse($this->fileAccessHelper->file_get_contents($fallbackLogo), Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
		}
		$response->cacheFor(86400);
		return $response;
	}

	/**
	 * Return a 512x512 icon for touch devices
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $app ID of the app
	 * @return DataDisplayResponse<Http::STATUS_OK, array{Content-Type: 'image/png'}>|FileDisplayResponse<Http::STATUS_OK, array{Content-Type: 'image/x-icon'|'image/png'}>|NotFoundResponse<Http::STATUS_NOT_FOUND, array{}>
	 * @throws \Exception
	 *
	 * 200: Touch icon returned
	 * 404: Touch icon not found
	 */
	public function getTouchIcon(string $app = 'core'): Response {
		if ($app !== 'core' && !$this->appManager->isEnabledForUser($app)) {
			$app = 'core';
		}

		$response = null;
		try {
			$iconFile = $this->imageManager->getImage('favicon');
			$response = new FileDisplayResponse($iconFile, Http::STATUS_OK, ['Content-Type' => 'image/x-icon']);
		} catch (NotFoundException $e) {
		}
		if ($this->imageManager->shouldReplaceIcons()) {
			$color = $this->themingDefaults->getColorPrimary();
			try {
				$iconFile = $this->imageManager->getCachedImage('touchIcon-' . $app . $color);
			} catch (NotFoundException $exception) {
				$icon = $this->iconBuilder->getTouchIcon($app);
				if ($icon === false || $icon === '') {
					return new NotFoundResponse();
				}
				$iconFile = $this->imageManager->setCachedImage('touchIcon-' . $app . $color, $icon);
			}
			$response = new FileDisplayResponse($iconFile, Http::STATUS_OK, ['Content-Type' => 'image/png']);
		}
		if ($response === null) {
			$fallbackLogo = \OC::$SERVERROOT . '/core/img/favicon-touch.png';
			$response = new DataDisplayResponse($this->fileAccessHelper->file_get_contents($fallbackLogo), Http::STATUS_OK, ['Content-Type' => 'image/png']);
		}
		$response->cacheFor(86400);
		return $response;
	}
}
