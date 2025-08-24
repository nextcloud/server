<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Core\Command\TwoFactorAuth;

use OC\Core\Command\TwoFactorAuth\Cleanup;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class CleanupTest extends TestCase {
	/** @var IRegistry|MockObject */
	private $registry;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var CommandTester */
	private $cmd;

	protected function setUp(): void {
		parent::setUp();

		$this->registry = $this->createMock(IRegistry::class);
		$this->userManager = $this->createMock(IUserManager::class);

		$cmd = new Cleanup($this->registry, $this->userManager);
		$this->cmd = new CommandTester($cmd);
	}

	public function testCleanup(): void {
		$this->registry->expects($this->once())
			->method('cleanUp')
			->with('u2f');

		$rc = $this->cmd->execute([
			'provider-id' => 'u2f',
		]);

		$this->assertEquals(0, $rc);
		$output = $this->cmd->getDisplay();
		$this->assertStringContainsString('All user-provider associations for provider u2f have been removed', $output);
	}
}
