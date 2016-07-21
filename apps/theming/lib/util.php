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

namespace OCA\Theming;

class Util {

	/**
	 * @param string $color rgb color value
	 * @return bool
	 */
	public static function invertTextColor($color) {
		$l = self::calculateLuminance($color);
		if($l>0.5) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param string $color rgb color value
	 * @return float
	 */
	public static function calculateLuminance($color) {
		$hex = preg_replace("/[^0-9A-Fa-f]/", '', $color);
		if (strlen($hex) === 3) {
			$hex = $hex{0} . $hex{0} . $hex{1} . $hex{1} . $hex{2} . $hex{2};
		}
		if (strlen($hex) !== 6) {
			return 0;
		}
		$r = hexdec(substr($hex, 0, 2));
		$g = hexdec(substr($hex, 2, 2));
		$b = hexdec(substr($hex, 4, 2));
		return (0.299 * $r + 0.587 * $g + 0.114 * $b)/255;
	}

}
