<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Command;

use OCA\DAV\Command\ListenerIntrospector;
use OCA\DAV\Command\ShowListeners;
use OCP\Files\ISetupManager;
use OCP\IConfig;
use OCP\IRequestId;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

/**
 * The introspection internals are covered by ListenerIntrospectorTest; this
 * class covers the command's option handling. The happy path is not unit
 * tested because it constructs the full OCA\DAV\Server.
 *
 * @package OCA\DAV\Tests\unit\Command
 */
class ShowListenersTest extends TestCase {
	private IConfig&MockObject $config;
	private IRequestId&MockObject $requestId;
	private IUserManager&MockObject $userManager;
	private IUserSession&MockObject $userSession;
	private ISetupManager&MockObject $setupManager;
	private ShowListeners $command;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->requestId = $this->createMock(IRequestId::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->setupManager = $this->createMock(ISetupManager::class);

		$this->command = new ShowListeners(
			$this->config,
			$this->requestId,
			$this->userManager,
			$this->userSession,
			$this->setupManager,
			new ListenerIntrospector(),
		);
	}

	public function testExecuteFailsForUnknownUser(): void {
		$this->userManager->expects($this->once())
			->method('get')
			->with('nope')
			->willReturn(null);
		$this->userSession->expects($this->never())
			->method('setUser');

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['--user' => 'nope']);

		$this->assertSame(ShowListeners::FAILURE, $exitCode);
		$this->assertStringContainsString('User "nope" does not exist.', $tester->getDisplay());
	}

	public function testExecuteSetsUpSessionForExistingUser(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->expects($this->once())
			->method('get')
			->with('alice')
			->willReturn($user);
		$this->userSession->expects($this->once())
			->method('setUser')
			->with($user);

		$tester = new CommandTester($this->command);
		// Building the full DAV server is expected to fail in a bare unit test
		// environment; asserting on the session setup and the graceful error is
		// all this test is for.
		$exitCode = $tester->execute(['--user' => 'alice']);

		if ($exitCode === ShowListeners::FAILURE) {
			$this->assertStringContainsString('Could not', $tester->getDisplay());
		}
	}
}
