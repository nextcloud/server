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

use OCA\Theming\ITheme;
use OCA\Theming\Themes\DefaultTheme;
use OCA\Theming\Util;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUserSession;

class ThemeInjectionService {
	private ?string $userId = null;

	public function __construct(
		private IURLGenerator $urlGenerator,
		private ThemesService $themesService,
		private DefaultTheme  $defaultTheme,
		private Util $util,
		private IConfig $config,
		IUserSession $userSession,
	) {
		$this->userId = $userSession->getUser()?->getUID();
	}

	public function injectHeaders(): void {
		$themes = $this->themesService->getThemes();
		$defaultTheme = $themes[$this->defaultTheme->getId()];
		$mediaThemes = array_filter($themes, function ($theme) {
			// Check if the theme provides a media query
			return (bool)$theme->getMediaQuery();
		});

		// Default theme fallback
		$this->addThemeHeaders($defaultTheme);

		// Themes applied by media queries
		foreach ($mediaThemes as $theme) {
			$this->addThemeHeaders($theme, true, $theme->getMediaQuery());
		}

		// Themes
		foreach ($this->themesService->getThemes() as $theme) {
			// Ignore default theme as already processed first
			if ($theme->getId() === $this->defaultTheme->getId()) {
				continue;
			}
			$this->addThemeHeaders($theme, false);
		}

		// Meta headers
		$this->addThemeMetaHeaders($themes);
	}

	/**
	 * Inject theme header into rendered page
	 *
	 * @param ITheme $theme the theme
	 * @param bool $plain request the :root syntax
	 * @param string $media media query to use in the <link> element
	 */
	private function addThemeHeaders(ITheme $theme, bool $plain = true, ?string $media = null): void {
		$linkToCSS = $this->urlGenerator->linkToRoute('theming.Theming.getThemeStylesheet', [
			'themeId' => $theme->getId(),
			'plain' => $plain,
			'v' => $this->util->getCacheBuster(),
		]);
		\OCP\Util::addHeader('link', [
			'rel' => 'stylesheet',
			'media' => $media,
			'href' => $linkToCSS,
			'class' => 'theme'
		]);
	}

	/**
	 * Inject meta headers into rendered page
	 *
	 * @param ITheme[] $themes the theme
	 */
	private function addThemeMetaHeaders(array $themes): void {
		$metaHeaders = [];

		// Meta headers
		foreach ($this->themesService->getThemes() as $theme) {
			if (!empty($theme->getMeta())) {
				foreach ($theme->getMeta() as $meta) {
					if (!isset($meta['name']) || !isset($meta['content'])) {
						continue;
					}

					if (!isset($metaHeaders[$meta['name']])) {
						$metaHeaders[$meta['name']] = [];
					}
					$metaHeaders[$meta['name']][] = $meta['content'];
				}
			}
		}

		foreach ($metaHeaders as $name => $content) {
			\OCP\Util::addHeader('meta', [
				'name' => $name,
				'content' => join(' ', array_unique($content)),
			]);
		}
	}
}
