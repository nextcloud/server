<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\Files\View;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCA\DAV\Connector\Sabre\FilesReportPlugin as FilesReportPluginImplementation;
use OCP\Accounts\IAccountManager;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IFilenameValidator;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ITagManager;
use OCP\ITags;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\ResponseInterface;

class FilesReportPluginTest extends \Test\TestCase {

	private \Sabre\DAV\Server&MockObject $server;
	private Tree&MockObject $tree;
	private ISystemTagObjectMapper&MockObject $tagMapper;
	private ISystemTagManager&MockObject $tagManager;
	private ITags&MockObject $privateTags;
	private ITagManager&MockObject $privateTagManager;
	private IUserSession&MockObject $userSession;
	private FilesReportPluginImplementation $plugin;
	private View&MockObject $view;
	private IGroupManager&MockObject $groupManager;
	private Folder&MockObject $userFolder;
	private IPreview&MockObject $previewManager;
	private IAppManager&MockObject $appManager;

	protected function setUp(): void {
		parent::setUp();

		$this->tree = $this->createMock(Tree::class);
		$this->view = $this->createMock(View::class);

		$this->server = $this->getMockBuilder(Server::class)
			->setConstructorArgs([$this->tree])
			->onlyMethods(['getRequestUri', 'getBaseUri'])
			->getMock();

		$this->server->expects($this->any())
			->method('getBaseUri')
			->willReturn('http://example.com/owncloud/remote.php/dav');

		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userFolder = $this->createMock(Folder::class);
		$this->previewManager = $this->createMock(IPreview::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->tagManager = $this->createMock(ISystemTagManager::class);
		$this->tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->privateTags = $this->createMock(ITags::class);
		$this->privateTagManager = $this->createMock(ITagManager::class);
		$this->privateTagManager->expects($this->any())
			->method('load')
			->with('files')
			->willReturn($this->privateTags);

		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('testuser');
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);

		$this->plugin = new FilesReportPluginImplementation(
			$this->tree,
			$this->view,
			$this->tagManager,
			$this->tagMapper,
			$this->privateTagManager,
			$this->userSession,
			$this->groupManager,
			$this->userFolder,
			$this->appManager
		);
	}

	public function testOnReportInvalidNode(): void {
		$path = 'totally/unrelated/13';

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->willReturn($this->createMock(INode::class));

		$this->server->expects($this->any())
			->method('getRequestUri')
			->willReturn($path);
		$this->plugin->initialize($this->server);

		$this->assertNull($this->plugin->onReport(FilesReportPluginImplementation::REPORT_NAME, [], '/' . $path));
	}

	public function testOnReportInvalidReportName(): void {
		$path = 'test';

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->willReturn(
				$this->getMockBuilder(INode::class)
					->disableOriginalConstructor()
					->getMock()
			);

		$this->server->expects($this->any())
			->method('getRequestUri')
			->willReturn($path);
		$this->plugin->initialize($this->server);

		$this->assertNull($this->plugin->onReport('{whoever}whatever', [], '/' . $path));
	}

