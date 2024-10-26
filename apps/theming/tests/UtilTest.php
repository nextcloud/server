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
use OCP\Server;
use OCP\ServerVersion;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UtilTest extends TestCase {

	protected Util $util;
	protected IConfig&MockObject $config;
	protected IAppData&MockObject $appData;
	protected IAppManager $appManager;
	protected ImageManager&MockObject $imageManager;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->appData = $this->createMock(IAppData::class);
		$this->appManager = Server::get(IAppManager::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->util = new Util($this->createMock(ServerVersion::class), $this->config, $this->appManager, $this->appData, $this->imageManager);
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
	public function testColorContrast(string $color1, string $color2, $contrast): void {
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
	public function testInvertTextColor($color, $expected): void {
		$invert = $this->util->invertTextColor($color);
		$this->assertEquals($expected, $invert);
	}

	public function testCalculateLuminanceLight(): void {
		$luminance = $this->util->calculateLuminance('#ffffff');
		$this->assertEquals(1, $luminance);
	}

	public function testCalculateLuminanceDark(): void {
		$luminance = $this->util->calculateLuminance('#000000');
		$this->assertEquals(0, $luminance);
	}

	public function testCalculateLuminanceLightShorthand(): void {
		$luminance = $this->util->calculateLuminance('#fff');
		$this->assertEquals(1, $luminance);
	}

	public function testCalculateLuminanceDarkShorthand(): void {
		$luminance = $this->util->calculateLuminance('#000');
		$this->assertEquals(0, $luminance);
	}

	public function testInvertTextColorInvalid(): void {
		$this->expectException(\Exception::class);
		$this->util->invertTextColor('aaabbbcccddd123');
	}

	public function testInvertTextColorEmpty(): void {
		$this->expectException(\Exception::class);
		$this->util->invertTextColor('');
	}

	public function testElementColorDefaultBlack(): void {
		$elementColor = $this->util->elementColor('#000000');
		$this->assertEquals('#4d4d4d', $elementColor);
	}

	public function testElementColorDefaultWhite(): void {
		$elementColor = $this->util->elementColor('#ffffff');
		$this->assertEquals('#b3b3b3', $elementColor);
	}

	public function testElementColorBlackOnDarkBackground(): void {
		$elementColor = $this->util->elementColor('#000000', false);
		$this->assertEquals('#4d4d4d', $elementColor);
	}

	public function testElementColorBlackOnBrightBackground(): void {
		$elementColor = $this->util->elementColor('#000000', true);
		$this->assertEquals('#000000', $elementColor);
	}

	public function testElementColorWhiteOnBrightBackground(): void {
		$elementColor = $this->util->elementColor('#ffffff', true);
		$this->assertEquals('#b3b3b3', $elementColor);
	}

	public function testElementColorWhiteOnDarkBackground(): void {
		$elementColor = $this->util->elementColor('#ffffff', false);
		$this->assertEquals('#ffffff', $elementColor);
	}

	public function testGenerateRadioButtonWhite(): void {
		$button = $this->util->generateRadioButton('#ffffff');
		$expected = 'PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiI+PHBhdGggZD0iTTggMWE3IDcgMCAwIDAtNyA3IDcgNyAwIDAgMCA3IDcgNyA3IDAgMCAwIDctNyA3IDcgMCAwIDAtNy03em0wIDFhNiA2IDAgMCAxIDYgNiA2IDYgMCAwIDEtNiA2IDYgNiAwIDAgMS02LTYgNiA2IDAgMCAxIDYtNnptMCAyYTQgNCAwIDEgMCAwIDggNCA0IDAgMCAwIDAtOHoiIGZpbGw9IiNmZmZmZmYiLz48L3N2Zz4=';
		$this->assertEquals($expected, $button);
	}

	public function testGenerateRadioButtonBlack(): void {
		$button = $this->util->generateRadioButton('#000000');
		$expected = 'PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiI+PHBhdGggZD0iTTggMWE3IDcgMCAwIDAtNyA3IDcgNyAwIDAgMCA3IDcgNyA3IDAgMCAwIDctNyA3IDcgMCAwIDAtNy03em0wIDFhNiA2IDAgMCAxIDYgNiA2IDYgMCAwIDEtNiA2IDYgNiAwIDAgMS02LTYgNiA2IDAgMCAxIDYtNnptMCAyYTQgNCAwIDEgMCAwIDggNCA0IDAgMCAwIDAtOHoiIGZpbGw9IiMwMDAwMDAiLz48L3N2Zz4=';
		$this->assertEquals($expected, $button);
	}

	/**
	 * @dataProvider dataGetAppIcon
	 */
	public function testGetAppIcon($app, $expected): void {
		$this->appData->expects($this->any())
			->method('getFolder')
			->with('global/images')
			->willThrowException(new NotFoundException());
		$icon = $this->util->getAppIcon($app);
		$this->assertEquals($expected, $icon);
	}

	public function dataGetAppIcon() {
		return [
			['user_ldap', Server::get(IAppManager::class)->getAppPath('user_ldap') . '/img/app.svg'],
			['noapplikethis', \OC::$SERVERROOT . '/core/img/logo/logo.svg'],
			['comments', Server::get(IAppManager::class)->getAppPath('comments') . '/img/comments.svg'],
		];
	}

	public function testGetAppIconThemed(): void {
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
	public function testGetAppImage($app, $image, $expected): void {
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

	public function testColorizeSvg(): void {
		$input = '#0082c9 #0082C9 #000000 #FFFFFF';
		$expected = '#AAAAAA #AAAAAA #000000 #FFFFFF';
		$result = $this->util->colorizeSvg($input, '#AAAAAA');
		$this->assertEquals($expected, $result);
	}

	public function testIsAlreadyThemedFalse(): void {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('theme', '')
			->willReturn('');
		$actual = $this->util->isAlreadyThemed();
		$this->assertFalse($actual);
	}

	public function testIsAlreadyThemedTrue(): void {
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
	public function testIsBackgroundThemed($backgroundMime, $expected): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('theming', 'backgroundMime', '')
			->willReturn($backgroundMime);
		$this->assertEquals($expected, $this->util->isBackgroundThemed());
	}
}
