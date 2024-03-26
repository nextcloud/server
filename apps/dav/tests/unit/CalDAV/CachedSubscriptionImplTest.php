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

use OCA\DAV\CalDAV\CachedSubscription;
use OCA\DAV\CalDAV\CachedSubscriptionImpl;
use OCA\DAV\CalDAV\CalDavBackend;
use Test\TestCase;

class CachedSubscriptionImplTest extends TestCase {
	private CachedSubscription $cachedSubscription;
	private array $cachedSubscriptionInfo;
	private CachedSubscriptionImpl $cachedSubscriptionImpl;
	private CalDavBackend $backend;

	protected function setUp(): void {
		parent::setUp();

		$this->cachedSubscription = $this->createMock(CachedSubscription::class);
		$this->cachedSubscriptionInfo = [
			'id' => 'fancy_id_123',
			'{DAV:}displayname' => 'user readable name 123',
			'{http://apple.com/ns/ical/}calendar-color' => '#AABBCC',
			'uri' => '/this/is/a/uri',
			'source' => 'https://test.localhost/calendar1',
		];
		$this->backend = $this->createMock(CalDavBackend::class);

		$this->cachedSubscriptionImpl = new CachedSubscriptionImpl(
			$this->cachedSubscription,
			$this->cachedSubscriptionInfo,
			$this->backend
		);
	}

	public function testGetKey(): void {
		$this->assertEquals($this->cachedSubscriptionImpl->getKey(), 'fancy_id_123');
	}

	public function testGetDisplayname(): void {
		$this->assertEquals($this->cachedSubscriptionImpl->getDisplayName(), 'user readable name 123');
	}

	public function testGetDisplayColor(): void {
		$this->assertEquals($this->cachedSubscriptionImpl->getDisplayColor(), '#AABBCC');
	}

	public function testGetSource(): void {
		$this->assertEquals($this->cachedSubscriptionImpl->getSource(), 'https://test.localhost/calendar1');
	}

	public function testSearch(): void {
		$this->backend->expects($this->once())
			->method('search')
			->with($this->cachedSubscriptionInfo, 'abc', ['def'], ['ghi'], 42, 1337)
			->willReturn(['SEARCHRESULTS']);

		$result = $this->cachedSubscriptionImpl->search('abc', ['def'], ['ghi'], 42, 1337);
		$this->assertEquals($result, ['SEARCHRESULTS']);
	}

	public function testGetPermissionRead(): void {
		$this->cachedSubscription->expects($this->once())
			->method('getACL')
			->with()
			->willReturn([
				['privilege' => '{DAV:}read']
			]);

		$this->assertEquals(1, $this->cachedSubscriptionImpl->getPermissions());
	}
}
