<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_External\Tests\SharePoint;

use OCA\Files_External\Lib\SharePoint\ContextsFactory;
use OCA\Files_External\Lib\SharePoint\SharePointClient;
use Office365\PHP\Client\Runtime\Auth\AuthenticationContext;
use Office365\PHP\Client\SharePoint\ClientContext;
use Office365\PHP\Client\SharePoint\File;
use Office365\PHP\Client\SharePoint\Folder;
use Office365\PHP\Client\SharePoint\FolderCollection;
use Office365\PHP\Client\SharePoint\Web;
use Test\TestCase;

class SharePointClientTest extends TestCase {
	/** @var  ContextsFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $contextsFactory;

	/** @var  string */
	protected $documentLibraryTitle;

	/** @var  SharePointClient */
	protected $client;

	public function setUp() {
		parent::setUp();

		$this->contextsFactory = $this->createMock(ContextsFactory::class);
		$credentials = ['user' => 'foobar', 'password' => 'barfoo'];
		$this->documentLibraryTitle = 'Our Docs';

		$this->client = new SharePointClient(
			$this->contextsFactory,
			'my.sp.server',
			$credentials,
			$this->documentLibraryTitle
		);
	}

	public function testFetchFileByFileOrFolder() {
		$path = '/' . $this->documentLibraryTitle . '/OurFile.txt';
		$properties = ['Length', 'TimeLastModified'];

		$this->contextsFactory->expects($this->once())
			->method('getAuthContext')
			->willReturn($this->createMock(AuthenticationContext::class));

		$fileMock = $this->createMock(File::class);

		$webMock = $this->createMock(Web::class);
		$webMock->expects($this->once())
			->method('getFileByServerRelativeUrl')
			->with($path)
			->willReturn($fileMock);

		$clientContextMock = $this->createMock(ClientContext::class);
		$clientContextMock->expects($this->once())
			->method('getWeb')
			->willReturn($webMock);
		$clientContextMock->expects($this->once())
			->method('load')
			->with($fileMock, $properties);
		$clientContextMock->expects($this->once())
			->method('executeQuery');

		$this->contextsFactory->expects($this->once())
			->method('getClientContext')
			->willReturn($clientContextMock);

		$fileObject = $this->client->fetchFileOrFolder($path, $properties);
		$this->assertSame($fileMock, $fileObject);
	}

	public function testFetchFolderByFileOrFolder() {
		$path = '/' . $this->documentLibraryTitle . '/Our Directory';
		$properties = ['Length', 'TimeLastModified'];

		$this->contextsFactory->expects($this->once())
			->method('getAuthContext')
			->willReturn($this->createMock(AuthenticationContext::class));

		$folderMock = $this->createMock(Folder::class);

		$webMock = $this->createMock(Web::class);
		$webMock->expects($this->never())
			->method('getFileByServerRelativeUrl');
		$webMock->expects($this->once())
			->method('getFolderByServerRelativeUrl')
			->with($path)
			->willReturn($folderMock);

		$clientContextMock = $this->createMock(ClientContext::class);
		$clientContextMock->expects($this->once())
			->method('getWeb')
			->willReturn($webMock);
		$clientContextMock->expects($this->once())
			->method('load')
			->withConsecutive([$folderMock, $properties]);
		$clientContextMock->expects($this->once())
			->method('executeQuery');

		$this->contextsFactory->expects($this->once())
			->method('getClientContext')
			->willReturn($clientContextMock);

		$folderObject = $this->client->fetchFileOrFolder($path, $properties);
		$this->assertSame($folderMock, $folderObject);
	}

	/**
	 * @expectedException \OCA\Files_External\Lib\SharePoint\NotFoundException
	 *
	 * also fully covers fetchFolder(), loadAndExecute(), createClientContext()
	 */
	public function testFetchNotExistingByFileOrFolder() {
		$path = '/' . $this->documentLibraryTitle . '/Our Directory/not-here.pdf';
		$properties = ['Length', 'TimeLastModified'];

		$this->contextsFactory->expects($this->once())
			->method('getAuthContext')
			->willReturn($this->createMock(AuthenticationContext::class));

		$fileMock = $this->createMock(File::class);
		$folderMock = $this->createMock(Folder::class);

		$webMock = $this->createMock(Web::class);
		$webMock->expects($this->once())
			->method('getFileByServerRelativeUrl')
			->with($path)
			->willReturn($fileMock);
		$webMock->expects($this->once())
			->method('getFolderByServerRelativeUrl')
			->with($path)
			->willReturn($folderMock);

		$clientContextMock = $this->createMock(ClientContext::class);
		$clientContextMock->expects($this->exactly(2))
			->method('getWeb')
			->willReturn($webMock);
		$clientContextMock->expects($this->exactly(2))
			->method('load')
			->withConsecutive([$fileMock, $properties], [$folderMock, $properties]);
		$clientContextMock->expects($this->at(2))
			->method('executeQuery')
			->willThrowException(new \Exception('The file /whatwasitsname does not exist.'));
		$clientContextMock->expects($this->at(5))
			->method('executeQuery')
			->willThrowException(new \Exception('Unknown Error'));

		$this->contextsFactory->expects($this->exactly(3))
			->method('getClientContext')
			->willReturn($clientContextMock);

		$this->client->fetchFileOrFolder($path, $properties);
	}

