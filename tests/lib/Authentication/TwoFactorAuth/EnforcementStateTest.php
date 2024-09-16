<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Created by PhpStorm.
 * User: christoph
 * Date: 11.10.18
 * Time: 13:01
 */

namespace Tests\Authentication\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\EnforcementState;
use Test\TestCase;

class EnforcementStateTest extends TestCase {
	public function testIsEnforced(): void {
		$state = new EnforcementState(true);

		$this->assertTrue($state->isEnforced());
	}

	public function testGetEnforcedGroups(): void {
		$state = new EnforcementState(true, ['twofactorers']);

		$this->assertEquals(['twofactorers'], $state->getEnforcedGroups());
	}

	public function testGetExcludedGroups(): void {
		$state = new EnforcementState(true, [], ['yoloers']);

		$this->assertEquals(['yoloers'], $state->getExcludedGroups());
	}

	public function testJsonSerialize(): void {
		$state = new EnforcementState(true, ['twofactorers'], ['yoloers']);
		$expected = [
			'enforced' => true,
			'enforcedGroups' => ['twofactorers'],
			'excludedGroups' => ['yoloers'],
		];

		$json = $state->jsonSerialize();

		$this->assertEquals($expected, $json);
	}
}
