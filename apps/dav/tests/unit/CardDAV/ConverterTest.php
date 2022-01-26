<?php
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
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Component\VCard;
use Test\TestCase;

class ConverterTest extends TestCase {

	/** @var IAccountManager|MockObject */
	private $accountManager;

	protected function setUp(): void {
		parent::setUp();

		$this->accountManager = $this->createMock(IAccountManager::class);
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
			->method('getProperties')
			->willReturnCallback(function () use ($user) {
				return [
					$this->getAccountPropertyMock(IAccountManager::PROPERTY_DISPLAYNAME, $user->getDisplayName(), IAccountManager::SCOPE_FEDERATED),
					$this->getAccountPropertyMock(IAccountManager::PROPERTY_ADDRESS, '', IAccountManager::SCOPE_LOCAL),
					$this->getAccountPropertyMock(IAccountManager::PROPERTY_WEBSITE, '', IAccountManager::SCOPE_LOCAL),
					$this->getAccountPropertyMock(IAccountManager::PROPERTY_EMAIL, $user->getEMailAddress(), IAccountManager::SCOPE_FEDERATED),
					$this->getAccountPropertyMock(IAccountManager::PROPERTY_AVATAR, $user->getAvatarImage(-1)->data(), IAccountManager::SCOPE_FEDERATED),
					$this->getAccountPropertyMock(IAccountManager::PROPERTY_PHONE, '', IAccountManager::SCOPE_LOCAL),
					$this->getAccountPropertyMock(IAccountManager::PROPERTY_TWITTER, '', IAccountManager::SCOPE_LOCAL),
				];
			});

		$accountManager = $this->createMock(IAccountManager::class);
		$accountManager->expects($this->any())->method('getAccount')->willReturn($account);

		return $accountManager;
	}

	/**
	 * @dataProvider providesNewUsers
	 */
	public function testCreation(?array $expectedVCard, ?string $displayName = null, ?string $eMailAddress = null, string $cloudId = null) {
		$user = $this->getUserMock((string)$displayName, $eMailAddress, $cloudId);
		$accountManager = $this->getAccountManager($user);

		$converter = new Converter($accountManager);
		$vCard = $converter->createCardFromUser($user);
		if ($expectedVCard !== null) {
			$this->assertInstanceOf(VCard::class, $vCard);
			$cardData = $vCard->jsonSerialize();
			$this->compareData($expectedVCard, $cardData);
		} else {
			$this->assertSame($expectedVCard, $vCard);
		}
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
				$this->fail('Expected data: ' . $key . ' not found.');
			}
		}
	}

	public function providesNewUsers(): array {
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
	 */
	public function testNameSplitter(string $expected, string $fullName) {
		$converter = new Converter($this->accountManager);
		$r = $converter->splitFullName($fullName);
		$r = implode(';', $r);
		$this->assertEquals($expected, $r);
	}

	public function providesNames(): array {
		return [
			['Sauron;;;;', 'Sauron'],
			['Baggins;Bilbo;;;', 'Bilbo Baggins'],
			['Tolkien;John;Ronald Reuel;;', 'John Ronald Reuel Tolkien'],
		];
	}

	/**
	 * @return IUser | MockObject
	 */
	protected function getUserMock(string $displayName, ?string $eMailAddress, ?string $cloudId) {
		$image0 = $this->createMock(IImage::class);
		$image0->method('mimeType')->willReturn('image/jpeg');
		$image0->method('data')->willReturn('123456789');
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('12345');
		$user->method('getDisplayName')->willReturn($displayName);
		$user->method('getEMailAddress')->willReturn($eMailAddress);
		$user->method('getCloudId')->willReturn($cloudId);
		$user->method('getAvatarImage')->willReturn($image0);
		return $user;
	}
}
