<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\FilesReportPlugin as FilesReportPluginImplementation;
use OCA\DAV\Files\Xml\FilterRequest;
use OCP\Files\File;
use OCP\IConfig;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ITagManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagObjectMapper;
use OC\Files\View;
use OCP\Files\Folder;
use OCP\IGroupManager;
use OCP\SystemTag\ISystemTagManager;
use OCP\ITags;
use OCP\Files\FileInfo;
use Sabre\DAV\INode;
use Sabre\DAV\Tree;

class FilesReportPluginTest extends \Test\TestCase {
	/** @var \Sabre\DAV\Server|\PHPUnit_Framework_MockObject_MockObject */
	private $server;

	/** @var \Sabre\DAV\Tree|\PHPUnit_Framework_MockObject_MockObject */
	private $tree;

	/** @var ISystemTagObjectMapper|\PHPUnit_Framework_MockObject_MockObject */
	private $tagMapper;

	/** @var ISystemTagManager|\PHPUnit_Framework_MockObject_MockObject */
	private $tagManager;

	/** @var ITags|\PHPUnit_Framework_MockObject_MockObject */
	private $privateTags;

	/** @var  \OCP\IUserSession */
	private $userSession;

	/** @var FilesReportPluginImplementation */
	private $plugin;

	/** @var View|\PHPUnit_Framework_MockObject_MockObject **/
	private $view;

	/** @var IGroupManager|\PHPUnit_Framework_MockObject_MockObject **/
	private $groupManager;

	/** @var Folder|\PHPUnit_Framework_MockObject_MockObject **/
	private $userFolder;

	/** @var IPreview|\PHPUnit_Framework_MockObject_MockObject * */
	private $previewManager;

	public function setUp() {
		parent::setUp();
		$this->tree = $this->getMockBuilder(Tree::class)
			->disableOriginalConstructor()
			->getMock();

		$this->view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()
			->getMock();

		$this->server = $this->getMockBuilder('\Sabre\DAV\Server')
			->setConstructorArgs([$this->tree])
			->setMethods(['getRequestUri', 'getBaseUri', 'generateMultiStatus'])
			->getMock();

		$this->server->expects($this->any())
			->method('getBaseUri')
			->will($this->returnValue('http://example.com/owncloud/remote.php/dav'));

		$this->groupManager = $this->getMockBuilder(IGroupManager::class)
			->disableOriginalConstructor()
			->getMock();

		$this->userFolder = $this->getMockBuilder(Folder::class)
			->disableOriginalConstructor()
			->getMock();

		$this->previewManager = $this->getMockBuilder(IPreview::class)
			->disableOriginalConstructor()
			->getMock();

		$this->tagManager = $this->createMock(ISystemTagManager::class);
		$this->tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->privateTags = $this->createMock(ITags::class);
		$privateTagManager = $this->createMock(ITagManager::class);
		$privateTagManager->expects($this->any())
			->method('load')
			->with('files')
			->will($this->returnValue($this->privateTags));

		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('testuser'));
		$this->userSession->expects($this->any())
			->method('getUser')
			->will($this->returnValue($user));

		// add FilesPlugin to test more properties
		$this->server->addPlugin(
			new \OCA\DAV\Connector\Sabre\FilesPlugin(
				$this->tree,
				$this->createMock(IConfig::class),
				$this->createMock(IRequest::class),
				$this->createMock(IPreview::class)
			)
		);