	public function testCreateFolderSuccess() {
		$dirName = 'New Project Dir';
		$parentPath = '/' . $this->documentLibraryTitle . '/Our Directory';
		$path = $parentPath . '/'. $dirName;

		$this->contextsFactory->expects($this->once())
			->method('getAuthContext')
			->willReturn($this->createMock(AuthenticationContext::class));

		$folderCollectionMock = $this->createMock(FolderCollection::class);
		$folderCollectionMock->expects($this->once())
			->method('add')
			->with($dirName);

		$folderMock = $this->createMock(Folder::class);
		$folderMock->expects($this->once())
			->method('getFolders')
			->willReturn($folderCollectionMock);

		$webMock = $this->createMock(Web::class);
		$webMock->expects($this->once())
			->method('getFolderByServerRelativeUrl')
			->with($parentPath)
			->willReturn($folderMock);

		$clientContextMock = $this->createMock(ClientContext::class);
		$clientContextMock->expects($this->once())
			->method('getWeb')
			->willReturn($webMock);
		$clientContextMock->expects($this->once())
			->method('executeQuery');

		$this->contextsFactory->expects($this->once())
			->method('getClientContext')
			->willReturn($clientContextMock);

		$this->client->createFolder($path);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testCreateFolderError() {
		$dirName = 'New Project Dir';
		$parentPath = '/' . $this->documentLibraryTitle . '/Our Directory';
		$path = $parentPath . '/'. $dirName;

		$this->contextsFactory->expects($this->once())
			->method('getAuthContext')
			->willReturn($this->createMock(AuthenticationContext::class));

		$folderCollectionMock = $this->createMock(FolderCollection::class);
		$folderCollectionMock->expects($this->once())
			->method('add')
			->with($dirName);

		$folderMock = $this->createMock(Folder::class);
		$folderMock->expects($this->once())
			->method('getFolders')
			->willReturn($folderCollectionMock);

		$webMock = $this->createMock(Web::class);
		$webMock->expects($this->once())
			->method('getFolderByServerRelativeUrl')
			->with($parentPath)
			->willReturn($folderMock);

		$clientContextMock = $this->createMock(ClientContext::class);
		$clientContextMock->expects($this->once())
			->method('getWeb')
			->willReturn($webMock);
		$clientContextMock->expects($this->once())
			->method('executeQuery')
			->willThrowException(new \Exception('Whatever'));

		$this->contextsFactory->expects($this->exactly(2))
			->method('getClientContext')
			->willReturn($clientContextMock);

		$this->client->createFolder($path);
	}

	public function  boolXBoolProvider() {
		return [
			[ true, true ],
			[ true, false ],
			[ false, false ],
			[ false, true ],
		];
	}

	/**
	 * @dataProvider boolXBoolProvider
	 */
	public function testDeleteFolder($successfull, $folderProvided) {
		$dirName = 'Target Project Dir';
		$path = '/' . $this->documentLibraryTitle . '/Our Directory/' . $dirName;

		$this->contextsFactory->expects($this->once())
			->method('getAuthContext')
			->willReturn($this->createMock(AuthenticationContext::class));

		$folderMock = $this->createMock(Folder::class);
		$folderMock->expects($this->once())
			->method('deleteObject');

		$webMock = $this->createMock(Web::class);
		$clientContextMock = $this->createMock(ClientContext::class);

		$folderParameter = $folderMock;
		if($folderProvided === false) {
			$folderParameter = null;

			$webMock->expects($this->once())
				->method('getFolderByServerRelativeUrl')
				->with($path)
				->willReturn($folderMock);

			$clientContextMock->expects($this->once())
				->method('getWeb')
				->willReturn($webMock);
		}

		if($successfull) {
			$getClientContextCalls = 1;
			$clientContextMock->expects($this->once())
				->method('executeQuery');
		} else {
			$getClientContextCalls = 2;
			$this->setExpectedException(\Exception::class);
			$exceptionAt = $folderProvided ? 0 : 1;
			$clientContextMock->expects($this->at($exceptionAt))
				->method('executeQuery')
				->willThrowException(new \Exception('Whatever'));
		}

		$this->contextsFactory->expects($this->exactly($getClientContextCalls))
			->method('getClientContext')
			->willReturn($clientContextMock);

		$this->client->deleteFolder($path, $folderParameter);
	}
}
