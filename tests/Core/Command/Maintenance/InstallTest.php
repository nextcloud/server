<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Maintenance;

use bantu\IniGetWrapper\IniGetWrapper;
use OC\Core\Command\Maintenance\Install;
use OC\SystemConfig;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Test\TestCase;

class InstallTest extends TestCase {
	private Install $command;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->command = new Install(
			$this->createMock(SystemConfig::class),
			$this->createMock(IniGetWrapper::class),
		);
	}

	/**
	 * @param array<string, string|bool> $parameters
	 * @return array The installer options built from the command line input
	 */
	private function validateInput(array $parameters): array {
		$input = new ArrayInput(array_merge([
			'--database-name' => 'nextcloud',
			'--database-user' => 'admin',
			'--database-pass' => 'admin-password',
			'--admin-pass' => 'admin-password',
		], $parameters), $this->command->getDefinition());

		return self::invokePrivate($this->command, 'validateInput', [$input, new NullOutput(), ['sqlite', 'mysql', 'pgsql', 'oci']]);
	}

	public static function encryptionOptions(): array {
		return [
			'--database-ssl-mode' => ['--database-ssl-mode', 'verify-full', 'dbsslmode', 'verify-full'],
			'--database-ssl-ca' => ['--database-ssl-ca', '/ca.pem', 'dbsslca', '/ca.pem'],
			'--database-ssl-cert' => ['--database-ssl-cert', '/client.crt', 'dbsslcert', '/client.crt'],
			'--database-ssl-key' => ['--database-ssl-key', '/client.key', 'dbsslkey', '/client.key'],
			'--database-ssl-crl' => ['--database-ssl-crl', '/crl.pem', 'dbsslcrl', '/crl.pem'],
			'--database-ssl-no-verify' => ['--database-ssl-no-verify', true, 'dbsslnoverify', true],
		];
	}

	/**
	 * The command only forwards the options, the database setup translates them into the
	 * system config values and rejects the ones it does not support.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('encryptionOptions')]
	public function testForwardsEncryptionOptions(string $parameter, string|bool $value, string $option, string|bool $expected): void {
		$options = $this->validateInput([
			'--database' => 'pgsql',
			$parameter => $value,
		]);

		$this->assertSame($expected, $options[$option]);
	}

	public function testNoEncryptionOptions(): void {
		$options = $this->validateInput(['--database' => 'mysql']);

		foreach (['dbsslmode', 'dbsslca', 'dbsslcert', 'dbsslkey', 'dbsslcrl', 'dbsslnoverify'] as $option) {
			$this->assertArrayNotHasKey($option, $options);
		}
	}

	/**
	 * An option that does not apply to the chosen database is not filtered out here, it
	 * has to be reported by the database setup instead of being silently dropped.
	 */
	public function testForwardsEncryptionOptionsRegardlessOfDatabase(): void {
		$options = $this->validateInput([
			'--database' => 'sqlite',
			'--database-ssl-ca' => '/ca.pem',
		]);

		$this->assertSame('/ca.pem', $options['dbsslca']);
	}
}
