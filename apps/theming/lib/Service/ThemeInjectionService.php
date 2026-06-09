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

		// A request-scoped light/dark override must win over the OS
		// `prefers-color-scheme` preference, so we force it on `:root`
		// instead of relying on the media-query auto-switching.
		// An admin-enforced theme always takes precedence over the override.
		$requestThemeOverride = $this->config->getSystemValueString('enforce_theme', '') === ''
			? $this->themesService->getRequestThemeOverride()
			: null;
		if ($requestThemeOverride !== null && isset($themes[$requestThemeOverride])) {
			$this->injectOverrideHeaders($themes, $defaultTheme, $themes[$requestThemeOverride]);
			return;
		}

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
	 * Inject the headers for a request-scoped light/dark theme override.
	 *
	 * The override has to take precedence over the OS `prefers-color-scheme`
	 * preference, so the overridden theme is forced on `:root` (without any
	 * media query) and only its `color-scheme` meta is exposed.
	 *
	 * @param ITheme[] $themes all registered themes
	 * @param ITheme $defaultTheme the default theme used as a fallback
	 * @param ITheme $overrideTheme the theme requested through the query string
	 */
	private function injectOverrideHeaders(array $themes, ITheme $defaultTheme, ITheme $overrideTheme): void {
		// Default theme fallback
		$this->addThemeHeaders($defaultTheme);

		// Force the overridden theme unconditionally on `:root`
		$this->addThemeHeaders($overrideTheme, true);

		// Keep body-scoped themes so `[data-theme-*]` selectors keep working
		foreach ($themes as $theme) {
			if ($theme->getId() === $this->defaultTheme->getId()) {
				continue;
			}
			$this->addThemeHeaders($theme, false);
		}

		// Only expose the overridden theme color-scheme meta
		$this->addThemeMetaHeaders([$overrideTheme->getId() => $overrideTheme]);
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
		foreach ($themes as $theme) {
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
