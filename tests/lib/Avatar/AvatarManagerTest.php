<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Avatar;

use OC\Avatar\AvatarManager;
use OC\Avatar\PlaceholderAvatar;
use OC\Avatar\RemoteAvatar;
use OC\Avatar\UserAvatar;
use OC\KnownUser\KnownUserService;
use OC\User\Manager;
use OC\User\User;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\Config\IUserConfig;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Class AvatarManagerTest
 */
class AvatarManagerTest extends \Test\TestCase {
	/** @var AvatarManager | \PHPUnit\Framework\MockObject\MockObject */
	private $avatarManager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->avatarManager = $this->createInstanceWithMocks(AvatarManager::class);
	}

	public function testGetAvatarForSelf(): void {
		$user = $this->createMock(User::class);
		$user
			->expects($this->any())
			->method('getUID')
			->willReturn('valid-user');

		$user
			->expects($this->any())
			->method('isEnabled')
			->willReturn(true);

		// requesting user
		$this->mocks[IUserSession::class]->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$this->mocks[Manager::class]
			->expects($this->once())
			->method('get')
			->with('valid-user')
			->willReturn($user);

		$account = $this->createMock(IAccount::class);
		$this->mocks[IAccountManager::class]->expects($this->once())
			->method('getAccount')
			->with($user)
			->willReturn($account);

		$property = $this->createMock(IAccountProperty::class);
		$account->expects($this->once())
			->method('getProperty')
			->with(IAccountManager::PROPERTY_AVATAR)
			->willReturn($property);

		$property->expects($this->once())
			->method('getScope')
			->willReturn(IAccountManager::SCOPE_PRIVATE);

		$this->mocks[KnownUserService::class]->expects($this->any())
			->method('isKnownToUser')
			->with('valid-user', 'valid-user')
			->willReturn(true);

		$folder = $this->createMock(ISimpleFolder::class);
		$this->mocks[IAppData::class]
			->expects($this->once())
			->method('getFolder')
			->with('valid-user')
			->willReturn($folder);

		$expected = new UserAvatar($folder, $this->mocks[IL10N::class], $user, $this->mocks[LoggerInterface::class], $this->mocks[IConfig::class], $this->mocks[IUserConfig::class]);
		$this->assertEquals($expected, $this->avatarManager->getAvatar('valid-user'));
	}

	public function testGetAvatarValidUserDifferentCasing(): void {
		$user = $this->createMock(User::class);
		$this->mocks[Manager::class]->expects($this->once())
			->method('get')
			->with('vaLid-USER')
			->willReturn($user);

		$user->expects($this->once())
			->method('getUID')
			->willReturn('valid-user');

		$user
			->expects($this->any())
			->method('isEnabled')
			->willReturn(true);

		$this->mocks[IUserSession::class]->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$folder = $this->createMock(ISimpleFolder::class);
		$this->mocks[IAppData::class]
			->expects($this->once())
			->method('getFolder')
			->with('valid-user')
			->willReturn($folder);

		$account = $this->createMock(IAccount::class);
		$this->mocks[IAccountManager::class]->expects($this->once())
			->method('getAccount')
			->with($user)
			->willReturn($account);

		$property = $this->createMock(IAccountProperty::class);
		$account->expects($this->once())
			->method('getProperty')
			->with(IAccountManager::PROPERTY_AVATAR)
			->willReturn($property);

		$property->expects($this->once())
			->method('getScope')
			->willReturn(IAccountManager::SCOPE_FEDERATED);

		$expected = new UserAvatar($folder, $this->mocks[IL10N::class], $user, $this->mocks[LoggerInterface::class], $this->mocks[IConfig::class], $this->mocks[IUserConfig::class]);
		$this->assertEquals($expected, $this->avatarManager->getAvatar('vaLid-USER'));
	}

	public static function dataGetAvatarScopes(): array {
		return [
			// public access cannot see real avatar
			[IAccountManager::SCOPE_PRIVATE, true, false, true],
			// unknown users cannot see real avatar
			[IAccountManager::SCOPE_PRIVATE, false, false, true],
			// known users can see real avatar
			[IAccountManager::SCOPE_PRIVATE, false, true, false],
			[IAccountManager::SCOPE_LOCAL, false, false, false],
			[IAccountManager::SCOPE_LOCAL, true, false, false],
			[IAccountManager::SCOPE_FEDERATED, false, false, false],
			[IAccountManager::SCOPE_FEDERATED, true, false, false],
			[IAccountManager::SCOPE_PUBLISHED, false, false, false],
			[IAccountManager::SCOPE_PUBLISHED, true, false, false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetAvatarScopes')]
	public function testGetAvatarScopes($avatarScope, $isPublicCall, $isKnownUser, $expectedPlaceholder): void {
		if ($isPublicCall) {
			$requestingUser = null;
		} else {
			$requestingUser = $this->createMock(IUser::class);
			$requestingUser->method('getUID')->willReturn('requesting-user');
		}

		// requesting user
		$this->mocks[IUserSession::class]->expects($this->once())
			->method('getUser')
			->willReturn($requestingUser);

		$user = $this->createMock(User::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('valid-user');

		$user
			->expects($this->any())
			->method('isEnabled')
			->willReturn(true);

		$this->mocks[Manager::class]
			->expects($this->once())
			->method('get')
			->with('valid-user')
			->willReturn($user);

		$account = $this->createMock(IAccount::class);
		$this->mocks[IAccountManager::class]->expects($this->once())
			->method('getAccount')
			->with($user)
			->willReturn($account);

		$property = $this->createMock(IAccountProperty::class);
		$account->expects($this->once())
			->method('getProperty')
			->with(IAccountManager::PROPERTY_AVATAR)
			->willReturn($property);

		$property->expects($this->once())
			->method('getScope')
			->willReturn($avatarScope);

		$folder = $this->createMock(ISimpleFolder::class);
		$this->mocks[IAppData::class]
			->expects($this->once())
			->method('getFolder')
			->with('valid-user')
			->willReturn($folder);

		if (!$isPublicCall) {
			$this->mocks[KnownUserService::class]->expects($this->any())
				->method('isKnownToUser')
				->with('requesting-user', 'valid-user')
				->willReturn($isKnownUser);
		} else {
			$this->mocks[KnownUserService::class]->expects($this->never())
				->method('isKnownToUser');
		}

		if ($expectedPlaceholder) {
			$expected = new PlaceholderAvatar($folder, $user, $this->mocks[IConfig::class], $this->mocks[LoggerInterface::class], $this->mocks[IUserConfig::class]);
		} else {
			$expected = new UserAvatar($folder, $this->mocks[IL10N::class], $user, $this->mocks[LoggerInterface::class], $this->mocks[IConfig::class], $this->mocks[IUserConfig::class]);
		}
		$this->assertEquals($expected, $this->avatarManager->getAvatar('valid-user'));
	}

	public static function dataCanCacheAvatarLongTerm(): array {
		return [
			'federated is the same for everyone' => [IAccountManager::SCOPE_FEDERATED, true, true],
			'no scope resolves to one placeholder' => ['', true, true],
			'private depends on the viewer' => [IAccountManager::SCOPE_PRIVATE, true, false],
			'disabled' => [IAccountManager::SCOPE_FEDERATED, false, false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataCanCacheAvatarLongTerm')]
	public function testCanCacheAvatarLongTerm(string $scope, bool $enabled, bool $expected): void {
		$user = $this->createMock(User::class);
		$user->method('getUID')->willReturn('valid-user');
		$user->method('isEnabled')->willReturn($enabled);
		$this->mocks[Manager::class]->method('get')->with('valid-user')->willReturn($user);

		$property = $this->createMock(IAccountProperty::class);
		$property->method('getScope')->willReturn($scope);
		$account = $this->createMock(IAccount::class);
		$account->method('getProperty')->with(IAccountManager::PROPERTY_AVATAR)->willReturn($property);
		$this->mocks[IAccountManager::class]->method('getAccount')->with($user)->willReturn($account);

		$this->assertEquals($expected, $this->avatarManager->canCacheAvatarLongTerm('valid-user'));
	}

	public function testCannotCacheAnAvatarForAnUnknownUser(): void {
		$this->mocks[Manager::class]->method('get')->with('nobody')->willReturn(null);

		$this->assertFalse($this->avatarManager->canCacheAvatarLongTerm('nobody'));
	}

	public function testGetAvatarInvalidUser(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('user does not exist');

		$this->mocks[Manager::class]
			->expects($this->once())
			->method('get')
			->with('invalidUser')
			->willReturn(null);

		$this->avatarManager->getAvatar('invalidUser');
	}

	public function testGetAvatarForRemoteUser(): void {
		$cloudId = 'user@https://remote.example.com';

		$this->mocks[Manager::class]
			->expects($this->once())
			->method('get')
			->willReturn(null);

		$resolvedCloudId = $this->createMock(ICloudId::class);
		$resolvedCloudId->method('getUser')->willReturn('user');
		$resolvedCloudId->method('getRemote')->willReturn('https://remote.example.com');
		$resolvedCloudId->method('getDisplayId')->willReturn('user@remote.example.com');

		$this->mocks[ICloudIdManager::class]->expects($this->once())
			->method('isValidCloudId')
			->with($cloudId)
			->willReturn(true);
		$this->mocks[ICloudIdManager::class]->method('resolveCloudId')
			->with($cloudId)
			->willReturn($resolvedCloudId);
		$this->overwriteService(ICloudIdManager::class, $this->mocks[ICloudIdManager::class]);

		$this->mocks[IAppData::class]->expects($this->once())->method('getFolder');
		$this->mocks[IAccountManager::class]->expects($this->never())->method('getAccount');

		$avatar = $this->avatarManager->getAvatar($cloudId);

		$this->assertInstanceOf(RemoteAvatar::class, $avatar);
		$this->assertTrue($avatar->exists());
		$this->assertTrue($avatar->isCustomAvatar());
		$this->assertSame('user@remote.example.com', $avatar->getDisplayName());
	}

	public function testGetAvatarThrowsForUnknownUserThatIsNotACloudId(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('user does not exist');

		$this->mocks[Manager::class]
			->expects($this->once())
			->method('get')
			->with('invalidUser')
			->willReturn(null);

		$this->mocks[ICloudIdManager::class]->expects($this->once())
			->method('isValidCloudId')
			->with('invalidUser')
			->willReturn(false);

		$this->avatarManager->getAvatar('invalidUser');
	}
}
