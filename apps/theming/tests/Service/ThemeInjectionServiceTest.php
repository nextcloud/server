<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Tests\Service;

use OCA\Theming\ITheme;
use OCA\Theming\Service\ThemeInjectionService;
use OCA\Theming\Service\ThemesService;
use OCA\Theming\Themes\DarkHighContrastTheme;
use OCA\Theming\Themes\DarkTheme;
use OCA\Theming\Themes\DefaultTheme;
use OCA\Theming\Themes\DyslexiaFont;
use OCA\Theming\Themes\HighContrastTheme;
use OCA\Theming\Themes\LightTheme;
use OCA\Theming\Util;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ThemeInjectionServiceTest extends TestCase {
	private IURLGenerator&MockObject $urlGenerator;
	private ThemesService&MockObject $themesService;
	private Util&MockObject $util;
	private IConfig&MockObject $config;
	private IUserSession&MockObject $userSession;
	private DefaultTheme&MockObject $defaultTheme;

	private ThemeInjectionService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->themesService = $this->createMock(ThemesService::class);
		$this->util = $this->createMock(Util::class);
		$this->config = $this->createMock(IConfig::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->defaultTheme = $this->createMock(DefaultTheme::class);

		$this->defaultTheme->method('getId')->willReturn('default');

		$this->urlGenerator->method('linkToRoute')
			->willReturnCallback(function (string $route, array $params): string {
				return '/css/' . $params['themeId'] . '?plain=' . ($params['plain'] ? '1' : '0');
			});

		$this->service = new ThemeInjectionService(
			$this->urlGenerator,
			$this->themesService,
			$this->defaultTheme,
			$this->util,
			$this->config,
			$this->userSession,
		);

		// Reset the static headers collected by the service
		\OC_Util::$headers = [];
	}

	protected function tearDown(): void {
		\OC_Util::$headers = [];
		parent::tearDown();
	}

	/**
	 * @return ITheme[]
	 */
	private function buildThemes(): array {
		$default = $this->createMock(DefaultTheme::class);
		$default->method('getId')->willReturn('default');
		$default->method('getMediaQuery')->willReturn('');
		$default->method('getMeta')->willReturn([]);

		$light = $this->createMock(LightTheme::class);
		$light->method('getId')->willReturn('light');
		$light->method('getMediaQuery')->willReturn('(prefers-color-scheme: light)');
		$light->method('getMeta')->willReturn([['name' => 'color-scheme', 'content' => 'light']]);

		$dark = $this->createMock(DarkTheme::class);
		$dark->method('getId')->willReturn('dark');
		$dark->method('getMediaQuery')->willReturn('(prefers-color-scheme: dark)');
		$dark->method('getMeta')->willReturn([['name' => 'color-scheme', 'content' => 'dark']]);

		$lightHc = $this->createMock(HighContrastTheme::class);
		$lightHc->method('getId')->willReturn('light-highcontrast');
		$lightHc->method('getMediaQuery')->willReturn('(prefers-contrast: more)');
		$lightHc->method('getMeta')->willReturn([]);

		$darkHc = $this->createMock(DarkHighContrastTheme::class);
		$darkHc->method('getId')->willReturn('dark-highcontrast');
		$darkHc->method('getMediaQuery')->willReturn('(prefers-color-scheme: dark) and (prefers-contrast: more)');
		$darkHc->method('getMeta')->willReturn([]);

		$dyslexic = $this->createMock(DyslexiaFont::class);
		$dyslexic->method('getId')->willReturn('opendyslexic');
		$dyslexic->method('getMediaQuery')->willReturn('');
		$dyslexic->method('getMeta')->willReturn([]);

		return [
			'default' => $default,
			'light' => $light,
			'dark' => $dark,
			'light-highcontrast' => $lightHc,
			'dark-highcontrast' => $darkHc,
			'opendyslexic' => $dyslexic,
		];
	}

	/**
	 * @return array{links: list<array{themeId: string, media: ?string, plain: bool}>, metas: list<array{name: string, content: string}>}
	 */
	private function collectHeaders(): array {
		$links = [];
		$metas = [];
		foreach (\OC_Util::$headers as $header) {
			$attrs = $header['attributes'];
			if ($header['tag'] === 'link' && ($attrs['class'] ?? '') === 'theme') {
				preg_match('#/css/([a-z-]+)\?plain=([01])#', $attrs['href'], $m);
				$links[] = [
					'themeId' => $m[1],
					'media' => $attrs['media'] ?? null,
					'plain' => $m[2] === '1',
				];
			} elseif ($header['tag'] === 'meta' && ($attrs['name'] ?? '') === 'color-scheme') {
				$metas[] = ['name' => $attrs['name'], 'content' => $attrs['content']];
			}
		}
		return ['links' => $links, 'metas' => $metas];
	}

	public function testInjectHeadersWithoutOverrideUsesMediaQueries(): void {
		$themes = $this->buildThemes();
		$this->themesService->method('getThemes')->willReturn($themes);
		$this->config->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn('');
		$this->themesService->method('getRequestThemeOverride')->willReturn(null);

		$this->service->injectHeaders();
		$collected = $this->collectHeaders();

		// Media-query based stylesheets must be present for auto-switching
		$mediaLinks = array_filter($collected['links'], fn ($l) => $l['media'] === '(prefers-color-scheme: dark)' && $l['plain']);
		$this->assertCount(1, $mediaLinks, 'Dark prefers-color-scheme stylesheet should be injected when no override');

		// Color scheme meta should contain both light and dark
		$this->assertCount(1, $collected['metas']);
		$this->assertEqualsCanonicalizing(['light', 'dark'], explode(' ', $collected['metas'][0]['content']));
	}

	public function testInjectHeadersWithLightOverrideForcesRootWithoutMedia(): void {
		$themes = $this->buildThemes();
		$this->themesService->method('getThemes')->willReturn($themes);
		$this->config->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn('');
		$this->themesService->method('getRequestThemeOverride')->willReturn('light');

		$this->service->injectHeaders();
		$collected = $this->collectHeaders();

		// No prefers-color-scheme media stylesheet must be injected
		foreach ($collected['links'] as $link) {
			$this->assertStringNotContainsString('prefers-color-scheme', (string)$link['media']);
		}

		// The light theme must be forced on :root (plain, no media)
		$forcedLight = array_filter(
			$collected['links'],
			fn ($l) => $l['themeId'] === 'light' && $l['plain'] && ($l['media'] === null || $l['media'] === ''),
		);
		$this->assertCount(1, $forcedLight, 'Light theme must be forced on :root without a media query');

		// Only the light color-scheme meta must be exposed
		$this->assertCount(1, $collected['metas']);
		$this->assertSame('light', $collected['metas'][0]['content']);
	}

	public function testInjectHeadersWithDarkOverrideForcesRootWithoutMedia(): void {
		$themes = $this->buildThemes();
		$this->themesService->method('getThemes')->willReturn($themes);
		$this->config->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn('');
		$this->themesService->method('getRequestThemeOverride')->willReturn('dark');

		$this->service->injectHeaders();
		$collected = $this->collectHeaders();

		$forcedDark = array_filter(
			$collected['links'],
			fn ($l) => $l['themeId'] === 'dark' && $l['plain'] && ($l['media'] === null || $l['media'] === ''),
		);
		$this->assertCount(1, $forcedDark, 'Dark theme must be forced on :root without a media query');

		$this->assertCount(1, $collected['metas']);
		$this->assertSame('dark', $collected['metas'][0]['content']);
	}

	public function testInjectHeadersDoesNotApplyOverrideWhenThemeEnforced(): void {
		$themes = $this->buildThemes();
		$this->themesService->method('getThemes')->willReturn($themes);
		$this->config->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn('light');
		// Override must not even be queried when a theme is enforced
		$this->themesService->expects($this->never())
			->method('getRequestThemeOverride');

		$this->service->injectHeaders();
		$collected = $this->collectHeaders();

		// Media-query stylesheets remain (regular injection path)
		$mediaLinks = array_filter($collected['links'], fn ($l) => $l['media'] === '(prefers-color-scheme: dark)' && $l['plain']);
		$this->assertCount(1, $mediaLinks);
	}
}
