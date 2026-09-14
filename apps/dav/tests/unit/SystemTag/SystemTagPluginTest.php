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
use OCA\DAV\SystemTag\SystemTagFragmentCache;
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
use Sabre\Xml\Writer;

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

	public function testFolderPropFindLoadsTagsOnceAndOrdersThem(): void {
		$tags = [
			'1' => new SystemTag('1', 'img2', true, true),
			'2' => new SystemTag('2', 'img10', true, true),
			'4' => new SystemTag('4', 'Hidden', false, false),
			'5' => new SystemTag('5', 'img3', true, true),
			'6' => new SystemTag('6', 'img3', true, false),
		];

		$this->tagMapper->expects($this->once())
			->method('getTagIdsForObjects')
			->with(['10', '11', '12', '13'], 'files')
			->willReturn([
				'11' => ['6', '2', '5', '4', '1'],
				'12' => ['5', '6'],
			]);
		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->willReturnCallback(fn (array $tagIds): array => array_intersect_key($tags, array_flip($tagIds)));
		$this->tagManager->expects($this->any())
			->method('canUserSeeTag')
			->willReturnCallback(fn (ISystemTag $tag): bool => $tag->isUserVisible());
		$this->tagManager->expects($this->any())
			->method('canUserAssignTag')
			->willReturn(true);

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

		$this->assertSame(['1', '6', '5', '2'], $this->tagIdsFromPropFind($file1));
		$this->assertSame(['5', '6'], $this->tagIdsFromPropFind($file2));
		$this->assertSame([], $this->tagIdsFromPropFind($untagged));
	}

	/**
	 * @return string[]
	 */
	private function tagIdsFromPropFind(Node $file): array {
		$propFind = new PropFind('/files/user/folder/file', [SystemTagPlugin::SYSTEM_TAGS_PROPERTYNAME], 0);
		$this->plugin->handleGetProperties($propFind, $file);
		$list = $propFind->get(SystemTagPlugin::SYSTEM_TAGS_PROPERTYNAME);
		$this->assertInstanceOf(SystemTagList::class, $list);
		return array_map(fn (ISystemTag $tag): string => $tag->getId(), $list->getTags());
	}

	public function testSystemTagListSerializationMatchesElementWriter(): void {
		$tags = [
			new SystemTag('7', 'a<b>&"c', true, true, null, 'ff0000'),
			new SystemTag('8', 'plain', true, false),
		];
		$this->tagManager->expects($this->any())
			->method('canUserAssignTag')
			->willReturnCallback(fn (ISystemTag $tag): bool => $tag->isUserAssignable());

		$expected = $this->newWriter();
		foreach ($tags as $tag) {
			$expected->startElement('{http://nextcloud.org/ns}system-tag');
			$expected->writeAttributes([
				SystemTagPlugin::CANASSIGN_PROPERTYNAME => $tag->isUserAssignable() ? 'true' : 'false',
				SystemTagPlugin::ID_PROPERTYNAME => $tag->getId(),
				SystemTagPlugin::USERASSIGNABLE_PROPERTYNAME => $tag->isUserAssignable() ? 'true' : 'false',
				SystemTagPlugin::USERVISIBLE_PROPERTYNAME => $tag->isUserVisible() ? 'true' : 'false',
				SystemTagPlugin::COLOR_PROPERTYNAME => $tag->getColor() ?? '',
			]);
			$expected->write($tag->getName());
			$expected->endElement();
		}
		$expected->endElement();
		$expectedXml = $expected->outputMemory();
		$this->assertStringContainsString('a&lt;b&gt;&amp;&quot;c', $expectedXml);

		$list = new SystemTagList($tags, $this->tagManager, $this->user);
		$actual = $this->newWriter();
		$list->xmlSerialize($actual);
		$actual->endElement();
		$this->assertSame($expectedXml, $actual->outputMemory());

		$secondRun = $this->newWriter();
		$list->xmlSerialize($secondRun);
		$secondRun->endElement();
		$this->assertSame($expectedXml, $secondRun->outputMemory());
	}

	public function testSystemTagListSerializationDependsOnCanAssign(): void {
		$tag = new SystemTag('9', 'shared', true, true);
		$assignable = $this->createMock(ISystemTagManager::class);
		$assignable->method('canUserAssignTag')->willReturn(true);
		$notAssignable = $this->createMock(ISystemTagManager::class);
		$notAssignable->method('canUserAssignTag')->willReturn(false);

		$sharedFragments = new SystemTagFragmentCache();

		$writer = $this->newWriter();
		(new SystemTagList([$tag], $assignable, $this->user, $sharedFragments))->xmlSerialize($writer);
		$writer->endElement();
		$this->assertStringContainsString('oc:can-assign="true"', $writer->outputMemory());

		$writer = $this->newWriter();
		(new SystemTagList([$tag], $notAssignable, $this->user, $sharedFragments))->xmlSerialize($writer);
		$writer->endElement();
		$this->assertStringContainsString('oc:can-assign="false"', $writer->outputMemory());
	}

	public function testSystemTagListSerializationWithoutKnownPrefixes(): void {
		$tag = new SystemTag('7', 'a<b>&"c', true, true, null, 'ff0000');
		$this->tagManager->expects($this->any())
			->method('canUserAssignTag')
			->willReturn(true);

		$expected = $this->newWriter([]);
		$expected->startElement('{http://nextcloud.org/ns}system-tag');
		$expected->writeAttributes([
			SystemTagPlugin::CANASSIGN_PROPERTYNAME => 'true',
			SystemTagPlugin::ID_PROPERTYNAME => '7',
			SystemTagPlugin::USERASSIGNABLE_PROPERTYNAME => 'true',
			SystemTagPlugin::USERVISIBLE_PROPERTYNAME => 'true',
			SystemTagPlugin::COLOR_PROPERTYNAME => 'ff0000',
		]);
		$expected->write($tag->getName());
		$expected->endElement();
		$expected->endElement();

		$actual = $this->newWriter([]);
		(new SystemTagList([$tag], $this->tagManager, $this->user))->xmlSerialize($actual);
		$actual->endElement();
		$this->assertSame($expected->outputMemory(), $actual->outputMemory());
	}

	/**
	 * @param array<string,string>|null $namespaceMap null uses the prefixes the DAV server registers
	 */
	private function newWriter(?array $namespaceMap = null): Writer {
		$writer = new Writer();
		$writer->namespaceMap = $namespaceMap ?? [
			'DAV:' => 'd',
			'http://owncloud.org/ns' => 'oc',
			'http://nextcloud.org/ns' => 'nc',
		];
		$writer->openMemory();
		$writer->startElement('{http://nextcloud.org/ns}system-tags');
		return $writer;
	}
}
