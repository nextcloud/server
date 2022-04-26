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

use OC\App\AppManager;
use OCA\Theming\ImageManager;
use OCA\Theming\ITheme;
use OCA\Theming\Themes\DefaultTheme;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\Files\IAppData;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;


class DefaultThemeTest extends TestCase {
	/** @var ThemingDefaults|MockObject */
	private $themingDefaults;
	/** @var IURLGenerator|MockObject */
	private $urlGenerator;
	/** @var ImageManager|MockObject */
	private $imageManager;
	/** @var IConfig|MockObject */
	private $config;
	/** @var IL10N|MockObject */
	private $l10n;

	private DefaultTheme $defaultTheme;

	protected function setUp(): void {
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);

		$util = new Util(
			$this->config,
			$this->createMock(AppManager::class),
			$this->createMock(IAppData::class)
		);

		$this->themingDefaults
			->expects($this->any())
			->method('getColorPrimary')
			->willReturn('#0082c9');

		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});

		$this->defaultTheme = new DefaultTheme(
			$util,
			$this->themingDefaults,
			$this->urlGenerator,
			$this->imageManager,
			$this->config,
			$this->l10n,
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
		$this->assertEquals('Light theme', $this->defaultTheme->getTitle());
	}

	public function testGetEnableLabel() {
		$this->assertEquals('Enable the default light theme', $this->defaultTheme->getEnableLabel());
	}

	public function testGetDescription() {
		$this->assertEquals('The default light appearance.', $this->defaultTheme->getDescription());
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

		$this->assertEquals($css, $fallbackCss);
	}
}
