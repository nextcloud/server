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
use OCA\Theming\Themes\LightTheme;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class ThemesService {
	/** @var ITheme[] */
	private array $themesProviders;

	public function __construct(
		private IUserSession $userSession,
		private IConfig $config,
		private LoggerInterface $logger,
		private DefaultTheme $defaultTheme,
		LightTheme $lightTheme,
		private DarkTheme $darkTheme,
		HighContrastTheme $highContrastTheme,
		DarkHighContrastTheme $darkHighContrastTheme,
		DyslexiaFont $dyslexiaFont) {

		// Register themes
		$this->themesProviders = [
			$defaultTheme->getId() => $defaultTheme,
			$lightTheme->getId() => $lightTheme,
			$darkTheme->getId() => $darkTheme,
			$highContrastTheme->getId() => $highContrastTheme,
			$darkHighContrastTheme->getId() => $darkHighContrastTheme,
			$dyslexiaFont->getId() => $dyslexiaFont,
		];
	}

	/**
	 * Get the list of all registered themes
	 *
	 * @return ITheme[]
	 */
	public function getThemes(): array {
		// Enforced theme if configured
		$enforcedTheme = $this->config->getSystemValueString('enforce_theme', '');
		if ($enforcedTheme !== '') {
			if (!isset($this->themesProviders[$enforcedTheme])) {
				$this->logger->error('Enforced theme not found', ['theme' => $enforcedTheme]);
				return $this->themesProviders;
			}

			$defaultTheme = $this->themesProviders[$this->defaultTheme->getId()];
			$darkTheme = $this->themesProviders[$this->darkTheme->getId()];
			$theme = $this->themesProviders[$enforcedTheme];
			return [
				// Leave the default theme as a fallback
				$defaultTheme->getId() => $defaultTheme,
				// Make sure we also have the dark theme to allow apps
				// to scope sections of their UI to the dark theme
				$darkTheme->getId() => $darkTheme,
				// Finally, the enforced theme
				$theme->getId() => $theme,
			];
		}

		return $this->themesProviders;
	}

	/**
	 * Enable a theme for the logged-in user
	 *
	 * @param ITheme $theme the theme to enable
	 * @return string[] the enabled themes
	 */
	public function enableTheme(ITheme $theme): array {
		$themesIds = $this->getEnabledThemes();

		// If already enabled, ignore
		if (in_array($theme->getId(), $themesIds)) {
			return $themesIds;
		}

		/** @var ITheme[] */
		$themes = array_filter(array_map(function ($themeId) {
			return $this->getThemes()[$themeId];
		}, $themesIds));

		// Filtering all themes with the same type
		$filteredThemes = array_filter($themes, function (ITheme $t) use ($theme) {
			return $theme->getType() === $t->getType();
		});

		// Retrieve IDs only
		/** @var string[] */
		$filteredThemesIds = array_map(function (ITheme $t) {
			return $t->getId();
		}, array_values($filteredThemes));

		$enabledThemes = array_merge(array_diff($themesIds, $filteredThemesIds), [$theme->getId()]);
		$this->setEnabledThemes($enabledThemes);

		return $enabledThemes;
	}

	/**
	 * Disable a theme for the logged-in user
	 *
	 * @param ITheme $theme the theme to disable
	 * @return string[] the enabled themes
	 */
	public function disableTheme(ITheme $theme): array {
		$themesIds = $this->getEnabledThemes();

		// If enabled, removing it
		if (in_array($theme->getId(), $themesIds)) {
			$enabledThemes = array_diff($themesIds, [$theme->getId()]);
			$this->setEnabledThemes($enabledThemes);
			return $enabledThemes;
		}

		return $themesIds;
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
		return false;
	}

	/**
	 * Get the list of all enabled themes IDs
	 * for the logged-in user
	 *
	 * @return string[]
	 */
	public function getEnabledThemes(): array {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return [];
		}

		$enforcedTheme = $this->config->getSystemValueString('enforce_theme', '');
		$enabledThemes = json_decode($this->config->getUserValue($user->getUID(), Application::APP_ID, 'enabled-themes', '["default"]'));
		if ($enforcedTheme !== '') {
			return array_merge([$enforcedTheme], $enabledThemes);
		}

		try {
			return $enabledThemes;
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
		$this->config->setUserValue($user->getUID(), Application::APP_ID, 'enabled-themes', json_encode(array_values(array_unique($themes))));
	}
}