	public function testOnReport(): void {
		$path = 'test';

		$parameters = [
			[
				'name' => '{DAV:}prop',
				'value' => [
					['name' => '{DAV:}getcontentlength', 'value' => ''],
					['name' => '{http://owncloud.org/ns}size', 'value' => ''],
				],
			],
			[
				'name' => '{http://owncloud.org/ns}filter-rules',
				'value' => [
					['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
					['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
				],
			],
		];

		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn(true);

		$reportTargetNode = $this->createMock(Directory::class);
		$reportTargetNode->expects($this->any())
			->method('getPath')
			->willReturn('');

		$response = $this->createMock(ResponseInterface::class);

		$response->expects($this->once())
			->method('setHeader')
			->with('Content-Type', 'application/xml; charset=utf-8');

		$response->expects($this->once())
			->method('setStatus')
			->with(207);

		$response->expects($this->once())
			->method('setBody');

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->willReturn($reportTargetNode);

		$filesNode1 = $this->createMock(File::class);
		$filesNode1->expects($this->any())
			->method('getSize')
			->willReturn(12);
		$filesNode2 = $this->createMock(Folder::class);
		$filesNode2->expects($this->any())
			->method('getSize')
			->willReturn(10);

		$tag123 = $this->createMock(ISystemTag::class);
		$tag123->expects($this->any())
			->method('getName')
			->willReturn('OneTwoThree');
		$tag123->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);
		$tag456 = $this->createMock(ISystemTag::class);
		$tag456->expects($this->any())
			->method('getName')
			->willReturn('FourFiveSix');
		$tag456->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123', '456'])
			->willReturn([$tag123, $tag456]);

		$this->userFolder->expects($this->exactly(2))
			->method('searchBySystemTag')
			->willReturnMap([
				['OneTwoThree', 'testuser', 0, 0, [$filesNode1]],
				['FourFiveSix', 'testuser', 0, 0, [$filesNode2]],
			]);

		$this->server->expects($this->any())
			->method('getRequestUri')
			->willReturn($path);
		$this->server->httpResponse = $response;
		$this->plugin->initialize($this->server);

		$this->assertFalse($this->plugin->onReport(FilesReportPluginImplementation::REPORT_NAME, $parameters, '/' . $path));
	}

	public function testFindNodesByFileIdsRoot(): void {
		$filesNode1 = $this->createMock(Folder::class);
		$filesNode1->expects($this->once())
			->method('getName')
			->willReturn('first node');

		$filesNode2 = $this->createMock(File::class);
		$filesNode2->expects($this->once())
			->method('getName')
			->willReturn('second node');

		$reportTargetNode = $this->createMock(Directory::class);
		$reportTargetNode->expects($this->any())
			->method('getPath')
			->willReturn('/');

		$this->userFolder->expects($this->exactly(2))
			->method('getFirstNodeById')
			->willReturnMap([
				[111, $filesNode1],
				[222, $filesNode2],
			]);

		/** @var Directory&MockObject $reportTargetNode */
		$result = $this->plugin->findNodesByFileIds($reportTargetNode, ['111', '222']);

		$this->assertCount(2, $result);
		$this->assertInstanceOf(Directory::class, $result[0]);
		$this->assertEquals('first node', $result[0]->getName());
		$this->assertInstanceOf(\OCA\DAV\Connector\Sabre\File::class, $result[1]);
		$this->assertEquals('second node', $result[1]->getName());
	}

	public function testFindNodesByFileIdsSubDir(): void {
		$filesNode1 = $this->createMock(Folder::class);
		$filesNode1->expects($this->once())
			->method('getName')
			->willReturn('first node');

		$filesNode2 = $this->createMock(File::class);
		$filesNode2->expects($this->once())
			->method('getName')
			->willReturn('second node');

		$reportTargetNode = $this->createMock(Directory::class);
		$reportTargetNode->expects($this->any())
			->method('getPath')
			->willReturn('/sub1/sub2');


		$subNode = $this->createMock(Folder::class);

		$this->userFolder->expects($this->once())
			->method('get')
			->with('/sub1/sub2')
			->willReturn($subNode);

		$subNode->expects($this->exactly(2))
			->method('getFirstNodeById')
			->willReturnMap([
				[111, $filesNode1],
				[222, $filesNode2],
			]);

		/** @var Directory&MockObject $reportTargetNode */
		$result = $this->plugin->findNodesByFileIds($reportTargetNode, ['111', '222']);

		$this->assertCount(2, $result);
		$this->assertInstanceOf(Directory::class, $result[0]);
		$this->assertEquals('first node', $result[0]->getName());
		$this->assertInstanceOf(\OCA\DAV\Connector\Sabre\File::class, $result[1]);
		$this->assertEquals('second node', $result[1]->getName());
	}

	public function testPrepareResponses(): void {
		$requestedProps = ['{DAV:}getcontentlength', '{http://owncloud.org/ns}fileid', '{DAV:}resourcetype'];

		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->method('isReadable')->willReturn(true);

		$node1 = $this->createMock(Directory::class);
		$node2 = $this->createMock(\OCA\DAV\Connector\Sabre\File::class);

		$node1->expects($this->once())
			->method('getInternalFileId')
			->willReturn('111');
		$node1->expects($this->any())
			->method('getPath')
			->willReturn('/node1');
		$node1->method('getFileInfo')->willReturn($fileInfo);
		$node2->expects($this->once())
			->method('getInternalFileId')
			->willReturn('222');
		$node2->expects($this->once())
			->method('getSize')
			->willReturn(1024);
		$node2->expects($this->any())
			->method('getPath')
			->willReturn('/sub/node2');
		$node2->method('getFileInfo')->willReturn($fileInfo);

		$config = $this->createMock(IConfig::class);
		$validator = $this->createMock(IFilenameValidator::class);
		$accountManager = $this->createMock(IAccountManager::class);

		$this->server->addPlugin(
			new FilesPlugin(
				$this->tree,
				$config,
				$this->createMock(IRequest::class),
				$this->previewManager,
				$this->createMock(IUserSession::class),
				$validator,
				$accountManager,
			)
		);
		$this->plugin->initialize($this->server);
		$responses = $this->plugin->prepareResponses('/files/username', $requestedProps, [$node1, $node2]);

		$this->assertCount(2, $responses);

		$this->assertEquals('http://example.com/owncloud/remote.php/dav/files/username/node1', $responses[0]->getHref());
		$this->assertEquals('http://example.com/owncloud/remote.php/dav/files/username/sub/node2', $responses[1]->getHref());

		$props1 = $responses[0]->getResponseProperties();
		$this->assertEquals('111', $props1[200]['{http://owncloud.org/ns}fileid']);
		$this->assertNull($props1[404]['{DAV:}getcontentlength']);
		$this->assertInstanceOf('\Sabre\DAV\Xml\Property\ResourceType', $props1[200]['{DAV:}resourcetype']);
		$resourceType1 = $props1[200]['{DAV:}resourcetype']->getValue();
		$this->assertEquals('{DAV:}collection', $resourceType1[0]);

		$props2 = $responses[1]->getResponseProperties();
		$this->assertEquals('1024', $props2[200]['{DAV:}getcontentlength']);
		$this->assertEquals('222', $props2[200]['{http://owncloud.org/ns}fileid']);
		$this->assertInstanceOf('\Sabre\DAV\Xml\Property\ResourceType', $props2[200]['{DAV:}resourcetype']);
		$this->assertCount(0, $props2[200]['{DAV:}resourcetype']->getValue());
	}

	public function testProcessFilterRulesSingle(): void {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn(true);

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
		];

		$filesNode1 = $this->createMock(File::class);
		$filesNode1->expects($this->any())
			->method('getSize')
			->willReturn(12);
		$filesNode2 = $this->createMock(Folder::class);
		$filesNode2->expects($this->any())
			->method('getSize')
			->willReturn(10);

		$tag123 = $this->createMock(ISystemTag::class);
		$tag123->expects($this->any())
			->method('getName')
			->willReturn('OneTwoThree');
		$tag123->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123'])
			->willReturn([$tag123]);

		$this->userFolder->expects($this->once())
			->method('searchBySystemTag')
			->with('OneTwoThree')
			->willReturn([$filesNode1, $filesNode2]);

		$this->assertEquals([$filesNode1, $filesNode2], self::invokePrivate($this->plugin, 'processFilterRulesForFileNodes', [$rules, 0, 0]));
	}

