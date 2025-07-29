<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Controller;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\ITheme;
use OCA\Theming\ResponseDefinitions;
use OCA\Theming\Service\BackgroundService;
use OCA\Theming\Service\ThemesService;
use OCA\Theming\ThemingDefaults;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCSController;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\PreConditionNotMetException;

/**
 * @psalm-import-type ThemingBackground from ResponseDefinitions
 */
class UserThemeController extends OCSController {

	protected ?string $userId = null;

	public function __construct(
		string $appName,
		IRequest $request,
		private IConfig $config,
		IUserSession $userSession,
		private ThemesService $themesService,
		private ThemingDefaults $themingDefaults,
		private BackgroundService $backgroundService,
	) {
		parent::__construct($appName, $request);

		$user = $userSession->getUser();
		if ($user !== null) {
			$this->userId = $user->getUID();
		}
	}

	/**
	 * Enable theme
	 *
	 * @param string $themeId the theme ID
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSBadRequestException Enabling theme is not possible
	 * @throws PreConditionNotMetException
	 *
	 * 200: Theme enabled successfully
	 */
	#[NoAdminRequired]
	public function enableTheme(string $themeId): DataResponse {
		$theme = $this->validateTheme($themeId);

		// Enable selected theme
		$this->themesService->enableTheme($theme);
		return new DataResponse();
	}

	/**
	 * Disable theme
	 *
	 * @param string $themeId the theme ID
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSBadRequestException Disabling theme is not possible
	 * @throws PreConditionNotMetException
	 *
	 * 200: Theme disabled successfully
	 */
	#[NoAdminRequired]
	public function disableTheme(string $themeId): DataResponse {
		$theme = $this->validateTheme($themeId);

		// Enable selected theme
		$this->themesService->disableTheme($theme);
		return new DataResponse();
	}

	/**
	 * Validate and return the matching ITheme
	 *
	 * Disable theme
	 *
	 * @param string $themeId the theme ID
	 * @return ITheme
	 * @throws OCSBadRequestException
	 * @throws PreConditionNotMetException
	 */
	private function validateTheme(string $themeId): ITheme {
		if ($themeId === '' || !$themeId) {
			throw new OCSBadRequestException('Invalid theme id: ' . $themeId);
		}

		$themes = $this->themesService->getThemes();
		if (!isset($themes[$themeId])) {
			throw new OCSBadRequestException('Invalid theme id: ' . $themeId);
		}

		// If trying to toggle another theme but this is enforced
		if ($this->config->getSystemValueString('enforce_theme', '') !== ''
			&& $themes[$themeId]->getType() === ITheme::TYPE_THEME) {
			throw new OCSForbiddenException('Theme switching is disabled');
		}

		return $themes[$themeId];
	}

	/**
	 * Get the background image
	 * @return FileDisplayResponse<Http::STATUS_OK, array{Content-Type: string}>|NotFoundResponse<Http::STATUS_NOT_FOUND, array{}>
	 *
	 * 200: Background image returned
	 * 404: Background image not found
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function getBackground(): Response {
		$file = $this->backgroundService->getBackground();
		if ($file !== null) {
			$response = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => $file->getMimeType()]);
			$response->cacheFor(24 * 60 * 60, false, true);
			return $response;
		}
		return new NotFoundResponse();
	}

	/**
	 * Delete the background
	 *
	 * @return JSONResponse<Http::STATUS_OK, ThemingBackground, array{}>
	 *
	 * 200: Background deleted successfully
	 */
	#[NoAdminRequired]
	public function deleteBackground(): JSONResponse {
		$currentVersion = (int)$this->config->getUserValue($this->userId, Application::APP_ID, 'userCacheBuster', '0');
		$this->backgroundService->deleteBackgroundImage();
		return new JSONResponse([
			'backgroundImage' => null,
			'backgroundColor' => $this->themingDefaults->getColorBackground(),
			'primaryColor' => $this->themingDefaults->getColorPrimary(),
			'version' => $currentVersion,
		]);
	}

	/**
	 * Set the background
	 *
	 * @param string $type Type of background
	 * @param string $value Path of the background image
	 * @param string|null $color Color for the background
	 * @return JSONResponse<Http::STATUS_OK, ThemingBackground, array{}>|JSONResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_INTERNAL_SERVER_ERROR, array{error: string}, array{}>
	 *
	 * 200: Background set successfully
	 * 400: Setting background is not possible
	 */
	#[NoAdminRequired]
	public function setBackground(string $type = BackgroundService::BACKGROUND_DEFAULT, string $value = '', ?string $color = null): JSONResponse {
		$currentVersion = (int)$this->config->getUserValue($this->userId, Application::APP_ID, 'userCacheBuster', '0');

		// Set color if provided
		if ($color) {
			$this->backgroundService->setColorBackground($color);
		}

		// Set background image if provided
		try {
			switch ($type) {
				case BackgroundService::BACKGROUND_SHIPPED:
					$this->backgroundService->setShippedBackground($value);
					break;
				case BackgroundService::BACKGROUND_CUSTOM:
					$this->backgroundService->setFileBackground($value);
					break;
				case BackgroundService::BACKGROUND_DEFAULT:
					// Delete both background and color keys
					$this->backgroundService->setDefaultBackground();
					break;
				default:
					if (!$color) {
						return new JSONResponse(['error' => 'Invalid type provided'], Http::STATUS_BAD_REQUEST);
					}
			}
		} catch (\InvalidArgumentException $e) {
			return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		} catch (\Throwable $e) {
			return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		$currentVersion++;
		$this->config->setUserValue($this->userId, Application::APP_ID, 'userCacheBuster', (string)$currentVersion);

		return new JSONResponse([
			'backgroundImage' => $this->config->getUserValue($this->userId, Application::APP_ID, 'background_image', BackgroundService::BACKGROUND_DEFAULT),
			'backgroundColor' => $this->themingDefaults->getColorBackground(),
			'primaryColor' => $this->themingDefaults->getColorPrimary(),
			'version' => $currentVersion,
		]);
	}
}
