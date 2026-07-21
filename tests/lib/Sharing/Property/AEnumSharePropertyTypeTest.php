<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing\Property;

use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Property\AEnumSharePropertyType;
use Test\TestCase;

final class TestEnumSharePropertyType extends AEnumSharePropertyType {
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

final class AEnumSharePropertyTypeTest extends TestCase {
	private AEnumSharePropertyType $propertyType;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->propertyType = new TestEnumSharePropertyType(['valid']);
	}

	public function testValidateValue(): void {
		$l10nFactory = Server::get(IFactory::class);
		$this->assertTrue($this->propertyType->validateValue($l10nFactory, 'valid'));
		$this->assertIsString($this->propertyType->validateValue($l10nFactory, 'invalid'));
	}
}
