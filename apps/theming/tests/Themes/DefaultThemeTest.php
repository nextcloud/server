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
namespace OCA\Theming\Tests\Service;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\ImageManager;
use OCA\Theming\ITheme;
use OCA\Theming\Service\BackgroundService;
use OCA\Theming\Themes\DefaultTheme;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\App\IAppManager;
use OCP\Files\IAppData;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class DefaultThemeTest extends TestCase {
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

	private DefaultTheme $defaultTheme;

	protected function setUp(): void {
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->appManager = $this->createMock(IAppManager::class);

		$util = new Util(
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

		$this->defaultTheme = new DefaultTheme(
			$util,
			$this->themingDefaults,
			$this->userSession,
			$this->urlGenerator,
			$this->imageManager,
			$this->config,
			$this->l10n,
			$this->appManager,
		);

		parent::setUp();
	}


	public function testGetId() {
		$this->assertEquals('default', $this->defaultTheme->getId());
	}

	public function testGetType() {
		$this->assertEquals(ITheme::TYPE_THEME, $this->defaultTheme->getType());
	}

	public function testGetTitle() {
		$this->assertEquals('System default theme', $this->defaultTheme->getTitle());
	}

	public function testGetEnableLabel() {
		$this->assertEquals('Enable the system default', $this->defaultTheme->getEnableLabel());
	}

	public function testGetDescription() {
		$this->assertEquals('Using the default system appearance.', $this->defaultTheme->getDescription());
	}

	public function testGetMediaQuery() {
		$this->assertEquals('', $this->defaultTheme->getMediaQuery());
	}

	public function testGetCustomCss() {
		$this->assertEquals('', $this->defaultTheme->getCustomCss());
	}

	/**
	 * Ensure parity between the default theme and the static generated file
	 * @see ThemingController.php:313
	 */
	public function testThemindDisabledFallbackCss() {
		// Generate variables
		$variables = '';
		foreach ($this->defaultTheme->getCSSVariables() as $variable => $value) {
			$variables .= "  $variable: $value;" . PHP_EOL;
		};

		$css = ":root {" . PHP_EOL . "$variables}" . PHP_EOL;
		$fallbackCss = file_get_contents(__DIR__ . '/../../css/default.css');
		// Remove comments
		$fallbackCss = preg_replace('/\s*\/\*[\s\S]*?\*\//m', '', $fallbackCss);

		$this->assertEquals($css, $fallbackCss);
	}
}