	public function testProcessFilterRulesAndCondition(): void {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn(true);

		$filesNode1 = $this->createMock(File::class);
		$filesNode1->expects($this->any())
			->method('getSize')
			->willReturn(12);
		$filesNode1->expects($this->any())
			->method('getId')
			->willReturn(111);
		$filesNode2 = $this->createMock(Folder::class);
		$filesNode2->expects($this->any())
			->method('getSize')
			->willReturn(10);
		$filesNode2->expects($this->any())
			->method('getId')
			->willReturn(222);
		$filesNode3 = $this->createMock(File::class);
		$filesNode3->expects($this->any())
			->method('getSize')
			->willReturn(14);
		$filesNode3->expects($this->any())
			->method('getId')
			->willReturn(333);

		$tag123 = $this->createMock(ISystemTag::class);
		$tag123->expects($this->any())
			->method('getName')
			->willReturn('OneTwoThree');
		$tag123->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);
		$tag456 = $this->createMock(ISystemTag::class);
		$tag456->expects($this->any())
			->method('getName')
			->willReturn('FourFiveSix');
		$tag456->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123', '456'])
			->willReturn([$tag123, $tag456]);

		$this->userFolder->expects($this->exactly(2))
			->method('searchBySystemTag')
			->willReturnMap([
				['OneTwoThree', 'testuser', 0, 0, [$filesNode1, $filesNode2]],
				['FourFiveSix', 'testuser', 0, 0, [$filesNode2, $filesNode3]],
			]);

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
		];

		$this->assertEquals([$filesNode2], array_values(self::invokePrivate($this->plugin, 'processFilterRulesForFileNodes', [$rules, null, null])));
	}

	public function testProcessFilterRulesAndConditionWithOneEmptyResult(): void {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn(true);

		$filesNode1 = $this->createMock(File::class);
		$filesNode1->expects($this->any())
			->method('getSize')
			->willReturn(12);
		$filesNode1->expects($this->any())
			->method('getId')
			->willReturn(111);
		$filesNode2 = $this->createMock(Folder::class);
		$filesNode2->expects($this->any())
			->method('getSize')
			->willReturn(10);
		$filesNode2->expects($this->any())
			->method('getId')
			->willReturn(222);

		$tag123 = $this->createMock(ISystemTag::class);
		$tag123->expects($this->any())
			->method('getName')
			->willReturn('OneTwoThree');
		$tag123->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);
		$tag456 = $this->createMock(ISystemTag::class);
		$tag456->expects($this->any())
			->method('getName')
			->willReturn('FourFiveSix');
		$tag456->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123', '456'])
			->willReturn([$tag123, $tag456]);

		$this->userFolder->expects($this->exactly(2))
			->method('searchBySystemTag')
			->willReturnMap([
				['OneTwoThree', 'testuser', 0, 0, [$filesNode1, $filesNode2]],
				['FourFiveSix', 'testuser', 0, 0, []],
			]);

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
		];

		$this->assertEquals([], self::invokePrivate($this->plugin, 'processFilterRulesForFileNodes', [$rules, null, null]));
	}

	public function testProcessFilterRulesAndConditionWithFirstEmptyResult(): void {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn(true);

		$filesNode1 = $this->createMock(File::class);
		$filesNode1->expects($this->any())
			->method('getSize')
			->willReturn(12);
		$filesNode1->expects($this->any())
			->method('getId')
			->willReturn(111);
		$filesNode2 = $this->createMock(Folder::class);
		$filesNode2->expects($this->any())
			->method('getSize')
			->willReturn(10);
		$filesNode2->expects($this->any())
			->method('getId')
			->willReturn(222);

		$tag123 = $this->createMock(ISystemTag::class);
		$tag123->expects($this->any())
			->method('getName')
			->willReturn('OneTwoThree');
		$tag123->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);
		$tag456 = $this->createMock(ISystemTag::class);
		$tag456->expects($this->any())
			->method('getName')
			->willReturn('FourFiveSix');
		$tag456->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123', '456'])
			->willReturn([$tag123, $tag456]);

		$this->userFolder->expects($this->once())
			->method('searchBySystemTag')
			->willReturnMap([
				['OneTwoThree', 'testuser', 0, 0, []],
			]);

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
		];

		$this->assertEquals([], self::invokePrivate($this->plugin, 'processFilterRulesForFileNodes', [$rules, null, null]));
	}

	public function testProcessFilterRulesAndConditionWithEmptyMidResult(): void {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn(true);

		$filesNode1 = $this->createMock(File::class);
		$filesNode1->expects($this->any())
			->method('getSize')
			->willReturn(12);
		$filesNode1->expects($this->any())
			->method('getId')
			->willReturn(111);
		$filesNode2 = $this->createMock(Folder::class);
		$filesNode2->expects($this->any())
			->method('getSize')
			->willReturn(10);
		$filesNode2->expects($this->any())
			->method('getId')
			->willReturn(222);
		$filesNode3 = $this->createMock(Folder::class);
		$filesNode3->expects($this->any())
			->method('getSize')
			->willReturn(13);
		$filesNode3->expects($this->any())
			->method('getId')
			->willReturn(333);

		$tag123 = $this->createMock(ISystemTag::class);
		$tag123->expects($this->any())
			->method('getName')
			->willReturn('OneTwoThree');
		$tag123->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);
		$tag456 = $this->createMock(ISystemTag::class);
		$tag456->expects($this->any())
			->method('getName')
			->willReturn('FourFiveSix');
		$tag456->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);
		$tag789 = $this->createMock(ISystemTag::class);
		$tag789->expects($this->any())
			->method('getName')
			->willReturn('SevenEightNine');
		$tag789->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123', '456', '789'])
			->willReturn([$tag123, $tag456, $tag789]);

		$this->userFolder->expects($this->exactly(2))
			->method('searchBySystemTag')
			->willReturnMap([
				['OneTwoThree', 'testuser', 0, 0, [$filesNode1, $filesNode2]],
				['FourFiveSix', 'testuser', 0, 0, [$filesNode3]],
			]);

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '789'],
		];

		$this->assertEquals([], array_values(self::invokePrivate($this->plugin, 'processFilterRulesForFileNodes', [$rules, null, null])));
	}

	public function testProcessFilterRulesInvisibleTagAsAdmin(): void {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn(true);

		$filesNode1 = $this->createMock(File::class);
		$filesNode1->expects($this->any())
			->method('getSize')
			->willReturn(12);
		$filesNode1->expects($this->any())
			->method('getId')
			->willReturn(111);
		$filesNode2 = $this->createMock(Folder::class);
		$filesNode2->expects($this->any())
			->method('getSize')
			->willReturn(10);
		$filesNode2->expects($this->any())
			->method('getId')
			->willReturn(222);
		$filesNode3 = $this->createMock(Folder::class);
		$filesNode3->expects($this->any())
			->method('getSize')
			->willReturn(13);
		$filesNode3->expects($this->any())
			->method('getId')
			->willReturn(333);

		$tag123 = $this->createMock(ISystemTag::class);
		$tag123->expects($this->any())
			->method('getName')
			->willReturn('OneTwoThree');
		$tag123->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);
		$tag456 = $this->createMock(ISystemTag::class);
		$tag456->expects($this->any())
			->method('getName')
			->willReturn('FourFiveSix');
		$tag456->expects($this->any())
			->method('isUserVisible')
			->willReturn(false);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123', '456'])
			->willReturn([$tag123, $tag456]);

		$this->userFolder->expects($this->exactly(2))
			->method('searchBySystemTag')
			->willReturnMap([
				['OneTwoThree', 'testuser', 0, 0, [$filesNode1, $filesNode2]],
				['FourFiveSix', 'testuser', 0, 0, [$filesNode2, $filesNode3]],
			]);

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
		];

		$this->assertEquals([$filesNode2], array_values(self::invokePrivate($this->plugin, 'processFilterRulesForFileNodes', [$rules, null, null])));
	}


	public function testProcessFilterRulesInvisibleTagAsUser(): void {
		$this->expectException(TagNotFoundException::class);

		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn(false);

		$tag123 = $this->createMock(ISystemTag::class);
		$tag123->expects($this->any())
			->method('getName')
			->willReturn('OneTwoThree');
		$tag123->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);
		$tag456 = $this->createMock(ISystemTag::class);
		$tag456->expects($this->any())
			->method('getName')
			->willReturn('FourFiveSix');
		$tag456->expects($this->any())
			->method('isUserVisible')
			->willReturn(false);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123', '456'])
			->willThrowException(new TagNotFoundException());

		$this->userFolder->expects($this->never())
			->method('searchBySystemTag');

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
		];

		self::invokePrivate($this->plugin, 'processFilterRulesForFileNodes', [$rules, null, null]);
	}

	public function testProcessFilterRulesVisibleTagAsUser(): void {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn(false);

		$tag1 = $this->createMock(ISystemTag::class);
		$tag1->expects($this->any())
			->method('getId')
			->willReturn('123');
		$tag1->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);
		$tag1->expects($this->any())
			->method('getName')
			->willReturn('OneTwoThree');

		$tag2 = $this->createMock(ISystemTag::class);
		$tag2->expects($this->any())
			->method('getId')
			->willReturn('123');
		$tag2->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);
		$tag2->expects($this->any())
			->method('getName')
			->willReturn('FourFiveSix');

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123', '456'])
			->willReturn([$tag1, $tag2]);

		$filesNode1 = $this->createMock(File::class);
		$filesNode1->expects($this->any())
			->method('getId')
			->willReturn(111);
		$filesNode1->expects($this->any())
			->method('getSize')
			->willReturn(12);
		$filesNode2 = $this->createMock(Folder::class);
		$filesNode2->expects($this->any())
			->method('getId')
			->willReturn(222);
		$filesNode2->expects($this->any())
			->method('getSize')
			->willReturn(10);
		$filesNode3 = $this->createMock(Folder::class);
		$filesNode3->expects($this->any())
			->method('getId')
			->willReturn(333);
		$filesNode3->expects($this->any())
			->method('getSize')
			->willReturn(33);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123', '456'])
			->willReturn([$tag1, $tag2]);

		// main assertion: only user visible tags are being passed through.
		$this->userFolder->expects($this->exactly(2))
			->method('searchBySystemTag')
			->willReturnMap([
				['OneTwoThree', 'testuser', 0, 0, [$filesNode1, $filesNode2]],
				['FourFiveSix', 'testuser', 0, 0, [$filesNode2, $filesNode3]],
			]);

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
		];

		$this->assertEquals([$filesNode2], array_values(self::invokePrivate($this->plugin, 'processFilterRulesForFileNodes', [$rules, null, null])));
	}

	public function testProcessFavoriteFilter(): void {
		$rules = [
			['name' => '{http://owncloud.org/ns}favorite', 'value' => '1'],
		];

		$this->privateTags->expects($this->once())
			->method('getFavorites')
			->willReturn(['456', '789']);

		$this->assertEquals(['456', '789'], array_values(self::invokePrivate($this->plugin, 'processFilterRulesForFileIDs', [$rules])));
	}

	public static function filesBaseUriProvider(): array {
		return [
			['', '', ''],
			['files/username', '', '/files/username'],
			['files/username/test', '/test', '/files/username'],
			['files/username/test/sub', '/test/sub', '/files/username'],
			['test', '/test', ''],
		];
	}

	/**
	 * @dataProvider filesBaseUriProvider
	 */
	public function testFilesBaseUri(string $uri, string $reportPath, string $expectedUri): void {
		$this->assertEquals($expectedUri, self::invokePrivate($this->plugin, 'getFilesBaseUri', [$uri, $reportPath]));
	}
}
