<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\Accounts\Account;
use OC\Accounts\AccountProperty;
use OC\User\User;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCA\DAV\Connector\Sabre\File;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCA\DAV\Connector\Sabre\Node;
use OCP\Accounts\IAccountManager;
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
	private IAccountManager&MockObject $accountManager;
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
		$this->accountManager = $this->createMock(IAccountManager::class);

		$this->plugin = new FilesPlugin(
			$this->tree,
			$this->config,
			$this->request,
			$this->previewManager,
			$this->userSession,
			$this->filenameValidator,
			$this->accountManager,
		);

		$response = $this->createMock(ResponseInterface::class);
		$this->server->httpResponse = $response;
		$this->server->xml = new Service();

		$this->plugin->initialize($this->server);
	}

	private function createTestNode(string $class, string $path = '/dummypath'): MockObject {
		$node = $this->createMock($class);

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
		/** @var File&MockObject $node */
		$node = $this->createTestNode(File::class);

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

		$user = $this->createMock(User::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('foo');
		$user
			->expects($this->once())
			->method('getDisplayName')
			->willReturn('M. Foo');

		$owner = $this->createMock(Account::class);
		$this->accountManager->expects($this->once())
			->method('getAccount')
			->with($user)
			->willReturn($owner);

		$node->expects($this->once())
			->method('getDirectDownload')
			->willReturn(['url' => 'http://example.com/']);
		$node->expects($this->exactly(2))
			->method('getOwner')
			->willReturn($user);

		$displayNameProp = $this->createMock(AccountProperty::class);
		$owner
			->expects($this->once())
			->method('getProperty')
			->with(IAccountManager::PROPERTY_DISPLAYNAME)
			->willReturn($displayNameProp);
		$displayNameProp
			->expects($this->once())
			->method('getScope')
			->willReturn(IAccountManager::SCOPE_PUBLISHED);

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

	public function testGetDisplayNamePropertyWhenNotPublished(): void {
		$node = $this->createTestNode(File::class);
		$propFind = new PropFind(
			'/dummyPath',
			[
				FilesPlugin::OWNER_DISPLAY_NAME_PROPERTYNAME,
			],
			0
		);

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn(null);

		$user = $this->createMock(User::class);

		$user->expects($this->never())
			->method('getDisplayName');

		$owner = $this->createMock(Account::class);
		$this->accountManager->expects($this->once())
			->method('getAccount')
			->with($user)
			->willReturn($owner);

		$node->expects($this->once())
			->method('getOwner')
			->willReturn($user);

		$displayNameProp = $this->createMock(AccountProperty::class);
		$owner
			->expects($this->once())
			->method('getProperty')
			->with(IAccountManager::PROPERTY_DISPLAYNAME)
			->willReturn($displayNameProp);
		$displayNameProp
			->expects($this->once())
			->method('getScope')
			->willReturn(IAccountManager::SCOPE_PRIVATE);

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$this->assertEquals(null, $propFind->get(FilesPlugin::OWNER_DISPLAY_NAME_PROPERTYNAME));
	}

	public function testGetDisplayNamePropertyWhenNotPublishedButLoggedIn(): void {
		$node = $this->createTestNode(File::class);

		$propFind = new PropFind(
			'/dummyPath',
			[
				FilesPlugin::OWNER_DISPLAY_NAME_PROPERTYNAME,
			],
			0
		);

		$user = $this->createMock(User::class);

		$node->expects($this->once())
			->method('getOwner')
			->willReturn($user);

		$loggedInUser = $this->createMock(User::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);

		$user
			->expects($this->once())
			->method('getDisplayName')
			->willReturn('M. Foo');

		$this->accountManager->expects($this->never())
			->method('getAccount');

		$this->plugin->handleGetProperties(
			$propFind,
			$node
		);

		$this->assertEquals('M. Foo', $propFind->get(FilesPlugin::OWNER_DISPLAY_NAME_PROPERTYNAME));
	}

	public function testGetPropertiesStorageNotAvailable(): void {
		/** @var File&MockObject $node */
		$node = $this->createTestNode(File::class);

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
		$request = $this->createMock(IRequest::class);
		$this->plugin = new FilesPlugin(
			$this->tree,
			$this->config,
			$request,
			$this->previewManager,
			$this->userSession,
			$this->filenameValidator,
			$this->accountManager,
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

		/** @var File&MockObject $node */
		$node = $this->createTestNode(File::class);
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
		/** @var Directory&MockObject $node */
		$node = $this->createTestNode(Directory::class);

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
		/** @var Directory&MockObject $node */
		$node = $this->createMock(Directory::class);
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
		/** @var Directory&MockObject $node */
		$node = $this->createMock(Directory::class);
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
		$node = $this->createTestNode(File::class);

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

		$fileInfoFolderATestTXT = $this->createMock(FileInfo::class);
		$fileInfoFolderATestTXT->expects($this->once())
			->method('isDeletable')
			->willReturn(false);

		$node = $this->createMock(Node::class);
		$node->expects($this->atLeastOnce())
			->method('getFileInfo')
			->willReturn($fileInfoFolderATestTXT);

		$this->tree->expects($this->atLeastOnce())
			->method('getNodeForPath')
			->willReturn($node);

		$this->plugin->checkMove('FolderA/test.txt', 'test.txt');
	}

	public function testMoveSrcDeletable(): void {
		$fileInfoFolderATestTXT = $this->createMock(FileInfo::class);
		$fileInfoFolderATestTXT->expects($this->once())
			->method('isDeletable')
			->willReturn(true);

		$node = $this->createMock(Node::class);
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

		$node = $this->createMock(Node::class);
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

	public static function downloadHeadersProvider(): array {
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
	public function testDownloadHeaders(bool $isClumsyAgent, string $contentDispositionHeader): void {
		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);

		$request
			->expects($this->once())
			->method('getPath')
			->willReturn('test/somefile.xml');

		$node = $this->createMock(File::class);
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

		$calls = [
			['Content-Disposition', $contentDispositionHeader],
			['X-Accel-Buffering', 'no'],
		];
		$response
			->expects($this->exactly(count($calls)))
			->method('addHeader')
			->willReturnCallback(function () use (&$calls) {
				$expected = array_shift($calls);
				$this->assertSame($expected, func_get_args());
			});

		$this->plugin->httpGet($request, $response);
	}

	public function testHasPreview(): void {
		/** @var Directory&MockObject $node */
		$node = $this->createTestNode(Directory::class);

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
