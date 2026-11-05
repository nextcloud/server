<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Permission;

use NCU\Permission\ABooleanPermission;
use NCU\Permission\AEnumPermission;
use NCU\Permission\PermissionOption;
use NCU\Permission\PermissionValueType;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class AEnumPermissionTest extends TestCase {
	private AEnumPermission $permission;

	protected function setUp(): void {
		parent::setUp();

		$this->permission = new class extends AEnumPermission {
			public function getId(): string {
				return 'forms:results';
			}

			public function getDisplayName(): string {
				return 'See results';
			}

			public function getPriority(): int {
				return 50;
			}

			public function getDefaultValue(): string {
				return 'own';
			}

			public function getOptions(): array {
				return [
					new PermissionOption('none', 'No results'),
					new PermissionOption('own', 'Own results'),
					new PermissionOption('all', 'All results'),
				];
			}
		};
	}

	public function testValueType(): void {
		$this->assertSame(PermissionValueType::Enum, $this->permission->getValueType());
		$this->assertSame('none', $this->permission->getMostRestrictiveValue());
		$this->assertSame('all', $this->permission->getMostPermissiveValue());
		$this->assertNull($this->permission->getParentPermissionId());
	}

	private static function booleanParent(): ABooleanPermission {
		return new class extends ABooleanPermission {
			public function getId(): string {
				return 'app:share';
			}

			public function getDisplayName(): string {
				return 'Share';
			}

			public function getPriority(): int {
				return 50;
			}

			public function getDefaultValue(): string {
				return self::VALUE_TRUE;
			}
		};
	}

	public static function dataInheritValueFromBoolean(): array {
		return [
			['false', 'none'],
			['true', 'all'],
		];
	}

	#[DataProvider('dataInheritValueFromBoolean')]
	public function testInheritValueFromBoolean(string $parentValue, string $expected): void {
		$this->assertSame($expected, $this->permission->inheritValue(self::booleanParent(), $parentValue));
	}

	public static function dataInheritValueFromEnum(): array {
		return [
			['none', 'none'],
			['own', 'own'],
			['all', 'all'],
		];
	}

	#[DataProvider('dataInheritValueFromEnum')]
	public function testInheritValueFromEnum(string $parentValue, string $expected): void {
		$parent = clone $this->permission;
		$this->assertSame($expected, $this->permission->inheritValue($parent, $parentValue));
	}

	public function testInheritInvalidParentValue(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->permission->inheritValue(self::booleanParent(), 'all');
	}

	public static function dataValidateValue(): array {
		return [
			['none', true],
			['own', true],
			['all', true],
			['', false],
			['ALL', false],
			['some', false],
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
			['none', 'own', -1],
			['none', 'all', -1],
			['own', 'all', -1],
			['all', 'none', 1],
			['own', 'own', 0],
		];
	}

	#[DataProvider('dataCompare')]
	public function testCompare(string $a, string $b, int $expected): void {
		$this->assertSame($expected, $this->permission->compare($a, $b));
	}

	public function testCompareInvalidValue(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->permission->compare('some', 'all');
	}
}
