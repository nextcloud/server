<?php
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Tests\Themes;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\ImageManager;
use OCA\Theming\ITheme;
use OCA\Theming\Service\BackgroundService;
use OCA\Theming\Themes\DarkTheme;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\App\IAppManager;
use OCP\Files\IAppData;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\ServerVersion;
use PHPUnit\Framework\MockObject\MockObject;

class DarkThemeTest extends AccessibleThemeTestCase {
	/** @var ThemingDefaults|MockObject */
	private $themingDefaults;
	/** @var IUserSession|MockObject */
	private $userSession;
	/** @var IURLGenerator|MockObject */
	private $urlGenerator;
	/** @var ImageManager|MockObject */
	private $imageManager;
	/** @var IConfig|MockObject */
	private $config;
	/** @var IL10N|MockObject */
	private $l10n;
	/** @var IAppManager|MockObject */
	private $appManager;

	protected function setUp(): void {
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->appManager = $this->createMock(IAppManager::class);

		$this->util = new Util(
			$this->createMock(ServerVersion::class),
			$this->config,
			$this->appManager,
			$this->createMock(IAppData::class),
			$this->imageManager
		);

		$this->themingDefaults
			->expects($this->any())
			->method('getColorPrimary')
			->willReturn('#0082c9');

		$this->themingDefaults
			->expects($this->any())
			->method('getDefaultColorPrimary')
			->willReturn('#0082c9');
		$this->themingDefaults
			->expects($this->any())
			->method('getColorBackground')
			->willReturn('#0082c9');
		$this->themingDefaults
			->expects($this->any())
			->method('getDefaultColorBackground')
			->willReturn('#0082c9');

		$this->themingDefaults
			->expects($this->any())
			->method('getBackground')
			->willReturn('/apps/' . Application::APP_ID . '/img/background/' . BackgroundService::DEFAULT_BACKGROUND_IMAGE);

		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});

		$this->urlGenerator
			->expects($this->any())
			->method('imagePath')
			->willReturnCallback(function ($app = 'core', $filename = '') {
				return "/$app/img/$filename";
			});

		$this->theme = new DarkTheme(
			$this->util,
			$this->themingDefaults,
			$this->userSession,
			$this->urlGenerator,
			$this->imageManager,
			$this->config,
			$this->l10n,
			$this->appManager,
			null,
		);

		parent::setUp();
	}


	public function testGetId(): void {
		$this->assertEquals('dark', $this->theme->getId());
	}

	public function testGetType(): void {
		$this->assertEquals(ITheme::TYPE_THEME, $this->theme->getType());
	}

	public function testGetTitle(): void {
		$this->assertEquals('Dark theme', $this->theme->getTitle());
	}

	public function testGetEnableLabel(): void {
		$this->assertEquals('Enable dark theme', $this->theme->getEnableLabel());
	}

	public function testGetDescription(): void {
		$this->assertEquals('A dark theme to ease your eyes by reducing the overall luminosity and brightness.', $this->theme->getDescription());
	}

	public function testGetMediaQuery(): void {
		$this->assertEquals('(prefers-color-scheme: dark)', $this->theme->getMediaQuery());
	}

	public function testGetCustomCss(): void {
		$this->assertEquals('', $this->theme->getCustomCss());
	}
}
