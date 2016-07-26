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

use OCA\DAV\Connector\Sabre\FilesReportPlugin as FilesReportPluginImplementation;
use OCP\IPreview;
use Sabre\DAV\Exception\NotFound;
use OCP\SystemTag\ISystemTagObjectMapper;
use OC\Files\View;
use OCP\Files\Folder;
use OCP\IGroupManager;
use OCP\SystemTag\ISystemTagManager;

class FilesReportPluginTest extends \Test\TestCase {
	/** @var \Sabre\DAV\Server|\PHPUnit_Framework_MockObject_MockObject */
	private $server;

	/** @var \Sabre\DAV\Tree|\PHPUnit_Framework_MockObject_MockObject */
	private $tree;

	/** @var ISystemTagObjectMapper|\PHPUnit_Framework_MockObject_MockObject */
	private $tagMapper;

	/** @var ISystemTagManager|\PHPUnit_Framework_MockObject_MockObject */
	private $tagManager;

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
		$this->tree = $this->getMockBuilder('\Sabre\DAV\Tree')
			->disableOriginalConstructor()
			->getMock();

		$this->view = $this->getMockBuilder('\OC\Files\View')
			->disableOriginalConstructor()
			->getMock();

		$this->server = $this->getMockBuilder('\Sabre\DAV\Server')
			->setConstructorArgs([$this->tree])
			->setMethods(['getRequestUri'])
			->getMock();

		$this->groupManager = $this->getMockBuilder('\OCP\IGroupManager')
			->disableOriginalConstructor()
			->getMock();

		$this->userFolder = $this->getMockBuilder('\OCP\Files\Folder')
			->disableOriginalConstructor()
			->getMock();

		$this->tagManager = $this->getMockBuilder('\OCP\SystemTag\ISystemTagManager')
			->disableOriginalConstructor()
			->getMock();
		$this->tagMapper = $this->getMockBuilder('\OCP\SystemTag\ISystemTagObjectMapper')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder('\OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();

		$this->previewManager = $this->getMockBuilder('\OCP\IPreview')
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('testuser'));
		$this->userSession->expects($this->any())
			->method('getUser')
			->will($this->returnValue($user));

