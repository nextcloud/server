<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Testing;

use OC\Core\Command\Testing\PrepareCi;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class PrepareCiTest extends TestCase {
	private IConfig&MockObject $config;
	private CommandTester $commandTester;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->commandTester = new CommandTester(new PrepareCi($this->config));
	}

	public function testSetsValues(): void {
		$this->config->method('getSystemValue')
			->with('openssl', [])
			->willReturn([]);
		$this->config->expects($this->once())
			->method('setSystemValues')
			->with([
				'hashing_default_password' => true,
				'hashingCost' => 4,
				'openssl' => ['private_key_bits' => 2048],
			]);

		$this->assertSame(0, $this->commandTester->execute([]));

		$display = $this->commandTester->getDisplay();
		$this->assertStringContainsString('Set hashing_default_password to true', $display);
		$this->assertStringContainsString('Set hashingCost to 4', $display);
		$this->assertStringContainsString('Set openssl to {"private_key_bits":2048}', $display);
	}

	public function testKeepsOtherOpensslOptions(): void {
		$this->config->method('getSystemValue')
			->with('openssl', [])
			->willReturn(['config' => '/etc/ssl/openssl.cnf', 'private_key_bits' => 4096]);
		$this->config->expects($this->once())
			->method('setSystemValues')
			->with($this->callback(static fn (array $values): bool
				=> $values['openssl'] === ['config' => '/etc/ssl/openssl.cnf', 'private_key_bits' => 2048]));

		$this->assertSame(0, $this->commandTester->execute([]));
	}
}
