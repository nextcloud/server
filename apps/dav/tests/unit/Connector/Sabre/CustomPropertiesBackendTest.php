<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * Class CustomPropertiesBackend
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre
 */
class CustomPropertiesBackendTest extends \Test\TestCase {

	/**
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @var \Sabre\DAV\Tree
	 */
	private $tree;

	/**
	 * @var \OCA\DAV\Connector\Sabre\CustomPropertiesBackend
	 */
	private $plugin;

	/**
	 * @var \OCP\IUser
	 */
	private $user;

	public function setUp() {
		parent::setUp();
		$this->server = new \Sabre\DAV\Server();
		$this->tree = $this->getMockBuilder('\Sabre\DAV\Tree')
			->disableOriginalConstructor()
			->getMock();

		$userId = $this->getUniqueID('testcustompropertiesuser');

		$this->user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$this->user->expects($this->any())
			->method('getUID')
			->will($this->returnValue($userId));

		$this->plugin = new \OCA\DAV\Connector\Sabre\CustomPropertiesBackend(
			$this->tree,
			\OC::$server->getDatabaseConnection(),
			$this->user
		);
	}

	public function tearDown() {
		$connection = \OC::$server->getDatabaseConnection();
		$deleteStatement = $connection->prepare(
			'DELETE FROM `*PREFIX*properties`' .
			' WHERE `userid` = ?'
		);
		$deleteStatement->execute(
			array(
				$this->user->getUID(),
			)
		);
		$deleteStatement->closeCursor();
	}

	private function createTestNode($class) {
		$node = $this->getMockBuilder($class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getId')
			->will($this->returnValue(123));

		$node->expects($this->any())
			->method('getPath')
			->will($this->returnValue('/dummypath'));

		return $node;
	}

	private function applyDefaultProps($path = '/dummypath') {
		// properties to set
		$propPatch = new \Sabre\DAV\PropPatch(array(
			'customprop' => 'value1',
			'customprop2' => 'value2',
		));

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
		$this->tree->expects($this->at(0))
			->method('getNodeForPath')
			->with('/dummypath')
			->will($this->throwException(new \Sabre\DAV\Exception\NotFound()));

		$this->tree->expects($this->at(1))
			->method('getNodeForPath')
			->with('/dummypath')
			->will($this->throwException(new \Sabre\DAV\Exception\ServiceUnavailable()));

		$propFind = new \Sabre\DAV\PropFind(
			'/dummypath',
			array(
				'customprop',
				'customprop2',
				'unsetprop',
			),
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

		// no exception, soft fail
		$this->assertTrue(true);
	}

	/**
	 * Test setting/getting properties
	 */
	public function testSetGetPropertiesForFile() {
		$node = $this->createTestNode('\OCA\DAV\Connector\Sabre\File');
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/dummypath')
			->will($this->returnValue($node));

		$this->applyDefaultProps();

		$propFind = new \Sabre\DAV\PropFind(
			'/dummypath',
			array(
				'customprop',
				'customprop2',
				'unsetprop',
			),
			0
		);

		$this->plugin->propFind(
			'/dummypath',
			$propFind
		);

		$this->assertEquals('value1', $propFind->get('customprop'));
		$this->assertEquals('value2', $propFind->get('customprop2'));
		$this->assertEquals(array('unsetprop'), $propFind->get404Properties());
	}

	/**
	 * Test getting properties from directory
	 */
	public function testGetPropertiesForDirectory() {
		$rootNode = $this->createTestNode('\OCA\DAV\Connector\Sabre\Directory');

		$nodeSub = $this->getMockBuilder('\OCA\DAV\Connector\Sabre\File')
			->disableOriginalConstructor()
			->getMock();
		$nodeSub->expects($this->any())
			->method('getId')
			->will($this->returnValue(456));

		$nodeSub->expects($this->any())
			->method('getPath')
			->will($this->returnValue('/dummypath/test.txt'));

		$rootNode->expects($this->once())
			->method('getChildren')
			->will($this->returnValue(array($nodeSub)));

		$this->tree->expects($this->at(0))
			->method('getNodeForPath')
			->with('/dummypath')
			->will($this->returnValue($rootNode));

		$this->tree->expects($this->at(1))
			->method('getNodeForPath')
			->with('/dummypath/test.txt')
			->will($this->returnValue($nodeSub));

		$this->tree->expects($this->at(2))
			->method('getNodeForPath')
			->with('/dummypath')
			->will($this->returnValue($rootNode));

		$this->tree->expects($this->at(3))
			->method('getNodeForPath')
			->with('/dummypath/test.txt')
			->will($this->returnValue($nodeSub));

		$this->applyDefaultProps('/dummypath');
		$this->applyDefaultProps('/dummypath/test.txt');

		$propNames = array(
			'customprop',
			'customprop2',
			'unsetprop',
		);

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
		$this->assertEquals(array('unsetprop'), $propFindRoot->get404Properties());

		$this->assertEquals('value1', $propFindSub->get('customprop'));
		$this->assertEquals('value2', $propFindSub->get('customprop2'));
		$this->assertEquals(array('unsetprop'), $propFindSub->get404Properties());
	}

	/**
	 * Test delete property
	 */
	public function testDeleteProperty() {
		$node = $this->createTestNode('\OCA\DAV\Connector\Sabre\File');
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/dummypath')
			->will($this->returnValue($node));

		$this->applyDefaultProps();

		$propPatch = new \Sabre\DAV\PropPatch(array(
			'customprop' => null,
		));

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
