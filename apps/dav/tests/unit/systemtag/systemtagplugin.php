<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\DAV\Tests\Unit\SystemTag;

use OC\SystemTag\SystemTag;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\SystemTag\TagAlreadyExistsException;

class SystemTagPlugin extends \Test\TestCase {

	const ID_PROPERTYNAME = \OCA\DAV\SystemTag\SystemTagPlugin::ID_PROPERTYNAME;
	const DISPLAYNAME_PROPERTYNAME = \OCA\DAV\SystemTag\SystemTagPlugin::DISPLAYNAME_PROPERTYNAME;
	const USERVISIBLE_PROPERTYNAME = \OCA\DAV\SystemTag\SystemTagPlugin::USERVISIBLE_PROPERTYNAME;
	const USERASSIGNABLE_PROPERTYNAME = \OCA\DAV\SystemTag\SystemTagPlugin::USERASSIGNABLE_PROPERTYNAME;

	/**
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @var \Sabre\DAV\Tree
	 */
	private $tree;

	/**
	 * @var \OCP\SystemTag\ISystemTagManager
	 */
	private $tagManager;

	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * @var \OCA\DAV\SystemTag\SystemTagPlugin
	 */
	private $plugin;

	public function setUp() {
		parent::setUp();
		$this->tree = $this->getMockBuilder('\Sabre\DAV\Tree')
			->disableOriginalConstructor()
			->getMock();

		$this->server = new \Sabre\DAV\Server($this->tree);

		$this->tagManager = $this->getMock('\OCP\SystemTag\ISystemTagManager');
		$this->groupManager = $this->getMock('\OCP\IGroupManager');
		$this->userSession = $this->getMock('\OCP\IUserSession');

		$this->plugin = new \OCA\DAV\SystemTag\SystemTagPlugin(
			$this->tagManager,
			$this->groupManager,
			$this->userSession
		);
		$this->plugin->initialize($this->server);
	}

	public function testGetProperties() {
		$systemTag = new SystemTag(1, 'Test', true, true);
		$requestedProperties = [
			self::ID_PROPERTYNAME,
			self::DISPLAYNAME_PROPERTYNAME,
			self::USERVISIBLE_PROPERTYNAME,
			self::USERASSIGNABLE_PROPERTYNAME
		];
		$expectedProperties = [
			200 => [
				self::ID_PROPERTYNAME => '1',
				self::DISPLAYNAME_PROPERTYNAME => 'Test',
				self::USERVISIBLE_PROPERTYNAME => 'true',
				self::USERASSIGNABLE_PROPERTYNAME => 'true',
			]
		];

		$node = $this->getMockBuilder('\OCA\DAV\SystemTag\SystemTagNode')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getSystemTag')
			->will($this->returnValue($systemTag));

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtag/1')
			->will($this->returnValue($node));

		$propFind = new \Sabre\DAV\PropFind(
			'/systemtag/1',
			$requestedProperties,
			0
		);

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$result = $propFind->getResultForMultiStatus();

		$this->assertEmpty($result[404]);
		unset($result[404]);
		$this->assertEquals($expectedProperties, $result);
	}

