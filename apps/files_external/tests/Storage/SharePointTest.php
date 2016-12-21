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

use OCA\Files_External\Lib\SharePoint\ContextsFactory;
use OCA\Files_External\Lib\Storage\SharePoint;
use Office365\PHP\Client\Runtime\Auth\IAuthenticationContext;
use Office365\PHP\Client\Runtime\ClientObjectCollection;
use Office365\PHP\Client\SharePoint\ClientContext;
use Office365\PHP\Client\SharePoint\Folder;
use Office365\PHP\Client\SharePoint\ListCollection;
use Office365\PHP\Client\SharePoint\SPList;
use Office365\PHP\Client\SharePoint\Web;
use Test\TestCase;

class SharePointTest extends TestCase {

	/** @var  SharePoint */
	protected $storage;

	/** @var  ContextsFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $factory;

	/** @var  ClientContext|\PHPUnit_Framework_MockObject_MockObject */
	protected $clientContextMock;

	/** @var  string */
	protected $documentLibraryName = 'Fancy Documents';

	/** @var  SPList|\PHPUnit_Framework_MockObject_MockObject */
	protected $sharePointList;

	/** @var string */
	protected $exampleHost = 'example.foo';

	/** @var string */
	protected $exampleUser = 'alice';

	/** @var string */
	protected $examplePwd = 'a123456';

	public function setUp() {
		parent::setUp();

		$this->factory = $this->createMock(ContextsFactory::class);

		$parameters = [
			'host'            => $this->exampleHost,
			'documentLibrary' => $this->documentLibraryName,
			'user'            => $this->exampleUser,
			'password'        => $this->examplePwd,
			'contextFactory'  => $this->factory,
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
	 * prepare Mocks for SharePoint::getDocumentLibrary() call
	 */
	private function prepareMockForGetDocumentLibrary() {
		$this->prepareFactoryMocks(); // precondition

		$this->sharePointList = $this->createMock(SPList::class);

		$clientObjectCollectionMock = $this->createMock(ClientObjectCollection::class);
		$clientObjectCollectionMock->expects($this->once())
			->method('top')
			->with(1)
			->willReturnSelf();
		$clientObjectCollectionMock->expects($this->once())
			->method('getCount')
			->willReturn(1);
		$clientObjectCollectionMock->expects($this->once())
			->method('getData')
			->willReturn([$this->sharePointList]);

		$listCollectionMock = $this->createMock(ListCollection::class);
		$listCollectionMock->expects($this->once())
			->method('filter')
			->with('Title eq "' . $this->documentLibraryName . '"')
			->willReturn($clientObjectCollectionMock);

		$webMock = $this->createMock(Web::class);
		$webMock->expects($this->once())
			->method('getLists')
			->willReturn($listCollectionMock);

		$this->clientContextMock->expects($this->once())
			->method('getWeb')
			->willReturn($webMock);
		$this->clientContextMock->expects($this->once())
			->method('load')
			->with($clientObjectCollectionMock)
			->willReturnSelf();
		$this->clientContextMock->expects($this->once())
			->method('executeQuery');
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

	public function testStat() {
		$this->prepareMockForGetDocumentLibrary();

		$mtime = new \DateTime('yesterday');
		$size = 4096;

		$folderMock = $this->createMock(Folder::class);
		$folderMock->expects($this->exactly(2))
			->method('getProperty')
			->withConsecutive(['Length'], ['TimeLastModified'])
			->willReturnOnConsecutiveCalls($size, $mtime);

		$this->sharePointList->expects($this->once())
			->method('getRootFolder')
			->willReturn($folderMock);

		$rootData = $this->storage->stat('/');

		$this->assertSame($mtime, $rootData['mtime']);
		$this->assertSame($size, $rootData['size']);
		$this->assertTrue($mtime->getTimestamp() < $rootData['atime']);
	}

}
