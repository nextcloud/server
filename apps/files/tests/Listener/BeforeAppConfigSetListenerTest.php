<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Listener;

use OCA\Files\Listener\BeforeAppConfigSetListener;
use OCP\Config\BeforeAppConfigSetEvent;
use OCP\IAppConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

final class BeforeAppConfigSetListenerTest extends TestCase {
	public static function dataHandle(): array {
		return [
			['files', 'default_quota', 'none', 0, true],
			['files', 'default_quota', 'none', 1, false],
			['files', 'default_quota', '1 GB', 0, false],
			['files', 'other_key', 'none', 0, false],
			['core', 'default_quota', 'none', 0, false],
		];
	}

	#[DataProvider('dataHandle')]
	public function testHandle(string $app, string $key, string $value, int $allowUnlimited, bool $rejected): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueInt')
			->with('files', 'allow_unlimited_quota', 1)
			->willReturn($allowUnlimited);

		$event = new BeforeAppConfigSetEvent($app, $key, $value);
		(new BeforeAppConfigSetListener($appConfig))->handle($event);
		$this->assertSame($rejected, $event->isRejected());
	}
}
