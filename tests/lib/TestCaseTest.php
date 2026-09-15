<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OCP\IConfig;
use OCP\Server;

/**
 * Tests for TestCase::overwriteSystemConfig(). System config is persisted to
 * config.php, so a value leaked by a test outlives the run and the next one.
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class TestCaseTest extends TestCase {
	private const KEY = 'testcase_overwrite_system_config';

	private IConfig $config;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->config = Server::get(IConfig::class);
		$this->config->deleteSystemValue(self::KEY);
	}

	#[\Override]
	protected function tearDown(): void {
		parent::tearDown();

		$this->config->deleteSystemValue(self::KEY);
	}

	public function testOverwriteSetsTheValue(): void {
		$this->overwriteSystemConfig(self::KEY, 'overwritten');

		$this->assertSame('overwritten', $this->config->getSystemValue(self::KEY));
	}

	public function testRestoreRemovesAPreviouslyUnsetKey(): void {
		$this->overwriteSystemConfig(self::KEY, 'overwritten');
		$this->restoreAllSystemConfig();

		$this->assertSame('fallback', $this->config->getSystemValue(self::KEY, 'fallback'));
	}

	public function testRestoreReturnsThePreviousValue(): void {
		$this->config->setSystemValue(self::KEY, 'original');

		$this->overwriteSystemConfig(self::KEY, 'overwritten');
		$this->restoreAllSystemConfig();

		$this->assertSame('original', $this->config->getSystemValue(self::KEY));
	}

	public function testRestoreReturnsThePreviousValueAfterRepeatedOverwrites(): void {
		$this->config->setSystemValue(self::KEY, 'original');

		$this->overwriteSystemConfig(self::KEY, 'first');
		$this->overwriteSystemConfig(self::KEY, 'second');
		$this->restoreAllSystemConfig();

		$this->assertSame('original', $this->config->getSystemValue(self::KEY));
	}

	public function testRestoreIsIdempotent(): void {
		$this->config->setSystemValue(self::KEY, 'original');

		$this->overwriteSystemConfig(self::KEY, 'overwritten');
		$this->restoreAllSystemConfig();
		$this->config->setSystemValue(self::KEY, 'set afterwards');
		$this->restoreAllSystemConfig();

		$this->assertSame('set afterwards', $this->config->getSystemValue(self::KEY));
	}

	/** false is falsy but set: an isset-based check would wrongly delete the key. */
	public function testRestoreReturnsAPreviousFalseValue(): void {
		$this->config->setSystemValue(self::KEY, false);

		$this->overwriteSystemConfig(self::KEY, true);
		$this->restoreAllSystemConfig();

		$this->assertFalse($this->config->getSystemValue(self::KEY, 'fallback'));
	}
}
