<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\DB\Middleware;

use OC\DB\Middleware\UtcTimezoneMiddleware;
use OC\DB\Middleware\UtcTimezoneMiddlewareDriver;
use Test\TestCase;

final class UtcTimezoneMiddlewareTest extends TestCase {
	public function testWrap(): void {
		$driver = $this->createMock(\Doctrine\DBAL\Driver::class);
		$middleware = new UtcTimezoneMiddleware();
		$wrappedDriver = $middleware->wrap($driver);

		$this->assertInstanceOf(UtcTimezoneMiddlewareDriver::class, $wrappedDriver);
	}
}
