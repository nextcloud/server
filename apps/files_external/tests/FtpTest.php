<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests;

use OCA\Files_External\Lib\Storage\FTP;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class FtpTest extends TestCase {
	public static function portProvider(): array {
		$parameters = [
			'host' => 'somehost',
			'user' => 'someuser',
			'password' => 'somepassword',
		];

		return [
			'no port given' => [$parameters, 21],
			'empty port' => [array_merge($parameters, ['port' => '']), 21],
			'null port' => [array_merge($parameters, ['port' => null]), 21],
			'non numeric port' => [array_merge($parameters, ['port' => 'ftp']), 21],
			'numeric string port' => [array_merge($parameters, ['port' => '2121']), 2121],
			'integer port' => [array_merge($parameters, ['port' => 2121]), 2121],
			'decimal port' => [array_merge($parameters, ['port' => '21.5']), 21],
			'zero port' => [array_merge($parameters, ['port' => '0']), 21],
			'negative port' => [array_merge($parameters, ['port' => '-2121']), 21],
			'out of range port' => [array_merge($parameters, ['port' => '65536']), 21],
			'highest valid port' => [array_merge($parameters, ['port' => '65535']), 65535],
		];
	}

	#[DataProvider('portProvider')]
	public function testPort(array $parameters, int $expectedPort): void {
		$instance = new FTP($parameters);

		$this->assertSame($expectedPort, self::invokePrivate($instance, 'port'));
	}
}
