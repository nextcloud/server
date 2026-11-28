<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\User;

use OC\Accounts\AccountManager;
use OC\Core\Command\User\SyncAccountDataCommand;
use OC\User\Database;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\IAvatar;
use OCP\IAvatarManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\Backend\IGetDisplayNameBackend;
use OCP\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class SyncAccountDataCommandTest extends TestCase {
	private IUserManager&MockObject $userManager;
	private AccountManager&MockObject $accountManager;
	private IAvatarManager&MockObject $avatarManager;
	private InputInterface&MockObject $consoleInput;
	private OutputInterface&MockObject $consoleOutput;
	private SyncAccountDataCommand $command;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->accountManager = $this->createMock(AccountManager::class);
		$this->avatarManager = $this->createMock(IAvatarManager::class);
		$this->consoleInput = $this->createMock(InputInterface::class);
		$this->consoleOutput = $this->createMock(OutputInterface::class);

		$this->consoleInput->method('getOption')
			->willReturnMap([
				['limit', '500'],
				['offset', '0'],
			]);

		$this->command = new SyncAccountDataCommand($this->userManager, $this->accountManager, $this->avatarManager);
	}

	private function createUser(string $displayName, bool $backendProvidesDisplayName = true): IUser&MockObject {
		$backend = $backendProvidesDisplayName
			? $this->createMock(Database::class)
			: $this->createMock(UserInterface::class);
		$this->assertSame($backendProvidesDisplayName, $backend instanceof IGetDisplayNameBackend);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('alice');
		$user->method('getDisplayName')
			->willReturn($displayName);
		$user->method('getBackend')
			->willReturn($backend);
		$this->userManager->method('searchDisplayName')
			->with('', 500, 0)
			->willReturn([$user]);

		return $user;
	}

	private function createAccount(IUser $user, string $displayName): IAccount&MockObject {
		$property = $this->createMock(IAccountProperty::class);
		$property->method('getValue')
			->willReturn($displayName);
		$property->method('getScope')
			->willReturn(IAccountManager::SCOPE_FEDERATED);
		$property->method('getVerified')
			->willReturn(IAccountManager::NOT_VERIFIED);
		$property->method('getVerificationData')
			->willReturn('');

		$account = $this->createMock(IAccount::class);
		$account->method('getProperty')
			->with(IAccountManager::PROPERTY_DISPLAYNAME)
			->willReturn($property);
		$this->accountManager->method('getAccount')
			->with($user)
			->willReturn($account);

		return $account;
	}

	public function testUpdatesOutdatedStoredDisplayName(): void {
		$user = $this->createUser('Alice Liddell');
		// getAccount() carries the display name of the backend, the stored one is outdated
		$account = $this->createAccount($user, 'Alice Liddell');
		$this->accountManager->method('getStoredDisplayName')
			->with($user)
			->willReturn('alice');

		$account->expects($this->once())
			->method('setProperty')
			->with(IAccountManager::PROPERTY_DISPLAYNAME, 'Alice Liddell', IAccountManager::SCOPE_FEDERATED, IAccountManager::NOT_VERIFIED, '');
		$this->accountManager->expects($this->once())
			->method('updateAccount')
			->with($account);

		$avatar = $this->createMock(IAvatar::class);
		$avatar->expects($this->once())
			->method('userChanged')
			->with('displayName', 'alice', 'Alice Liddell');
		$this->avatarManager->expects($this->once())
			->method('getAvatar')
			->with('alice')
			->willReturn($avatar);

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function testKeepsUpToDateStoredDisplayName(): void {
		$user = $this->createUser('Alice Liddell');
		$account = $this->createAccount($user, 'Alice Liddell');
		$this->accountManager->method('getStoredDisplayName')
			->with($user)
			->willReturn('Alice Liddell');

		$account->expects($this->never())
			->method('setProperty');
		$this->accountManager->expects($this->never())
			->method('updateAccount');
		$this->avatarManager->expects($this->never())
			->method('getAvatar');

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function testIgnoresBackendWithoutDisplayNames(): void {
		$user = $this->createUser('Alice Liddell', false);
		$account = $this->createAccount($user, 'Alice Liddell');

		$this->accountManager->expects($this->never())
			->method('getStoredDisplayName');
		$account->expects($this->never())
			->method('setProperty');
		$this->accountManager->expects($this->never())
			->method('updateAccount');
		$this->avatarManager->expects($this->never())
			->method('getAvatar');

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
