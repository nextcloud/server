<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace Test\Sharing\Property;

use OCP\Sharing\Property\AEnumSharePropertyType;
use Test\TestCase;

final readonly class TestEnumSharePropertyType extends AEnumSharePropertyType {
	public function __construct(
		/** @var non-empty-list<string> $validValues */
		public array $validValues,
	) {
	}

	/**
	 * @return non-empty-list<string>
	 */
	#[\Override]
	public function getValidValues(): array {
		return $this->validValues;
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

final class AEnumSharePropertyTypeTest extends TestCase {
	private AEnumSharePropertyType $propertyType;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->propertyType = new TestEnumSharePropertyType(['valid']);
	}

	public function testValidateValue(): void {
		$this->assertTrue($this->propertyType->validateValue('valid'));
		$this->assertIsString($this->propertyType->validateValue('invalid'));
	}
}
