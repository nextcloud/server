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
use OCP\Sharing\Share;
use OCP\Sharing\ShareState;
use OCP\Sharing\ShareUser;
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
	public function isAdvanced(): bool {
		throw new \RuntimeException();
	}

	#[\Override]
	public function isRequired(Share $share): bool {
		throw new \RuntimeException();
	}

	#[\Override]
	public function getDefaultValue(Share $share): ?string {
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
		$share = new Share(
			'123',
			new ShareUser('user', null),
			0,
			ShareState::Active,
			[],
			[],
			[],
			[],
		);
		$this->assertTrue($this->propertyType->validateValue($l10nFactory, $share, 'true'));
		$this->assertTrue($this->propertyType->validateValue($l10nFactory, $share, 'false'));
		$this->assertIsString($this->propertyType->validateValue($l10nFactory, $share, ''));
		$this->assertIsString($this->propertyType->validateValue($l10nFactory, $share, 'invalid'));
	}
}
