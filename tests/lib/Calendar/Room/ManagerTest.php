<?php
/**
 * @copyright 2018, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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

namespace Test\Calendar\Room;

use \OC\Calendar\Room\Manager;
use \OCP\Calendar\Room\IBackend;
use \Test\TestCase;

class ManagerTest extends TestCase {

	/** @var Manager */
	private $manager;

	protected function setUp() {
		parent::setUp();

		$this->manager = new Manager();
	}

	public function testRegisterUnregisterBackend() {
		$backend1 = $this->createMock(IBackend::class);
		$backend1->method('getBackendIdentifier')->will($this->returnValue('backend_1'));

		$backend2 = $this->createMock(IBackend::class);
		$backend2->method('getBackendIdentifier')->will($this->returnValue('backend_2'));

		$this->manager->registerBackend($backend1);
		$this->manager->registerBackend($backend2);

		$this->assertEquals([
			$backend1, $backend2
		], $this->manager->getBackends());

		$this->manager->unregisterBackend($backend1);

		$this->assertEquals([
			$backend2
		], $this->manager->getBackends());
	}

	public function testGetBackend() {
		$backend1 = $this->createMock(IBackend::class);
		$backend1->method('getBackendIdentifier')->will($this->returnValue('backend_1'));

		$backend2 = $this->createMock(IBackend::class);
		$backend2->method('getBackendIdentifier')->will($this->returnValue('backend_2'));

		$this->manager->registerBackend($backend1);
		$this->manager->registerBackend($backend2);

		$this->assertEquals($backend1, $this->manager->getBackend('backend_1'));
		$this->assertEquals($backend2, $this->manager->getBackend('backend_2'));
	}

	public function testClear() {
		$backend1 = $this->createMock(IBackend::class);
		$backend1->method('getBackendIdentifier')->will($this->returnValue('backend_1'));

		$backend2 = $this->createMock(IBackend::class);
		$backend2->method('getBackendIdentifier')->will($this->returnValue('backend_2'));

		$this->manager->registerBackend($backend1);
		$this->manager->registerBackend($backend2);

		$this->assertEquals([
			$backend1, $backend2
		], $this->manager->getBackends());

		$this->manager->clear();

		$this->assertEquals([], $this->manager->getBackends());
	}
}
