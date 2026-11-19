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
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IConfig;
use OCP\IRequest;

class IconController extends Controller {
	public function __construct(
		$appName,
		IRequest $request,
		private IConfig $config,
		private ThemingDefaults $themingDefaults,
		private IconBuilder $iconBuilder,
		private ImageManager $imageManager,
		private FileAccessHelper $fileAccessHelper,
		private IAppManager $appManager,
	) {
		parent::__construct($appName, $request);
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
		// The cache key is built from the raw path, so only canonical svg paths are
		// accepted to ensure every cached file belongs to exactly one icon.
		if (preg_match('/^([a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\/)*[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.svg$/D', $image) !== 1) {
			return new NotFoundResponse();
		}

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
	 * @return DataDisplayResponse<Http::STATUS_OK, array{Content-Type: 'image/png'}>|FileDisplayResponse<Http::STATUS_OK, array{Content-Type: string}>|NotFoundResponse<Http::STATUS_NOT_FOUND, array{}>
	 * @throws \Exception
	 *
	 * 200: Favicon returned
	 * 404: Favicon not found
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function getFavicon(string $app = 'core'): Response {
		if ($app !== 'core' && !$this->appManager->isEnabledForUser($app)) {
			$app = 'core';
		}

		$response = null;
		$iconFile = null;
		// retrieve instance favicon, a raster image is converted to a square ICO file
		try {
			$iconFile = $this->imageManager->getImage('favicon');
			$mimeType = $this->imageManager->getImageMime('favicon');
			$icoFile = $this->getCachedIcon('favIcon-favicon', fn () => $this->iconBuilder->getUploadedFavicon());
			if ($icoFile !== null) {
				$iconFile = $icoFile;
				$mimeType = 'image/x-icon';
			}
			$response = new FileDisplayResponse($iconFile, Http::STATUS_OK, ['Content-Type' => $mimeType]);
		} catch (NotFoundException $e) {
		}
		// retrieve or generate app specific favicon, but only if no custom favicon was uploaded
		if ($iconFile === null && ($this->imageManager->canConvert('PNG') || $this->imageManager->canConvert('SVG')) && $this->imageManager->canConvert('ICO')) {
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
		// fallback to core favicon
		if ($response === null) {
			$fallbackLogo = \OC::$SERVERROOT . '/core/img/favicon.png';
			$response = new DataDisplayResponse($this->fileAccessHelper->file_get_contents($fallbackLogo), Http::STATUS_OK, ['Content-Type' => 'image/png']);
		}
		$response->cacheFor(86400);
		return $response;
	}

	/**
	 * Return a 512x512 icon for touch devices
	 *
	 * @param string $app ID of the app
	 * @return DataDisplayResponse<Http::STATUS_OK, array{Content-Type: 'image/png'}>|FileDisplayResponse<Http::STATUS_OK, array{Content-Type: string}>|NotFoundResponse<Http::STATUS_NOT_FOUND, array{}>
	 * @throws \Exception
	 *
	 * 200: Touch icon returned
	 * 404: Touch icon not found
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function getTouchIcon(string $app = 'core'): Response {
		if ($app !== 'core' && !$this->appManager->isEnabledForUser($app)) {
			$app = 'core';
		}

		$response = null;
		$iconFile = null;
		// retrieve instance favicon, rendered like the app specific touch icons
		try {
			$favicon = $this->imageManager->getImage('favicon');
			$color = $this->themingDefaults->getColorPrimary();
			$iconFile = $this->getCachedIcon('touchIcon-favicon' . $color, fn () => $this->iconBuilder->getUploadedTouchIcon());
			if ($iconFile !== null) {
				$response = new FileDisplayResponse($iconFile, Http::STATUS_OK, ['Content-Type' => 'image/png']);
			} elseif (!$this->imageManager->canConvert('PNG')) {
				$iconFile = $favicon;
				$response = new FileDisplayResponse($iconFile, Http::STATUS_OK, ['Content-Type' => $this->imageManager->getImageMime('favicon')]);
			}
		} catch (NotFoundException $e) {
		}
		// retrieve or generate app specific touch icon, but only if no custom favicon was uploaded or it can not be rendered
		if ($iconFile === null && $this->imageManager->canConvert('PNG')) {
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
		// fallback to core touch icon
		if ($response === null) {
			$fallbackLogo = \OC::$SERVERROOT . '/core/img/favicon-touch.png';
			$response = new DataDisplayResponse($this->fileAccessHelper->file_get_contents($fallbackLogo), Http::STATUS_OK, ['Content-Type' => 'image/png']);
		}
		$response->cacheFor(86400);
		return $response;
	}

	/**
	 * Return the uploaded favicon as a transparent PNG image for the web app manifest
	 *
	 * @return FileDisplayResponse<Http::STATUS_OK, array{Content-Type: 'image/png'}>|NotFoundResponse<Http::STATUS_NOT_FOUND, array{}>
	 *
	 * 200: Icon returned
	 * 404: No favicon uploaded or it can not be rendered
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function getManifestIcon(): Response {
		if (!$this->imageManager->hasImage('favicon') || !$this->imageManager->canConvert('PNG')) {
			return new NotFoundResponse();
		}
		$iconFile = $this->getCachedIcon('manifestIcon-favicon', fn () => $this->iconBuilder->getUploadedManifestIcon());
		if ($iconFile === null) {
			return new NotFoundResponse();
		}
		$response = new FileDisplayResponse($iconFile, Http::STATUS_OK, ['Content-Type' => 'image/png']);
		$response->cacheFor(86400);
		return $response;
	}

	/**
	 * Get a generated icon from the cache, an empty cached file marks a failed generation
	 *
	 * @param callable(): (string|false) $generate
	 */
	private function getCachedIcon(string $name, callable $generate): ?ISimpleFile {
		try {
			$iconFile = $this->imageManager->getCachedImage($name);
		} catch (NotFoundException $e) {
			$icon = $generate();
			$iconFile = $this->imageManager->setCachedImage($name, $icon === false ? '' : $icon);
		}
		return $iconFile->getSize() > 0 ? $iconFile : null;
	}
}
