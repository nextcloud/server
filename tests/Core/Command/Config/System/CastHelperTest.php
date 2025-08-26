<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Core\Command\Config\System;

use OC\Core\Command\Config\System\CastHelper;
use Test\TestCase;

class CastHelperTest extends TestCase {
	private CastHelper $castHelper;

	protected function setUp(): void {
		parent::setUp();
		$this->castHelper = new CastHelper();
	}

	public static function castValueProvider(): array {
		return [
			[null, 'string', ['value' => '', 'readable-value' => 'empty string']],

			['abc', 'string', ['value' => 'abc', 'readable-value' => 'string abc']],

			['123', 'integer', ['value' => 123, 'readable-value' => 'integer 123']],
			['456', 'int', ['value' => 456, 'readable-value' => 'integer 456']],

			['2.25', 'double', ['value' => 2.25, 'readable-value' => 'double 2.25']],
			['0.5', 'float', ['value' => 0.5, 'readable-value' => 'double 0.5']],

			['', 'null', ['value' => null, 'readable-value' => 'null']],

			['true', 'boolean', ['value' => true, 'readable-value' => 'boolean true']],
			['false', 'bool', ['value' => false, 'readable-value' => 'boolean false']],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('castValueProvider')]
	public function testCastValue($value, $type, $expectedValue): void {
		$this->assertSame(
			$expectedValue,
			$this->castHelper->castValue($value, $type)
		);
	}

	public static function castValueInvalidProvider(): array {
		return [
			['123', 'foobar'],

			[null, 'integer'],
			['abc', 'integer'],
			['76ggg', 'double'],
			['true', 'float'],
			['foobar', 'boolean'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('castValueInvalidProvider')]
	public function testCastValueInvalid($value, $type): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->castHelper->castValue($value, $type);
	}
}