	public function testUpdateProperties() {
		$systemTag = new SystemTag(1, 'Test', true, false);
		$node = $this->getMockBuilder('\OCA\DAV\SystemTag\SystemTagNode')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getSystemTag')
			->will($this->returnValue($systemTag));

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtag/1')
			->will($this->returnValue($node));

		$node->expects($this->once())
			->method('update')
			->with('Test changed', false, true);

		// properties to set
		$propPatch = new \Sabre\DAV\PropPatch(array(
			self::DISPLAYNAME_PROPERTYNAME => 'Test changed',
			self::USERVISIBLE_PROPERTYNAME => 'false',
			self::USERASSIGNABLE_PROPERTYNAME => 'true',
		));

		$this->plugin->handleUpdateProperties(
			'/systemtag/1',
			$propPatch
		);

		$propPatch->commit();

		// all requested properties removed, as they were processed already
		$this->assertEmpty($propPatch->getRemainingMutations());

		$result = $propPatch->getResult();
		$this->assertEquals(200, $result[self::DISPLAYNAME_PROPERTYNAME]);
		$this->assertEquals(200, $result[self::USERASSIGNABLE_PROPERTYNAME]);
		$this->assertEquals(200, $result[self::USERVISIBLE_PROPERTYNAME]);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 * @expectedExceptionMessage Not sufficient permissions
	 */
	public function testCreateNotAssignableTagAsRegularUser() {
		$user = $this->getMock('\OCP\IUser');
		$user->expects($this->once())
			->method('getUID')
			->willReturn('admin');
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(false);

		$requestData = json_encode([
			'name' => 'Test',
			'userVisible' => true,
			'userAssignable' => false,
		]);

		$node = $this->getMockBuilder('\OCA\DAV\SystemTag\SystemTagsByIdCollection')
			->disableOriginalConstructor()
			->getMock();
		$this->tagManager->expects($this->never())
			->method('createTag');

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtags')
			->will($this->returnValue($node));

		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();
		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/systemtags'));

		$request->expects($this->once())
			->method('getBodyAsString')
			->will($this->returnValue($requestData));

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue('application/json'));

		$this->plugin->httpPost($request, $response);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 * @expectedExceptionMessage Not sufficient permissions
	 */
	public function testCreateInvisibleTagAsRegularUser() {
		$user = $this->getMock('\OCP\IUser');
		$user->expects($this->once())
			->method('getUID')
			->willReturn('admin');
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(false);

		$requestData = json_encode([
			'name' => 'Test',
			'userVisible' => false,
			'userAssignable' => true,
		]);

		$node = $this->getMockBuilder('\OCA\DAV\SystemTag\SystemTagsByIdCollection')
			->disableOriginalConstructor()
			->getMock();
		$this->tagManager->expects($this->never())
			->method('createTag');

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtags')
			->will($this->returnValue($node));

		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();
		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/systemtags'));

		$request->expects($this->once())
			->method('getBodyAsString')
			->will($this->returnValue($requestData));

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue('application/json'));

		$this->plugin->httpPost($request, $response);
	}

	public function testCreateTagInByIdCollectionAsRegularUser() {
		$systemTag = new SystemTag(1, 'Test', true, false);

		$requestData = json_encode([
			'name' => 'Test',
			'userVisible' => true,
			'userAssignable' => true,
		]);

		$node = $this->getMockBuilder('\OCA\DAV\SystemTag\SystemTagsByIdCollection')
			->disableOriginalConstructor()
			->getMock();
		$this->tagManager->expects($this->once())
			->method('createTag')
			->with('Test', true, true)
			->will($this->returnValue($systemTag));

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtags')
			->will($this->returnValue($node));

		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();
		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/systemtags'));

		$request->expects($this->once())
			->method('getBodyAsString')
			->will($this->returnValue($requestData));

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue('application/json'));

		$request->expects($this->once())
			->method('getUrl')
			->will($this->returnValue('http://example.com/dav/systemtags'));

		$response->expects($this->once())
			->method('setHeader')
			->with('Content-Location', 'http://example.com/dav/systemtags/1');