		$this->plugin = new FilesReportPluginImplementation(
			$this->tree,
			$this->view,
			$this->tagManager,
			$this->tagMapper,
			$this->userSession,
			$this->groupManager,
			$this->userFolder
		);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\ReportNotSupported
	 */
	public function testOnReportInvalidNode() {
		$path = 'totally/unrelated/13';

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->will($this->returnValue(
				$this->getMockBuilder('\Sabre\DAV\INode')
					->disableOriginalConstructor()
					->getMock()
			));

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));
		$this->plugin->initialize($this->server);

		$this->plugin->onReport(FilesReportPluginImplementation::REPORT_NAME, [], '/' . $path);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\ReportNotSupported
	 */
	public function testOnReportInvalidReportName() {
		$path = 'test';

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->will($this->returnValue(
				$this->getMockBuilder('\Sabre\DAV\INode')
					->disableOriginalConstructor()
					->getMock()
			));

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));
		$this->plugin->initialize($this->server);

		$this->plugin->onReport('{whoever}whatever', [], '/' . $path);
	}

	public function testOnReport() {
		$path = 'test';

		$parameters = [
			[
				'name'  => '{DAV:}prop',
				'value' => [
					['name' => '{DAV:}getcontentlength', 'value' => ''],
					['name' => '{http://owncloud.org/ns}size', 'value' => ''],
				],
			],
			[
				'name'  => '{http://owncloud.org/ns}filter-rules',
				'value' => [
					['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
					['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
				],
			],
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

		$reportTargetNode = $this->getMockBuilder('\OCA\DAV\Connector\Sabre\Directory')
			->disableOriginalConstructor()
			->getMock();

		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();

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

		$filesNode1 = $this->getMockBuilder('\OCP\Files\Folder')
			->disableOriginalConstructor()
			->getMock();
		$filesNode2 = $this->getMockBuilder('\OCP\Files\File')
			->disableOriginalConstructor()
			->getMock();

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

		$this->plugin->onReport(FilesReportPluginImplementation::REPORT_NAME, $parameters, '/' . $path);
	}

	public function testFindNodesByFileIdsRoot() {
		$filesNode1 = $this->getMockBuilder('\OCP\Files\Folder')
			->disableOriginalConstructor()
			->getMock();
		$filesNode1->expects($this->once())
			->method('getName')
			->will($this->returnValue('first node'));

		$filesNode2 = $this->getMockBuilder('\OCP\Files\File')
			->disableOriginalConstructor()
			->getMock();
		$filesNode2->expects($this->once())
			->method('getName')
			->will($this->returnValue('second node'));

		$reportTargetNode = $this->getMockBuilder('\OCA\DAV\Connector\Sabre\Directory')
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
		$filesNode1 = $this->getMockBuilder('\OCP\Files\Folder')
			->disableOriginalConstructor()
			->getMock();
		$filesNode1->expects($this->once())
			->method('getName')
			->will($this->returnValue('first node'));

		$filesNode2 = $this->getMockBuilder('\OCP\Files\File')
			->disableOriginalConstructor()
			->getMock();
		$filesNode2->expects($this->once())
			->method('getName')
			->will($this->returnValue('second node'));

		$reportTargetNode = $this->getMockBuilder('\OCA\DAV\Connector\Sabre\Directory')
			->disableOriginalConstructor()
			->getMock();
		$reportTargetNode->expects($this->any())
			->method('getPath')
			->will($this->returnValue('/sub1/sub2'));


		$subNode = $this->getMockBuilder('\OCP\Files\Folder')
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

		$node1 = $this->getMockBuilder('\OCA\DAV\Connector\Sabre\Directory')
			->disableOriginalConstructor()
			->getMock();
		$node2 = $this->getMockBuilder('\OCA\DAV\Connector\Sabre\File')
			->disableOriginalConstructor()
			->getMock();

		$node1->expects($this->once())
			->method('getInternalFileId')
			->will($this->returnValue('111'));
		$node2->expects($this->once())
			->method('getInternalFileId')
			->will($this->returnValue('222'));
		$node2->expects($this->once())
			->method('getSize')
			->will($this->returnValue(1024));

		$config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();

		$this->server->addPlugin(
			new \OCA\DAV\Connector\Sabre\FilesPlugin(
				$this->tree,
				$this->view,
				$config,
				$this->getMockBuilder('\OCP\IRequest')
					->disableOriginalConstructor()
					->getMock(),
				$this->previewManager
			)
		);
		$this->plugin->initialize($this->server);
		$responses = $this->plugin->prepareResponses($requestedProps, [$node1, $node2]);

		$this->assertCount(2, $responses);

		$this->assertEquals(200, $responses[0]->getHttpStatus());
		$this->assertEquals(200, $responses[1]->getHttpStatus());

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
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
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
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
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
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
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
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
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
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '789'],
		];

		$this->assertEquals([], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}

	public function testProcessFilterRulesInvisibleTagAsAdmin() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(true));

		$tag1 = $this->getMockBuilder('\OCP\SystemTag\ISystemTag')
			->disableOriginalConstructor()
			->getMock();
		$tag1->expects($this->any())
			->method('getId')
			->will($this->returnValue('123'));
		$tag1->expects($this->any())
			->method('isUserVisible')
			->will($this->returnValue(true));

		$tag2 = $this->getMockBuilder('\OCP\SystemTag\ISystemTag')
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
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
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

		$tag1 = $this->getMockBuilder('\OCP\SystemTag\ISystemTag')
			->disableOriginalConstructor()
			->getMock();
		$tag1->expects($this->any())
			->method('getId')
			->will($this->returnValue('123'));
		$tag1->expects($this->any())
			->method('isUserVisible')
			->will($this->returnValue(true));

		$tag2 = $this->getMockBuilder('\OCP\SystemTag\ISystemTag')
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
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
		];

		$this->invokePrivate($this->plugin, 'processFilterRules', [$rules]);
	}

	public function testProcessFilterRulesVisibleTagAsUser() {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(false));

		$tag1 = $this->getMockBuilder('\OCP\SystemTag\ISystemTag')
			->disableOriginalConstructor()
			->getMock();
		$tag1->expects($this->any())
			->method('getId')
			->will($this->returnValue('123'));
		$tag1->expects($this->any())
			->method('isUserVisible')
			->will($this->returnValue(true));

		$tag2 = $this->getMockBuilder('\OCP\SystemTag\ISystemTag')
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
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '123'],
			['name' => '{http://owncloud.org/ns}systemtag', 'value' => '456'],
		];

		$this->assertEquals(['222'], array_values($this->invokePrivate($this->plugin, 'processFilterRules', [$rules])));
	}
}
