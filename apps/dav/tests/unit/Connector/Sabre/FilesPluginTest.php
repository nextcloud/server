<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\User\User;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCA\DAV\Connector\Sabre\File;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCA\DAV\Connector\Sabre\Node;
use OCP\Files\FileInfo;
use OCP\Files\IFilenameValidator;
use OCP\Files\InvalidPathException;
use OCP\Files\StorageNotAvailableException;
use OCP\IConfig;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Xml\Service;
use Test\TestCase;

/**
 * @group DB
 */
class FilesPluginTest extends TestCase {

	private Tree&MockObject $tree;
	private Server&MockObject $server;
	private IConfig&MockObject $config;
	private IRequest&MockObject $request;
	private IPreview&MockObject $previewManager;
	private IUserSession&MockObject $userSession;
	private IFilenameValidator&MockObject $filenameValidator;
	private FilesPlugin $plugin;

	protected function setUp(): void {
		parent::setUp();
		$this->server = $this->createMock(Server::class);
		$this->tree = $this->createMock(Tree::class);
		$this->config = $this->createMock(IConfig::class);
		$this->config->expects($this->any())->method('getSystemValue')
			->with($this->equalTo('data-fingerprint'), $this->equalTo(''))
			->willReturn('my_fingerprint');
		$this->request = $this->createMock(IRequest::class);
		$this->previewManager = $this->createMock(IPreview::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->filenameValidator = $this->createMock(IFilenameValidator::class);

		$this->plugin = new FilesPlugin(
			$this->tree,
			$this->config,
			$this->request,
			$this->previewManager,
			$this->userSession,
			$this->filenameValidator,
		);

		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$this->server->httpResponse = $response;
		$this->server->xml = new Service();

		$this->plugin->initialize($this->server);
	}

	/**
	 * @param string $class
	 * @return \PHPUnit\Framework\MockObject\MockObject
	 */
	private function createTestNode($class, $path = '/dummypath') {
		$node = $this->getMockBuilder($class)
			->disableOriginalConstructor()
			->getMock();

		$node->expects($this->any())
			->method('getId')
			->willReturn(123);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with($path)
			->willReturn($node);

		$node->expects($this->any())
			->method('getFileId')
			->willReturn('00000123instanceid');
		$node->expects($this->any())
			->method('getInternalFileId')
			->willReturn('123');
		$node->expects($this->any())
			->method('getEtag')
			->willReturn('"abc"');
		$node->expects($this->any())
			->method('getDavPermissions')
			->willReturn('DWCKMSR');

		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->expects($this->any())
			->method('isReadable')
			->willReturn(true);
		$fileInfo->expects($this->any())
			->method('getCreationTime')
			->willReturn(123456789);

		$node->expects($this->any())
			->method('getFileInfo')
			->willReturn($fileInfo);

		return $node;
	}

	public function testGetPropertiesForFile(): void {
		/** @var File|\PHPUnit\Framework\MockObject\MockObject $node */
		$node = $this->createTestNode('\OCA\DAV\Connector\Sabre\File');

		$propFind = new PropFind(
			'/dummyPath',
			[
				FilesPlugin::GETETAG_PROPERTYNAME,
				FilesPlugin::FILEID_PROPERTYNAME,
				FilesPlugin::INTERNAL_FILEID_PROPERTYNAME,
				FilesPlugin::SIZE_PROPERTYNAME,
				FilesPlugin::PERMISSIONS_PROPERTYNAME,
				FilesPlugin::DOWNLOADURL_PROPERTYNAME,
				FilesPlugin::OWNER_ID_PROPERTYNAME,
				FilesPlugin::OWNER_DISPLAY_NAME_PROPERTYNAME,
				FilesPlugin::DATA_FINGERPRINT_PROPERTYNAME,
				FilesPlugin::CREATIONDATE_PROPERTYNAME,
			],
			0
		);

		$user = $this->getMockBuilder(User::class)
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('foo');
		$user
			->expects($this->once())
			->method('getDisplayName')
			->willReturn('M. Foo');

		$node->expects($this->once())
			->method('getDirectDownload')
			->willReturn(['url' => 'http://example.com/']);
		$node->expects($this->exactly(2))
			->method('getOwner')
			->willReturn($user);

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$this->assertEquals('"abc"', $propFind->get(FilesPlugin::GETETAG_PROPERTYNAME));
		$this->assertEquals('00000123instanceid', $propFind->get(FilesPlugin::FILEID_PROPERTYNAME));
		$this->assertEquals('123', $propFind->get(FilesPlugin::INTERNAL_FILEID_PROPERTYNAME));
		$this->assertEquals('1973-11-29T21:33:09+00:00', $propFind->get(FilesPlugin::CREATIONDATE_PROPERTYNAME));
		$this->assertEquals(0, $propFind->get(FilesPlugin::SIZE_PROPERTYNAME));
		$this->assertEquals('DWCKMSR', $propFind->get(FilesPlugin::PERMISSIONS_PROPERTYNAME));
		$this->assertEquals('http://example.com/', $propFind->get(FilesPlugin::DOWNLOADURL_PROPERTYNAME));
		$this->assertEquals('foo', $propFind->get(FilesPlugin::OWNER_ID_PROPERTYNAME));
		$this->assertEquals('M. Foo', $propFind->get(FilesPlugin::OWNER_DISPLAY_NAME_PROPERTYNAME));
		$this->assertEquals('my_fingerprint', $propFind->get(FilesPlugin::DATA_FINGERPRINT_PROPERTYNAME));
		$this->assertEquals([], $propFind->get404Properties());
	}

	public function testGetPropertiesStorageNotAvailable(): void {
		/** @var File|\PHPUnit\Framework\MockObject\MockObject $node */
		$node = $this->createTestNode('\OCA\DAV\Connector\Sabre\File');

		$propFind = new PropFind(
			'/dummyPath',
			[
				FilesPlugin::DOWNLOADURL_PROPERTYNAME,
			],
			0
		);

		$node->expects($this->once())
			->method('getDirectDownload')
			->will($this->throwException(new StorageNotAvailableException()));

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$this->assertEquals(null, $propFind->get(FilesPlugin::DOWNLOADURL_PROPERTYNAME));
	}

	public function testGetPublicPermissions(): void {
		/** @var IRequest&MockObject */
		$request = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()
			->getMock();
		$this->plugin = new FilesPlugin(
			$this->tree,
			$this->config,
			$request,
			$this->previewManager,
			$this->userSession,
			$this->filenameValidator,
			true,
		);
		$this->plugin->initialize($this->server);

		$propFind = new PropFind(
			'/dummyPath',
			[
				FilesPlugin::PERMISSIONS_PROPERTYNAME,
			],
			0
		);

		/** @var File|\PHPUnit\Framework\MockObject\MockObject $node */
		$node = $this->createTestNode('\OCA\DAV\Connector\Sabre\File');
		$node->expects($this->any())
			->method('getDavPermissions')
			->willReturn('DWCKMSR');

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$this->assertEquals('DWCKR', $propFind->get(FilesPlugin::PERMISSIONS_PROPERTYNAME));
	}

	public function testGetPropertiesForDirectory(): void {
		/** @var Directory|\PHPUnit\Framework\MockObject\MockObject $node */
		$node = $this->createTestNode('\OCA\DAV\Connector\Sabre\Directory');

		$propFind = new PropFind(
			'/dummyPath',
			[
				FilesPlugin::GETETAG_PROPERTYNAME,
				FilesPlugin::FILEID_PROPERTYNAME,
				FilesPlugin::SIZE_PROPERTYNAME,
				FilesPlugin::PERMISSIONS_PROPERTYNAME,
				FilesPlugin::DOWNLOADURL_PROPERTYNAME,
				FilesPlugin::DATA_FINGERPRINT_PROPERTYNAME,
			],
			0
		);

		$node->expects($this->once())
			->method('getSize')
			->willReturn(1025);

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$this->assertEquals('"abc"', $propFind->get(FilesPlugin::GETETAG_PROPERTYNAME));
		$this->assertEquals('00000123instanceid', $propFind->get(FilesPlugin::FILEID_PROPERTYNAME));
		$this->assertEquals(1025, $propFind->get(FilesPlugin::SIZE_PROPERTYNAME));
		$this->assertEquals('DWCKMSR', $propFind->get(FilesPlugin::PERMISSIONS_PROPERTYNAME));
		$this->assertEquals(null, $propFind->get(FilesPlugin::DOWNLOADURL_PROPERTYNAME));
		$this->assertEquals('my_fingerprint', $propFind->get(FilesPlugin::DATA_FINGERPRINT_PROPERTYNAME));
		$this->assertEquals([FilesPlugin::DOWNLOADURL_PROPERTYNAME], $propFind->get404Properties());
	}

	public function testGetPropertiesForRootDirectory(): void {
		/** @var Directory|\PHPUnit\Framework\MockObject\MockObject $node */
		$node = $this->getMockBuilder(Directory::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())->method('getPath')->willReturn('/');

		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->expects($this->any())
			->method('isReadable')
			->willReturn(true);

		$node->expects($this->any())
			->method('getFileInfo')
			->willReturn($fileInfo);

		$propFind = new PropFind(
			'/',
			[
				FilesPlugin::DATA_FINGERPRINT_PROPERTYNAME,
			],
			0
		);

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$this->assertEquals('my_fingerprint', $propFind->get(FilesPlugin::DATA_FINGERPRINT_PROPERTYNAME));
	}

	public function testGetPropertiesWhenNoPermission(): void {
		// No read permissions can be caused by files access control.
		// But we still want to load the directory list, so this is okay for us.
		// $this->expectException(\Sabre\DAV\Exception\NotFound::class);
		/** @var Directory|\PHPUnit\Framework\MockObject\MockObject $node */
		$node = $this->getMockBuilder(Directory::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())->method('getPath')->willReturn('/');

		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->expects($this->any())
			->method('isReadable')
			->willReturn(false);

		$node->expects($this->any())
			->method('getFileInfo')
			->willReturn($fileInfo);

		$propFind = new PropFind(
			'/test',
			[
				FilesPlugin::DATA_FINGERPRINT_PROPERTYNAME,
			],
			0
		);

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$this->addToAssertionCount(1);
	}

	public function testUpdateProps(): void {
		$node = $this->createTestNode('\OCA\DAV\Connector\Sabre\File');

		$testDate = 'Fri, 13 Feb 2015 00:01:02 GMT';
		$testCreationDate = '2007-08-31T16:47+00:00';

		$node->expects($this->once())
			->method('touch')
			->with($testDate);

		$node->expects($this->once())
			->method('setEtag')
			->with('newetag')
			->willReturn(true);

		$node->expects($this->once())
			->method('setCreationTime')
			->with('1188578820');

		// properties to set
		$propPatch = new PropPatch([
			FilesPlugin::GETETAG_PROPERTYNAME => 'newetag',
			FilesPlugin::LASTMODIFIED_PROPERTYNAME => $testDate,
			FilesPlugin::CREATIONDATE_PROPERTYNAME => $testCreationDate,
		]);


		$this->plugin->handleUpdateProperties(
			'/dummypath',
			$propPatch
		);

		$propPatch->commit();

		$this->assertEmpty($propPatch->getRemainingMutations());

		$result = $propPatch->getResult();
		$this->assertEquals(200, $result[FilesPlugin::LASTMODIFIED_PROPERTYNAME]);
		$this->assertEquals(200, $result[FilesPlugin::GETETAG_PROPERTYNAME]);
		$this->assertEquals(200, $result[FilesPlugin::CREATIONDATE_PROPERTYNAME]);
	}

	public function testUpdatePropsForbidden(): void {
		$propPatch = new PropPatch([
			FilesPlugin::OWNER_ID_PROPERTYNAME => 'user2',
			FilesPlugin::OWNER_DISPLAY_NAME_PROPERTYNAME => 'User Two',
			FilesPlugin::FILEID_PROPERTYNAME => 12345,
			FilesPlugin::PERMISSIONS_PROPERTYNAME => 'C',
			FilesPlugin::SIZE_PROPERTYNAME => 123,
			FilesPlugin::DOWNLOADURL_PROPERTYNAME => 'http://example.com/',
		]);

		$this->plugin->handleUpdateProperties(
			'/dummypath',
			$propPatch
		);

		$propPatch->commit();

		$this->assertEmpty($propPatch->getRemainingMutations());

		$result = $propPatch->getResult();
		$this->assertEquals(403, $result[FilesPlugin::OWNER_ID_PROPERTYNAME]);
		$this->assertEquals(403, $result[FilesPlugin::OWNER_DISPLAY_NAME_PROPERTYNAME]);
		$this->assertEquals(403, $result[FilesPlugin::FILEID_PROPERTYNAME]);
		$this->assertEquals(403, $result[FilesPlugin::PERMISSIONS_PROPERTYNAME]);
		$this->assertEquals(403, $result[FilesPlugin::SIZE_PROPERTYNAME]);
		$this->assertEquals(403, $result[FilesPlugin::DOWNLOADURL_PROPERTYNAME]);
	}

	/**
	 * Test case from https://github.com/owncloud/core/issues/5251
	 *
	 * |-FolderA
	 *  |-text.txt
	 * |-test.txt
	 *
	 * FolderA is an incoming shared folder and there are no delete permissions.
	 * Thus moving /FolderA/test.txt to /test.txt should fail already on that check
	 *
	 */
	public function testMoveSrcNotDeletable(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);
		$this->expectExceptionMessage('FolderA/test.txt cannot be deleted');

		$fileInfoFolderATestTXT = $this->getMockBuilder(FileInfo::class)
			->disableOriginalConstructor()
			->getMock();
		$fileInfoFolderATestTXT->expects($this->once())
			->method('isDeletable')
			->willReturn(false);

		$node = $this->getMockBuilder(Node::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->atLeastOnce())
			->method('getFileInfo')
			->willReturn($fileInfoFolderATestTXT);

		$this->tree->expects($this->atLeastOnce())
			->method('getNodeForPath')
			->willReturn($node);

		$this->plugin->checkMove('FolderA/test.txt', 'test.txt');
	}

