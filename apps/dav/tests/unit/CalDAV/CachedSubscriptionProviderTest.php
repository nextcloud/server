<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\CachedSubscriptionImpl;
use OCA\DAV\CalDAV\CachedSubscriptionProvider;
use OCA\DAV\CalDAV\CalDavBackend;
use Test\TestCase;

class CachedSubscriptionProviderTest extends TestCase {

	private CalDavBackend $backend;
	private CachedSubscriptionProvider $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->backend = $this->createMock(CalDavBackend::class);
		$this->backend
			->expects(self::once())
			->method('getSubscriptionsForUser')
			->with('user-principal-123')
			->willReturn([
				[
					'id' => 'subscription-1',
					'uri' => 'subscription-1',
					'principaluris' => 'user-principal-123',
					'source' => 'https://localhost/subscription-1',
					// A subscription array has actually more properties.
				],
				[
					'id' => 'subscription-2',
					'uri' => 'subscription-2',
					'principaluri' => 'user-principal-123',
					'source' => 'https://localhost/subscription-2',
					// A subscription array has actually more properties.
				]
			]);

		$this->provider = new CachedSubscriptionProvider($this->backend);
	}

	public function testGetCalendars(): void {
		$calendars = $this->provider->getCalendars(
			'user-principal-123',
			[]
		);

		$this->assertCount(2, $calendars);
		$this->assertInstanceOf(CachedSubscriptionImpl::class, $calendars[0]);
		$this->assertInstanceOf(CachedSubscriptionImpl::class, $calendars[1]);
	}

	public function testGetCalendarsFilterByUri(): void {
		$calendars = $this->provider->getCalendars(
			'user-principal-123',
			['subscription-1']
		);

		$this->assertCount(1, $calendars);
		$this->assertInstanceOf(CachedSubscriptionImpl::class, $calendars[0]);
		$this->assertEquals('subscription-1', $calendars[0]->getUri());
	}
}
