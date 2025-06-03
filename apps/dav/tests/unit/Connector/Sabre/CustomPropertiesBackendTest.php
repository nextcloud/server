<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\CalDAV\DefaultCalendarValidator;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File;
use OCA\DAV\DAV\CustomPropertiesBackend;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Tree;

/**
 * Class CustomPropertiesBackend
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre
 */
class CustomPropertiesBackendTest extends \Test\TestCase {
	private \Sabre\DAV\Server $server;
	private \Sabre\DAV\Tree&MockObject $tree;
	private IUser&MockObject $user;
	private DefaultCalendarValidator&MockObject $defaultCalendarValidator;
	private CustomPropertiesBackend $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->server = new \Sabre\DAV\Server();
		$this->tree = $this->createMock(Tree::class);

		$userId = self::getUniqueID('testcustompropertiesuser');

		$this->user = $this->createMock(IUser::class);
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn($userId);

		$this->defaultCalendarValidator = $this->createMock(DefaultCalendarValidator::class);

		$this->plugin = new CustomPropertiesBackend(
			$this->server,
			$this->tree,
			Server::get(IDBConnection::class),
			$this->user,
			$this->defaultCalendarValidator,
		);
	}

	protected function tearDown(): void {
		$connection = Server::get(IDBConnection::class);
		$deleteStatement = $connection->prepare(
			'DELETE FROM `*PREFIX*properties`' .
			' WHERE `userid` = ?'
		);
		$deleteStatement->execute(
			[
				$this->user->getUID(),
			]
		);
		$deleteStatement->closeCursor();

		parent::tearDown();
	}

	private function createTestNode(string $class) {
		$node = $this->createMock($class);
		$node->expects($this->any())
			->method('getId')
			->willReturn(123);

		$node->expects($this->any())
			->method('getPath')
			->willReturn('/dummypath');

		return $node;
	}

	private function applyDefaultProps($path = '/dummypath'): void {
		// properties to set
		$propPatch = new \Sabre\DAV\PropPatch([
			'customprop' => 'value1',
			'customprop2' => 'value2',
		]);

		$this->plugin->propPatch(
			$path,
			$propPatch
		);

		$propPatch->commit();

		$this->assertEmpty($propPatch->getRemainingMutations());

		$result = $propPatch->getResult();
		$this->assertEquals(200, $result['customprop']);
		$this->assertEquals(200, $result['customprop2']);
	}

	/**
	 * Test that propFind on a missing file soft fails
	 */
	public function testPropFindMissingFileSoftFail(): void {
		$propFind = new \Sabre\DAV\PropFind(
			'/dummypath',
			[
				'customprop',
				'customprop2',
				'unsetprop',
			],
			0
		);

		$this->plugin->propFind(
			'/dummypath',
			$propFind
		);

		$this->plugin->propFind(
			'/dummypath',
			$propFind
		);

		// assert that the above didn't throw exceptions
		$this->assertTrue(true);
	}

	/**
	 * Test setting/getting properties
	 */
	public function testSetGetPropertiesForFile(): void {
		$this->applyDefaultProps();

		$propFind = new \Sabre\DAV\PropFind(
			'/dummypath',
			[
				'customprop',
				'customprop2',
				'unsetprop',
			],
			0
		);

		$this->plugin->propFind(
			'/dummypath',
			$propFind
		);

		$this->assertEquals('value1', $propFind->get('customprop'));
		$this->assertEquals('value2', $propFind->get('customprop2'));
		$this->assertEquals(['unsetprop'], $propFind->get404Properties());
	}

	/**
	 * Test getting properties from directory
	 */
	public function testGetPropertiesForDirectory(): void {
		$this->applyDefaultProps('/dummypath');
		$this->applyDefaultProps('/dummypath/test.txt');

		$propNames = [
			'customprop',
			'customprop2',
			'unsetprop',
		];

		$propFindRoot = new \Sabre\DAV\PropFind(
			'/dummypath',
			$propNames,
			1
		);

		$propFindSub = new \Sabre\DAV\PropFind(
			'/dummypath/test.txt',
			$propNames,
			0
		);

		$this->plugin->propFind(
			'/dummypath',
			$propFindRoot
		);

		$this->plugin->propFind(
			'/dummypath/test.txt',
			$propFindSub
		);

		// TODO: find a way to assert that no additional SQL queries were
		// run while doing the second propFind

		$this->assertEquals('value1', $propFindRoot->get('customprop'));
		$this->assertEquals('value2', $propFindRoot->get('customprop2'));
		$this->assertEquals(['unsetprop'], $propFindRoot->get404Properties());

		$this->assertEquals('value1', $propFindSub->get('customprop'));
		$this->assertEquals('value2', $propFindSub->get('customprop2'));
		$this->assertEquals(['unsetprop'], $propFindSub->get404Properties());
	}

	/**
	 * Test delete property
	 */
	public function testDeleteProperty(): void {
		$this->applyDefaultProps();

		$propPatch = new \Sabre\DAV\PropPatch([
			'customprop' => null,
		]);

		$this->plugin->propPatch(
			'/dummypath',
			$propPatch
		);

		$propPatch->commit();

		$this->assertEmpty($propPatch->getRemainingMutations());

		$result = $propPatch->getResult();
		$this->assertEquals(204, $result['customprop']);
	}
}
