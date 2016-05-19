<?php
/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller thomas.mueller@tmit.eu
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Test\PublicNamespace;

class OCPConfigTest extends \Test\TestCase {

	public function testSetAppValueIfSetToNull() {

		$key = $this->getUniqueID('key-');

		$result = \OCP\Config::setAppValue('unit-test', $key, null);
		$this->assertTrue($result);

		$result = \OCP\Config::setAppValue('unit-test', $key, '12');
		$this->assertTrue($result);

	}

}
