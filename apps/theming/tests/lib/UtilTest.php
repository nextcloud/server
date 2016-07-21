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

	public function testInvertTextColorLight() {
		$invert = Util::invertTextColor('#ffffff');
		$this->assertEquals(true, $invert);
	}

	public function testInvertTextColorDark() {
		$invert = Util::invertTextColor('#000000');
		$this->assertEquals(false, $invert);
	}

	public function testCalculateLuminanceLight() {
		$luminance = Util::calculateLuminance('#ffffff');
		$this->assertEquals(1, $luminance);
	}

	public function testCalculateLuminanceDark() {
		$luminance = Util::calculateLuminance('#000000');
		$this->assertEquals(0, $luminance);
	}

	public function testCalculateLuminanceLightShorthand() {
		$luminance = Util::calculateLuminance('#fff');
		$this->assertEquals(1, $luminance);
	}

	public function testCalculateLuminanceDarkShorthand() {
		$luminance = Util::calculateLuminance('#000');
		$this->assertEquals(0, $luminance);
	}
	public function testInvertTextColorInvalid() {
		$invert = Util::invertTextColor('aaabbbcccddd123');
		$this->assertEquals(false, $invert);
	}
	
	public function testInvertTextColorEmpty() {
		$invert = Util::invertTextColor('');
		$this->assertEquals(false, $invert);
	}
}