		$this->plugin->httpPost($request, $response);
	}

	public function testCreateTagInByIdCollection() {
		$user = $this->getMock('\OCP\IUser');
		$user->expects($this->once())
			->method('getUID')
			->willReturn('admin');
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);

		$systemTag = new SystemTag(1, 'Test', true, false);

		$requestData = json_encode([
			'name' => 'Test',
			'userVisible' => true,
			'userAssignable' => false,
		]);

		$node = $this->getMockBuilder('\OCA\DAV\SystemTag\SystemTagsByIdCollection')
			->disableOriginalConstructor()
			->getMock();
		$this->tagManager->expects($this->once())
			->method('createTag')
			->with('Test', true, false)
			->will($this->returnValue($systemTag));

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtags')
			->will($this->returnValue($node));

		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
				->disableOriginalConstructor()
				->getMock();
		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
				->disableOriginalConstructor()
				->getMock();

		$request->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/systemtags'));

		$request->expects($this->once())
			->method('getBodyAsString')
			->will($this->returnValue($requestData));

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue('application/json'));	

		$request->expects($this->once())
			->method('getUrl')
			->will($this->returnValue('http://example.com/dav/systemtags'));

		$response->expects($this->once())
			->method('setHeader')
			->with('Content-Location', 'http://example.com/dav/systemtags/1');

		$this->plugin->httpPost($request, $response);
	}

	public function nodeClassProvider() {
		return [
			['\OCA\DAV\SystemTag\SystemTagsByIdCollection'],
			['\OCA\DAV\SystemTag\SystemTagsObjectMappingCollection'],
		];
	}

	public function testCreateTagInMappingCollection() {
		$user = $this->getMock('\OCP\IUser');
		$user->expects($this->once())
			->method('getUID')
			->willReturn('admin');
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);

		$systemTag = new SystemTag(1, 'Test', true, false);

		$requestData = json_encode([
			'name' => 'Test',
			'userVisible' => true,
			'userAssignable' => false,
		]);

		$node = $this->getMockBuilder('\OCA\DAV\SystemTag\SystemTagsObjectMappingCollection')
			->disableOriginalConstructor()
			->getMock();

		$this->tagManager->expects($this->once())
			->method('createTag')
			->with('Test', true, false)
			->will($this->returnValue($systemTag));

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtags-relations/files/12')
			->will($this->returnValue($node));

		$node->expects($this->once())
			->method('createFile')
			->with(1);

		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
				->disableOriginalConstructor()
				->getMock();
		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
				->disableOriginalConstructor()
				->getMock();

		$request->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/systemtags-relations/files/12'));

		$request->expects($this->once())
			->method('getBodyAsString')
			->will($this->returnValue($requestData));

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue('application/json'));	

		$request->expects($this->once())
			->method('getBaseUrl')
			->will($this->returnValue('http://example.com/dav/'));

		$response->expects($this->once())
			->method('setHeader')
			->with('Content-Location', 'http://example.com/dav/systemtags/1');

		$this->plugin->httpPost($request, $response);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\NotFound
	 */
	public function testCreateTagToUnknownNode() {
		$systemTag = new SystemTag(1, 'Test', true, false);

		$node = $this->getMockBuilder('\OCA\DAV\SystemTag\SystemTagsObjectMappingCollection')
			->disableOriginalConstructor()
			->getMock();

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->will($this->throwException(new \Sabre\DAV\Exception\NotFound()));

		$this->tagManager->expects($this->never())
			->method('createTag');

		$node->expects($this->never())
			->method('createFile');

		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
				->disableOriginalConstructor()
				->getMock();
		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
				->disableOriginalConstructor()
				->getMock();

		$request->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/systemtags-relations/files/12'));

		$this->plugin->httpPost($request, $response);
	}

	/**
	 * @dataProvider nodeClassProvider
	 * @expectedException \Sabre\DAV\Exception\Conflict
	 */
	public function testCreateTagConflict($nodeClass) {
		$user = $this->getMock('\OCP\IUser');
		$user->expects($this->once())
			->method('getUID')
			->willReturn('admin');
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);

		$requestData = json_encode([
			'name' => 'Test',
			'userVisible' => true,
			'userAssignable' => false,
		]);

		$node = $this->getMockBuilder($nodeClass)
			->disableOriginalConstructor()
			->getMock();
		$this->tagManager->expects($this->once())
			->method('createTag')
			->with('Test', true, false)
			->will($this->throwException(new TagAlreadyExistsException('Tag already exists')));

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtags')
			->will($this->returnValue($node));

		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
				->disableOriginalConstructor()
				->getMock();
		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
				->disableOriginalConstructor()
				->getMock();

		$request->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/systemtags'));

		$request->expects($this->once())
			->method('getBodyAsString')
			->will($this->returnValue($requestData));

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue('application/json'));	

		$this->plugin->httpPost($request, $response);
	}

}
