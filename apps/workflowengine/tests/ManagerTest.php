<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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

namespace OCA\WorkflowEngine\Tests;


use OCA\WorkflowEngine\Manager;
use OCP\IDBConnection;
use Test\TestCase;

/**
 * Class ManagerTest
 *
 * @package OCA\WorkflowEngine\Tests
 * @group DB
 */
class ManagerTest extends TestCase {

	/** @var Manager */
	protected $manager;
	/** @var IDBConnection */
	protected $db;

	protected function setUp() {
		parent::setUp();

		$this->db = \OC::$server->getDatabaseConnection();
		$container = $this->getMockBuilder('OCP\IServerContainer')->getMock();
		$l = $this->getMockBuilder('OCP\IL10N')->getMock();
		$l->method('t')
			->will($this->returnCallback(function($text, $parameters = []) {
				return vsprintf($text, $parameters);
			}));

		$this->manager = new Manager(
			\OC::$server->getDatabaseConnection(),
			$container,
			$l
		);
		$this->clearChecks();
	}

	protected function tearDown() {
		$this->clearChecks();
		parent::tearDown();
	}

	public function clearChecks() {
		$query = $this->db->getQueryBuilder();
		$query->delete('flow_checks')
			->execute();
	}

	public function testChecks() {
		$check1 = $this->invokePrivate($this->manager, 'addCheck', ['Test', 'equal', 1]);
		$check2 = $this->invokePrivate($this->manager, 'addCheck', ['Test', '!equal', 2]);

		$data = $this->manager->getChecks([$check1]);
		$this->assertArrayHasKey($check1, $data);
		$this->assertArrayNotHasKey($check2, $data);

		$data = $this->manager->getChecks([$check1, $check2]);
		$this->assertArrayHasKey($check1, $data);
		$this->assertArrayHasKey($check2, $data);

		$data = $this->manager->getChecks([$check2, $check1]);
		$this->assertArrayHasKey($check1, $data);
		$this->assertArrayHasKey($check2, $data);

		$data = $this->manager->getChecks([$check2]);
		$this->assertArrayNotHasKey($check1, $data);
		$this->assertArrayHasKey($check2, $data);
	}
}
