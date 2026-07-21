<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing\Property;

use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Property\AStringSharePropertyType;
use Test\TestCase;

final class TestStringSharePropertyType extends AStringSharePropertyType {
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
	public function getDisplayName(IFactory $l10nFactory): string {
		throw new \RuntimeException();
	}

	#[\Override]
	public function getHint(IFactory $l10nFactory): ?string {
		throw new \RuntimeException();
	}

	#[\Override]
	public function getPriority(): int {
		throw new \RuntimeException();
	}

	#[\Override]
	public function isAdvanced(): bool {
		throw new \RuntimeException();
	}

	#[\Override]
	public function isRequired(): bool {
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
		$l10nFactory = Server::get(IFactory::class);
		$this->assertIsString($this->propertyType->validateValue($l10nFactory, 'ab'));
		$this->assertTrue($this->propertyType->validateValue($l10nFactory, 'abc'));
		$this->assertTrue($this->propertyType->validateValue($l10nFactory, 'abcd'));
		$this->assertTrue($this->propertyType->validateValue($l10nFactory, 'abcde'));
		$this->assertIsString($this->propertyType->validateValue($l10nFactory, 'abcdef'));
	}
}
