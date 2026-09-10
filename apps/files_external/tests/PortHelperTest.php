<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests;

use OCA\Files_External\Lib\PortHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class PortHelperTest extends TestCase {
	public static function portProvider(): array {
		return [
			// valid ports are returned as integers
			'integer port' => [2121, 2121],
			'numeric string port' => ['2121', 2121],
			'padded numeric string port' => ['0022', 22],
			'lowest valid port' => [1, 1],
			'highest valid port' => [65535, 65535],
			'highest valid port as string' => ['65535', 65535],

			// unset or empty values fall back
			'null port' => [null, 21],
			'empty port' => ['', 21],
			'whitespace port' => [' ', 21],
			'array port' => [[2121], 21],

			// non integer values fall back
			'non numeric port' => ['ftp', 21],
			'float port' => [21.5, 21],
			'integer float port' => [2121.0, 21],
			'decimal string port' => ['21.5', 21],
			'exponential string port' => ['1e3', 21],
			'hexadecimal string port' => ['0x15', 21],
			'signed string port' => ['+2121', 21],
			'padded string port' => [' 2121', 21],
			'boolean port' => [true, 21],

			// out of range values fall back
			'zero port' => [0, 21],
			'zero string port' => ['0', 21],
			'negative port' => [-2121, 21],
			'negative string port' => ['-2121', 21],
			'too large port' => [65536, 21],
			'too large string port' => ['65536', 21],
			'way too large string port' => ['999999999999999999999999', 21],
		];
	}

	#[DataProvider('portProvider')]
	public function testParsePort(mixed $port, int $expectedPort): void {
		$this->assertSame($expectedPort, PortHelper::parsePort($port, 21));
	}

	public function testParsePortReturnsGivenFallback(): void {
		$this->assertSame(22, PortHelper::parsePort('', 22));
	}
}
