<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Permission;

use NCU\Permission\ABooleanPermission;
use NCU\Permission\PermissionValueType;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class ABooleanPermissionTest extends TestCase {
	private ABooleanPermission $permission;

	protected function setUp(): void {
		parent::setUp();

		$this->permission = new class extends ABooleanPermission {
			public function getId(): string {
				return 'app:download';
			}

			public function getDisplayName(): string {
				return 'Download';
			}

			public function getPriority(): int {
				return 50;
			}

			public function getDefaultValue(): string {
				return self::VALUE_TRUE;
			}
		};
	}

	public function testValueType(): void {
		$this->assertSame(PermissionValueType::Boolean, $this->permission->getValueType());
		$this->assertSame('false', $this->permission->getMostRestrictiveValue());
		$this->assertNull($this->permission->getHint());
		$this->assertSame([], $this->permission->getRequiredPermissionIds());
	}

	public static function dataValidateValue(): array {
		return [
			['true', true],
			['false', true],
			['1', false],
			['', false],
			['TRUE', false],
		];
	}

	#[DataProvider('dataValidateValue')]
	public function testValidateValue(string $value, bool $valid): void {
		$result = $this->permission->validateValue($value);
		if ($valid) {
			$this->assertTrue($result);
		} else {
			$this->assertIsString($result);
		}
	}

	public static function dataCompare(): array {
		return [
			['false', 'true', -1],
			['true', 'false', 1],
			['true', 'true', 0],
			['false', 'false', 0],
		];
	}

	#[DataProvider('dataCompare')]
	public function testCompare(string $a, string $b, int $expected): void {
		$this->assertSame($expected, $this->permission->compare($a, $b));
	}

	public function testCompareInvalidValue(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->permission->compare('true', 'yes');
	}
}
