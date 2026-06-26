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
		];
		$this->assertEquals($expected, array_keys($this->themesService->getThemes()));
	}

	public function testGetThemesEnforced(): void {
		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn('dark');
		$this->logger->expects($this->never())
			->method('error');

		$expected = [
			'default',
			'dark',
		];

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
		];
	}

	/**
	 *
	 * @param string[] $enabledThemes
	 * @param string[] $expectedEnabled
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestEnableTheme')]
	public function testEnableTheme(string $toEnable, array $enabledThemes, array $expectedEnabled): void {
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

		$this->assertEquals($expectedEnabled, $this->themesService->enableTheme($this->themes[$toEnable]));
	}


	public static function dataTestDisableTheme(): array {
		return [
			['dark', ['default'], ['default']],
			['dark', ['dark'], []],
			['opendyslexic', ['dark', 'opendyslexic'], ['dark'], ],
			['light-highcontrast', ['opendyslexic'], ['opendyslexic']],
		];
	}

	/**
	 *
	 * @param string[] $enabledThemes
	 * @param string[] $expectedEnabled
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestDisableTheme')]
	public function testDisableTheme(string $toDisable, array $enabledThemes, array $expectedEnabled): void {
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

	public function testGetEnabledThemes(): void {
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
			->willReturn(json_encode(['default']));
		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn('');

		$this->assertEquals(['default'], $this->themesService->getEnabledThemes());
	}

	public function testGetEnabledThemesEnforced(): void {
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
			->willReturn(json_encode([]));
		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn('light');

		$this->assertEquals(['light'], $this->themesService->getEnabledThemes());
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
		];
	}
}
