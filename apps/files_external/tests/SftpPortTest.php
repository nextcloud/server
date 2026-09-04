<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests;

use OCA\Files_External\Lib\Storage\SFTP;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class SftpPortTest extends TestCase {
	public static function portProvider(): array {
		$parameters = [
			'host' => 'somehost',
			'user' => 'someuser',
			'password' => 'somepassword',
		];

		return [
			'no port given' => [$parameters, 22],
			'empty port' => [array_merge($parameters, ['port' => '']), 22],
			'null port' => [array_merge($parameters, ['port' => null]), 22],
			'non numeric port' => [array_merge($parameters, ['port' => 'sftp']), 22],
			'numeric string port' => [array_merge($parameters, ['port' => '2222']), 2222],
			'integer port' => [array_merge($parameters, ['port' => 2222]), 2222],
			'decimal port' => [array_merge($parameters, ['port' => '22.5']), 22],
			'zero port' => [array_merge($parameters, ['port' => '0']), 22],
			'negative port' => [array_merge($parameters, ['port' => '-2222']), 22],
			'out of range port' => [array_merge($parameters, ['port' => '65536']), 22],
			'highest valid port' => [array_merge($parameters, ['port' => '65535']), 65535],

			// the port can also be part of the host field
			'port in host' => [array_merge($parameters, ['host' => 'somehost:2222']), 2222],
			'port in host with empty port' => [array_merge($parameters, ['host' => 'somehost:2222', 'port' => '']), 2222],
			'port in host overwritten by port' => [array_merge($parameters, ['host' => 'somehost:2222', 'port' => '2223']), 2223],
			'port in host with invalid port' => [array_merge($parameters, ['host' => 'somehost:2222', 'port' => '65536']), 2222],
		];
	}

	#[DataProvider('portProvider')]
	public function testPort(array $parameters, int $expectedPort): void {
		$instance = new SFTP($parameters);

		$this->assertSame($expectedPort, self::invokePrivate($instance, 'port'));
	}
}
