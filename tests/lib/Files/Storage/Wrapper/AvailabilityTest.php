<?php
/**
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test\Files\Storage\Wrapper;

class AvailabilityTest extends \Test\TestCase {
	protected function getWrapperInstance() {
		$storage = $this->getMockBuilder('\OC\Files\Storage\Temporary')
			->disableOriginalConstructor()
			->getMock();
		$wrapper = new \OC\Files\Storage\Wrapper\Availability(['storage' => $storage]);
		return [$storage, $wrapper];
	}

	/**
	 * Storage is available
	 */
	public function testAvailable() {
		list($storage, $wrapper) = $this->getWrapperInstance();
		$storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => true, 'last_checked' => 0]);
		$storage->expects($this->never())
			->method('test');
		$storage->expects($this->once())
			->method('mkdir');

		$wrapper->mkdir('foobar');
	}

	/**
	 * Storage marked unavailable, TTL not expired
	 *
	 * @expectedException \OCP\Files\StorageNotAvailableException
	 */
	public function testUnavailable() {
		list($storage, $wrapper) = $this->getWrapperInstance();
		$storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => false, 'last_checked' => time()]);
		$storage->expects($this->never())
			->method('test');
		$storage->expects($this->never())
			->method('mkdir');

		$wrapper->mkdir('foobar');
	}

	/**
	 * Storage marked unavailable, TTL expired
	 */
	public function testUnavailableRecheck() {
		list($storage, $wrapper) = $this->getWrapperInstance();
		$storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => false, 'last_checked' => 0]);
		$storage->expects($this->once())
			->method('test')
			->willReturn(true);
		$storage->expects($this->exactly(2))
			->method('setAvailability')
			->withConsecutive(
				[$this->equalTo(false)], // prevents concurrent rechecks
				[$this->equalTo(true)] // sets correct availability
			);
		$storage->expects($this->once())
			->method('mkdir');

		$wrapper->mkdir('foobar');
	}

	/**
	 * Storage marked available, but throws StorageNotAvailableException
	 *
	 * @expectedException \OCP\Files\StorageNotAvailableException
	 */
	public function testAvailableThrowStorageNotAvailable() {
		list($storage, $wrapper) = $this->getWrapperInstance();
		$storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => true, 'last_checked' => 0]);
		$storage->expects($this->never())
			->method('test');
		$storage->expects($this->once())
			->method('mkdir')
			->will($this->throwException(new \OCP\Files\StorageNotAvailableException()));
		$storage->expects($this->once())
			->method('setAvailability')
			->with($this->equalTo(false));

		$wrapper->mkdir('foobar');
	}

	/**
	 * Storage available, but call fails
	 * Method failure does not indicate storage unavailability
	 */
	public function testAvailableFailure() {
		list($storage, $wrapper) = $this->getWrapperInstance();
		$storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => true, 'last_checked' => 0]);
		$storage->expects($this->never())
			->method('test');
		$storage->expects($this->once())
			->method('mkdir')
			->willReturn(false);
		$storage->expects($this->never())
			->method('setAvailability');

		$wrapper->mkdir('foobar');
	}

	/**
	 * Storage available, but throws exception
	 * Standard exception does not indicate storage unavailability
	 *
	 * @expectedException \Exception
	 */
	public function testAvailableThrow() {
		list($storage, $wrapper) = $this->getWrapperInstance();
		$storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => true, 'last_checked' => 0]);
		$storage->expects($this->never())
			->method('test');
		$storage->expects($this->once())
			->method('mkdir')
			->will($this->throwException(new \Exception()));
		$storage->expects($this->never())
			->method('setAvailability');

		$wrapper->mkdir('foobar');
	}
}
