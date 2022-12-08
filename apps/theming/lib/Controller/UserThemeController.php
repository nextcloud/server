<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
 * @copyright Copyright (c) 2019 Janis Köhr <janiskoehr@icloud.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Janis Köhr <janis.koehr@novatec-gmbh.de>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

use OCA\Theming\AppInfo\Application;
use OCA\Theming\ITheme;
use OCA\Theming\Service\BackgroundService;
use OCA\Theming\Service\ThemesService;
use OCA\Theming\ThemingDefaults;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCSController;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\PreConditionNotMetException;

class UserThemeController extends OCSController {

	protected ?string $userId = null;
	
	private IConfig $config;
	private IUserSession $userSession;
	private ThemesService $themesService;
	private ThemingDefaults $themingDefaults;
	private BackgroundService $backgroundService;

	public function __construct(string $appName,
								IRequest $request,
								IConfig $config,
								IUserSession $userSession,
								ThemesService $themesService,
								ThemingDefaults $themingDefaults,
								BackgroundService $backgroundService) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->userSession = $userSession;
		$this->themesService = $themesService;
		$this->themingDefaults = $themingDefaults;
		$this->backgroundService = $backgroundService;

		$user = $userSession->getUser();
		if ($user !== null) {
			$this->userId = $user->getUID();
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * Enable theme
	 *
	 * @param string $themeId the theme ID
	 * @return DataResponse
	 * @throws OCSBadRequestException|PreConditionNotMetException
	 */
	public function enableTheme(string $themeId): DataResponse {
		$theme = $this->validateTheme($themeId);

		// Enable selected theme
		$this->themesService->enableTheme($theme);
		return new DataResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * Disable theme
	 *
	 * @param string $themeId the theme ID
	 * @return DataResponse
	 * @throws OCSBadRequestException|PreConditionNotMetException
	 */
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
	 * @throws OCSBadRequestException|PreConditionNotMetException
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
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getBackground(): Http\Response {
		$file = $this->backgroundService->getBackground();
		if ($file !== null) {
			$response = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => $file->getMimeType()]);
			$response->cacheFor(24 * 60 * 60, false, true);
			return $response;
		}
		return new NotFoundResponse();
	}

	/**
	 * @NoAdminRequired
	 */
	public function deleteBackground(): JSONResponse {
		$currentVersion = (int)$this->config->getUserValue($this->userId, Application::APP_ID, 'userCacheBuster', '0');
		$this->backgroundService->deleteBackgroundImage();
		return new JSONResponse([
			'backgroundImage' => null,
			'backgroundColor' => $this->themingDefaults->getColorPrimary(),
			'version' => $currentVersion,
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function setBackground(string $type = BackgroundService::BACKGROUND_DEFAULT, string $value = '', string $color = null): JSONResponse {
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
			'backgroundColor' => $this->themingDefaults->getColorPrimary(),
			'version' => $currentVersion,
		]);
	}
}
