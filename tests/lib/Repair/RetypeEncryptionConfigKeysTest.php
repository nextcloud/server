<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair;

use OC\Repair\RetypeEncryptionConfigKeys;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * @group DB
 */
class RetypeEncryptionConfigKeysTest extends TestCase {
	private IAppConfig $appConfig;
	private IOutput&MockObject $output;
	private RetypeEncryptionConfigKeys $repair;

	protected function setUp(): void {
		parent::setUp();
		$this->appConfig = Server::get(IAppConfig::class);
		$this->output = $this->createMock(IOutput::class);
		$this->repair = new RetypeEncryptionConfigKeys($this->appConfig);
		// Clean slate in case previous test runs or occ invocations left residue
		$this->appConfig->deleteKey('core', 'encryption_enabled');
		$this->appConfig->deleteKey('encryption', 'encryptHomeStorage');
	}

	protected function tearDown(): void {
		$this->appConfig->deleteKey('core', 'encryption_enabled');
		$this->appConfig->deleteKey('encryption', 'encryptHomeStorage');
		parent::tearDown();
	}

	public function testAbsentKeyIsNoOp(): void {
		$this->output->expects($this->never())->method('info');
		$this->repair->run($this->output);
		// No exception, no write
		$this->assertTrue(true);
	}

	public static function dataStringValues(): array {
		return [
			['yes', true],
			['no', false],
			['1', true],
			['0', false],
			['true', true],
			['false', false],
			['on', true],
			['YES', true],
		];
	}

	/**
	 * @dataProvider dataStringValues
	 */
	public function testEncryptionEnabledIsRetypedFromString(string $raw, bool $expected): void {
		$this->appConfig->setValueString('core', 'encryption_enabled', $raw);

		$this->repair->run($this->output);

		$this->assertSame(IAppConfig::VALUE_BOOL, $this->appConfig->getValueType('core', 'encryption_enabled'));
		$this->assertSame($expected, $this->appConfig->getValueBool('core', 'encryption_enabled', !$expected));
	}

	/**
	 * @dataProvider dataStringValues
	 */
	public function testEncryptHomeStorageIsRetypedFromString(string $raw, bool $expected): void {
		$this->appConfig->setValueString('encryption', 'encryptHomeStorage', $raw);

		$this->repair->run($this->output);

		$this->assertSame(IAppConfig::VALUE_BOOL, $this->appConfig->getValueType('encryption', 'encryptHomeStorage'));
		$this->assertSame($expected, $this->appConfig->getValueBool('encryption', 'encryptHomeStorage', !$expected));
	}

	public function testAlreadyBoolIsNoOp(): void {
		$this->appConfig->setValueBool('core', 'encryption_enabled', true);
		$this->appConfig->setValueBool('encryption', 'encryptHomeStorage', false);

		// Should log "already typed" messages but not re-write
		$this->output->expects($this->exactly(2))
			->method('info')
			->with($this->stringContains('already typed'));

		$this->repair->run($this->output);

		$this->assertTrue($this->appConfig->getValueBool('core', 'encryption_enabled', false));
		$this->assertFalse($this->appConfig->getValueBool('encryption', 'encryptHomeStorage', true));
	}
}
