<?php
/**
 * @copyright Copyright (c) 2019 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\files_external\tests\Config;

use OC_Mount_Config;
use Test\TestCase;

class PlaceholderSubstituteTest extends TestCase {

	public function dataArePlaceholdersSubstituted(): array {
		return [
			['smb_$user', false],
			['hidden_share$', true],
			[['smb_$user', 'hidden_share$'], false],
			[['smb_hello', 'hidden_share$'], true]
		];
	}

	/**
	 * @dataProvider dataArePlaceholdersSubstituted
	 * @param string|array $option
	 * @param bool $expected
	 */
	public function testArePlaceholdersSubstituted($option, $expected): void {
		$this->assertSame($expected, OC_Mount_Config::arePlaceholdersSubstituted($option));
	}

}
