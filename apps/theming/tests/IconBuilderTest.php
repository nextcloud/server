<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Tests;

use OC\Files\AppData\AppData;
use OCA\Theming\IconBuilder;
use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\App\IAppManager;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\ServerVersion;
use PHPUnit\Framework\Error\Warning;
use Test\TestCase;

class IconBuilderTest extends TestCase {

	/** @var IConfig */
	protected $config;
	/** @var AppData */
	protected $appData;
	/** @var ThemingDefaults */
	protected $themingDefaults;
	/** @var Util */
	protected $util;
	/** @var ImageManager */
	protected $imageManager;
	/** @var IconBuilder */
	protected $iconBuilder;
	/** @var IAppManager */
	protected $appManager;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->appData = $this->createMock(AppData::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->util = new Util($this->createMock(ServerVersion::class), $this->config, $this->appManager, $this->appData, $this->imageManager);
		$this->iconBuilder = new IconBuilder($this->themingDefaults, $this->util, $this->imageManager);
	}

	private function checkImagick() {
		if (!extension_loaded('imagick')) {
			$this->markTestSkipped('Imagemagick is required for dynamic icon generation.');
		}
		$checkImagick = new \Imagick();
		if (count($checkImagick->queryFormats('SVG')) < 1) {
			$this->markTestSkipped('No SVG provider present.');
		}
		if (count($checkImagick->queryFormats('PNG')) < 1) {
			$this->markTestSkipped('No PNG provider present.');
		}
	}

	public function dataRenderAppIcon() {
		return [
			['core', '#0082c9', 'touch-original.png'],
			['core', '#FF0000', 'touch-core-red.png'],
			['testing', '#FF0000', 'touch-testing-red.png'],
			['comments', '#0082c9', 'touch-comments.png'],
			['core', '#0082c9', 'touch-original-png.png'],
		];
	}

	/**
	 * @dataProvider dataRenderAppIcon
	 * @param $app
	 * @param $color
	 * @param $file
	 */
	public function testRenderAppIcon($app, $color, $file): void {
		$this->checkImagick();
		$this->themingDefaults->expects($this->once())
			->method('getColorPrimary')
			->willReturn($color);
		$this->appData->expects($this->once())
			->method('getFolder')
			->with('global/images')
			->willThrowException(new NotFoundException());

		$expectedIcon = new \Imagick(realpath(__DIR__) . '/data/' . $file);
		$icon = $this->iconBuilder->renderAppIcon($app, 512);

		$this->assertEquals(true, $icon->valid());
		$this->assertEquals(512, $icon->getImageWidth());
		$this->assertEquals(512, $icon->getImageHeight());
		$this->assertEquals($icon, $expectedIcon);
		$icon->destroy();
		$expectedIcon->destroy();
		// FIXME: We may need some comparison of the generated and the test images
		// cloud be something like $expectedIcon->compareImages($icon, Imagick::METRIC_MEANABSOLUTEERROR)[1])
	}

	/**
	 * @dataProvider dataRenderAppIcon
	 * @param $app
	 * @param $color
	 * @param $file
	 */
	public function testGetTouchIcon($app, $color, $file): void {
		$this->checkImagick();
		$this->themingDefaults->expects($this->once())
			->method('getColorPrimary')
			->willReturn($color);
		$this->appData->expects($this->once())
			->method('getFolder')
			->with('global/images')
			->willThrowException(new NotFoundException());

		$expectedIcon = new \Imagick(realpath(__DIR__) . '/data/' . $file);
		$icon = new \Imagick();
		$icon->readImageBlob($this->iconBuilder->getTouchIcon($app));

		$this->assertEquals(true, $icon->valid());
		$this->assertEquals(512, $icon->getImageWidth());
		$this->assertEquals(512, $icon->getImageHeight());
		$this->assertEquals($icon, $expectedIcon);
		$icon->destroy();
		$expectedIcon->destroy();
		// FIXME: We may need some comparison of the generated and the test images
		// cloud be something like $expectedIcon->compareImages($icon, Imagick::METRIC_MEANABSOLUTEERROR)[1])
	}

	/**
	 * @dataProvider dataRenderAppIcon
	 * @param $app
	 * @param $color
	 * @param $file
	 */
	public function testGetFavicon($app, $color, $file): void {
		$this->checkImagick();
		$this->imageManager->expects($this->once())
			->method('shouldReplaceIcons')
			->willReturn(true);
		$this->themingDefaults->expects($this->once())
			->method('getColorPrimary')
			->willReturn($color);
		$this->appData->expects($this->once())
			->method('getFolder')
			->with('global/images')
			->willThrowException(new NotFoundException());

		$expectedIcon = new \Imagick(realpath(__DIR__) . '/data/' . $file);
		$actualIcon = $this->iconBuilder->getFavicon($app);

		$icon = new \Imagick();
		$icon->setFormat('ico');
		$icon->readImageBlob($actualIcon);

		$this->assertEquals(true, $icon->valid());
		$this->assertEquals(128, $icon->getImageWidth());
		$this->assertEquals(128, $icon->getImageHeight());
		$icon->destroy();
		$expectedIcon->destroy();
		// FIXME: We may need some comparison of the generated and the test images
		// cloud be something like $expectedIcon->compareImages($icon, Imagick::METRIC_MEANABSOLUTEERROR)[1])
	}

	public function testGetFaviconNotFound(): void {
		$this->checkImagick();
		$this->expectWarning(Warning::class);
		$util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();
		$iconBuilder = new IconBuilder($this->themingDefaults, $util, $this->imageManager);
		$this->imageManager->expects($this->once())
			->method('shouldReplaceIcons')
			->willReturn(true);
		$util->expects($this->once())
			->method('getAppIcon')
			->willReturn('notexistingfile');
		$this->assertFalse($iconBuilder->getFavicon('noapp'));
	}

	public function testGetTouchIconNotFound(): void {
		$this->checkImagick();
		$this->expectWarning(Warning::class);
		$util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();
		$iconBuilder = new IconBuilder($this->themingDefaults, $util, $this->imageManager);
		$util->expects($this->once())
			->method('getAppIcon')
			->willReturn('notexistingfile');
		$this->assertFalse($iconBuilder->getTouchIcon('noapp'));
	}

	public function testColorSvgNotFound(): void {
		$this->checkImagick();
		$this->expectWarning(Warning::class);
		$util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();
		$iconBuilder = new IconBuilder($this->themingDefaults, $util, $this->imageManager);
		$util->expects($this->once())
			->method('getAppImage')
			->willReturn('notexistingfile');
		$this->assertFalse($iconBuilder->colorSvg('noapp', 'noimage'));
	}
}
