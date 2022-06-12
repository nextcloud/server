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
use OC\Route\Router;
use OCA\Theming\ImageManager;
use OCA\Theming\ITheme;
use OCA\Theming\Themes\DyslexiaFont;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\Files\IAppData;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;


class DyslexiaFontTest extends TestCase {
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

	private DyslexiaFont $dyslexiaFont;

	protected function setUp(): void {
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);

		$util = new Util(
			$this->config,
			$this->createMock(AppManager::class),
			$this->createMock(IAppData::class)
		);

		$userSession = $this->createMock(IUserSession::class);
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$request = $this->createMock(IRequest::class);
		$router = $this->createMock(Router::class);
		$this->urlGenerator = new \OC\URLGenerator(
			$this->config,
			$userSession,
			$cacheFactory,
			$request,
			$router
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

		$this->dyslexiaFont = new DyslexiaFont(
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
		$this->assertEquals('opendyslexic', $this->dyslexiaFont->getId());
	}

	public function testGetType() {
		$this->assertEquals(ITheme::TYPE_FONT, $this->dyslexiaFont->getType());
	}

	public function testGetTitle() {
		$this->assertNotEmpty($this->dyslexiaFont->getTitle());
	}

	public function testGetEnableLabel() {
		$this->assertNotEmpty($this->dyslexiaFont->getEnableLabel());
	}

	public function testGetDescription() {
		$this->assertNotEmpty($this->dyslexiaFont->getDescription());
	}

	public function testGetMediaQuery() {
		$this->assertEquals('', $this->dyslexiaFont->getMediaQuery());
	}

	public function testGetCSSVariables() {
		$this->assertStringStartsWith('OpenDyslexic', $this->dyslexiaFont->getCSSVariables()['--font-face']);
	}

	public function dataTestGetCustomCss() {
		return [
			['', true],
			['', false],
			['/subfolder', true],
			['/subfolder', false],
		];
	}

	/**
	 * @dataProvider dataTestGetCustomCss
	 * 
	 * Ensure the fonts are always loaded from the web root
	 * despite having url rewriting enabled or not
	 *
	 * @param string $webRoot
	 * @param bool $prettyUrlsEnabled
	 */
	public function testGetCustomCss($webRoot, $prettyUrlsEnabled) {
		\OC::$WEBROOT = $webRoot;
		$this->config->expects($this->any())
			->method('getSystemValue')
			->with('htaccess.IgnoreFrontController', false)
			->willReturn($prettyUrlsEnabled);
		
		$this->assertStringContainsString("'$webRoot/apps/theming/fonts/OpenDyslexic-Regular.woff'", $this->dyslexiaFont->getCustomCss());
		$this->assertStringNotContainsString('index.php', $this->dyslexiaFont->getCustomCss());
	}
}
