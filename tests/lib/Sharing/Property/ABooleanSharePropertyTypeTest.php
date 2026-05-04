<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace Test\Sharing\Property;

use OCP\Sharing\Property\ABooleanSharePropertyType;
use Test\TestCase;

final readonly class TestBooleanSharePropertyType extends ABooleanSharePropertyType {
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

final class ABooleanSharePropertyTypeTest extends TestCase {
	private ABooleanSharePropertyType $propertyType;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->propertyType = new TestBooleanSharePropertyType();
	}

	public function testValidateValue(): void {
		$this->assertTrue($this->propertyType->validateValue('true'));
		$this->assertTrue($this->propertyType->validateValue('false'));
		$this->assertIsString($this->propertyType->validateValue(''));
		$this->assertIsString($this->propertyType->validateValue('invalid'));
	}
}
