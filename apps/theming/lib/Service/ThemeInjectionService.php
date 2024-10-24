<?php
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Service;

use OCA\Theming\ITheme;
use OCA\Theming\Themes\DefaultTheme;
use OCA\Theming\Util;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUserSession;

class ThemeInjectionService {

	private ?string $userId;

	public function __construct(
		private IURLGenerator $urlGenerator,
		private ThemesService $themesService,
		private DefaultTheme $defaultTheme,
		private Util $util,
		private IConfig $config,
		IUserSession $userSession,
	) {
		if ($userSession->getUser() !== null) {
			$this->userId = $userSession->getUser()->getUID();
		} else {
			$this->userId = null;
		}
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
