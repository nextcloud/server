<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\SystemTag;

use OC\SystemTag\SystemTag;
use OCA\DAV\SystemTag\SystemTagNode;
use OCA\DAV\SystemTag\SystemTagPlugin;
use OCA\DAV\SystemTag\SystemTagsByIdCollection;
use OCA\DAV\SystemTag\SystemTagsObjectMappingCollection;
use OCP\Files\IRootFolder;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagAlreadyExistsException;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class SystemTagPluginTest extends \Test\TestCase {
	public const ID_PROPERTYNAME = SystemTagPlugin::ID_PROPERTYNAME;
	public const DISPLAYNAME_PROPERTYNAME = SystemTagPlugin::DISPLAYNAME_PROPERTYNAME;
	public const USERVISIBLE_PROPERTYNAME = SystemTagPlugin::USERVISIBLE_PROPERTYNAME;
	public const USERASSIGNABLE_PROPERTYNAME = SystemTagPlugin::USERASSIGNABLE_PROPERTYNAME;
	public const CANASSIGN_PROPERTYNAME = SystemTagPlugin::CANASSIGN_PROPERTYNAME;
	public const GROUPS_PROPERTYNAME = SystemTagPlugin::GROUPS_PROPERTYNAME;

	private \Sabre\DAV\Server $server;
	private \Sabre\DAV\Tree&MockObject $tree;
	private ISystemTagManager&MockObject $tagManager;
	private IGroupManager&MockObject $groupManager;
	private IUserSession&MockObject $userSession;
	private IRootFolder&MockObject $rootFolder;
	private IUser&MockObject $user;
	private ISystemTagObjectMapper&MockObject $tagMapper;
	private SystemTagPlugin $plugin;

	protected function setUp(): void {
		parent::setUp();
		$this->tree = $this->createMock(Tree::class);

		$this->server = new \Sabre\DAV\Server($this->tree);

		$this->tagManager = $this->createMock(ISystemTagManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->user = $this->createMock(IUser::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($this->user);
		$this->userSession
			->expects($this->any())
			->method('isLoggedIn')
			->willReturn(true);

		$this->tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);

		$this->plugin = new SystemTagPlugin(
			$this->tagManager,
			$this->groupManager,
			$this->userSession,
			$this->rootFolder,
			$this->tagMapper
		);
		$this->plugin->initialize($this->server);
	}

	public static function getPropertiesDataProvider(): array {
		return [
			[
				new SystemTag('1', 'Test', true, true),
				[],
				[
					self::ID_PROPERTYNAME,
					self::DISPLAYNAME_PROPERTYNAME,
					self::USERVISIBLE_PROPERTYNAME,
					self::USERASSIGNABLE_PROPERTYNAME,
					self::CANASSIGN_PROPERTYNAME,
				],
				[
					self::ID_PROPERTYNAME => '1',
					self::DISPLAYNAME_PROPERTYNAME => 'Test',
					self::USERVISIBLE_PROPERTYNAME => 'true',
					self::USERASSIGNABLE_PROPERTYNAME => 'true',
					self::CANASSIGN_PROPERTYNAME => 'true',
				]
			],
			[
				new SystemTag('1', 'Test', true, false),
				[],
				[
					self::ID_PROPERTYNAME,
					self::DISPLAYNAME_PROPERTYNAME,
					self::USERVISIBLE_PROPERTYNAME,
					self::USERASSIGNABLE_PROPERTYNAME,
					self::CANASSIGN_PROPERTYNAME,
				],
				[
					self::ID_PROPERTYNAME => '1',
					self::DISPLAYNAME_PROPERTYNAME => 'Test',
					self::USERVISIBLE_PROPERTYNAME => 'true',
					self::USERASSIGNABLE_PROPERTYNAME => 'false',
					self::CANASSIGN_PROPERTYNAME => 'false',
				]
			],
			[
				new SystemTag('1', 'Test', true, false),
				['group1', 'group2'],
				[
					self::ID_PROPERTYNAME,
					self::GROUPS_PROPERTYNAME,
				],
				[
					self::ID_PROPERTYNAME => '1',
					self::GROUPS_PROPERTYNAME => 'group1|group2',
				]
			],
			[
				new SystemTag('1', 'Test', true, true),
				['group1', 'group2'],
				[
					self::ID_PROPERTYNAME,
					self::GROUPS_PROPERTYNAME,
				],
				[
					self::ID_PROPERTYNAME => '1',
					// groups only returned when userAssignable is false
					self::GROUPS_PROPERTYNAME => '',
				]
			],
		];
	}

	/**
	 * @dataProvider getPropertiesDataProvider
	 */
	public function testGetProperties(ISystemTag $systemTag, array $groups, array $requestedProperties, array $expectedProperties): void {
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('admin');
		$this->groupManager
			->expects($this->any())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);

		$node = $this->getMockBuilder(SystemTagNode::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getSystemTag')
			->willReturn($systemTag);

		$this->tagManager->expects($this->any())
			->method('canUserAssignTag')
			->willReturn($systemTag->isUserAssignable());

		$this->tagManager->expects($this->any())
			->method('getTagGroups')
			->willReturn($groups);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtag/1')
			->willReturn($node);

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
		$this->assertEquals($expectedProperties, $result[200]);
	}


	public function testGetPropertiesForbidden(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$systemTag = new SystemTag('1', 'Test', true, false);
		$requestedProperties = [
			self::ID_PROPERTYNAME,
			self::GROUPS_PROPERTYNAME,
		];
		$this->user->expects($this->once())
			->method('getUID')
			->willReturn('admin');
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(false);

		$node = $this->getMockBuilder(SystemTagNode::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getSystemTag')
			->willReturn($systemTag);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtag/1')
			->willReturn($node);

		$propFind = new \Sabre\DAV\PropFind(
			'/systemtag/1',
			$requestedProperties,
			0
		);

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);
	}

	public function testUpdatePropertiesAdmin(): void {
		$systemTag = new SystemTag('1', 'Test', true, false);
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('admin');
		$this->groupManager
			->expects($this->any())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);

		$node = $this->getMockBuilder(SystemTagNode::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getSystemTag')
			->willReturn($systemTag);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtag/1')
			->willReturn($node);

		$node->expects($this->once())
			->method('update')
			->with('Test changed', false, true);

		$this->tagManager->expects($this->once())
			->method('setTagGroups')
			->with($systemTag, ['group1', 'group2']);

		// properties to set
		$propPatch = new \Sabre\DAV\PropPatch([
			self::DISPLAYNAME_PROPERTYNAME => 'Test changed',
			self::USERVISIBLE_PROPERTYNAME => 'false',
			self::USERASSIGNABLE_PROPERTYNAME => 'true',
			self::GROUPS_PROPERTYNAME => 'group1|group2',
		]);

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


	public function testUpdatePropertiesForbidden(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$systemTag = new SystemTag('1', 'Test', true, false);
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('admin');
		$this->groupManager
			->expects($this->any())
			->method('isAdmin')
			->with('admin')
			->willReturn(false);

		$node = $this->getMockBuilder(SystemTagNode::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getSystemTag')
			->willReturn($systemTag);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtag/1')
			->willReturn($node);

		$node->expects($this->never())
			->method('update');

		$this->tagManager->expects($this->never())
			->method('setTagGroups');

		// properties to set
		$propPatch = new \Sabre\DAV\PropPatch([
			self::GROUPS_PROPERTYNAME => 'group1|group2',
		]);

		$this->plugin->handleUpdateProperties(
			'/systemtag/1',
			$propPatch
		);

		$propPatch->commit();
	}

	public static function createTagInsufficientPermissionsProvider(): array {
		return [
			[true, false, ''],
			[false, true, ''],
			[true, true, 'group1|group2'],
		];
	}
	/**
	 * @dataProvider createTagInsufficientPermissionsProvider
	 */
	public function testCreateNotAssignableTagAsRegularUser(bool $userVisible, bool $userAssignable, string $groups): void {
		$this->expectException(\Sabre\DAV\Exception\BadRequest::class);
		$this->expectExceptionMessage('Not sufficient permissions');

		$this->user->expects($this->once())
			->method('getUID')
			->willReturn('admin');
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(false);

		$requestData = [
			'name' => 'Test',
			'userVisible' => $userVisible,
			'userAssignable' => $userAssignable,
		];
		if (!empty($groups)) {
			$requestData['groups'] = $groups;
		}
		$requestData = json_encode($requestData);

		$node = $this->createMock(SystemTagsByIdCollection::class);
		$this->tagManager->expects($this->never())
			->method('createTag');
		$this->tagManager->expects($this->never())
			->method('setTagGroups');

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtags')
			->willReturn($node);

		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);

		$request->expects($this->once())
			->method('getPath')
			->willReturn('/systemtags');

		$request->expects($this->once())
			->method('getBodyAsString')
			->willReturn($requestData);

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->willReturn('application/json');

		$this->plugin->httpPost($request, $response);
	}

	public function testCreateTagInByIdCollectionAsRegularUser(): void {
		$systemTag = new SystemTag('1', 'Test', true, false);

		$requestData = json_encode([
			'name' => 'Test',
			'userVisible' => true,
			'userAssignable' => true,
		]);

		$node = $this->createMock(SystemTagsByIdCollection::class);
		$this->tagManager->expects($this->once())
			->method('createTag')
			->with('Test', true, true)
			->willReturn($systemTag);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtags')
			->willReturn($node);

		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);

		$request->expects($this->once())
			->method('getPath')
			->willReturn('/systemtags');

		$request->expects($this->once())
			->method('getBodyAsString')
			->willReturn($requestData);

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->willReturn('application/json');

		$request->expects($this->once())
			->method('getUrl')
			->willReturn('http://example.com/dav/systemtags');

		$response->expects($this->once())
			->method('setHeader')
			->with('Content-Location', 'http://example.com/dav/systemtags/1');

		$this->plugin->httpPost($request, $response);
	}

	public static function createTagProvider(): array {
		return [
			[true, false, ''],
			[false, false, ''],
			[true, false, 'group1|group2'],
		];
	}

	/**
	 * @dataProvider createTagProvider
	 */
	public function testCreateTagInByIdCollection(bool $userVisible, bool $userAssignable, string $groups): void {
		$this->user->expects($this->once())
			->method('getUID')
			->willReturn('admin');
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);

		$systemTag = new SystemTag('1', 'Test', true, false);

		$requestData = [
			'name' => 'Test',
			'userVisible' => $userVisible,
			'userAssignable' => $userAssignable,
		];
		if (!empty($groups)) {
			$requestData['groups'] = $groups;
		}
		$requestData = json_encode($requestData);

		$node = $this->createMock(SystemTagsByIdCollection::class);
		$this->tagManager->expects($this->once())
			->method('createTag')
			->with('Test', $userVisible, $userAssignable)
			->willReturn($systemTag);

		if (!empty($groups)) {
			$this->tagManager->expects($this->once())
				->method('setTagGroups')
				->with($systemTag, explode('|', $groups))
				->willReturn($systemTag);
		} else {
			$this->tagManager->expects($this->never())
				->method('setTagGroups');
		}

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtags')
			->willReturn($node);

		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);

		$request->expects($this->once())
			->method('getPath')
			->willReturn('/systemtags');

		$request->expects($this->once())
			->method('getBodyAsString')
			->willReturn($requestData);

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->willReturn('application/json');

		$request->expects($this->once())
			->method('getUrl')
			->willReturn('http://example.com/dav/systemtags');

		$response->expects($this->once())
			->method('setHeader')
			->with('Content-Location', 'http://example.com/dav/systemtags/1');

		$this->plugin->httpPost($request, $response);
	}

	public static function nodeClassProvider(): array {
		return [
			['\OCA\DAV\SystemTag\SystemTagsByIdCollection'],
			['\OCA\DAV\SystemTag\SystemTagsObjectMappingCollection'],
		];
	}

	public function testCreateTagInMappingCollection(): void {
		$this->user->expects($this->once())
			->method('getUID')
			->willReturn('admin');
		$this->groupManager
			->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);

		$systemTag = new SystemTag('1', 'Test', true, false);

		$requestData = json_encode([
			'name' => 'Test',
			'userVisible' => true,
			'userAssignable' => false,
		]);

		$node = $this->createMock(SystemTagsObjectMappingCollection::class);

		$this->tagManager->expects($this->once())
			->method('createTag')
			->with('Test', true, false)
			->willReturn($systemTag);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtags-relations/files/12')
			->willReturn($node);

		$node->expects($this->once())
			->method('createFile')
			->with(1);

		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);

		$request->expects($this->once())
			->method('getPath')
			->willReturn('/systemtags-relations/files/12');

		$request->expects($this->once())
			->method('getBodyAsString')
			->willReturn($requestData);

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->willReturn('application/json');

		$request->expects($this->once())
			->method('getBaseUrl')
			->willReturn('http://example.com/dav/');

		$response->expects($this->once())
			->method('setHeader')
			->with('Content-Location', 'http://example.com/dav/systemtags/1');

		$this->plugin->httpPost($request, $response);
	}


	public function testCreateTagToUnknownNode(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		$node = $this->createMock(SystemTagsObjectMappingCollection::class);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->will($this->throwException(new \Sabre\DAV\Exception\NotFound()));

		$this->tagManager->expects($this->never())
			->method('createTag');

		$node->expects($this->never())
			->method('createFile');

		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);

		$request->expects($this->once())
			->method('getPath')
			->willReturn('/systemtags-relations/files/12');

		$this->plugin->httpPost($request, $response);
	}

	/**
	 * @dataProvider nodeClassProvider
	 */
	public function testCreateTagConflict(string $nodeClass): void {
		$this->expectException(\Sabre\DAV\Exception\Conflict::class);

		$this->user->expects($this->once())
			->method('getUID')
			->willReturn('admin');
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

		$node = $this->createMock($nodeClass);
		$this->tagManager->expects($this->once())
			->method('createTag')
			->with('Test', true, false)
			->will($this->throwException(new TagAlreadyExistsException('Tag already exists')));

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/systemtags')
			->willReturn($node);

		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);

		$request->expects($this->once())
			->method('getPath')
			->willReturn('/systemtags');

		$request->expects($this->once())
			->method('getBodyAsString')
			->willReturn($requestData);

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->willReturn('application/json');

		$this->plugin->httpPost($request, $response);
	}
}
