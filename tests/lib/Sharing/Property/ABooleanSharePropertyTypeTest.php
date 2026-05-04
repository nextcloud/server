<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing\Property;

use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Property\ABooleanSharePropertyType;
use Test\TestCase;

final class TestBooleanSharePropertyType extends ABooleanSharePropertyType {
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
		$l10nFactory = Server::get(IFactory::class);
		$this->assertTrue($this->propertyType->validateValue($l10nFactory, 'true'));
		$this->assertTrue($this->propertyType->validateValue($l10nFactory, 'false'));
		$this->assertIsString($this->propertyType->validateValue($l10nFactory, ''));
		$this->assertIsString($this->propertyType->validateValue($l10nFactory, 'invalid'));
	}
}