	public function testMoveSrcDeletable(): void {
		$fileInfoFolderATestTXT = $this->getMockBuilder(FileInfo::class)
			->disableOriginalConstructor()
			->getMock();
		$fileInfoFolderATestTXT->expects($this->once())
			->method('isDeletable')
			->willReturn(true);

		$node = $this->getMockBuilder(Node::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->atLeastOnce())
			->method('getFileInfo')
			->willReturn($fileInfoFolderATestTXT);

		$this->tree->expects($this->atLeastOnce())
			->method('getNodeForPath')
			->willReturn($node);

		$this->plugin->checkMove('FolderA/test.txt', 'test.txt');
	}

	public function testMoveSrcNotExist(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);
		$this->expectExceptionMessage('FolderA/test.txt does not exist');

		$node = $this->getMockBuilder(Node::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->atLeastOnce())
			->method('getFileInfo')
			->willReturn(null);

		$this->tree->expects($this->atLeastOnce())
			->method('getNodeForPath')
			->willReturn($node);

		$this->plugin->checkMove('FolderA/test.txt', 'test.txt');
	}

	public function testMoveDestinationInvalid(): void {
		$this->expectException(InvalidPath::class);
		$this->expectExceptionMessage('Mocked exception');

		$fileInfoFolderATestTXT = $this->createMock(FileInfo::class);
		$fileInfoFolderATestTXT->expects(self::any())
			->method('isDeletable')
			->willReturn(true);

		$node = $this->createMock(Node::class);
		$node->expects($this->atLeastOnce())
			->method('getFileInfo')
			->willReturn($fileInfoFolderATestTXT);

		$this->tree->expects($this->atLeastOnce())
			->method('getNodeForPath')
			->willReturn($node);

		$this->filenameValidator->expects(self::once())
			->method('validateFilename')
			->with('invalid\\path.txt')
			->willThrowException(new InvalidPathException('Mocked exception'));

		$this->plugin->checkMove('FolderA/test.txt', 'invalid\\path.txt');
	}

	public function testCopySrcNotExist(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);
		$this->expectExceptionMessage('FolderA/test.txt does not exist');

		$node = $this->createMock(Node::class);
		$node->expects($this->atLeastOnce())
			->method('getFileInfo')
			->willReturn(null);

		$this->tree->expects($this->atLeastOnce())
			->method('getNodeForPath')
			->willReturn($node);

		$this->plugin->checkCopy('FolderA/test.txt', 'test.txt');
	}

	public function testCopyDestinationInvalid(): void {
		$this->expectException(InvalidPath::class);
		$this->expectExceptionMessage('Mocked exception');

		$fileInfoFolderATestTXT = $this->createMock(FileInfo::class);
		$node = $this->createMock(Node::class);
		$node->expects($this->atLeastOnce())
			->method('getFileInfo')
			->willReturn($fileInfoFolderATestTXT);

		$this->tree->expects($this->atLeastOnce())
			->method('getNodeForPath')
			->willReturn($node);

		$this->filenameValidator->expects(self::once())
			->method('validateFilename')
			->with('invalid\\path.txt')
			->willThrowException(new InvalidPathException('Mocked exception'));

		$this->plugin->checkCopy('FolderA/test.txt', 'invalid\\path.txt');
	}

	public function downloadHeadersProvider() {
		return [
			[
				false,
				'attachment; filename*=UTF-8\'\'somefile.xml; filename="somefile.xml"'
			],
			[
				true,
				'attachment; filename="somefile.xml"'
			],
		];
	}

	/**
	 * @dataProvider downloadHeadersProvider
	 */
	public function testDownloadHeaders($isClumsyAgent, $contentDispositionHeader): void {
		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$request
			->expects($this->once())
			->method('getPath')
			->willReturn('test/somefile.xml');

		$node = $this->getMockBuilder(File::class)
			->disableOriginalConstructor()
			->getMock();
		$node
			->expects($this->once())
			->method('getName')
			->willReturn('somefile.xml');

		$this->tree
			->expects($this->once())
			->method('getNodeForPath')
			->with('test/somefile.xml')
			->willReturn($node);

		$this->request
			->expects($this->once())
			->method('isUserAgent')
			->willReturn($isClumsyAgent);

		$response
			->expects($this->exactly(2))
			->method('addHeader')
			->withConsecutive(
				['Content-Disposition', $contentDispositionHeader],
				['X-Accel-Buffering', 'no']
			);

		$this->plugin->httpGet($request, $response);
	}

	public function testHasPreview(): void {
		/** @var Directory|\PHPUnit\Framework\MockObject\MockObject $node */
		$node = $this->createTestNode('\OCA\DAV\Connector\Sabre\Directory');

		$propFind = new PropFind(
			'/dummyPath',
			[
				FilesPlugin::HAS_PREVIEW_PROPERTYNAME
			],
			0
		);

		$this->previewManager->expects($this->once())
			->method('isAvailable')
			->willReturn(false);

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$this->assertEquals('false', $propFind->get(FilesPlugin::HAS_PREVIEW_PROPERTYNAME));
	}
}
