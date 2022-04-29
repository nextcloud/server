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

use OCA\Theming\ITheme;
use OCA\Theming\Service\ThemesService;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCSController;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\PreConditionNotMetException;

class UserThemeController extends OCSController {

	protected string $userId;
	private IConfig $config;
	private IUserSession $userSession;
	private ThemesService $themesService;

	/**
	 * Config constructor.
	 */
	public function __construct(string $appName,
								IRequest $request,
								IConfig $config,
								IUserSession $userSession,
								ThemesService $themesService) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->userSession = $userSession;
		$this->themesService = $themesService;
		$this->userId = $userSession->getUser()->getUID();
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
}
