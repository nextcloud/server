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


use OCA\WorkflowEngine\Entity\File;
use OCA\WorkflowEngine\Manager;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IServerContainer;
use OCP\WorkflowEngine\IEntity;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
	/** @var MockObject|IDBConnection */
	protected $db;
	/** @var \PHPUnit\Framework\MockObject\MockObject|ILogger */
	protected $logger;
	/** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
	protected $eventDispatcher;
	/** @var MockObject|IServerContainer */
	protected $container;

	protected function setUp() {
		parent::setUp();

		$this->db = \OC::$server->getDatabaseConnection();
		$this->container = $this->createMock(IServerContainer::class);
		/** @var IL10N|MockObject $l */
		$l = $this->createMock(IL10N::class);
		$l->method('t')
			->will($this->returnCallback(function($text, $parameters = []) {
				return vsprintf($text, $parameters);
			}));

		$this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
		$this->logger = $this->createMock(ILogger::class);

		$this->manager = new Manager(
			\OC::$server->getDatabaseConnection(),
			$this->container,
			$l,
			$this->eventDispatcher,
			$this->logger
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

	public function testGetEntitiesListBuildInOnly() {
		$fileEntityMock = $this->createMock(File::class);

		$this->container->expects($this->once())
			->method('query')
			->with(File::class)
			->willReturn($fileEntityMock);

		$entities = $this->manager->getEntitiesList();

		$this->assertCount(1, $entities);
		$this->assertInstanceOf(IEntity::class, $entities[0]);
	}

	public function testGetEntitiesList() {
		$fileEntityMock = $this->createMock(File::class);

		$this->container->expects($this->once())
			->method('query')
			->with(File::class)
			->willReturn($fileEntityMock);

		/** @var MockObject|IEntity $extraEntity */
		$extraEntity = $this->createMock(IEntity::class);

		$this->eventDispatcher->expects($this->once())
			->method('dispatch')
			->with('OCP\WorkflowEngine::registerEntities', $this->anything())
			->willReturnCallback(function() use ($extraEntity) {
				$this->manager->registerEntity($extraEntity);
			});

		$entities = $this->manager->getEntitiesList();

		$this->assertCount(2, $entities);

		$entityTypeCounts = array_reduce($entities, function (array $carry, IEntity $entity) {
			if($entity instanceof File) $carry[0]++;
			else if($entity instanceof IEntity) $carry[1]++;
			return $carry;
		}, [0, 0]);

		$this->assertSame(1, $entityTypeCounts[0]);
		$this->assertSame(1, $entityTypeCounts[1]);
	}
}
