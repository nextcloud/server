<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\TwoFactorAuth;

use OC\Core\Command\TwoFactorAuth\Cleanup;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class CleanupTest extends TestCase {
	/** @var CommandTester */
	private $cmd;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$cmd = $this->createInstanceWithMocks(Cleanup::class);
		$this->cmd = new CommandTester($cmd);
	}

	public function testCleanup(): void {
		$this->mocks[IRegistry::class]->expects($this->once())
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
