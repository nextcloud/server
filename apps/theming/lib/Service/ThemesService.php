<?php
/**
 * @copyright Copyright (c) 2022 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
namespace OCA\Theming\Service;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\Themes\DefaultTheme;
use OCA\Theming\Themes\DarkTheme;
use OCA\Theming\Themes\DarkHighContrastTheme;
use OCA\Theming\Themes\HighContrastTheme;
use OCA\Theming\ITheme;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;

class ThemesService {
	private IUserSession $session;
	private IConfig $config;

	/** @var ITheme[] */
	private array $themesProviders;

	public function __construct(IUserSession $userSession,
								IConfig $config,
								DefaultTheme $defaultTheme,
								DarkTheme $darkTheme,
								DarkHighContrastTheme $darkHighContrastTheme,
								HighContrastTheme $highContrastTheme) {
		$this->userSession = $userSession;
		$this->config = $config;

		// Register themes
		$this->themesProviders = [
			$defaultTheme->getId()			=> $defaultTheme,
			$darkTheme->getId()				=> $darkTheme,
			$darkHighContrastTheme->getId()	=> $darkHighContrastTheme,
			$highContrastTheme->getId()		=> $highContrastTheme,
		];
	}

	public function getThemes(): array {
		return $this->themesProviders;
	}

	public function getThemeVariables(string $id): array {
		return $this->themesProviders[$id]->getCSSVariables();
	}

	public function enableTheme(ITheme $theme): void {
		$themes = $this->getEnabledThemes();
		array_push($themes, $theme->getId());
		$this->setEnabledThemes($themes);
	}

	public function disableTheme(ITheme $theme): void {
		// Using keys as it's faster
		$themes = $this->getEnabledThemes();
		// If enabled, removing it
		if (in_array($theme->getId(), $themes)) {
			$this->setEnabledThemes(array_filter($themes, function($themeId) use ($theme) {
				return $themeId !== $theme->getId();
			}));
		}
	}

	public function isEnabled(ITheme $theme): bool {
		$user = $this->userSession->getUser();
		if ($user instanceof IUser) {
			// Using keys as it's faster
			$themes = $this->getEnabledThemes();
			return in_array($theme->getId(), $themes);
		}
	}

	public function getEnabledThemes(): array {
		$user = $this->userSession->getUser();
		$enabledThemes = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'enabled-themes', '[]');
		return json_decode($enabledThemes);
	}

	private function setEnabledThemes(array $themes): void {
		$user = $this->userSession->getUser();
		$this->config->setUserValue($user->getUID(), Application::APP_ID, 'enabled-themes', json_encode(array_unique($themes)));
	}
}
