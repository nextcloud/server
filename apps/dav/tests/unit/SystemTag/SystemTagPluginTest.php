<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Tests\unit\SystemTag;

use OC\SystemTag\SystemTag;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Node;
use OCA\DAV\SystemTag\SystemTagList;
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
use Sabre\DAV\PropFind;
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

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'getPropertiesDataProvider')]
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
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'createTagInsufficientPermissionsProvider')]
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

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'createTagProvider')]
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
			->willThrowException(new \Sabre\DAV\Exception\NotFound());

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

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'nodeClassProvider')]
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
			->willThrowException(new TagAlreadyExistsException('Tag already exists'));

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

	public function testFolderPropFindPreloadsSortedVisibleTags(): void {
		$tags = [
			'1' => new SystemTag('1', 'img2', true, true),
			'2' => new SystemTag('2', 'img10', true, true),
			'4' => new SystemTag('4', 'Hidden', false, false),
			'5' => new SystemTag('5', 'img3', true, true),
			'6' => new SystemTag('6', 'img3', true, false),
			'7' => new SystemTag('7', 'a<b>&"c', true, true, null, 'ff0000'),
		];
		$this->tagMapper->expects($this->once())
			->method('getTagIdsForObjects')
			->with(['10', '11', '12', '13'], 'files')
			->willReturn([
				'10' => ['1'],
				'11' => ['6', '2', '5', '4', '1', '7'],
				'12' => ['5', '6'],
				'13' => [],
			]);
		$requestedTagIds = null;
		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->willReturnCallback(function (array $tagIds) use ($tags, &$requestedTagIds): array {
				$requestedTagIds = array_map('strval', $tagIds);
				return ['7' => $tags['7'], '6' => $tags['6'], '5' => $tags['5'], '4' => $tags['4'], '2' => $tags['2'], '1' => $tags['1']];
			});
		$this->tagManager->expects($this->any())
			->method('canUserSeeTag')
			->willReturnCallback(fn (ISystemTag $tag): bool => $tag->isUserVisible());
		$this->tagManager->expects($this->any())
			->method('canUserAssignTag')
			->willReturnCallback(fn (ISystemTag $tag): bool => $tag->isUserAssignable());

		$folder = $this->createMock(Directory::class);
		$folder->method('getId')->willReturn(10);
		$file1 = $this->createMock(Node::class);
		$file1->method('getId')->willReturn(11);
		$file2 = $this->createMock(Node::class);
		$file2->method('getId')->willReturn(12);
		$untagged = $this->createMock(Node::class);
		$untagged->method('getId')->willReturn(13);
		$folder->method('getChildren')->willReturn([$file1, $file2, $untagged]);

		$folderPropFind = new PropFind('/files/user/folder', [SystemTagPlugin::SYSTEM_TAGS_PROPERTYNAME], 1);
		$this->server->emit('preloadCollection', [$folderPropFind, $folder]);
		$this->assertEqualsCanonicalizing(['1', '2', '4', '5', '6', '7'], $requestedTagIds);

		$this->assertSame(['1'], $this->tagIds($this->systemTagListFromPropFind($folder)));
		$list1 = $this->systemTagListFromPropFind($file1);
		$this->assertSame(['7', '1', '5', '6', '2'], $this->tagIds($list1));
		$this->assertSame(['5', '6'], $this->tagIds($this->systemTagListFromPropFind($file2)));
		$this->assertSame([], $this->tagIds($this->systemTagListFromPropFind($untagged)));

		$this->assertSame(
			'<nc:system-tag oc:can-assign="true" oc:id="7" oc:user-assignable="true" oc:user-visible="true" nc:color="ff0000">a&lt;b&gt;&amp;&quot;c</nc:system-tag>'
			. '<nc:system-tag oc:can-assign="true" oc:id="1" oc:user-assignable="true" oc:user-visible="true" nc:color="">img2</nc:system-tag>'
			. '<nc:system-tag oc:can-assign="true" oc:id="5" oc:user-assignable="true" oc:user-visible="true" nc:color="">img3</nc:system-tag>'
			. '<nc:system-tag oc:can-assign="false" oc:id="6" oc:user-assignable="false" oc:user-visible="true" nc:color="">img3</nc:system-tag>'
			. '<nc:system-tag oc:can-assign="true" oc:id="2" oc:user-assignable="true" oc:user-visible="true" nc:color="">img10</nc:system-tag>',
			$this->serializedTags($list1),
		);
	}

	public function testSingleFileAndNestedFoldersReuseLoadedTags(): void {
		$tags = [
			'1' => new SystemTag('1', 'img2', true, true),
			'2' => new SystemTag('2', 'img1', true, true),
			'3' => new SystemTag('3', 'img3', true, true),
			'4' => new SystemTag('4', 'img4', true, true),
		];
		$this->tagMapper->expects($this->exactly(3))
			->method('getTagIdsForObjects')
			->willReturnCallback(fn (array $fileIds): array => match ($fileIds) {
				['20'] => ['20' => ['1', '2']],
				['30', '31'] => ['30' => [], '31' => ['2', '3']],
				['31', '32'] => ['31' => ['2', '3'], '32' => ['4', '1']],
				default => self::fail('unexpected file ids ' . json_encode($fileIds)),
			});
		$requestedTagIds = [];
		$this->tagManager->expects($this->exactly(3))
			->method('getTagsByIds')
			->willReturnCallback(function (array $tagIds) use ($tags, &$requestedTagIds): array {
				$requestedTagIds[] = array_map('strval', $tagIds);
				return array_intersect_key($tags, array_flip($tagIds));
			});
		$this->tagManager->expects($this->any())
			->method('canUserSeeTag')
			->willReturn(true);
		$this->tagManager->expects($this->any())
			->method('canUserAssignTag')
			->willReturn(true);

		$file = $this->createMock(Node::class);
		$file->method('getId')->willReturn(20);
		$this->assertSame(['2', '1'], $this->tagIds($this->systemTagListFromPropFind($file)));

		$folder = $this->createMock(Directory::class);
		$folder->method('getId')->willReturn(30);
		$subfolder = $this->createMock(Directory::class);
		$subfolder->method('getId')->willReturn(31);
		$folder->method('getChildren')->willReturn([$subfolder]);
		$grandchild = $this->createMock(Node::class);
		$grandchild->method('getId')->willReturn(32);
		$subfolder->method('getChildren')->willReturn([$grandchild]);
		$folderPropFind = new PropFind('/files/user/folder', [SystemTagPlugin::SYSTEM_TAGS_PROPERTYNAME], 'infinity');
		$this->server->emit('preloadCollection', [$folderPropFind, $folder]);
		$this->server->emit('preloadCollection', [$folderPropFind, $folder]);
		$this->server->emit('preloadCollection', [$folderPropFind, $subfolder]);

		$subfolderList = $this->systemTagListFromPropFind($subfolder);
		$this->assertSame(['2', '3'], $this->tagIds($subfolderList));
		$this->assertSame(['1', '4'], $this->tagIds($this->systemTagListFromPropFind($grandchild)));
		$this->assertSame([['1', '2'], ['3'], ['4']], $requestedTagIds);
		$this->assertSame(
			'<nc:system-tag oc:can-assign="true" oc:id="2" oc:user-assignable="true" oc:user-visible="true" nc:color="">img1</nc:system-tag>'
			. '<nc:system-tag oc:can-assign="true" oc:id="3" oc:user-assignable="true" oc:user-visible="true" nc:color="">img3</nc:system-tag>',
			$this->serializedTags($subfolderList),
		);
	}

	private function systemTagListFromPropFind(Node $node): SystemTagList {
		$propFind = new PropFind('/files/user/folder/file', [SystemTagPlugin::SYSTEM_TAGS_PROPERTYNAME], 0);
		$this->plugin->handleGetProperties($propFind, $node);
		$list = $propFind->get(SystemTagPlugin::SYSTEM_TAGS_PROPERTYNAME);
		$this->assertInstanceOf(SystemTagList::class, $list);
		return $list;
	}

	/**
	 * @return string[]
	 */
	private function tagIds(SystemTagList $list): array {
		return array_map(fn (ISystemTag $tag): string => $tag->getId(), $list->getTags());
	}

	/**
	 * The system-tag elements as the response writer emits them, without the enclosing property element
	 */
	private function serializedTags(SystemTagList $list): string {
		$writer = $this->server->xml->getWriter();
		$writer->openMemory();
		$writer->startElement(SystemTagPlugin::SYSTEM_TAGS_PROPERTYNAME);
		$list->xmlSerialize($writer);
		$writer->endElement();
		$xml = $writer->outputMemory();
		$this->assertMatchesRegularExpression('#^<nc:system-tags( xmlns:[a-z]+="[^"]*")+>.*</nc:system-tags>$#s', $xml);
		return preg_replace('#^<nc:system-tags[^>]*>|</nc:system-tags>$#', '', $xml);
	}
}
