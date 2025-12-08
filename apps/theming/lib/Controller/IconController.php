<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Controller;

use OC\IntegrityCheck\Helpers\FileAccessHelper;
use OCA\Theming\IconBuilder;
use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\Files\NotFoundException;
use OCP\IRequest;

class IconController extends Controller {
	/** @var FileAccessHelper */
	private $fileAccessHelper;

	public function __construct(
		$appName,
		IRequest $request,
		private ThemingDefaults $themingDefaults,
		private IconBuilder $iconBuilder,
		private ImageManager $imageManager,
		FileAccessHelper $fileAccessHelper,
		private IAppManager $appManager,
	) {
		parent::__construct($appName, $request);
		$this->fileAccessHelper = $fileAccessHelper;
	}

	/**
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
	#[PublicPage]
	#[NoCSRFRequired]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
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
	 * @param string $app ID of the app
	 * @return DataDisplayResponse<Http::STATUS_OK, array{Content-Type: 'image/x-icon'}>|FileDisplayResponse<Http::STATUS_OK, array{Content-Type: 'image/x-icon'}>|NotFoundResponse<Http::STATUS_NOT_FOUND, array{}>
	 * @throws \Exception
	 *
	 * 200: Favicon returned
	 * 404: Favicon not found
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function getFavicon(string $app = 'core'): Response {
		// Always serve the static favicon.ico from theming app for all apps
		$staticFavicon = \OC::$SERVERROOT . '/apps/theming/img/favicon.ico';
		if (file_exists($staticFavicon)) {
			$response = new DataDisplayResponse(
				$this->fileAccessHelper->file_get_contents($staticFavicon),
				Http::STATUS_OK,
				['Content-Type' => 'image/x-icon']
			);
			$response->cacheFor(86400);
			return $response;
		}

		// Fallback to core favicon if theming favicon doesn't exist
		$fallbackLogo = \OC::$SERVERROOT . '/core/img/favicon.png';
		$response = new DataDisplayResponse(
			$this->fileAccessHelper->file_get_contents($fallbackLogo),
			Http::STATUS_OK,
			['Content-Type' => 'image/png']
		);
		$response->cacheFor(86400);
		return $response;
	}

	/**
	 * Return a 512x512 icon for touch devices
	 *
	 * @param string $app ID of the app
	 * @return DataDisplayResponse<Http::STATUS_OK, array{Content-Type: 'image/png'}>|FileDisplayResponse<Http::STATUS_OK, array{Content-Type: 'image/x-icon'|'image/png'}>|NotFoundResponse<Http::STATUS_NOT_FOUND, array{}>
	 * @throws \Exception
	 *
	 * 200: Touch icon returned
	 * 404: Touch icon not found
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function getTouchIcon(string $app = 'core'): Response {
		// Always serve the static favicon-touch.png from theming app for all apps
		$staticTouchIcon = \OC::$SERVERROOT . '/apps/theming/img/favicon-touch.png';
		if (file_exists($staticTouchIcon)) {
			$response = new DataDisplayResponse(
				$this->fileAccessHelper->file_get_contents($staticTouchIcon),
				Http::STATUS_OK,
				['Content-Type' => 'image/png']
			);
			$response->cacheFor(86400);
			return $response;
		}

		// Fallback to core touch icon if theming icon doesn't exist
		$fallbackLogo = \OC::$SERVERROOT . '/core/img/favicon-touch.png';
		$response = new DataDisplayResponse(
			$this->fileAccessHelper->file_get_contents($fallbackLogo),
			Http::STATUS_OK,
			['Content-Type' => 'image/png']
		);
		$response->cacheFor(86400);
		return $response;
	}
}
