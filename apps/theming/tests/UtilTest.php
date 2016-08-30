<?php
/**
 * @copyright Copyright (c) 2016 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Haertl <jus@bitgrid.net>
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

use OCA\Theming\Util;
use OCP\IConfig;
use OCP\Files\IRootFolder;
use Test\TestCase;

class UtilTest extends TestCase {

	/** @var Util */
	protected $util;
	/** @var IConfig */
	protected $config;
	/** @var IRootFolder */
	protected $rootFolder;

	protected function setUp() {
		parent::setUp();
		$this->config = $this->getMockBuilder('\OCP\IConfig')->getMock();
		$this->rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();
		$this->util = new Util($this->config, $this->rootFolder);
	}

	public function testInvertTextColorLight() {
		$invert = $this->util->invertTextColor('#ffffff');
		$this->assertEquals(true, $invert);
	}

	public function testInvertTextColorDark() {
		$invert = $this->util->invertTextColor('#000000');
		$this->assertEquals(false, $invert);
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
		$invert = $this->util->invertTextColor('aaabbbcccddd123');
		$this->assertEquals(false, $invert);
	}
	
	public function testInvertTextColorEmpty() {
		$invert = $this->util->invertTextColor('');
		$this->assertEquals(false, $invert);
	}

	public function testElementColorDefault() {
		$elementColor = $this->util->elementColor("#000000");
		$this->assertEquals('#000000', $elementColor);
	}

	public function testElementColorOnBrightBackground() {
		$elementColor = $this->util->elementColor('#ffffff');
		$this->assertEquals('#555555', $elementColor);
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
		$icon = $this->util->getAppIcon($app);
		$this->assertEquals($expected, $icon);
	}

	public function dataGetAppIcon() {
		return [
			['user_ldap', \OC_App::getAppPath('user_ldap') . '/img/app.svg'],
			['noapplikethis', \OC::$SERVERROOT . '/core/img/logo.svg'],
			['comments', \OC_App::getAppPath('comments') . '/img/comments.svg'],
		];
	}

	public function testGetAppIconThemed() {
		$this->rootFolder->expects($this->once())
			->method('nodeExists')
			->with('/themedinstancelogo')
			->willReturn(true);
		$expected = '/themedinstancelogo';
		$icon = $this->util->getAppIcon('noapplikethis');
		$this->assertEquals($expected, $icon);
	}

	/**
	 * @dataProvider dataGetAppImage
	 */
	public function testGetAppImage($app, $image, $expected) {
		$this->assertEquals($expected, $this->util->getAppImage($app, $image));
	}
	public function dataGetAppImage() {
		return [
			['core', 'logo.svg', \OC::$SERVERROOT . '/core/img/logo.svg'],
			['files', 'external', \OC::$SERVERROOT . '/apps/files/img/external.svg'],
			['files', 'external.svg', \OC::$SERVERROOT . '/apps/files/img/external.svg'],
			['noapplikethis', 'foobar.svg', false],
		];
	}

	public function testColorizeSvg() {
		$input = "#0082c9 #0082C9 #000000 #FFFFFF";
		$expected = "#AAAAAA #AAAAAA #000000 #FFFFFF";
		$result = $this->util->colorizeSvg($input, '#AAAAAA');
		$this->assertEquals($expected, $result);
	}

}
