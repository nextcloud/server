<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
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

namespace Test\Security\IdentityProof;

use OC\Security\IdentityProof\Key;
use OC\Security\IdentityProof\Manager;
use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IUser;
use OCP\Security\ICrypto;
use Test\TestCase;

class ManagerTest extends TestCase  {
	/** @var IAppData|\PHPUnit_Framework_MockObject_MockObject */
	private $appData;
	/** @var ICrypto|\PHPUnit_Framework_MockObject_MockObject */
	private $crypto;
	/** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
	private $manager;

	public function setUp() {
		parent::setUp();
		$this->appData = $this->createMock(IAppData::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->appData,
				$this->crypto
			])
			->setMethods(['generateKeyPair'])
			->getMock();
	}

	public function testGetKeyWithExistingKey() {
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('MyUid');
		$folder = $this->createMock(ISimpleFolder::class);
		$privateFile = $this->createMock(ISimpleFile::class);
		$privateFile
			->expects($this->once())
			->method('getContent')
			->willReturn('EncryptedPrivateKey');
		$publicFile = $this->createMock(ISimpleFile::class);
		$publicFile
			->expects($this->once())
			->method('getContent')
			->willReturn('MyPublicKey');
		$this->crypto
			->expects($this->once())
			->method('decrypt')
			->with('EncryptedPrivateKey')
			->willReturn('MyPrivateKey');
		$folder
			->expects($this->at(0))
			->method('getFile')
			->with('private')
			->willReturn($privateFile);
		$folder
			->expects($this->at(1))
			->method('getFile')
			->with('public')
			->willReturn($publicFile);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('MyUid')
			->willReturn($folder);

		$expected = new Key('MyPublicKey', 'MyPrivateKey');
		$this->assertEquals($expected, $this->manager->getKey($user));
	}

	public function testGetKeyWithNotExistingKey() {
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->exactly(3))
			->method('getUID')
			->willReturn('MyUid');
		$this->appData
			->expects($this->at(0))
			->method('getFolder')
			->with('MyUid')
			->willThrowException(new \Exception());
		$this->manager
			->expects($this->once())
			->method('generateKeyPair')
			->willReturn(['MyNewPublicKey', 'MyNewPrivateKey']);
		$this->appData
			->expects($this->at(1))
			->method('newFolder')
			->with('MyUid');
		$folder = $this->createMock(ISimpleFolder::class);
		$this->crypto
			->expects($this->once())
			->method('encrypt')
			->with('MyNewPrivateKey')
			->willReturn('MyNewEncryptedPrivateKey');
		$privateFile = $this->createMock(ISimpleFile::class);
		$privateFile
			->expects($this->once())
			->method('putContent')
			->with('MyNewEncryptedPrivateKey');
		$publicFile = $this->createMock(ISimpleFile::class);
		$publicFile
			->expects($this->once())
			->method('putContent')
			->with('MyNewPublicKey');
		$folder
			->expects($this->at(0))
			->method('newFile')
			->with('private')
			->willReturn($privateFile);
		$folder
			->expects($this->at(1))
			->method('newFile')
			->with('public')
			->willReturn($publicFile);
		$this->appData
			->expects($this->at(2))
			->method('getFolder')
			->with('MyUid')
			->willReturn($folder);


		$expected = new Key('MyNewPublicKey', 'MyNewPrivateKey');
		$this->assertEquals($expected, $this->manager->getKey($user));
	}

	public function testGenerateKeyPair() {
		$manager = new Manager(
			$this->appData,
			$this->crypto
		);
		$data = 'MyTestData';

		list($resultPublicKey, $resultPrivateKey) = $this->invokePrivate($manager, 'generateKeyPair');
		openssl_sign($data, $signature, $resultPrivateKey);
		$details = openssl_pkey_get_details(openssl_pkey_get_public($resultPublicKey));

		$this->assertSame(1, openssl_verify($data, $signature, $resultPublicKey));
		$this->assertSame(2048, $details['bits']);
	}
}
