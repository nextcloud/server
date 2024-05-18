<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
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

namespace Test\App\AppStore\Version;

use OC\App\AppStore\Version\Version;
use Test\TestCase;

class VersionTest extends TestCase {
	public function testGetMinimumVersion() {
		$version = new Version('9', '10');
		$this->assertSame('9', $version->getMinimumVersion());
	}

	public function testGetMaximumVersion() {
		$version = new Version('9', '10');
		$this->assertSame('10', $version->getMaximumVersion());
	}
}
