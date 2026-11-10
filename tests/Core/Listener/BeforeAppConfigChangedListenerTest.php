<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Listener;

use OC\Core\Listener\BeforeAppConfigChangedListener;
use OCP\Config\BeforeAppConfigDeletedEvent;
use OCP\Config\BeforeAppConfigSetEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class BeforeAppConfigChangedListenerTest extends TestCase {
	public static function dataSet(): array {
		return [
			['activity', 'abc', '', false],
			['dav', 'public_route', '', false],
			['files', 'remote_route', '', false],
			['core', 'encryption_enabled', 'yes', false],
			['core', 'other', 'no', false],
			['activity', 'installed_version', '', true],
			['calendar', 'enabled', '', true],
			['contacts', 'types', '', true],
			['core', 'encryption_enabled', 'no', true],
			['core', 'encryption_enabled', '', true],
			['core', 'public_files', '', true],
			['core', 'public_dav', '', true],
			['core', 'remote_files', '', true],
			['core', 'remote_dav', '', true],
		];
	}

	#[DataProvider('dataSet')]
	public function testSet(string $app, string $key, string $value, bool $rejected): void {
		$event = new BeforeAppConfigSetEvent($app, $key, $value);
		(new BeforeAppConfigChangedListener())->handle($event);
		$this->assertSame($rejected, $event->isRejected());
	}

	public static function dataDelete(): array {
		return [
			['dav', 'public_route', false],
			['core', 'other', false],
			['calendar', 'enabled', true],
			['core', 'encryption_enabled', true],
			['core', 'public_files', true],
			['core', 'remote_dav', true],
		];
	}

	#[DataProvider('dataDelete')]
	public function testDelete(string $app, string $key, bool $rejected): void {
		$event = new BeforeAppConfigDeletedEvent($app, $key);
		(new BeforeAppConfigChangedListener())->handle($event);
		$this->assertSame($rejected, $event->isRejected());
	}
}
