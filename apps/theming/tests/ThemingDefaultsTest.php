<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Theming\Tests;

use OCA\Theming\ThemingDefaults;
use OCP\Files\IAppData;
use OCA\Theming\Util;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use Test\TestCase;

class ThemingDefaultsTest extends TestCase {
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var \OC_Defaults|\PHPUnit_Framework_MockObject_MockObject */
	private $defaults;
	/** @var IAppData|\PHPUnit_Framework_MockObject_MockObject */
	private $appData;
	/** @var ICacheFactory|\PHPUnit_Framework_MockObject_MockObject */
	private $cacheFactory;
	/** @var ThemingDefaults */
	private $template;
	/** @var Util|\PHPUnit_Framework_MockObject_MockObject */
	private $util;
	/** @var ICache|\PHPUnit_Framework_MockObject_MockObject */
	private $cache;

	public function setUp() {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->appData = $this->createMock(IAppData::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = $this->createMock(ICache::class);
		$this->util = $this->createMock(Util::class);
		$this->defaults = $this->getMockBuilder(\OC_Defaults::class)
			->disableOriginalConstructor()
			->getMock();
		$this->defaults
			->expects($this->at(0))
			->method('getName')
			->willReturn('Nextcloud');
		$this->defaults
			->expects($this->at(1))
			->method('getBaseUrl')
			->willReturn('https://nextcloud.com/');
		$this->defaults
			->expects($this->at(2))
			->method('getSlogan')
			->willReturn('Safe Data');
		$this->defaults
			->expects($this->at(3))
			->method('getColorPrimary')
			->willReturn('#000');
		$this->cacheFactory
			->expects($this->any())
			->method('create')
			->with('theming')
			->willReturn($this->cache);
		$this->template = new ThemingDefaults(
			$this->config,
			$this->l10n,
			$this->urlGenerator,
			$this->defaults,
			$this->appData,
			$this->cacheFactory,
			$this->util
		);
	}

	public function testGetNameWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getName());
	}

	public function testGetNameWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getName());
	}

	public function testGetHTMLNameWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getHTMLName());
	}

	public function testGetHTMLNameWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getHTMLName());
	}

	public function testGetTitleWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getTitle());
	}

	public function testGetTitleWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getTitle());
	}


	public function testGetEntityWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getEntity());
	}

	public function testGetEntityWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getEntity());
	}

	public function testGetBaseUrlWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'url', 'https://nextcloud.com/')
			->willReturn('https://nextcloud.com/');

		$this->assertEquals('https://nextcloud.com/', $this->template->getBaseUrl());
	}

	public function testGetBaseUrlWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'url', 'https://nextcloud.com/')
			->willReturn('https://example.com/');

		$this->assertEquals('https://example.com/', $this->template->getBaseUrl());
	}

	public function testGetSloganWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'slogan', 'Safe Data')
			->willReturn('Safe Data');

		$this->assertEquals('Safe Data', $this->template->getSlogan());
	}

	public function testGetSloganWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'slogan', 'Safe Data')
			->willReturn('My custom Slogan');

		$this->assertEquals('My custom Slogan', $this->template->getSlogan());
	}

	public function testGetShortFooter() {
		$this->config
			->expects($this->exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'url', 'https://nextcloud.com/', 'url'],
				['theming', 'name', 'Nextcloud', 'Name'],
				['theming', 'slogan', 'Safe Data', 'Slogan'],
			]);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer">Name</a> – Slogan', $this->template->getShortFooter());
	}

	public function testGetShortFooterEmptySlogan() {
		$this->config
			->expects($this->exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'url', 'https://nextcloud.com/', 'url'],
				['theming', 'name', 'Nextcloud', 'Name'],
				['theming', 'slogan', 'Safe Data', ''],
			]);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer">Name</a>', $this->template->getShortFooter());
	}

	public function testgetColorPrimaryWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'color', '#000')
			->willReturn('#000');

		$this->assertEquals('#000', $this->template->getColorPrimary());
	}

	public function testgetColorPrimaryWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'color', '#000')
			->willReturn('#fff');

		$this->assertEquals('#fff', $this->template->getColorPrimary());
	}

	public function testSet() {
		$this->config
			->expects($this->at(0))
			->method('setAppValue')
			->with('theming', 'MySetting', 'MyValue');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('15');
		$this->config
			->expects($this->at(2))
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);
		$this->cache
			->expects($this->once())
			->method('clear')
			->with('getScssVariables');
		$this->template->set('MySetting', 'MyValue');
	}

	public function testUndoName() {
		$this->config
			->expects($this->at(0))
			->method('deleteAppValue')
			->with('theming', 'name');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('15');
		$this->config
			->expects($this->at(2))
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertSame('Nextcloud', $this->template->undo('name'));
	}

	public function testUndoBaseUrl() {
		$this->config
			->expects($this->at(0))
			->method('deleteAppValue')
			->with('theming', 'url');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('15');
		$this->config
			->expects($this->at(2))
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('theming', 'url', 'https://nextcloud.com/')
			->willReturn('https://nextcloud.com/');

		$this->assertSame('https://nextcloud.com/', $this->template->undo('url'));
	}

	public function testUndoSlogan() {
		$this->config
			->expects($this->at(0))
			->method('deleteAppValue')
			->with('theming', 'slogan');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('15');
		$this->config
			->expects($this->at(2))
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('theming', 'slogan', 'Safe Data')
			->willReturn('Safe Data');

		$this->assertSame('Safe Data', $this->template->undo('slogan'));
	}

	public function testUndoColor() {
		$this->config
			->expects($this->at(0))
			->method('deleteAppValue')
			->with('theming', 'color');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('15');
		$this->config
			->expects($this->at(2))
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('theming', 'color', '#000')
			->willReturn('#000');

		$this->assertSame('#000', $this->template->undo('color'));
	}

	public function testUndoDefaultAction() {
		$this->config
			->expects($this->at(0))
			->method('deleteAppValue')
			->with('theming', 'defaultitem');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('15');
		$this->config
			->expects($this->at(2))
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);

		$this->assertSame('', $this->template->undo('defaultitem'));
	}

	public function testGetBackgroundDefault() {
		$this->appData->expects($this->once())
			->method('getFolder')
			->willThrowException(new NotFoundException());
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'backgroundMime')
			->willReturn('');
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('images')
			->willThrowException(new \Exception());
		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('core', 'background.jpg')
			->willReturn('core-background');
		$this->assertEquals('core-background', $this->template->getBackground());
	}

	public function testGetBackgroundCustom() {
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$folder->expects($this->once())
			->method('getFile')
			->willReturn($file);
		$this->appData->expects($this->once())
			->method('getFolder')
			->willReturn($folder);
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'backgroundMime', false)
			->willReturn('image/svg+xml');
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('theming.Theming.getLoginBackground')
			->willReturn('custom-background');
		$this->assertEquals('custom-background', $this->template->getBackground());
	}

	public function testGetLogoDefault() {
		$this->appData->expects($this->once())
			->method('getFolder')
			->willThrowException(new NotFoundException());
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('theming', 'logoMime')
			->willReturn('');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('images')
			->willThrowException(new \Exception());
		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('core', 'logo.svg')
			->willReturn('core-logo');
		$this->assertEquals('core-logo' . '?v=0', $this->template->getLogo());
	}

	public function testGetLogoCustom() {
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$folder->expects($this->once())
			->method('getFile')
			->willReturn($file);
		$this->appData->expects($this->once())
			->method('getFolder')
			->willReturn($folder);
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('theming', 'logoMime', false)
			->willReturn('image/svg+xml');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('theming.Theming.getLogo')
			->willReturn('custom-logo');
		$this->assertEquals('custom-logo' . '?v=0', $this->template->getLogo());
	}

	public function testGetScssVariablesCached() {
		$this->cache->expects($this->once())->method('get')->with('getScssVariables')->willReturn(['foo'=>'bar']);
		$this->assertEquals(['foo'=>'bar'], $this->template->getScssVariables());
	}

	public function testGetScssVariables() {
		$this->config->expects($this->at(0))->method('getAppValue')->with('theming', 'cachebuster', '0')->willReturn('0');
		$this->config->expects($this->at(1))->method('getAppValue')->with('theming', 'logoMime', false)->willReturn('jpeg');
		$this->config->expects($this->at(2))->method('getAppValue')->with('theming', 'cachebuster', '0')->willReturn('0');
		$this->config->expects($this->at(3))->method('getAppValue')->with('theming', 'backgroundMime', false)->willReturn('jpeg');
		$this->config->expects($this->at(4))->method('getAppValue')->with('theming', 'color', null)->willReturn('#000000');
		$this->config->expects($this->at(5))->method('getAppValue')->with('theming', 'color', '#000')->willReturn('#000000');
		$this->config->expects($this->at(6))->method('getAppValue')->with('theming', 'color', '#000')->willReturn('#000000');

		$this->util->expects($this->any())->method('invertTextColor')->with('#000000')->willReturn(false);
		$this->cache->expects($this->once())->method('get')->with('getScssVariables')->willReturn(null);
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$folder->expects($this->any())->method('getFile')->willReturn($file);
		$this->appData->expects($this->any())
			->method('getFolder')
			->willReturn($folder);

		$this->urlGenerator->expects($this->exactly(2))
			->method('linkToRoute')
			->willReturnMap([
				['theming.Theming.getLogo', [], 'custom-logo'],
				['theming.Theming.getLoginBackground', [], 'custom-background'],
			]);

		$this->urlGenerator->expects($this->exactly(2))
			->method('getAbsoluteURL')
			->willReturnCallback(function ($path) {
				return 'absolute-' . $path;
			});

		$expected = [
			'theming-cachebuster' => '\'0\'',
			'image-logo' => "'absolute-custom-logo?v=0'",
			'image-login-background' => "'absolute-custom-background'",
			'color-primary' => '#000000',
			'color-primary-text' => '#ffffff'

		];
		$this->assertEquals($expected, $this->template->getScssVariables());
	}
}
