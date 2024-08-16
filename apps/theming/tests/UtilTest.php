<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Tests;

use OCA\Theming\ImageManager;
use OCA\Theming\Util;
use OCP\App\IAppManager;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UtilTest extends TestCase {

	/** @var Util */
	protected $util;
	/** @var IConfig|MockObject */
	protected $config;
	/** @var IAppData|MockObject */
	protected $appData;
	/** @var IAppManager|MockObject */
	protected $appManager;
	/** @var ImageManager|MockObject */
	protected $imageManager;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->appData = $this->createMock(IAppData::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->util = new Util($this->config, $this->appManager, $this->appData, $this->imageManager);
	}

	public function dataColorContrast() {
		return [
			['#ffffff', '#FFFFFF', 1],
			['#000000', '#000000', 1],
			['#ffffff', '#000000', 21],
			['#000000', '#FFFFFF', 21],
			['#9E9E9E', '#353535', 4.578],
			['#353535', '#9E9E9E', 4.578],
		];
	}

	/**
	 * @dataProvider dataColorContrast
	 */
	public function testColorContrast(string $color1, string $color2, $contrast) {
		$this->assertEqualsWithDelta($contrast, $this->util->colorContrast($color1, $color2), .001);
	}

	public function dataInvertTextColor() {
		return [
			['#ffffff', true],
			['#000000', false],
			['#00679e', false],
			['#ffff00', true],
		];
	}
	/**
	 * @dataProvider dataInvertTextColor
	 */
	public function testInvertTextColor($color, $expected) {
		$invert = $this->util->invertTextColor($color);
		$this->assertEquals($expected, $invert);
	}

	public function testCalculateLuminanceLight() {
		$luminance = $this->util->calculateLuminance('#ffffff');
		$this->assertEquals(1, $luminance);
	}

	public function testCalculateLuminanceDark() {
		$luminance = $this->util->calculateLuminance('#000000');
		$this->assertEquals(0, $luminance);
	}

	public function testCalculateLuminanceLightShorthand() {
		$luminance = $this->util->calculateLuminance('#fff');
		$this->assertEquals(1, $luminance);
	}

	public function testCalculateLuminanceDarkShorthand() {
		$luminance = $this->util->calculateLuminance('#000');
		$this->assertEquals(0, $luminance);
	}

	public function testInvertTextColorInvalid() {
		$this->expectException(\Exception::class);
		$this->util->invertTextColor('aaabbbcccddd123');
	}

	public function testInvertTextColorEmpty() {
		$this->expectException(\Exception::class);
		$this->util->invertTextColor('');
	}

	public function testElementColorDefaultBlack() {
		$elementColor = $this->util->elementColor("#000000");
		$this->assertEquals('#4d4d4d', $elementColor);
	}

	public function testElementColorDefaultWhite() {
		$elementColor = $this->util->elementColor("#ffffff");
		$this->assertEquals('#b3b3b3', $elementColor);
	}

	public function testElementColorBlackOnDarkBackground() {
		$elementColor = $this->util->elementColor("#000000", false);
		$this->assertEquals('#4d4d4d', $elementColor);
	}

	public function testElementColorBlackOnBrightBackground() {
		$elementColor = $this->util->elementColor("#000000", true);
		$this->assertEquals('#000000', $elementColor);
	}

	public function testElementColorWhiteOnBrightBackground() {
		$elementColor = $this->util->elementColor('#ffffff', true);
		$this->assertEquals('#b3b3b3', $elementColor);
	}

	public function testElementColorWhiteOnDarkBackground() {
		$elementColor = $this->util->elementColor('#ffffff', false);
		$this->assertEquals('#ffffff', $elementColor);
	}

	public function testGenerateRadioButtonWhite() {
		$button = $this->util->generateRadioButton('#ffffff');
		$expected = 'PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiI+PHBhdGggZD0iTTggMWE3IDcgMCAwIDAtNyA3IDcgNyAwIDAgMCA3IDcgNyA3IDAgMCAwIDctNyA3IDcgMCAwIDAtNy03em0wIDFhNiA2IDAgMCAxIDYgNiA2IDYgMCAwIDEtNiA2IDYgNiAwIDAgMS02LTYgNiA2IDAgMCAxIDYtNnptMCAyYTQgNCAwIDEgMCAwIDggNCA0IDAgMCAwIDAtOHoiIGZpbGw9IiNmZmZmZmYiLz48L3N2Zz4=';
		$this->assertEquals($expected, $button);
	}

	public function testGenerateRadioButtonBlack() {
		$button = $this->util->generateRadioButton('#000000');
		$expected = 'PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiI+PHBhdGggZD0iTTggMWE3IDcgMCAwIDAtNyA3IDcgNyAwIDAgMCA3IDcgNyA3IDAgMCAwIDctNyA3IDcgMCAwIDAtNy03em0wIDFhNiA2IDAgMCAxIDYgNiA2IDYgMCAwIDEtNiA2IDYgNiAwIDAgMS02LTYgNiA2IDAgMCAxIDYtNnptMCAyYTQgNCAwIDEgMCAwIDggNCA0IDAgMCAwIDAtOHoiIGZpbGw9IiMwMDAwMDAiLz48L3N2Zz4=';
		$this->assertEquals($expected, $button);
	}

	/**
	 * @dataProvider dataGetAppIcon
	 */
	public function testGetAppIcon($app, $expected) {
		$this->appData->expects($this->any())
			->method('getFolder')
			->with('global/images')
			->willThrowException(new NotFoundException());
		$this->appManager->expects($this->once())
			->method('getAppPath')
			->with($app)
			->willReturn(\OC_App::getAppPath($app));
		$icon = $this->util->getAppIcon($app);
		$this->assertEquals($expected, $icon);
	}

	public function dataGetAppIcon() {
		return [
			['user_ldap', \OC_App::getAppPath('user_ldap') . '/img/app.svg'],
			['noapplikethis', \OC::$SERVERROOT . '/core/img/logo/logo.svg'],
			['comments', \OC_App::getAppPath('comments') . '/img/comments.svg'],
		];
	}

	public function testGetAppIconThemed() {
		$file = $this->createMock(ISimpleFile::class);
		$folder = $this->createMock(ISimpleFolder::class);
		$folder->expects($this->once())
			->method('getFile')
			->with('logo')
			->willReturn($file);
		$this->appData->expects($this->once())
			->method('getFolder')
			->with('global/images')
			->willReturn($folder);
		$icon = $this->util->getAppIcon('noapplikethis');
		$this->assertEquals($file, $icon);
	}

	/**
	 * @dataProvider dataGetAppImage
	 */
	public function testGetAppImage($app, $image, $expected) {
		if ($app !== 'core') {
			$this->appManager->expects($this->once())
				->method('getAppPath')
				->with($app)
				->willReturn(\OC_App::getAppPath($app));
		}
		$this->assertEquals($expected, $this->util->getAppImage($app, $image));
	}

	public function dataGetAppImage() {
		return [
			['core', 'logo/logo.svg', \OC::$SERVERROOT . '/core/img/logo/logo.svg'],
			['files', 'folder', \OC::$SERVERROOT . '/apps/files/img/folder.svg'],
			['files', 'folder.svg', \OC::$SERVERROOT . '/apps/files/img/folder.svg'],
			['noapplikethis', 'foobar.svg', false],
		];
	}

	public function testColorizeSvg() {
		$input = "#0082c9 #0082C9 #000000 #FFFFFF";
		$expected = "#AAAAAA #AAAAAA #000000 #FFFFFF";
		$result = $this->util->colorizeSvg($input, '#AAAAAA');
		$this->assertEquals($expected, $result);
	}

	public function testIsAlreadyThemedFalse() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('theme', '')
			->willReturn('');
		$actual = $this->util->isAlreadyThemed();
		$this->assertFalse($actual);
	}

	public function testIsAlreadyThemedTrue() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('theme', '')
			->willReturn('example');
		$actual = $this->util->isAlreadyThemed();
		$this->assertTrue($actual);
	}

	public function dataIsBackgroundThemed() {
		return [
			['', false],
			['png', true],
			['backgroundColor', false],
		];
	}
	/**
	 * @dataProvider dataIsBackgroundThemed
	 */
	public function testIsBackgroundThemed($backgroundMime, $expected) {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('theming', 'backgroundMime', '')
			->willReturn($backgroundMime);
		$this->assertEquals($expected, $this->util->isBackgroundThemed());
	}
}
