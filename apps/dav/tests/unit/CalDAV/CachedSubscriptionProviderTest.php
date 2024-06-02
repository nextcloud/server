<?php

declare(strict_types=1);

/**
 * @copyright 2024 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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

	public function testGetCalendars() {
		$calendars = $this->provider->getCalendars(
			'user-principal-123',
			[]
		);

		$this->assertCount(2, $calendars);
		$this->assertInstanceOf(CachedSubscriptionImpl::class, $calendars[0]);
		$this->assertInstanceOf(CachedSubscriptionImpl::class, $calendars[1]);
	}

	public function testGetCalendarsFilterByUri() {
		$calendars = $this->provider->getCalendars(
			'user-principal-123',
			['subscription-1']
		);

		$this->assertCount(1, $calendars);
		$this->assertInstanceOf(CachedSubscriptionImpl::class, $calendars[0]);
		$this->assertEquals('subscription-1', $calendars[0]->getUri());
	}
}
