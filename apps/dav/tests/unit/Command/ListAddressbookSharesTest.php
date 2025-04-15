<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\Command;

use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\Command\ListAddressbookShares;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\Sharing\SharingMapper;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class ListAddressbookSharesTest extends TestCase {

	private IUserManager&MockObject $userManager;
	private Principal&MockObject $principal;
	private CardDavBackend&MockObject $carddav;
	private SharingMapper $sharingMapper;
	private ListAddressbookShares $command;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->principal = $this->createMock(Principal::class);
		$this->carddav = $this->createMock(CardDavBackend::class);
		$this->sharingMapper = $this->createMock(SharingMapper::class);

		$this->command = new ListAddressbookShares(
			$this->userManager,
			$this->principal,
			$this->carddav,
			$this->sharingMapper,
		);
	}

	public function testUserUnknown(): void {
		$user = 'bob';

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage("User $user is unknown");

		$this->userManager->expects($this->once())
			->method('userExists')
			->with($user)
			->willReturn(false);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => $user,
		]);
	}

	public function testPrincipalNotFound(): void {
		$user = 'bob';

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage("Unable to fetch principal for user $user");

		$this->userManager->expects($this->once())
			->method('userExists')
			->with($user)
			->willReturn(true);

		$this->principal->expects($this->once())
			->method('getPrincipalByPath')
			->with('principals/users/' . $user)
			->willReturn(null);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => $user,
		]);
	}

	public function testNoAddressbookShares(): void {
		$user = 'bob';

		$this->userManager->expects($this->once())
			->method('userExists')
			->with($user)
			->willReturn(true);

		$this->principal->expects($this->once())
			->method('getPrincipalByPath')
			->with('principals/users/' . $user)
			->willReturn([
				'uri' => 'principals/users/' . $user,
			]);

		$this->principal->expects($this->once())
			->method('getGroupMembership')
			->willReturn([]);
		$this->principal->expects($this->once())
			->method('getCircleMembership')
			->willReturn([]);

		$this->sharingMapper->expects($this->once())
			->method('getSharesByPrincipals')
			->willReturn([]);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => $user,
		]);

		$this->assertStringContainsString(
			"User $user has no addressbook shares",
			$commandTester->getDisplay()
		);
	}

	public function testFilterByAddressbookId(): void {
		$user = 'bob';

		$this->userManager->expects($this->once())
			->method('userExists')
			->with($user)
			->willReturn(true);

		$this->principal->expects($this->once())
			->method('getPrincipalByPath')
			->with('principals/users/' . $user)
			->willReturn([
				'uri' => 'principals/users/' . $user,
			]);

		$this->principal->expects($this->once())
			->method('getGroupMembership')
			->willReturn([]);
		$this->principal->expects($this->once())
			->method('getCircleMembership')
			->willReturn([]);

		$this->sharingMapper->expects($this->once())
			->method('getSharesByPrincipals')
			->willReturn([
				[
					'id' => 1000,
					'principaluri' => 'principals/users/bob',
					'type' => 'addressbook',
					'access' => 2,
					'resourceid' => 10
				],
				[
					'id' => 1001,
					'principaluri' => 'principals/users/bob',
					'type' => 'addressbook',
					'access' => 3,
					'resourceid' => 11
				],
			]);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => $user,
			'--addressbook-id' => 10,
		]);

		$this->assertStringNotContainsString(
			'1001',
			$commandTester->getDisplay()
		);
	}
}
