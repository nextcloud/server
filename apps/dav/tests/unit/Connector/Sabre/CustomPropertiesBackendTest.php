<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use OC;
use OCA\DAV\DAV\CustomPropertiesBackend;
use OCP\IDBConnection;
use OCP\IUser;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Test\TestCase;

/**
 * Class CustomPropertiesBackend
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre
 */
class CustomPropertiesBackendTest extends TestCase {

	/**
	 * @var Server
	 */
	private $server;

	/**
	 * @var CustomPropertiesBackend
	 */
	private $plugin;

	/**
	 * @var IUser
	 */
	private $user;

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->server = new Server();
		$tree = $this->createMock(Tree::class);
		$userId = $this->getUniqueID('testcustompropertiesuser');

		$this->user = $this->createMock(IUser::class);
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn($userId);

		$this->plugin = new CustomPropertiesBackend(
			$tree,
			OC::$server->get(IDBConnection::class),
			$this->user
		);
	}

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	protected function tearDown(): void {
		$connection = OC::$server->get(IDBConnection::class);
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
	}

	private function applyDefaultProps($path = '/dummypath') {
		// properties to set
		$propPatch = new PropPatch([
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
	public function testPropFindMissingFileSoftFail() {
		$propFind = new PropFind(
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
	public function testSetGetPropertiesForFile() {
		$this->applyDefaultProps();

		$propFind = new PropFind(
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
	public function testGetPropertiesForDirectory() {
		$this->applyDefaultProps();
		$this->applyDefaultProps('/dummypath/test.txt');

		$propNames = [
			'customprop',
			'customprop2',
			'unsetprop',
		];

		$propFindRoot = new PropFind(
			'/dummypath',
			$propNames,
			1
		);

		$propFindSub = new PropFind(
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
	public function testDeleteProperty() {
		$this->applyDefaultProps();

		$propPatch = new PropPatch([
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
