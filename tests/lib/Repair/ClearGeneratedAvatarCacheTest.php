<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair;

use OC\Repair\ClearGeneratedAvatarCache;
use OCP\IConfig;

class ClearGeneratedAvatarCacheTest extends \Test\TestCase {

	protected ClearGeneratedAvatarCache $repair;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->repair = $this->createInstanceWithMocks(ClearGeneratedAvatarCache::class);
	}

	public static function shouldRunDataProvider(): array {
		return [
			['11.0.0.0', true],
			['15.0.0.3', true],
			['13.0.5.2', true],
			['12.0.0.0', true],
			['26.0.0.1', true],
			['15.0.0.2', true],
			['13.0.0.0', true],
			['27.0.0.5', false]
		];
	}

	/**
	 *
	 * @param string $from
	 * @param boolean $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('shouldRunDataProvider')]
	public function testShouldRun($from, $expected): void {
		$this->mocks[IConfig::class]->expects($this->any())
			->method('getSystemValueString')
			->with('version', '0.0.0.0')
			->willReturn($from);

		$this->assertEquals($expected, $this->invokePrivate($this->repair, 'shouldRun'));
	}
}
