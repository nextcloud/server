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
use OCA\Theming\ITheme;
use OCA\Theming\Themes\DarkHighContrastTheme;
use OCA\Theming\Themes\DarkTheme;
use OCA\Theming\Themes\DefaultTheme;
use OCA\Theming\Themes\DyslexiaFont;
use OCA\Theming\Themes\HighContrastTheme;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;

class ThemesService {
	private IUserSession $userSession;
	private IConfig $config;

	/** @var ITheme[] */
	private array $themesProviders;

	public function __construct(IUserSession $userSession,
								IConfig $config,
								DefaultTheme $defaultTheme,
								DarkTheme $darkTheme,
								DarkHighContrastTheme $darkHighContrastTheme,
								HighContrastTheme $highContrastTheme,
								DyslexiaFont $dyslexiaFont) {
		$this->userSession = $userSession;
		$this->config = $config;

		// Register themes
		$this->themesProviders = [
			$defaultTheme->getId()			=> $defaultTheme,
			$darkTheme->getId()				=> $darkTheme,
			$highContrastTheme->getId()		=> $highContrastTheme,
			$darkHighContrastTheme->getId()	=> $darkHighContrastTheme,
			$dyslexiaFont->getId()			=> $dyslexiaFont,
		];
	}

	/**
	 * Get the list of all registered themes
	 * 
	 * @return ITheme[]
	 */
	public function getThemes(): array {
		return $this->themesProviders;
	}

	/**
	 * Enable a theme for the logged-in user
	 * 
	 * @param ITheme $theme the theme to enable
	 */
	public function enableTheme(ITheme $theme): void {
		$themesIds = $this->getEnabledThemes();

		/** @var ITheme[] */
		$themes = array_map(function($themeId) {
			return $this->getThemes()[$themeId];
		}, $themesIds);

		// Filtering all themes with the same type
		$filteredThemes = array_filter($themes, function($t) use ($theme) {
			return $theme->getType() === $t->getType();
		});

		// Disable all the other themes of the same type
		// as there can only be one enabled at the same time
		foreach ($filteredThemes as $t) {
			$this->disableTheme($t);
		}

		$this->setEnabledThemes([...$this->getEnabledThemes(), $theme->getId()]);
	}

	/**
	 * Disable a theme for the logged-in user
	 * 
	 * @param ITheme $theme the theme to disable
	 */
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

	/**
	 * Check whether a theme is enabled or not
	 * for the logged-in user
	 * 
	 * @return bool
	 */
	public function isEnabled(ITheme $theme): bool {
		$user = $this->userSession->getUser();
		if ($user instanceof IUser) {
			// Using keys as it's faster
			$themes = $this->getEnabledThemes();
			return in_array($theme->getId(), $themes);
		}
	}

	/**
	 * Get the list of all enabled themes IDs
	 * for the logged-in user
	 * 
	 * @return string[]
	 */
	public function getEnabledThemes(): array {
		$user = $this->userSession->getUser();
		try {
			return json_decode($this->config->getUserValue($user->getUID(), Application::APP_ID, 'enabled-themes', '[]'));
		} catch (\Exception $e) {
			return [];
		}
	}

	/**
	 * Set the list of enabled themes 
	 * for the logged-in user
	 * 
	 * @param string[] $themes the list of enabled themes IDs
	 */
	private function setEnabledThemes(array $themes): void {
		$user = $this->userSession->getUser();
		$this->config->setUserValue($user->getUID(), Application::APP_ID, 'enabled-themes', json_encode(array_unique($themes)));
	}
}
