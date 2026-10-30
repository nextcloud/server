<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing\Property;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use NCU\Sharing\Property\ADateSharePropertyType;
use NCU\Sharing\Share;
use NCU\Sharing\ShareState;
use NCU\Sharing\ShareUser;
use OCP\L10N\IFactory;
use OCP\Server;
use Test\TestCase;

final class TestDateSharePropertyType extends ADateSharePropertyType {
	public function __construct(
		public ?DateTimeImmutable $minDate,
		public ?DateTimeImmutable $maxDate,
	) {
	}

	#[\Override]
	public function getMinDate(Share $share): ?DateTimeImmutable {
		return $this->minDate;
	}

	#[\Override]
	public function getMaxDate(Share $share): ?DateTimeImmutable {
		return $this->maxDate;
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		throw new \RuntimeException();
	}

	#[\Override]
	public function getHint(IFactory $l10nFactory, Share $share): ?string {
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

final class ADateSharePropertyTypeTest extends TestCase {
	private DateTimeImmutable $now;

	private ADateSharePropertyType $propertyType;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->now = new DateTimeImmutable();
		$this->propertyType = new TestDateSharePropertyType(
			$this->now->sub(new DateInterval('PT1M')),
			$this->now->add(new DateInterval('PT1M')),
		);
	}

	public function testValidateValue(): void {
		$l10nFactory = Server::get(IFactory::class);
		$share = new Share(
			'123',
			new ShareUser('user', null),
			new DateTimeImmutable(),
			ShareState::Active,
			null,
			[],
			[],
			[],
			[],
		);
		$this->assertTrue($this->propertyType->validateValue($l10nFactory, $share, $this->now->format(DateTimeInterface::ATOM)));
		$this->assertIsString($this->propertyType->validateValue($l10nFactory, $share, $this->now->sub(new DateInterval('PT2M'))->format(DateTimeInterface::ATOM)));
		$this->assertIsString($this->propertyType->validateValue($l10nFactory, $share, $this->now->add(new DateInterval('PT2M'))->format(DateTimeInterface::ATOM)));
	}
}
