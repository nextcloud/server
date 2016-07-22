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
namespace OCA\DAV\Tests\Unit\Connector\Sabre;

use OCP\Files\StorageNotAvailableException;

/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class FilesPlugin extends \Test\TestCase {
	const GETETAG_PROPERTYNAME = \OCA\DAV\Connector\Sabre\FilesPlugin::GETETAG_PROPERTYNAME;
	const FILEID_PROPERTYNAME = \OCA\DAV\Connector\Sabre\FilesPlugin::FILEID_PROPERTYNAME;
	const INTERNAL_FILEID_PROPERTYNAME = \OCA\DAV\Connector\Sabre\FilesPlugin::INTERNAL_FILEID_PROPERTYNAME;
	const SIZE_PROPERTYNAME = \OCA\DAV\Connector\Sabre\FilesPlugin::SIZE_PROPERTYNAME;
	const PERMISSIONS_PROPERTYNAME = \OCA\DAV\Connector\Sabre\FilesPlugin::PERMISSIONS_PROPERTYNAME;
	const LASTMODIFIED_PROPERTYNAME = \OCA\DAV\Connector\Sabre\FilesPlugin::LASTMODIFIED_PROPERTYNAME;
	const DOWNLOADURL_PROPERTYNAME = \OCA\DAV\Connector\Sabre\FilesPlugin::DOWNLOADURL_PROPERTYNAME;
	const OWNER_ID_PROPERTYNAME = \OCA\DAV\Connector\Sabre\FilesPlugin::OWNER_ID_PROPERTYNAME;
	const OWNER_DISPLAY_NAME_PROPERTYNAME = \OCA\DAV\Connector\Sabre\FilesPlugin::OWNER_DISPLAY_NAME_PROPERTYNAME;

	/**
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @var \Sabre\DAV\Tree
	 */
	private $tree;

	/**
	 * @var \OCA\DAV\Connector\Sabre\FilesPlugin
	 */
	private $plugin;

	/**
	 * @var \OC\Files\View
	 */
	private $view;

	public function setUp() {
		parent::setUp();
		$this->server = $this->getMockBuilder('\Sabre\DAV\Server')
			->disableOriginalConstructor()
			->getMock();
		$this->tree = $this->getMockBuilder('\Sabre\DAV\Tree')
			->disableOriginalConstructor()
			->getMock();
		$this->view = $this->getMockBuilder('\OC\Files\View')
			->disableOriginalConstructor()
			->getMock();
		$request = $this->getMock('\OCP\IRequest');

		$this->plugin = new \OCA\DAV\Connector\Sabre\FilesPlugin(
			$this->tree,
			$this->view,
			$request
		);
		$this->plugin->initialize($this->server);
	}

	/**
	 * @param string $class
	 * @param bool $hasAllMethods
	 * @return \PHPUnit_Framework_MockObject_MockObject|\Sabre\DAV\IFile
	 */
	private function createTestNode($class, $hasAllMethods = true)
	{
		$node = $this->getMockBuilder($class)
			->disableOriginalConstructor()
			->getMock();

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/dummypath')
			->will($this->returnValue($node));

		if ($hasAllMethods) {
			$node->expects($this->any())
				->method('getId')
				->will($this->returnValue(123));
			$node->expects($this->any())
				->method('getFileId')
				->will($this->returnValue('00000123instanceid'));
			$node->expects($this->any())
				->method('getInternalFileId')
				->will($this->returnValue('123'));
			$node->expects($this->any())
				->method('getEtag')
				->will($this->returnValue('"abc"'));
			$node->expects($this->any())
				->method('getDavPermissions')
				->will($this->returnValue('DWCKMSR'));
		}

		return $node;
	}

	public function testGetPropertiesForFile() {
		$node = $this->createTestNode('\OCA\DAV\Connector\Sabre\File');

		$propFind = new \Sabre\DAV\PropFind(
			'/dummyPath',
			array(
				self::GETETAG_PROPERTYNAME,
				self::FILEID_PROPERTYNAME,
				self::INTERNAL_FILEID_PROPERTYNAME,
				self::SIZE_PROPERTYNAME,
				self::PERMISSIONS_PROPERTYNAME,
				self::DOWNLOADURL_PROPERTYNAME,
				self::OWNER_ID_PROPERTYNAME,
				self::OWNER_DISPLAY_NAME_PROPERTYNAME
			),
			0
		);

		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('foo'));
		$user
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('M. Foo'));

		$node->expects($this->once())
			->method('getDirectDownload')
			->will($this->returnValue(array('url' => 'http://example.com/')));
		$node->expects($this->exactly(2))
			->method('getOwner')
			->will($this->returnValue($user));
		$node->expects($this->never())
			->method('getSize');

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$this->assertEquals('"abc"', $propFind->get(self::GETETAG_PROPERTYNAME));
		$this->assertEquals('00000123instanceid', $propFind->get(self::FILEID_PROPERTYNAME));
		$this->assertEquals('123', $propFind->get(self::INTERNAL_FILEID_PROPERTYNAME));
		$this->assertEquals(null, $propFind->get(self::SIZE_PROPERTYNAME));
		$this->assertEquals('DWCKMSR', $propFind->get(self::PERMISSIONS_PROPERTYNAME));
		$this->assertEquals('http://example.com/', $propFind->get(self::DOWNLOADURL_PROPERTYNAME));
		$this->assertEquals('foo', $propFind->get(self::OWNER_ID_PROPERTYNAME));
		$this->assertEquals('M. Foo', $propFind->get(self::OWNER_DISPLAY_NAME_PROPERTYNAME));
		$this->assertEquals(array(self::SIZE_PROPERTYNAME), $propFind->get404Properties());
	}

	public function testGetPropertiesForFileHome() {
		$node = $this->createTestNode('\OCA\DAV\Files\FilesHome', false);

		$propFind = new \Sabre\DAV\PropFind(
			'/dummyPath',
			array(
				self::GETETAG_PROPERTYNAME,
				self::FILEID_PROPERTYNAME,
				self::INTERNAL_FILEID_PROPERTYNAME,
				self::SIZE_PROPERTYNAME,
				self::PERMISSIONS_PROPERTYNAME,
				self::DOWNLOADURL_PROPERTYNAME,
				self::OWNER_ID_PROPERTYNAME,
				self::OWNER_DISPLAY_NAME_PROPERTYNAME
			),
			0
		);

		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user->expects($this->never())->method('getUID');
		$user->expects($this->never())->method('getDisplayName');
		// Method does not exist: $node->expects($this->never())->method('getDirectDownload');
		// Method does not exist: $node->expects($this->never())->method('getOwner');
		// Method does not exist: $node->expects($this->never())->method('getSize');

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$this->assertEquals(null, $propFind->get(self::GETETAG_PROPERTYNAME));
		$this->assertEquals(null, $propFind->get(self::FILEID_PROPERTYNAME));
		$this->assertEquals(null, $propFind->get(self::INTERNAL_FILEID_PROPERTYNAME));
		$this->assertEquals(null, $propFind->get(self::SIZE_PROPERTYNAME));
		$this->assertEquals(null, $propFind->get(self::PERMISSIONS_PROPERTYNAME));
		$this->assertEquals(null, $propFind->get(self::DOWNLOADURL_PROPERTYNAME));
		$this->assertEquals(null, $propFind->get(self::OWNER_ID_PROPERTYNAME));
		$this->assertEquals(null, $propFind->get(self::OWNER_DISPLAY_NAME_PROPERTYNAME));
		$this->assertEquals(['{DAV:}getetag',
					'{http://owncloud.org/ns}id',
					'{http://owncloud.org/ns}fileid',
					'{http://owncloud.org/ns}size',
					'{http://owncloud.org/ns}permissions',
					'{http://owncloud.org/ns}downloadURL',
					'{http://owncloud.org/ns}owner-id',
					'{http://owncloud.org/ns}owner-display-name'
				], $propFind->get404Properties());
	}

	public function testGetPropertiesStorageNotAvailable() {
		$node = $this->createTestNode('\OCA\DAV\Connector\Sabre\File');

		$propFind = new \Sabre\DAV\PropFind(
			'/dummyPath',
			array(
				self::DOWNLOADURL_PROPERTYNAME,
			),
			0
		);

		$node->expects($this->once())
			->method('getDirectDownload')
			->will($this->throwException(new StorageNotAvailableException()));

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$this->assertEquals(null, $propFind->get(self::DOWNLOADURL_PROPERTYNAME));
	}

	public function testGetPublicPermissions() {
		$this->plugin = new \OCA\DAV\Connector\Sabre\FilesPlugin(
			$this->tree,
			$this->view,
			$this->getMock('\OCP\IRequest'),
			true);
		$this->plugin->initialize($this->server);

		$propFind = new \Sabre\DAV\PropFind(
			'/dummyPath',
			[
				self::PERMISSIONS_PROPERTYNAME,
			],
			0
		);

		$node = $this->createTestNode('\OCA\DAV\Connector\Sabre\File');
		$node->expects($this->any())
			->method('getDavPermissions')
			->will($this->returnValue('DWCKMSR'));

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$this->assertEquals('DWCKR', $propFind->get(self::PERMISSIONS_PROPERTYNAME));
	}

	public function testGetPropertiesForDirectory() {
		$node = $this->createTestNode('\OCA\DAV\Connector\Sabre\Directory');

		$propFind = new \Sabre\DAV\PropFind(
			'/dummyPath',
			array(
				self::GETETAG_PROPERTYNAME,
				self::FILEID_PROPERTYNAME,
				self::SIZE_PROPERTYNAME,
				self::PERMISSIONS_PROPERTYNAME,
				self::DOWNLOADURL_PROPERTYNAME,
			),
			0
		);

		// Method does not exist: $node->expects($this->never())->method('getDirectDownload');
		$node->expects($this->once())
			->method('getSize')
			->will($this->returnValue(1025));

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$this->assertEquals('"abc"', $propFind->get(self::GETETAG_PROPERTYNAME));
		$this->assertEquals('00000123instanceid', $propFind->get(self::FILEID_PROPERTYNAME));
		$this->assertEquals(1025, $propFind->get(self::SIZE_PROPERTYNAME));
		$this->assertEquals('DWCKMSR', $propFind->get(self::PERMISSIONS_PROPERTYNAME));
		$this->assertEquals(null, $propFind->get(self::DOWNLOADURL_PROPERTYNAME));
		$this->assertEquals(array(self::DOWNLOADURL_PROPERTYNAME), $propFind->get404Properties());
	}

	public function testUpdateProps() {
		$node = $this->createTestNode('\OCA\DAV\Connector\Sabre\File');

		$testDate = 'Fri, 13 Feb 2015 00:01:02 GMT';

		$node->expects($this->once())
			->method('touch')
			->with($testDate);

		$node->expects($this->once())
			->method('setEtag')
			->with('newetag')
			->will($this->returnValue(true));

		// properties to set
		$propPatch = new \Sabre\DAV\PropPatch(array(
			self::GETETAG_PROPERTYNAME => 'newetag',
			self::LASTMODIFIED_PROPERTYNAME => $testDate
		));

		$this->plugin->handleUpdateProperties(
			'/dummypath',
			$propPatch
		);

		$propPatch->commit();

		$this->assertEmpty($propPatch->getRemainingMutations());

		$result = $propPatch->getResult();
		$this->assertEquals(200, $result[self::LASTMODIFIED_PROPERTYNAME]);
		$this->assertEquals(200, $result[self::GETETAG_PROPERTYNAME]);
	}

	public function testUpdatePropsForbidden() {
		$node = $this->createTestNode('\OCA\DAV\Connector\Sabre\File');

		$propPatch = new \Sabre\DAV\PropPatch(array(
			self::OWNER_ID_PROPERTYNAME => 'user2',
			self::OWNER_DISPLAY_NAME_PROPERTYNAME => 'User Two',
			self::FILEID_PROPERTYNAME => 12345,
			self::PERMISSIONS_PROPERTYNAME => 'C',
			self::SIZE_PROPERTYNAME => 123,
			self::DOWNLOADURL_PROPERTYNAME => 'http://example.com/',
		));

		$this->plugin->handleUpdateProperties(
			'/dummypath',
			$propPatch
		);

		$propPatch->commit();

		$this->assertEmpty($propPatch->getRemainingMutations());

		$result = $propPatch->getResult();
		$this->assertEquals(403, $result[self::OWNER_ID_PROPERTYNAME]);
		$this->assertEquals(403, $result[self::OWNER_DISPLAY_NAME_PROPERTYNAME]);
		$this->assertEquals(403, $result[self::FILEID_PROPERTYNAME]);
		$this->assertEquals(403, $result[self::PERMISSIONS_PROPERTYNAME]);
		$this->assertEquals(403, $result[self::SIZE_PROPERTYNAME]);
		$this->assertEquals(403, $result[self::DOWNLOADURL_PROPERTYNAME]);
	}

	/**
	 * Testcase from https://github.com/owncloud/core/issues/5251
	 *
	 * |-FolderA
	 *  |-text.txt
	 * |-test.txt
	 *
	 * FolderA is an incomming shared folder and there are no delete permissions.
	 * Thus moving /FolderA/test.txt to /test.txt should fail already on that check
	 *
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage FolderA/test.txt cannot be deleted
	 */
	public function testMoveSrcNotDeletable() {
		$fileInfoFolderATestTXT = $this->getMockBuilder('\OCP\Files\FileInfo')
			->disableOriginalConstructor()
			->getMock();
		$fileInfoFolderATestTXT->expects($this->once())
			->method('isDeletable')
			->willReturn(false);

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('FolderA/test.txt')
			->willReturn($fileInfoFolderATestTXT);

		$this->plugin->checkMove('FolderA/test.txt', 'test.txt');
	}

	public function testMoveSrcDeletable() {
		$fileInfoFolderATestTXT = $this->getMockBuilder('\OCP\Files\FileInfo')
			->disableOriginalConstructor()
			->getMock();
		$fileInfoFolderATestTXT->expects($this->once())
			->method('isDeletable')
			->willReturn(true);

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('FolderA/test.txt')
			->willReturn($fileInfoFolderATestTXT);

		$this->plugin->checkMove('FolderA/test.txt', 'test.txt');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\NotFound
	 * @expectedExceptionMessage FolderA/test.txt does not exist
	 */
	public function testMoveSrcNotExist() {
		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('FolderA/test.txt')
			->willReturn(false);

		$this->plugin->checkMove('FolderA/test.txt', 'test.txt');
	}
}
