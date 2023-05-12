<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OCA\DAV\Tests\unit\CardDAV;

use OCA\DAV\CardDAV\Converter;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\IImage;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ConverterTest extends TestCase {

	/** @var IAccountManager|\PHPUnit\Framework\MockObject\MockObject */
	private $accountManager;
	/** @var IUserManager|(IUserManager&MockObject)|MockObject */
	private IUserManager|MockObject $userManager;

	protected function setUp(): void {
		parent::setUp();

		$this->accountManager = $this->createMock(IAccountManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
	}

	/**
	 * @return IAccountProperty|MockObject
	 */
	protected function getAccountPropertyMock(string $name, ?string $value, string $scope) {
		$property = $this->createMock(IAccountProperty::class);
		$property->expects($this->any())
			->method('getName')
			->willReturn($name);
		$property->expects($this->any())
			->method('getValue')
			->willReturn((string)$value);
		$property->expects($this->any())
			->method('getScope')
			->willReturn($scope);
		$property->expects($this->any())
			->method('getVerified')
			->willReturn(IAccountManager::NOT_VERIFIED);
		return $property;
	}

	public function getAccountManager(IUser $user) {
		$account = $this->createMock(IAccount::class);
		$account->expects($this->any())
			->method('getAllProperties')
			->willReturnCallback(function () use ($user) {
				yield $this->getAccountPropertyMock(IAccountManager::PROPERTY_DISPLAYNAME, $user->getDisplayName(), IAccountManager::SCOPE_FEDERATED);
				yield $this->getAccountPropertyMock(IAccountManager::PROPERTY_ADDRESS, '', IAccountManager::SCOPE_LOCAL);
				yield $this->getAccountPropertyMock(IAccountManager::PROPERTY_WEBSITE, '', IAccountManager::SCOPE_LOCAL);
				yield $this->getAccountPropertyMock(IAccountManager::PROPERTY_EMAIL, $user->getEMailAddress(), IAccountManager::SCOPE_FEDERATED);
				yield $this->getAccountPropertyMock(IAccountManager::PROPERTY_AVATAR, $user->getAvatarImage(-1)->data(), IAccountManager::SCOPE_FEDERATED);
				yield $this->getAccountPropertyMock(IAccountManager::PROPERTY_PHONE, '', IAccountManager::SCOPE_LOCAL);
				yield $this->getAccountPropertyMock(IAccountManager::PROPERTY_TWITTER, '', IAccountManager::SCOPE_LOCAL);
			});

		$accountManager = $this->getMockBuilder(IAccountManager::class)
			->disableOriginalConstructor()->getMock();

		$accountManager->expects($this->any())->method('getAccount')->willReturn($account);

		return $accountManager;
	}

	/**
	 * @dataProvider providesNewUsers
	 */
	public function testCreation($expectedVCard, $displayName = null, $eMailAddress = null, $cloudId = null): void {
		$user = $this->getUserMock((string)$displayName, $eMailAddress, $cloudId);
		$accountManager = $this->getAccountManager($user);

		$converter = new Converter($accountManager, $this->userManager);
		$vCard = $converter->createCardFromUser($user);
		if ($expectedVCard !== null) {
			$this->assertInstanceOf('Sabre\VObject\Component\VCard', $vCard);
			$cardData = $vCard->jsonSerialize();
			$this->compareData($expectedVCard, $cardData);
		} else {
			$this->assertSame($expectedVCard, $vCard);
		}
	}

	public function testManagerProp(): void {
		$user = $this->getUserMock("user", "user@domain.tld", "user@cloud.domain.tld");
		$user->method('getManagerUids')
			->willReturn(['mgr']);
		$this->userManager->expects(self::once())
			->method('getDisplayName')
			->with('mgr')
			->willReturn('Manager');
		$accountManager = $this->getAccountManager($user);

		$converter = new Converter($accountManager, $this->userManager);
		$vCard = $converter->createCardFromUser($user);

		$this->compareData(
			[
				'cloud' => 'user@cloud.domain.tld',
				'email' => 'user@domain.tld',
				'x-managersname' => 'Manager',
			],
			$vCard->jsonSerialize()
		);
	}

	protected function compareData($expected, $data) {
		foreach ($expected as $key => $value) {
			$found = false;
			foreach ($data[1] as $d) {
				if ($d[0] === $key && $d[3] === $value) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$this->assertTrue(false, 'Expected data: ' . $key . ' not found.');
			}
		}
	}

	public function providesNewUsers() {
		return [
			[
				null
			],
			[
				null,
				null,
				'foo@bar.net'
			],
			[
				[
					'cloud' => 'foo@cloud.net',
					'email' => 'foo@bar.net',
					'photo' => 'MTIzNDU2Nzg5',
				],
				null,
				'foo@bar.net',
				'foo@cloud.net'
			],
			[
				[
					'cloud' => 'foo@cloud.net',
					'email' => 'foo@bar.net',
					'fn' => 'Dr. Foo Bar',
					'photo' => 'MTIzNDU2Nzg5',
				],
				"Dr. Foo Bar",
				"foo@bar.net",
				'foo@cloud.net'
			],
			[
				[
					'cloud' => 'foo@cloud.net',
					'fn' => 'Dr. Foo Bar',
					'photo' => 'MTIzNDU2Nzg5',
				],
				"Dr. Foo Bar",
				null,
				"foo@cloud.net"
			],
			[
				[
					'cloud' => 'foo@cloud.net',
					'fn' => 'Dr. Foo Bar',
					'photo' => 'MTIzNDU2Nzg5',
				],
				'Dr. Foo Bar',
				'',
				'foo@cloud.net'
			],
		];
	}

	/**
	 * @dataProvider providesNames
	 * @param $expected
	 * @param $fullName
	 */
	public function testNameSplitter($expected, $fullName): void {
		$converter = new Converter($this->accountManager, $this->userManager);
		$r = $converter->splitFullName($fullName);
		$r = implode(';', $r);
		$this->assertEquals($expected, $r);
	}

	public function providesNames() {
		return [
			['Sauron;;;;', 'Sauron'],
			['Baggins;Bilbo;;;', 'Bilbo Baggins'],
			['Tolkien;John;Ronald Reuel;;', 'John Ronald Reuel Tolkien'],
		];
	}

	/**
	 * @param $displayName
	 * @param $eMailAddress
	 * @param $cloudId
	 * @return IUser | \PHPUnit\Framework\MockObject\MockObject
	 */
	protected function getUserMock(string $displayName, ?string $eMailAddress, ?string $cloudId) {
		$image0 = $this->getMockBuilder(IImage::class)->disableOriginalConstructor()->getMock();
		$image0->method('mimeType')->willReturn('image/jpeg');
		$image0->method('data')->willReturn('123456789');
		$user = $this->getMockBuilder(IUser::class)->disableOriginalConstructor()->getMock();
		$user->method('getUID')->willReturn('12345');
		$user->method('getDisplayName')->willReturn($displayName);
		$user->method('getEMailAddress')->willReturn($eMailAddress);
		$user->method('getCloudId')->willReturn($cloudId);
		$user->method('getAvatarImage')->willReturn($image0);
		return $user;
	}
}
