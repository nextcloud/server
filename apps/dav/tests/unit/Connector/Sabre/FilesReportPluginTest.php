<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

use OC\Files\View;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File as SabreFile;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCA\DAV\Connector\Sabre\FilesReportPlugin as FilesReportPluginImplementation;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
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
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\PreconditionFailed;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\DAV\Xml\Property\ResourceType;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class FilesReportPluginTest extends TestCase {
	/** @var Server|MockObject */
	private $server;

	/** @var Tree|MockObject */
	private $tree;

	/** @var ISystemTagObjectMapper|MockObject */
	private $tagMapper;

	/** @var ISystemTagManager|MockObject */
	private $tagManager;

	/** @var ITags|MockObject */
	private $privateTags;

	/** @var FilesReportPluginImplementation */
	private $plugin;

	/** @var IGroupManager|MockObject **/
	private $groupManager;

	/** @var Folder|MockObject **/
	private $userFolder;

	/** @var IPreview|MockObject * */
	private $previewManager;

	protected function setUp(): void {
		parent::setUp();
		$this->tree = $this->createMock(Tree::class);
		$view = $this->createMock(View::class);
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
		/** @var IAppManager|MockObject $appManager */
		$appManager = $this->createMock(IAppManager::class);
		$this->tagManager = $this->createMock(ISystemTagManager::class);
		$this->tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		$userSession = $this->createMock(IUserSession::class);
		$this->privateTags = $this->createMock(ITags::class);
		$privateTagManager = $this->createMock(ITagManager::class);
		$privateTagManager->expects($this->any())
			->method('load')
			->with('files')
			->willReturn($this->privateTags);

		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('testuser');
		$userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);

		$this->plugin = new FilesReportPluginImplementation(
			$this->tree,
			$view,
			$this->tagManager,
			$this->tagMapper,
			$privateTagManager,
			$userSession,
			$this->groupManager,
			$this->userFolder,
			$appManager
		);
	}

	/**
	 * @throws PreconditionFailed
	 * @throws BadRequest
	 * @throws NotFound
	 * @throws NotFoundException
	 */
	public function testOnReportInvalidNode() {
		$path = 'totally/unrelated/13';

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->willReturn(
				$this->createMock(INode::class)
			);

		$this->server->expects($this->any())
			->method('getRequestUri')
			->willReturn($path);
		$this->plugin->initialize($this->server);

		$this->assertNull($this->plugin->onReport(FilesReportPluginImplementation::REPORT_NAME, [], '/' . $path));
	}

	/**
	 * @throws PreconditionFailed
	 * @throws BadRequest
	 * @throws NotFound
	 * @throws NotFoundException
	 */
	public function testOnReportInvalidReportName() {
		$path = 'test';

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->willReturn(
				$this->createMock(INode::class)
			);

		$this->server->expects($this->any())
			->method('getRequestUri')
			->willReturn($path);
		$this->plugin->initialize($this->server);

		$this->assertNull($this->plugin->onReport('{whoever}whatever', [], '/' . $path));
	}

	/**
	 * @throws PreconditionFailed
	 * @throws BadRequest
	 * @throws NotFound
	 * @throws NotFoundException
	 */
	public function testOnReport() {
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

		$this->tagMapper->expects($this->exactly(2))
			->method('getObjectIdsForTags')
			->withConsecutive(
				['123', 'files'],
				['456', 'files']
			)
			->willReturnOnConsecutiveCalls(
				['111', '222'],
				['111', '222', '333']
			);

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

		$filesNode1 = $this->createMock(Folder::class);
		$filesNode2 = $this->createMock(File::class);

		$this->userFolder->expects($this->exactly(2))
			->method('getById')
			->withConsecutive(
				['111'], ['222']
			)->willReturnOnConsecutiveCalls(
				[$filesNode1], [$filesNode2]
			);

		$this->server->expects($this->any())
			->method('getRequestUri')
			->willReturn($path);
		$this->server->httpResponse = $response;
		$this->plugin->initialize($this->server);

		$this->assertFalse($this->plugin->onReport(FilesReportPluginImplementation::REPORT_NAME, $parameters, '/' . $path));
	}

	/**
	 * @throws NotFoundException
	 */
	public function testFindNodesByFileIdsRoot() {
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
			->method('getById')
			->withConsecutive(
				['111'], ['222']
			)->willReturnOnConsecutiveCalls(
				[$filesNode1], [$filesNode2]
			);

		/** @var Directory|MockObject $reportTargetNode */
		$result = $this->plugin->findNodesByFileIds($reportTargetNode, ['111', '222']);

		$this->assertCount(2, $result);
		$this->assertInstanceOf(Directory::class, $result[0]);
		$this->assertEquals('first node', $result[0]->getName());
		$this->assertInstanceOf(SabreFile::class, $result[1]);
		$this->assertEquals('second node', $result[1]->getName());
	}

	/**
	 * @throws NotFoundException
	 */
	public function testFindNodesByFileIdsSubDir() {
		$filesNode1 = $this->createMock(Folder::class);
		$filesNode1->expects($this->once())
			->method('getName')
			->willReturn('first node');

		$filesNode2 = $this->createMock(File::class);
		$filesNode2->expects($this->once())
			->method('getName')
			->willReturn('second node');

		$reportTargetNode = $this->createMock(Directory::class);
		$reportTargetNode->expects($this->exactly(2))
			->method('getPath')
			->willReturn('/sub1/sub2');


		$subNode = $this->createMock(Folder::class);

		$this->userFolder->expects($this->once())
			->method('get')
			->with('/sub1/sub2')
			->willReturn($subNode);

		$subNode->expects($this->exactly(2))
			->method('getById')
			->withConsecutive(
				['111'], ['222']
			)->willReturnOnConsecutiveCalls(
				[$filesNode1], [$filesNode2]
			);

		/** @var Directory|MockObject $reportTargetNode */
		$result = $this->plugin->findNodesByFileIds($reportTargetNode, ['111', '222']);

		$this->assertCount(2, $result);
		$this->assertInstanceOf(Directory::class, $result[0]);
		$this->assertEquals('first node', $result[0]->getName());
		$this->assertInstanceOf(SabreFile::class, $result[1]);
		$this->assertEquals('second node', $result[1]->getName());
	}

	public function testPrepareResponses() {
		$requestedProps = ['{DAV:}getcontentlength', '{http://owncloud.org/ns}fileid', '{DAV:}resourcetype'];

		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->method('isReadable')->willReturn(true);

		$node1 = $this->createMock(Directory::class);
		$node2 = $this->createMock(SabreFile::class);

		$node1->expects($this->once())
			->method('getInternalFileId')
			->willReturn(111);
		$node1->expects($this->any())
			->method('getPath')
			->willReturn('/node1');
		$node1->method('getFileInfo')->willReturn($fileInfo);
		$node2->expects($this->once())
			->method('getInternalFileId')
			->willReturn(222);
		$node2->expects($this->once())
			->method('getSize')
			->willReturn(1024);
		$node2->expects($this->any())
			->method('getPath')
			->willReturn('/sub/node2');
		$node2->method('getFileInfo')->willReturn($fileInfo);

		$config = $this->createMock(IConfig::class);

		$this->server->addPlugin(
			new FilesPlugin(
				$this->tree,
				$config,
				$this->createMock(IRequest::class),
				$this->previewManager,
				$this->createMock(IUserSession::class)
			)
		);
		$this->plugin->initialize($this->server);
		$responses = $this->plugin->prepareResponses('/files/username', $requestedProps, [$node1, $node2]);

		$this->assertCount(2, $responses);

		$this->assertEquals(200, $responses[0]->getHttpStatus());
		$this->assertEquals(200, $responses[1]->getHttpStatus());

		$this->assertEquals('http://example.com/owncloud/remote.php/dav/files/username/node1', $responses[0]->getHref());
		$this->assertEquals('http://example.com/owncloud/remote.php/dav/files/username/sub/node2', $responses[1]->getHref());

		$props1 = $responses[0]->getResponseProperties();
		$this->assertEquals('111', $props1[200]['{http://owncloud.org/ns}fileid']);
		$this->assertNull($props1[404]['{DAV:}getcontentlength']);
		$this->assertInstanceOf(ResourceType::class, $props1[200]['{DAV:}resourcetype']);
		$resourceType1 = $props1[200]['{DAV:}resourcetype']->getValue();
		$this->assertEquals('{DAV:}collection', $resourceType1[0]);

		$props2 = $responses[1]->getResponseProperties();
		$this->assertEquals('1024', $props2[200]['{DAV:}getcontentlength']);
		$this->assertEquals('222', $props2[200]['{http://owncloud.org/ns}fileid']);
		$this->assertInstanceOf(ResourceType::class, $props2[200]['{DAV:}resourcetype']);
		$this->assertCount(0, $props2[200]['{DAV:}resourcetype']->getValue());
	}

	public function testProcessFilterRulesSingle() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn(true);

		$this->tagMapper->expects($this->exactly(1))
			->method('getObjectIdsForTags')
			->withConsecutive(
				['123', 'files']
			)
			->willReturnMap([
				['123', 'files', 0, '', ['111', '222']],
			]);

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
		];

		$this->assertEquals(['111', '222'], $this->invokePrivate($this->plugin, 'processFilterRules', [$rules]));
	}

	public function testProcessFilterRulesAndCondition() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn(true);

		$this->tagMapper->expects($this->exactly(2))
			->method('getObjectIdsForTags')
			->withConsecutive(
				['123', 'files'],
				['456', 'files']
			)
			->willReturnMap([
				['123', 'files', 0, '', ['111', '222']],
				['456', 'files', 0, '', ['222', '333']],
			]);

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
		];

		$this->assertEquals(['222'], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}

	public function testProcessFilterRulesAndConditionWithOneEmptyResult() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn(true);

		$this->tagMapper->expects($this->exactly(2))
			->method('getObjectIdsForTags')
			->withConsecutive(
				['123', 'files'],
				['456', 'files']
			)
			->willReturnMap([
				['123', 'files', 0, '', ['111', '222']],
				['456', 'files', 0, '', []],
			]);

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
		];

		$this->assertEquals([], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}

	public function testProcessFilterRulesAndConditionWithFirstEmptyResult() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn(true);

		$this->tagMapper->expects($this->exactly(1))
			->method('getObjectIdsForTags')
			->withConsecutive(
				['123', 'files'],
				['456', 'files']
			)
			->willReturnMap([
				['123', 'files', 0, '', []],
				['456', 'files', 0, '', ['111', '222']],
			]);

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
		];

		$this->assertEquals([], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}

	public function testProcessFilterRulesAndConditionWithEmptyMidResult() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn(true);

		$this->tagMapper->expects($this->exactly(2))
			->method('getObjectIdsForTags')
			->withConsecutive(
				['123', 'files'],
				['456', 'files'],
				['789', 'files']
			)
			->willReturnMap([
				['123', 'files', 0, '', ['111', '222']],
				['456', 'files', 0, '', ['333']],
				['789', 'files', 0, '', ['111', '222']],
			]);

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '789'],
		];

		$this->assertEquals([], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}

	public function testProcessFilterRulesInvisibleTagAsAdmin() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn(true);

		$tag1 = $this->createMock(ISystemTag::class);
		$tag1->expects($this->any())
			->method('getId')
			->willReturn('123');
		$tag1->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);

		$tag2 = $this->createMock(ISystemTag::class);
		$tag2->expects($this->any())
			->method('getId')
			->willReturn('123');
		$tag2->expects($this->any())
			->method('isUserVisible')
			->willReturn(false);

		// no need to fetch tags to check permissions
		$this->tagManager->expects($this->never())
			->method('getTagsByIds');

		$this->tagMapper->expects($this->exactly(2))
			->method('getObjectIdsForTags')
			->withConsecutive(['123'], ['456'])
			->willReturnOnConsecutiveCalls(['111', '222'], ['222', '333']);

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
		];

		$this->assertEquals(['222'], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}


	public function testProcessFilterRulesInvisibleTagAsUser() {
		$this->expectException(TagNotFoundException::class);

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

		$tag2 = $this->createMock(ISystemTag::class);
		$tag2->expects($this->any())
			->method('getId')
			->willReturn('123');
		$tag2->expects($this->any())
			->method('isUserVisible')
			->willReturn(false); // invisible

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123', '456'])
			->willReturn([$tag1, $tag2]);

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
		];

		$this->invokePrivate($this->plugin, 'processFilterRules', [$rules]);
	}

	public function testProcessFilterRulesVisibleTagAsUser() {
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

		$tag2 = $this->createMock(ISystemTag::class);
		$tag2->expects($this->any())
			->method('getId')
			->willReturn('123');
		$tag2->expects($this->any())
			->method('isUserVisible')
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123', '456'])
			->willReturn([$tag1, $tag2]);

		$this->tagMapper->expects($this->exactly(2))
			->method('getObjectIdsForTags')
			->withConsecutive(['123'], ['456'])
			->willReturnOnConsecutiveCalls(['111', '222'], ['222', '333']);

		$rules = [
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
		];

		$this->assertEquals(['222'], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}

	public function testProcessFavoriteFilter() {
		$rules = [
			['name' => '{http://owncloud.org/ns}favorite', 'value' => '1'],
		];

		$this->privateTags->expects($this->once())
			->method('getFavorites')
			->willReturn(['456', '789']);

		$this->assertEquals(['456', '789'], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}

	public function filesBaseUriProvider(): array {
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
	public function testFilesBaseUri(string $uri, string $reportPath, string $expectedUri) {
		$this->assertEquals($expectedUri, $this->invokePrivate($this->plugin, 'getFilesBaseUri', [$uri, $reportPath]));
	}
}
