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
use Test\TestCase;

class UtilTest extends TestCase {

	/** @var Util */
	protected $util;

	protected function setUp() {
		parent::setUp();
		$this->util = new Util();
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
}
