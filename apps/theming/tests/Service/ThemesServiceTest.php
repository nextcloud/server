<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Tests\Service;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\ImageManager;
use OCA\Theming\ITheme;
use OCA\Theming\Service\ThemesService;
use OCA\Theming\Themes\DarkHighContrastTheme;
use OCA\Theming\Themes\DarkTheme;
use OCA\Theming\Themes\DefaultTheme;
use OCA\Theming\Themes\DyslexiaFont;
use OCA\Theming\Themes\HighContrastTheme;
use OCA\Theming\Themes\LightTheme;
use OCA\Theming\Themes\ReducedMotion;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ThemesServiceTest extends TestCase {
	private IUserSession&MockObject $userSession;
	private IConfig&MockObject $config;
	private LoggerInterface&MockObject $logger;

	private ThemingDefaults&MockObject $themingDefaults;
	private ThemesService $themesService;

	/** @var ITheme[] */
	private array $themes;

	protected function setUp(): void {
		$this->userSession = $this->createMock(IUserSession::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);

		$this->themingDefaults->expects($this->any())
			->method('getColorPrimary')
			->willReturn('#0082c9');

		$this->themingDefaults->expects($this->any())
			->method('getDefaultColorPrimary')
			->willReturn('#0082c9');

		$this->initThemes();

		$this->themesService = new ThemesService(
			$this->userSession,
			$this->config,
			$this->logger,
			...array_values($this->themes)
		);

		parent::setUp();
	}

	public function testGetThemes(): void {
		$expected = [
			'default',
			'light',
			'dark',
			'light-highcontrast',
			'dark-highcontrast',
			'opendyslexic',
			'reduced-motion',
		];
		$this->assertEquals($expected, array_keys($this->themesService->getThemes()));
	}

	public static function dataTestGetThemesEnforced(): array {
		return [
			['dark', ['default', 'dark', 'opendyslexic', 'reduced-motion']],
			['light', ['default', 'dark', 'light', 'opendyslexic', 'reduced-motion']],
			['light-highcontrast', ['default', 'dark', 'light-highcontrast', 'opendyslexic', 'reduced-motion']],
			['opendyslexic', ['default', 'dark', 'opendyslexic', 'reduced-motion']],
		];
	}

	/**
	 * @param string[] $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestGetThemesEnforced')]
	public function testGetThemesEnforced(string $enforcedTheme, array $expected): void {
		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn($enforcedTheme);
		$this->logger->expects($this->never())
			->method('error');

		$this->assertEquals($expected, array_keys($this->themesService->getThemes()));
	}

	public function testGetThemesEnforcedInvalid(): void {
		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn('invalid');
		$this->logger->expects($this->once())
			->method('error')
			->with('Enforced theme not found', ['theme' => 'invalid']);

		$expected = [
			'default',
			'light',
			'dark',
			'light-highcontrast',
			'dark-highcontrast',
			'opendyslexic',
			'reduced-motion',
		];

		$this->assertEquals($expected, array_keys($this->themesService->getThemes()));
	}

	public static function dataTestEnableTheme(): array {
		return [
			['default', ['default'], ['default']],
			['dark', ['default'], ['dark']],
			['dark', ['dark'], ['dark']],
			['opendyslexic', ['dark'], ['dark', 'opendyslexic']],
			['dark', ['light-highcontrast', 'opendyslexic'], ['opendyslexic', 'dark']],
			['reduced-motion', ['default'], ['default', 'reduced-motion'], 'light'],
			['opendyslexic', ['default'], ['default', 'opendyslexic'], 'light'],
			['opendyslexic', ['dark-highcontrast'], ['dark-highcontrast', 'opendyslexic'], 'light'],
		];
	}

	/**
	 *
	 * @param string[] $enabledThemes
	 * @param string[] $expectedEnabled
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestEnableTheme')]
	public function testEnableTheme(string $toEnable, array $enabledThemes, array $expectedEnabled, string $enforcedTheme = ''): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->config->expects($this->any())
			->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn($enforcedTheme);
		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user', Application::APP_ID, 'enabled-themes', '["default"]')
			->willReturn(json_encode($enabledThemes));
		$this->config->expects($expectedEnabled === $enabledThemes ? $this->never() : $this->once())
			->method('setUserValue')
			->with('user', Application::APP_ID, 'enabled-themes', json_encode($expectedEnabled));

		$this->assertEquals($expectedEnabled, $this->themesService->enableTheme($this->themes[$toEnable]));
	}

	public static function dataTestDisableTheme(): array {
		return [
			['dark', ['default'], ['default']],
			['dark', ['dark'], []],
			['opendyslexic', ['dark', 'opendyslexic'], ['dark'], ],
			['light-highcontrast', ['opendyslexic'], ['opendyslexic']],
			['reduced-motion', ['default', 'reduced-motion'], ['default'], 'light'],
		];
	}

	/**
	 *
	 * @param string[] $enabledThemes
	 * @param string[] $expectedEnabled
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestDisableTheme')]
	public function testDisableTheme(string $toDisable, array $enabledThemes, array $expectedEnabled, string $enforcedTheme = ''): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->config->expects($this->any())
			->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn($enforcedTheme);
		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user', Application::APP_ID, 'enabled-themes', '["default"]')
			->willReturn(json_encode($enabledThemes));
		$this->config->expects($expectedEnabled === $enabledThemes ? $this->never() : $this->once())
			->method('setUserValue')
			->with('user', Application::APP_ID, 'enabled-themes', json_encode($expectedEnabled));

		$this->assertEquals($expectedEnabled, $this->themesService->disableTheme($this->themes[$toDisable]));
	}

	public static function dataTestIsEnabled(): array {
		return [
			['dark', [], false],
			['dark', ['dark'], true],
			['opendyslexic', ['dark', 'opendyslexic'], true],
			['light-highcontrast', ['opendyslexic'], false],
		];
	}

	/**
	 * @param string[] $enabledThemes
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestIsEnabled')]
	public function testIsEnabled(string $themeId, array $enabledThemes, bool $expected): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user', Application::APP_ID, 'enabled-themes', '["default"]')
			->willReturn(json_encode($enabledThemes));

		$this->assertEquals($expected, $this->themesService->isEnabled($this->themes[$themeId]));
	}

	public static function dataTestGetEnabledThemes(): array {
		return [
			['["default"]', '', ['default']],
			['["dark","opendyslexic"]', '', ['dark', 'opendyslexic']],
			['[]', 'light', ['light']],
			['["default","reduced-motion"]', 'light', ['light', 'default', 'reduced-motion']],
			['', '', []],
			['null', '', []],
			['{"theme":"dark"}', '', []],
			['', 'light', ['light']],
			['["light","opendyslexic"]', 'light', ['light', 'opendyslexic']],
		];
	}

	/**
	 * @param string[] $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestGetEnabledThemes')]
	public function testGetEnabledThemes(string $storedThemes, string $enforcedTheme, array $expected): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user', Application::APP_ID, 'enabled-themes', '["default"]')
			->willReturn($storedThemes);
		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn($enforcedTheme);

		$this->assertEquals($expected, $this->themesService->getEnabledThemes());
	}

	public static function dataTestGetEnabledThemesLoggedOut(): array {
		return [
			['', []],
			['light', ['light']],
		];
	}

	/**
	 * @param string[] $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestGetEnabledThemesLoggedOut')]
	public function testGetEnabledThemesLoggedOut(string $enforcedTheme, array $expected): void {
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn(null);

		$this->config->expects($this->never())
			->method('getUserValue');
		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn($enforcedTheme);

		$this->assertEquals($expected, $this->themesService->getEnabledThemes());
	}

	public static function dataTestSetEnabledThemes(): array {
		return [
			[[], []],
			[['light'], ['light']],
			[['dark'], ['dark']],
			[['dark', 'dark', 'opendyslexic'], ['dark', 'opendyslexic']],
		];
	}

	/**
	 *
	 * @param string[] $enabledThemes
	 * @param string[] $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestSetEnabledThemes')]
	public function testSetEnabledThemes(array $enabledThemes, array $expected): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->config->expects($this->once())
			->method('setUserValue')
			->with('user', Application::APP_ID, 'enabled-themes', json_encode($expected));

		$this->invokePrivate($this->themesService, 'setEnabledThemes', [$enabledThemes]);
	}

	private function initThemes() {
		$util = $this->createMock(Util::class);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$imageManager = $this->createMock(ImageManager::class);
		$l10n = $this->createMock(IL10N::class);
		$appManager = $this->createMock(IAppManager::class);

		$this->themes = [
			'default' => new DefaultTheme(
				$util,
				$this->themingDefaults,
				$this->userSession,
				$urlGenerator,
				$imageManager,
				$this->config,
				$l10n,
				$appManager,
				null,
			),
			'light' => new LightTheme(
				$util,
				$this->themingDefaults,
				$this->userSession,
				$urlGenerator,
				$imageManager,
				$this->config,
				$l10n,
				$appManager,
				null,
			),
			'dark' => new DarkTheme(
				$util,
				$this->themingDefaults,
				$this->userSession,
				$urlGenerator,
				$imageManager,
				$this->config,
				$l10n,
				$appManager,
				null,
			),
			'light-highcontrast' => new HighContrastTheme(
				$util,
				$this->themingDefaults,
				$this->userSession,
				$urlGenerator,
				$imageManager,
				$this->config,
				$l10n,
				$appManager,
				null,
			),
			'dark-highcontrast' => new DarkHighContrastTheme(
				$util,
				$this->themingDefaults,
				$this->userSession,
				$urlGenerator,
				$imageManager,
				$this->config,
				$l10n,
				$appManager,
				null,
			),
			'opendyslexic' => new DyslexiaFont(
				$util,
				$this->themingDefaults,
				$this->userSession,
				$urlGenerator,
				$imageManager,
				$this->config,
				$l10n,
				$appManager,
				null,
			),
			'reduced-motion' => new ReducedMotion($l10n),
		];
	}
}
