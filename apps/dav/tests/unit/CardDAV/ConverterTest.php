<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\CardDAV;

use OCA\DAV\CardDAV\Converter;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\IImage;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ConverterTest extends TestCase {
	private IAccountManager&MockObject $accountManager;
	private IUserManager&MockObject $userManager;
	private IURLGenerator&MockObject $urlGenerator;
	private LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->accountManager = $this->createMock(IAccountManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->logger = $this->createMock(LoggerInterface::class);
	}

	/**
	 * @return IAccountProperty&MockObject
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

		$accountManager = $this->createMock(IAccountManager::class);

		$accountManager->expects($this->any())
			->method('getAccount')
			->willReturn($account);

		return $accountManager;
	}

	/**
	 * @dataProvider providesNewUsers
	 */
	public function testCreation($expectedVCard, $displayName = null, $eMailAddress = null, $cloudId = null): void {
		$user = $this->getUserMock((string)$displayName, $eMailAddress, $cloudId);
		$accountManager = $this->getAccountManager($user);

		$converter = new Converter($accountManager, $this->userManager, $this->urlGenerator, $this->logger);
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
		$user = $this->getUserMock('user', 'user@domain.tld', 'user@cloud.domain.tld');
		$user->method('getManagerUids')
			->willReturn(['mgr']);
		$this->userManager->expects(self::once())
			->method('getDisplayName')
			->with('mgr')
			->willReturn('Manager');
		$accountManager = $this->getAccountManager($user);

		$converter = new Converter($accountManager, $this->userManager, $this->urlGenerator, $this->logger);
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

	protected function compareData(array $expected, array $data): void {
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

	public static function providesNewUsers(): array {
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
				'Dr. Foo Bar',
				'foo@bar.net',
				'foo@cloud.net'
			],
			[
				[
					'cloud' => 'foo@cloud.net',
					'fn' => 'Dr. Foo Bar',
					'photo' => 'MTIzNDU2Nzg5',
				],
				'Dr. Foo Bar',
				null,
				'foo@cloud.net'
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
	public function testNameSplitter(string $expected, string $fullName): void {
		$converter = new Converter($this->accountManager, $this->userManager, $this->urlGenerator, $this->logger);
		$r = $converter->splitFullName($fullName);
		$r = implode(';', $r);
		$this->assertEquals($expected, $r);
	}

	public static function providesNames(): array {
		return [
			['Sauron;;;;', 'Sauron'],
			['Baggins;Bilbo;;;', 'Bilbo Baggins'],
			['Tolkien;John;Ronald Reuel;;', 'John Ronald Reuel Tolkien'],
		];
	}

	/**
	 * @return IUser&MockObject
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
