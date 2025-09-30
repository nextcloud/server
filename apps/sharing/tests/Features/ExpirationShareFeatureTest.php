<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace Features;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use OCA\Sharing\Features\ExpirationShareFeature;
use OCA\Sharing\Model\AShareFeature;
use Test\TestCase;

class ExpirationShareFeatureTest extends TestCase {
	private AShareFeature $feature;

	public function setUp(): void {
		parent::setUp();

		$this->feature = new ExpirationShareFeature();
	}

	public function testValidateProperties(): void {
		$now = new DateTimeImmutable();
		$future = $now->add(new DateInterval('PT1M'))->format(DateTimeInterface::ATOM);
		$past = $now->sub(new DateInterval('PT1M'))->format(DateTimeInterface::ATOM);

		$this->assertTrue($this->feature->validateProperties(['date' => [$future]]));

		$this->assertFalse($this->feature->validateProperties([]));
		$this->assertFalse($this->feature->validateProperties(['a' => ['a']]));
		$this->assertFalse($this->feature->validateProperties(['date' => [$future], 'a' => ['a']]));

		$this->assertFalse($this->feature->validateProperties(['date' => []]));
		$this->assertFalse($this->feature->validateProperties(['date' => [$future, $future]]));

		$this->assertFalse($this->feature->validateProperties(['date' => ['']]));
		$this->assertFalse($this->feature->validateProperties(['date' => ['a']]));
		$this->assertFalse($this->feature->validateProperties(['date' => [$past]]));
	}
}
