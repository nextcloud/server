<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OCA\Files_External\Tests\Storage;

use OCA\Files_External\Lib\SharePoint\NotFoundException;
use OCP\Files\FileInfo;
use OCA\Files_External\Lib\SharePoint\ContextsFactory;
use OCA\Files_External\Lib\SharePoint\SharePointClient;
use OCA\Files_External\Lib\SharePoint\SharePointClientFactory;
use OCA\Files_External\Lib\Storage\SharePoint;
use Office365\PHP\Client\Runtime\Auth\IAuthenticationContext;
use Office365\PHP\Client\SharePoint\ClientContext;
use Office365\PHP\Client\SharePoint\File;
use Office365\PHP\Client\SharePoint\Folder;
use Office365\PHP\Client\SharePoint\SPList;
use Test\TestCase;

class SharePointTest extends TestCase {

	/** @var  SharePoint */
	protected $storage;

	/** @var  ContextsFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $factory;

	/** @var  ClientContext|\PHPUnit_Framework_MockObject_MockObject */
	protected $clientContextMock;

	/** @var  string */
	protected $documentLibraryTitle = 'Fancy Documents';

	/** @var  SPList|\PHPUnit_Framework_MockObject_MockObject */
	protected $sharePointList;

	/** @var string */
	protected $exampleHost = 'example.foo';

	/** @var string */
	protected $exampleUser = 'alice';

	/** @var string */
	protected $examplePwd = 'a123456';

	/** @var  SharePointClientFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $clientFactory;

	/** @var  SharePointClient|\PHPUnit_Framework_MockObject_MockObject */
	protected $client;

	public function setUp() {
		parent::setUp();

		$this->factory = $this->createMock(ContextsFactory::class);
		$this->clientFactory = $this->createMock(SharePointClientFactory::class);
		$this->client = $this->createMock(SharePointClient::class);

		$this->clientFactory->expects($this->any())
			->method('getClient')
			->willReturn($this->client);

		$parameters = [
			'host'                    => $this->exampleHost,
			'documentLibrary'         => $this->documentLibraryTitle,
			'user'                    => $this->exampleUser,
			'password'                => $this->examplePwd,
			'contextFactory'          => $this->factory,
			'sharePointClientFactory' => $this->clientFactory,
		];

		$this->storage = new SharePoint($parameters);
	}

	private function prepareFactoryMocks() {
		$authContextMock = $this->createMock(IAuthenticationContext::class);
		$this->clientContextMock = $this->createMock(ClientContext::class);

		$this->factory->expects($this->once())
			->method('getAuthContext')
			->with($this->exampleUser, $this->examplePwd)
			->willReturn($authContextMock);
		$this->factory->expects($this->once())
			->method('getClientContext')
			->with($this->exampleHost, $authContextMock)
			->willReturn($this->clientContextMock);

	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testBadDocumentLibraryName() {
		$parameters = [
			'host'            => 'example.foo',
			'documentLibrary' => 'foo" or bar eq 42',
			'user'            => 'alicce',
			'password'        => 'asdf',
		];

		new SharePoint($parameters);
	}

	public function pathProvider() {
		return [
			['/', null],
			['', null],
			['Paperwork', null],
			['Paperwork/', null],
			['/Paperwork/', null],
			['My Documents', null],
			['Paperwork/This and That/Bills/', null],
			['Textfile.txt', 26624],
			['Paperwork/Letter Template.ott', 26624],
			['Paperwork/This and That/Foobar.ora', 26624],
		];
	}

	/**
	 * @dataProvider pathProvider
	 */
	public function testStatExisting($path, $returnSize) {
		$mtime = new \DateTime(null, new \DateTimeZone('Z'));
		$mtime->sub(new \DateInterval('P2D'));
		// a SP time string looks like: 2017-03-22T16:17:23Z
		$returnMTime = $mtime->format('o-m-d\TH:i:se');
		$size = $returnSize ?: FileInfo::SPACE_UNKNOWN;

		$folderMock = $this->createMock(Folder::class);
		$folderMock->expects($this->exactly(2))
			->method('getProperty')
			->withConsecutive(['Length'], ['TimeLastModified'])
			->willReturnOnConsecutiveCalls($returnSize, $returnMTime);

		$serverPath = '/' . $this->documentLibraryTitle;
		if(trim($path, '/') !== '') {
			$serverPath .= '/' . trim($path, '/');
		}

		$this->client->expects($this->once())
			->method('fetchFileOrFolder')
			->with($serverPath, [SharePoint::SP_PROPERTY_SIZE, SharePoint::SP_PROPERTY_MTIME])
			->willReturn($folderMock);

		$data = $this->storage->stat($path);

		$this->assertSame($mtime->getTimestamp(), $data['mtime']);
		$this->assertSame($size, $data['size']);
		$this->assertTrue($mtime->getTimestamp() < $data['atime']);
	}

	public function testStatNotExisting() {
		$path = '/foobar/bar.foo';
		$serverPath = '/' . $this->documentLibraryTitle . '/' . trim($path, '/');

		$this->client->expects($this->once())
			->method('fetchFileOrFolder')
			->with($serverPath, [SharePoint::SP_PROPERTY_SIZE, SharePoint::SP_PROPERTY_MTIME])
			->willThrowException(new NotFoundException());

		$this->assertFalse($this->storage->stat($path));
	}

	/**
	 * @dataProvider pathProvider
	 */
	public function testFileType($path, $returnSize) {
		if($returnSize === null) {
			$return = $this->createMock(Folder::class);
			$expectedType = 'dir';
		} else {
			$return = $this->createMock(File::class);
			$expectedType = 'file';
		}

		$serverPath = '/' . $this->documentLibraryTitle;
		if(trim($path, '/') !== '') {
			$serverPath .= '/' . trim($path, '/');
		}

		$this->client->expects($this->once())
			->method('fetchFileOrFolder')
			->with($serverPath)
			->willReturn($return);

		$this->assertSame($expectedType, $this->storage->filetype($path));
	}

	public function testFileTypeNotExisting() {
		$path = '/dingdong/nothing.sh';

		$serverPath = '/' . $this->documentLibraryTitle;
		if(trim($path, '/') !== '') {
			$serverPath .= '/' . trim($path, '/');
		}

		$this->client->expects($this->once())
			->method('fetchFileOrFolder')
			->with($serverPath)
			->willThrowException(new NotFoundException());

		$this->assertFalse($this->storage->filetype($path));
	}

	public function  boolProvider() {
		return [
			[ true ],
			[ false ]
		];
	}

	/**
	 * @dataProvider boolProvider
	 */
	public function testFileExists($exists) {
		$path = '/dingdong/nothing.sh';

		$serverPath = '/' . $this->documentLibraryTitle;
		if(trim($path, '/') !== '') {
			$serverPath .= '/' . trim($path, '/');
		}

		$invocationMocker = $this->client->expects($this->once())
			->method('fetchFileOrFolder')
			->with($serverPath);
		if($exists) {
			$invocationMocker->willReturn($this->createMock(File::class));
		} else {
			$invocationMocker->willThrowException(new NotFoundException());
		}

		$this->assertSame($exists, $this->storage->file_exists($path));
	}

}
