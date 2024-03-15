<?php
/**
 * @copyright Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
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

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\Swift;

/**
 * @group PRIMARY-swift
 */
class SwiftTest extends ObjectStoreTest {
	/**
	 * @return \OCP\Files\ObjectStore\IObjectStore
	 */
	protected function getInstance() {
		$config = \OC::$server->getConfig()->getSystemValue('objectstore');
		if (!is_array($config) || $config['class'] !== 'OC\\Files\\ObjectStore\\Swift') {
			$this->markTestSkipped('objectstore not configured for swift');
		}

		return new Swift($config['arguments']);
	}

	public function testFseekSize() {
		$this->markTestSkipped('Swift does not support seeking at the moment');
	}
}