		$this->plugin = new FilesReportPluginImplementation(
			$this->tree,
			$this->view,
			$this->tagManager,
			$this->tagMapper,
			$privateTagManager,
			$this->userSession,
			$this->groupManager,
			$this->userFolder
		);
	}

	public function testOnReportInvalidNode() {
		$path = 'totally/unrelated/13';

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->will($this->returnValue(
				$this->getMockBuilder(INode::class)
					->disableOriginalConstructor()
					->getMock()
			));

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));
		$this->plugin->initialize($this->server);

		$this->assertNull($this->plugin->onReport(FilesReportPluginImplementation::REPORT_NAME, [], '/' . $path));
	}

	public function testOnReportInvalidReportName() {
		$path = 'test';

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->will($this->returnValue(
				$this->getMockBuilder(INode::class)
					->disableOriginalConstructor()
					->getMock()
			));

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));
		$this->plugin->initialize($this->server);

		$this->assertNull($this->plugin->onReport('{whoever}whatever', [], '/' . $path));
	}

	public function testOnReport() {
		$path = 'test';

		$parameters = new FilterRequest();
		$parameters->properties = [
			'{DAV:}getcontentlength',
			'{http://owncloud.org/ns}size',
			'{http://owncloud.org/ns}fileid',
			'{DAV:}resourcetype',
		];
		$parameters->filters = [
			'systemtag' => [123, 456],
			'favorite' => null
		];

		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(true));

		$this->tagMapper->expects($this->at(0))
			->method('getObjectIdsForTags')
			->with('123', 'files')
			->will($this->returnValue(['111', '222']));
		$this->tagMapper->expects($this->at(1))
			->method('getObjectIdsForTags')
			->with('456', 'files')
			->will($this->returnValue(['111', '222', '333']));

		$reportTargetNode = $this->createMock(\OCA\DAV\Connector\Sabre\Directory::class);
		$response = $this->createMock(\Sabre\HTTP\ResponseInterface::class);
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
			->will($this->returnValue($reportTargetNode));

		$filesNode1 = $this->createMock(\OCP\Files\Folder::class);
		$filesNode1->method('getId')->willReturn(111);
		$filesNode1->method('getPath')->willReturn('/node1');
		$filesNode1->method('isReadable')->willReturn(true);
		$filesNode1->method('getSize')->willReturn(2048);
		$filesNode2 = $this->createMock(\OCP\Files\File::class);
		$filesNode2->method('getId')->willReturn(222);
		$filesNode2->method('getPath')->willReturn('/sub/node2');
		$filesNode2->method('getSize')->willReturn(1024);
		$filesNode2->method('isReadable')->willReturn(true);

		$this->userFolder->expects($this->at(0))
			->method('getById')
			->with('111')
			->will($this->returnValue([$filesNode1]));
		$this->userFolder->expects($this->at(1))
			->method('getById')
			->with('222')
			->will($this->returnValue([$filesNode2]));

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));
		$this->server->httpResponse = $response;
		$this->plugin->initialize($this->server);

		$responses = null;
		$this->server->expects($this->once())
			->method('generateMultiStatus')
			->will($this->returnCallback(function($responsesArg) use (&$responses) {
				$responses = $responsesArg;
			})
		);

		$this->assertFalse($this->plugin->onReport(FilesReportPluginImplementation::REPORT_NAME, $parameters, '/' . $path));

		$this->assertCount(2, $responses);

		$this->assertTrue(isset($responses[0][200]));
		$this->assertTrue(isset($responses[1][200]));

		$this->assertEquals('/test/node1', $responses[0]['href']);
		$this->assertEquals('/test/sub/node2', $responses[1]['href']);

		$props1 = $responses[0];
		$this->assertEquals('111', $props1[200]['{http://owncloud.org/ns}fileid']);
		$this->assertNull($props1[404]['{DAV:}getcontentlength']);
		$this->assertInstanceOf('\Sabre\DAV\Xml\Property\ResourceType', $props1[200]['{DAV:}resourcetype']);
		$resourceType1 = $props1[200]['{DAV:}resourcetype']->getValue();
		$this->assertEquals('{DAV:}collection', $resourceType1[0]);

		$props2 = $responses[1];
		$this->assertEquals('1024', $props2[200]['{DAV:}getcontentlength']);
		$this->assertEquals('222', $props2[200]['{http://owncloud.org/ns}fileid']);
		$this->assertInstanceOf('\Sabre\DAV\Xml\Property\ResourceType', $props2[200]['{DAV:}resourcetype']);
		$this->assertCount(0, $props2[200]['{DAV:}resourcetype']->getValue());
	}

	public function testOnReportPaginationFiltered() {
		$path = 'test';

		$parameters = new FilterRequest();
		$parameters->properties = [
			'{DAV:}getcontentlength',
		];
		$parameters->filters = [
			'systemtag' => [],
			'favorite' => true
		];
		$parameters->search = [
			'offset' => 2,
			'limit' => 3,
		];

		$filesNodes = [];
		for ($i = 0; $i < 20; $i++) {
			$filesNode = $this->createMock(\OCP\Files\File::class);
			$filesNode->method('getId')->willReturn(1000 + $i);
			$filesNode->method('getPath')->willReturn('/nodes/node' . $i);
			$filesNode->method('isReadable')->willReturn(true);
			$filesNodes[$filesNode->getId()] = $filesNode;
		}

		// return all above nodes as favorites
		$this->privateTags->expects($this->once())
			->method('getFavorites')
			->will($this->returnValue(array_keys($filesNodes)));

		$reportTargetNode = $this->createMock(\OCA\DAV\Connector\Sabre\Directory::class);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->will($this->returnValue($reportTargetNode));

		// getById must only be called for the required nodes
		$this->userFolder->expects($this->at(0))
			->method('getById')
			->with(1002)
			->willReturn([$filesNodes[1002]]);
		$this->userFolder->expects($this->at(1))
			->method('getById')
			->with(1003)
			->willReturn([$filesNodes[1003]]);
		$this->userFolder->expects($this->at(2))
			->method('getById')
			->with(1004)
			->willReturn([$filesNodes[1004]]);

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));

		$this->plugin->initialize($this->server);

		$responses = null;
		$this->server->expects($this->once())
			->method('generateMultiStatus')
			->will($this->returnCallback(function($responsesArg) use (&$responses) {
				$responses = $responsesArg;
			})
		);

		$this->assertFalse($this->plugin->onReport(FilesReportPluginImplementation::REPORT_NAME, $parameters, '/' . $path));

		$this->assertCount(3, $responses);

		$this->assertEquals('/test/nodes/node2', $responses[0]['href']);
		$this->assertEquals('/test/nodes/node3', $responses[1]['href']);
		$this->assertEquals('/test/nodes/node4', $responses[2]['href']);
	}

	public function testFindNodesByFileIdsRoot() {
		$filesNode1 = $this->getMockBuilder(Folder::class)
			->disableOriginalConstructor()
			->getMock();
		$filesNode1->expects($this->once())
			->method('getName')
			->will($this->returnValue('first node'));

		$filesNode2 = $this->getMockBuilder(File::class)
			->disableOriginalConstructor()
			->getMock();
		$filesNode2->expects($this->once())
			->method('getName')
			->will($this->returnValue('second node'));

		$reportTargetNode = $this->getMockBuilder(Directory::class)
			->disableOriginalConstructor()
			->getMock();
		$reportTargetNode->expects($this->any())
			->method('getPath')
			->will($this->returnValue('/'));

		$this->userFolder->expects($this->at(0))
			->method('getById')
			->with('111')
			->will($this->returnValue([$filesNode1]));
		$this->userFolder->expects($this->at(1))
			->method('getById')
			->with('222')
			->will($this->returnValue([$filesNode2]));

		/** @var \OCA\DAV\Connector\Sabre\Directory|\PHPUnit_Framework_MockObject_MockObject $reportTargetNode */
		$result = $this->plugin->findNodesByFileIds($reportTargetNode, ['111', '222']);

		$this->assertCount(2, $result);
		$this->assertInstanceOf('\OCA\DAV\Connector\Sabre\Directory', $result[0]);
		$this->assertEquals('first node', $result[0]->getName());
		$this->assertInstanceOf('\OCA\DAV\Connector\Sabre\File', $result[1]);
		$this->assertEquals('second node', $result[1]->getName());
	}

	public function testFindNodesByFileIdsSubDir() {
		$filesNode1 = $this->getMockBuilder(Folder::class)
			->disableOriginalConstructor()
			->getMock();
		$filesNode1->expects($this->once())
			->method('getName')
			->will($this->returnValue('first node'));

		$filesNode2 = $this->getMockBuilder(File::class)
			->disableOriginalConstructor()
			->getMock();
		$filesNode2->expects($this->once())
			->method('getName')
			->will($this->returnValue('second node'));

		$reportTargetNode = $this->getMockBuilder(Directory::class)
			->disableOriginalConstructor()
			->getMock();
		$reportTargetNode->expects($this->any())
			->method('getPath')
			->will($this->returnValue('/sub1/sub2'));


		$subNode = $this->getMockBuilder(Folder::class)
			->disableOriginalConstructor()
			->getMock();

		$this->userFolder->expects($this->at(0))
			->method('get')
			->with('/sub1/sub2')
			->will($this->returnValue($subNode));

		$subNode->expects($this->at(0))
			->method('getById')
			->with('111')
			->will($this->returnValue([$filesNode1]));
		$subNode->expects($this->at(1))
			->method('getById')
			->with('222')
			->will($this->returnValue([$filesNode2]));

		/** @var \OCA\DAV\Connector\Sabre\Directory|\PHPUnit_Framework_MockObject_MockObject $reportTargetNode */
		$result = $this->plugin->findNodesByFileIds($reportTargetNode, ['111', '222']);

		$this->assertCount(2, $result);
		$this->assertInstanceOf('\OCA\DAV\Connector\Sabre\Directory', $result[0]);
		$this->assertEquals('first node', $result[0]->getName());
		$this->assertInstanceOf('\OCA\DAV\Connector\Sabre\File', $result[1]);
		$this->assertEquals('second node', $result[1]->getName());
	}

	public function testPrepareResponses() {
		$requestedProps = ['{DAV:}getcontentlength', '{http://owncloud.org/ns}fileid', '{DAV:}resourcetype'];

		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->method('isReadable')->willReturn(true);

		$node1 = $this->getMockBuilder(Directory::class)
			->disableOriginalConstructor()
			->getMock();
		$node2 = $this->getMockBuilder(\OCA\DAV\Connector\Sabre\File::class)
			->disableOriginalConstructor()
			->getMock();

		$node1->expects($this->once())
			->method('getInternalFileId')
			->will($this->returnValue('111'));
		$node1->expects($this->any())
			->method('getPath')
			->will($this->returnValue('/node1'));
		$node1->method('getFileInfo')->willReturn($fileInfo);
		$node2->expects($this->once())
			->method('getInternalFileId')
			->will($this->returnValue('222'));
		$node2->expects($this->once())
			->method('getSize')
			->will($this->returnValue(1024));
		$node2->expects($this->any())
			->method('getPath')
			->will($this->returnValue('/sub/node2'));
		$node2->method('getFileInfo')->willReturn($fileInfo);

		$config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();

		$this->server->addPlugin(
			new \OCA\DAV\Connector\Sabre\FilesPlugin(
				$this->tree,
				$config,
				$this->getMockBuilder(IRequest::class)
					->disableOriginalConstructor()
					->getMock(),
				$this->previewManager
			)
		);
		$this->plugin->initialize($this->server);
		$responses = $this->plugin->prepareResponses('/files/username', $requestedProps, [$node1, $node2]);

		$this->assertCount(2, $responses);

		$this->assertEquals('/files/username/node1', $responses[0]['href']);
		$this->assertEquals('/files/username/sub/node2', $responses[1]['href']);

		$props1 = $responses[0];
		$this->assertEquals('111', $props1[200]['{http://owncloud.org/ns}fileid']);
		$this->assertNull($props1[404]['{DAV:}getcontentlength']);
		$this->assertInstanceOf('\Sabre\DAV\Xml\Property\ResourceType', $props1[200]['{DAV:}resourcetype']);
		$resourceType1 = $props1[200]['{DAV:}resourcetype']->getValue();
		$this->assertEquals('{DAV:}collection', $resourceType1[0]);

		$props2 = $responses[1];
		$this->assertEquals('1024', $props2[200]['{DAV:}getcontentlength']);
		$this->assertEquals('222', $props2[200]['{http://owncloud.org/ns}fileid']);
		$this->assertInstanceOf('\Sabre\DAV\Xml\Property\ResourceType', $props2[200]['{DAV:}resourcetype']);
		$this->assertCount(0, $props2[200]['{DAV:}resourcetype']->getValue());
	}

	public function testProcessFilterRulesSingle() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(true));

		$this->tagMapper->expects($this->exactly(1))
			->method('getObjectIdsForTags')
			->withConsecutive(
				['123', 'files']
			)
			->willReturnMap([
				['123', 'files', 0, '', ['111', '222']],
			]);

		$rules = [
			'systemtag' => ['123'],
			'favorite' => null
		];

		$this->assertEquals(['111', '222'], $this->invokePrivate($this->plugin, 'processFilterRules', [$rules]));
	}

	public function testProcessFilterRulesAndCondition() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(true));

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
			'systemtag' => ['123', '456'],
			'favorite' => null
		];

		$this->assertEquals(['222'], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}

	public function testProcessFilterRulesAndConditionWithOneEmptyResult() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(true));

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
			'systemtag' => ['123', '456'],
			'favorite' => null
		];

		$this->assertEquals([], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}

	public function testProcessFilterRulesAndConditionWithFirstEmptyResult() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(true));

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
			'systemtag' => ['123', '456'],
			'favorite' => null
		];

		$this->assertEquals([], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}

	public function testProcessFilterRulesAndConditionWithEmptyMidResult() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(true));

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
			'systemtag' => ['123', '456', '789'],
			'favorite' => null
		];

		$this->assertEquals([], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}

	public function testProcessFilterRulesInvisibleTagAsAdmin() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(true));

		$tag1 = $this->getMockBuilder(ISystemTag::class)
			->disableOriginalConstructor()
			->getMock();
		$tag1->expects($this->any())
			->method('getId')
			->will($this->returnValue('123'));
		$tag1->expects($this->any())
			->method('isUserVisible')
			->will($this->returnValue(true));

		$tag2 = $this->getMockBuilder(ISystemTag::class)
			->disableOriginalConstructor()
			->getMock();
		$tag2->expects($this->any())
			->method('getId')
			->will($this->returnValue('123'));
		$tag2->expects($this->any())
			->method('isUserVisible')
			->will($this->returnValue(false));

		// no need to fetch tags to check permissions
		$this->tagManager->expects($this->never())
			->method('getTagsByIds');

		$this->tagMapper->expects($this->at(0))
			->method('getObjectIdsForTags')
			->with('123')
			->will($this->returnValue(['111', '222']));
		$this->tagMapper->expects($this->at(1))
			->method('getObjectIdsForTags')
			->with('456')
			->will($this->returnValue(['222', '333']));

		$rules = [
			'systemtag' => ['123', '456'],
			'favorite' => null
		];

		$this->assertEquals(['222'], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}

	/**
	 * @expectedException \OCP\SystemTag\TagNotFoundException
	 */
	public function testProcessFilterRulesInvisibleTagAsUser() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(false));

		$tag1 = $this->getMockBuilder(ISystemTag::class)
			->disableOriginalConstructor()
			->getMock();
		$tag1->expects($this->any())
			->method('getId')
			->will($this->returnValue('123'));
		$tag1->expects($this->any())
			->method('isUserVisible')
			->will($this->returnValue(true));

		$tag2 = $this->getMockBuilder(ISystemTag::class)
			->disableOriginalConstructor()
			->getMock();
		$tag2->expects($this->any())
			->method('getId')
			->will($this->returnValue('123'));
		$tag2->expects($this->any())
			->method('isUserVisible')
			->will($this->returnValue(false)); // invisible

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123', '456'])
			->will($this->returnValue([$tag1, $tag2]));

		$rules = [
			'systemtag' => ['123', '456'],
			'favorite' => null
		];

		$this->invokePrivate($this->plugin, 'processFilterRules', [$rules]);
	}

	public function testProcessFilterRulesVisibleTagAsUser() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(false));

		$tag1 = $this->getMockBuilder(ISystemTag::class)
			->disableOriginalConstructor()
			->getMock();
		$tag1->expects($this->any())
			->method('getId')
			->will($this->returnValue('123'));
		$tag1->expects($this->any())
			->method('isUserVisible')
			->will($this->returnValue(true));

		$tag2 = $this->getMockBuilder(ISystemTag::class)
			->disableOriginalConstructor()
			->getMock();
		$tag2->expects($this->any())
			->method('getId')
			->will($this->returnValue('123'));
		$tag2->expects($this->any())
			->method('isUserVisible')
			->will($this->returnValue(true));

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123', '456'])
			->will($this->returnValue([$tag1, $tag2]));

		$this->tagMapper->expects($this->at(0))
			->method('getObjectIdsForTags')
			->with('123')
			->will($this->returnValue(['111', '222']));
		$this->tagMapper->expects($this->at(1))
			->method('getObjectIdsForTags')
			->with('456')
			->will($this->returnValue(['222', '333']));

		$rules = [
			'systemtag' => ['123', '456'],
			'favorite' => null
		];

		$this->assertEquals(['222'], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}

	public function testProcessFavoriteFilter() {
		$rules = [
			'systemtag' => [],
			'favorite' => true
		];

		$this->privateTags->expects($this->once())
			->method('getFavorites')
			->will($this->returnValue(['456', '789']));

		$this->assertEquals(['456', '789'], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}

	public function filesBaseUriProvider() {
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
	public function testFilesBaseUri($uri, $reportPath, $expectedUri) {
		$this->assertEquals($expectedUri, $this->invokePrivate($this->plugin, 'getFilesBaseUri', [$uri, $reportPath]));
	}
}
