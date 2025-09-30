<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace Features;

use OCA\Sharing\Features\LabelShareFeature;
use OCA\Sharing\Model\AShareFeature;
use Test\TestCase;

class LabelShareFeatureTest extends TestCase {
	private AShareFeature $feature;

	public function setUp(): void {
		parent::setUp();

		$this->feature = new LabelShareFeature();
	}

	public function testValidateProperties(): void {
		$this->assertTrue($this->feature->validateProperties(['text' => ['a']]));

		$this->assertFalse($this->feature->validateProperties([]));
		$this->assertFalse($this->feature->validateProperties(['a' => ['a']]));
		$this->assertFalse($this->feature->validateProperties(['text' => ['a'], 'a' => ['a']]));

		$this->assertFalse($this->feature->validateProperties(['text' => []]));
		$this->assertFalse($this->feature->validateProperties(['text' => ['a', 'b']]));

		$this->assertFalse($this->feature->validateProperties(['text' => ['']]));
	}
}
