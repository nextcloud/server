<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OCP\HintException;

class LegacyHookTest extends TestCase {
	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		\OC_Hook::clear('LegacyHookTest');
	}

	#[\Override]
	protected function tearDown(): void {
		\OC_Hook::clear('LegacyHookTest');
		// the exceptions thrown by the handlers below are expected, do not let
		// the base test case rethrow them
		\OC_Hook::$thrownExceptions = [];
		parent::tearDown();
	}

	public static function throwTypeError(): void {
		throw new \TypeError('type error thrown by a hook handler');
	}

	public static function throwHintException(): void {
		throw new HintException('hint exception thrown by a hook handler');
	}

	public function testEmitDoesNotPropagateThrowable(): void {
		\OC_Hook::connect('LegacyHookTest', 'error', self::class, 'throwTypeError');

		$this->assertTrue(\OC_Hook::emit('LegacyHookTest', 'error'));

		$this->assertCount(1, \OC_Hook::$thrownExceptions);
		$this->assertInstanceOf(\TypeError::class, \OC_Hook::$thrownExceptions[0]);
	}

	public function testEmitRethrowsHintException(): void {
		\OC_Hook::connect('LegacyHookTest', 'hint', self::class, 'throwHintException');

		$this->expectException(HintException::class);
		\OC_Hook::emit('LegacyHookTest', 'hint');
	}
}
