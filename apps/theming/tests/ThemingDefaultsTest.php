<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
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
use OCP\App\IAppManager;
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
	/** @var IAppManager|\PHPUnit_Framework_MockObject_MockObject */
	private $appManager;

	public function setUp() {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->appData = $this->createMock(IAppData::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = $this->createMock(ICache::class);
		$this->util = $this->createMock(Util::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->defaults = new \OC_Defaults();
		$this->cacheFactory
			->expects($this->any())
			->method('create')
			->with('theming')
			->willReturn($this->cache);
		$this->template = new ThemingDefaults(
			$this->config,
			$this->l10n,
			$this->urlGenerator,
			$this->appData,
			$this->cacheFactory,
			$this->util,
			$this->appManager
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
			->with('theming', 'url', $this->defaults->getBaseUrl())
			->willReturn($this->defaults->getBaseUrl());

		$this->assertEquals($this->defaults->getBaseUrl(), $this->template->getBaseUrl());
	}

	public function testGetBaseUrlWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'url', $this->defaults->getBaseUrl())
			->willReturn('https://example.com/');

		$this->assertEquals('https://example.com/', $this->template->getBaseUrl());
	}

	public function testGetSloganWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'slogan', $this->defaults->getSlogan())
			->willReturn($this->defaults->getSlogan());

		$this->assertEquals($this->defaults->getSlogan(), $this->template->getSlogan());
	}

	public function testGetSloganWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'slogan', $this->defaults->getSlogan())
			->willReturn('My custom Slogan');

		$this->assertEquals('My custom Slogan', $this->template->getSlogan());
	}

	public function testGetShortFooter() {
		$this->config
			->expects($this->exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'url', $this->defaults->getBaseUrl(), 'url'],
				['theming', 'name', 'Nextcloud', 'Name'],
				['theming', 'slogan', $this->defaults->getSlogan(), 'Slogan'],
			]);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener">Name</a> – Slogan', $this->template->getShortFooter());
	}

	public function testGetShortFooterEmptySlogan() {
		$this->config
			->expects($this->exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'url', $this->defaults->getBaseUrl(), 'url'],
				['theming', 'name', 'Nextcloud', 'Name'],
				['theming', 'slogan', $this->defaults->getSlogan(), ''],
			]);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener">Name</a>', $this->template->getShortFooter());
	}

	public function testgetColorPrimaryWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'color', $this->defaults->getColorPrimary())
			->willReturn($this->defaults->getColorPrimary());

		$this->assertEquals($this->defaults->getColorPrimary(), $this->template->getColorPrimary());
	}

	public function testgetColorPrimaryWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'color', $this->defaults->getColorPrimary())
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
			->with('theming', 'url', $this->defaults->getBaseUrl())
			->willReturn($this->defaults->getBaseUrl());

		$this->assertSame($this->defaults->getBaseUrl(), $this->template->undo('url'));
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
			->with('theming', 'slogan', $this->defaults->getSlogan())
			->willReturn($this->defaults->getSlogan());

		$this->assertSame($this->defaults->getSlogan(), $this->template->undo('slogan'));
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
			->with('theming', 'color', $this->defaults->getColorPrimary())
			->willReturn($this->defaults->getColorPrimary());

		$this->assertSame($this->defaults->getColorPrimary(), $this->template->undo('color'));
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
			->expects($this->at(0))
			->method('getAppValue')
			->with('theming', 'backgroundMime')
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
			->with('core', 'background.png')
			->willReturn('core-background');
		$this->assertEquals('core-background?v=0', $this->template->getBackground());
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
			->expects($this->at(0))
			->method('getAppValue')
			->with('theming', 'backgroundMime', false)
			->willReturn('image/svg+xml');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('theming.Theming.getLoginBackground')
			->willReturn('custom-background');
		$this->assertEquals('custom-background?v=0', $this->template->getBackground());
	}

	private function getLogoHelper($withName, $useSvg) {
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
			->with('core', $withName)
			->willReturn('core-logo');
		$this->assertEquals('core-logo?v=0', $this->template->getLogo($useSvg));
	}

	public function testGetLogoDefaultWithSvg() {
		$this->getLogoHelper('logo.svg', true);
	}

	public function testGetLogoDefaultWithoutSvg() {
		$this->getLogoHelper('logo.png', false);
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
		$this->config->expects($this->at(2))->method('getAppValue')->with('theming', 'backgroundMime', false)->willReturn('jpeg');
		$this->config->expects($this->at(3))->method('getAppValue')->with('theming', 'logoMime', false)->willReturn('jpeg');
		$this->config->expects($this->at(4))->method('getAppValue')->with('theming', 'cachebuster', '0')->willReturn('0');
		$this->config->expects($this->at(5))->method('getAppValue')->with('theming', 'backgroundMime', false)->willReturn('jpeg');
		$this->config->expects($this->at(6))->method('getAppValue')->with('theming', 'cachebuster', '0')->willReturn('0');
		$this->config->expects($this->at(7))->method('getAppValue')->with('theming', 'color', null)->willReturn($this->defaults->getColorPrimary());
		$this->config->expects($this->at(8))->method('getAppValue')->with('theming', 'color', $this->defaults->getColorPrimary())->willReturn($this->defaults->getColorPrimary());
		$this->config->expects($this->at(9))->method('getAppValue')->with('theming', 'color', $this->defaults->getColorPrimary())->willReturn($this->defaults->getColorPrimary());
		$this->config->expects($this->at(10))->method('getAppValue')->with('theming', 'color', $this->defaults->getColorPrimary())->willReturn($this->defaults->getColorPrimary());

		$this->util->expects($this->any())->method('invertTextColor')->with($this->defaults->getColorPrimary())->willReturn(false);
		$this->util->expects($this->any())->method('elementColor')->with($this->defaults->getColorPrimary())->willReturn('#aaaaaa');
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
			'theming-logo-mime' => '\'jpeg\'',
			'theming-background-mime' => '\'jpeg\'',
			'image-logo' => "'absolute-custom-logo?v=0'",
			'image-login-background' => "'absolute-custom-background?v=0'",
			'color-primary' => $this->defaults->getColorPrimary(),
			'color-primary-text' => '#ffffff',
			'image-login-plain' => 'false',
			'color-primary-element' => '#aaaaaa'

		];
		$this->assertEquals($expected, $this->template->getScssVariables());
	}

	public function testGetDefaultAndroidURL() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'AndroidClientUrl', 'https://play.google.com/store/apps/details?id=com.nextcloud.client')
			->willReturn('https://play.google.com/store/apps/details?id=com.nextcloud.client');

		$this->assertEquals('https://play.google.com/store/apps/details?id=com.nextcloud.client', $this->template->getAndroidClientUrl());
	}

	public function testGetCustomAndroidURL() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'AndroidClientUrl', 'https://play.google.com/store/apps/details?id=com.nextcloud.client')
			->willReturn('https://play.google.com/store/apps/details?id=com.mycloud.client');

		$this->assertEquals('https://play.google.com/store/apps/details?id=com.mycloud.client', $this->template->getAndroidClientUrl());
	}

	public function testGetDefaultiOSURL() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'iOSClientUrl', 'https://itunes.apple.com/us/app/nextcloud/id1125420102?mt=8')
			->willReturn('https://itunes.apple.com/us/app/nextcloud/id1125420102?mt=8');

		$this->assertEquals('https://itunes.apple.com/us/app/nextcloud/id1125420102?mt=8', $this->template->getiOSClientUrl());
	}

	public function testGetCustomiOSURL() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'iOSClientUrl', 'https://itunes.apple.com/us/app/nextcloud/id1125420102?mt=8')
			->willReturn('https://itunes.apple.com/us/app/nextcloud/id1234567890?mt=8');

		$this->assertEquals('https://itunes.apple.com/us/app/nextcloud/id1234567890?mt=8', $this->template->getiOSClientUrl());
	}

	public function testGetDefaultiTunesAppId() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'iTunesAppId', '1125420102')
			->willReturn('1125420102');

		$this->assertEquals('1125420102', $this->template->getiTunesAppId());
	}

	public function testGetCustomiTunesAppId() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'iTunesAppId', '1125420102')
			->willReturn('1234567890');

		$this->assertEquals('1234567890', $this->template->getiTunesAppId());
	}

	public function dataReplaceImagePath() {
		return [
			['core', 'test.png', false],
			['core', 'manifest.json'],
			['core', 'favicon.ico'],
			['core', 'favicon-touch.png']
		];
	}

	/** @dataProvider dataReplaceImagePath */
	public function testReplaceImagePath($app, $image, $result = 'themingRoute?v=0') {
		$this->cache->expects($this->any())
			->method('get')
			->with('shouldReplaceIcons')
			->willReturn(true);
		$this->config
			->expects($this->any())
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->urlGenerator
			->expects($this->any())
			->method('linkToRoute')
			->willReturn('themingRoute');
		$this->assertEquals($result, $this->template->replaceImagePath($app, $image));
	}

}
