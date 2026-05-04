<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace Test\Sharing\Property;

use OCP\Sharing\Property\AStringSharePropertyType;
use Test\TestCase;

final readonly class TestStringSharePropertyType extends AStringSharePropertyType {
	public function __construct(
		/** @var ?positive-int $minLength */
		public ?int $minLength,
		/** @var ?positive-int $maxLength */
		public ?int $maxLength,
	) {
	}

	#[\Override]
	public function getMinLength(): ?int {
		return $this->minLength;
	}

	#[\Override]
	public function getMaxLength(): ?int {
		return $this->maxLength;
	}

	#[\Override]
	public function getDisplayName(): string {
		throw new \RuntimeException();
	}

	#[\Override]
	public function getHint(): ?string {
		throw new \RuntimeException();
	}

	#[\Override]
	public function getPriority(): int {
		throw new \RuntimeException();
	}

	#[\Override]
	public function getRequired(): bool {
		throw new \RuntimeException();
	}

	#[\Override]
	public function getDefaultValue(): ?string {
		throw new \RuntimeException();
	}
}

final class AStringSharePropertyTypeTest extends TestCase {
	private AStringSharePropertyType $propertyType;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->propertyType = new TestStringSharePropertyType(
			3,
			5,
		);
	}

	public function testValiStringValue(): void {
		$this->assertIsString($this->propertyType->validateValue('ab'));
		$this->assertTrue($this->propertyType->validateValue('abc'));
		$this->assertTrue($this->propertyType->validateValue('abcd'));
		$this->assertTrue($this->propertyType->validateValue('abcde'));
		$this->assertIsString($this->propertyType->validateValue('abcdef'));
	}
}
