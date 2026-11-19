<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarProvider;
use OCA\DAV\CalDAV\Federation\FederatedCalendarImpl;
use OCA\DAV\Db\Property;
use OCA\DAV\Db\PropertyMapper;
use OCP\Constants;
use OCP\IConfig;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CalendarProviderTest extends TestCase {
	private CalDavBackend&MockObject $backend;
	private PropertyMapper&MockObject $propertyMapper;
	private CalendarProvider $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->backend = $this->createMock(CalDavBackend::class);
		$this->propertyMapper = $this->createMock(PropertyMapper::class);
		$this->provider = new CalendarProvider(
			$this->backend,
			$this->createMock(IL10N::class),
			$this->createMock(IConfig::class),
			$this->createMock(LoggerInterface::class),
			$this->propertyMapper,
		);
	}

	private function createProperty(string $path, string $name, string $value): Property {
		$property = new Property();
		$property->setUserid('user2');
		$property->setPropertypath($path);
		$property->setPropertyname($name);
		$property->setPropertyvalue($value);
		return $property;
	}

	public function testGetCalendarsAppliesOnlyEnabledStateToFederatedCalendars(): void {
		$this->backend->method('getCalendarsForUser')
			->willReturn([]);
		$this->backend->method('getFederatedCalendarsForUser')
			->with('principals/users/user2')
			->willReturn([[
				'id' => 666,
				'uri' => 'federated',
				'principaluri' => 'principals/users/user2',
				'federated' => 1,
				'{DAV:}displayname' => 'Federated',
				'{http://owncloud.org/ns}owner-principal' => 'principals/remote-users/user1',
				'{http://owncloud.org/ns}read-only' => 1,
				'{http://owncloud.org/ns}permissions' => Constants::PERMISSION_READ,
			]]);

		$path = 'calendars/user2/federated';
		$this->propertyMapper->method('findPropertiesByPathsAndUsers')
			->willReturnCallback(fn (array $calendars) => $calendars === [] ? [] : [
				$this->createProperty($path, '{http://owncloud.org/ns}calendar-enabled', '0'),
				$this->createProperty($path, '{http://owncloud.org/ns}permissions', (string)Constants::PERMISSION_ALL),
				$this->createProperty($path, '{http://owncloud.org/ns}read-only', '0'),
				$this->createProperty($path, '{DAV:}displayname', 'Stored'),
			]);

		$calendars = $this->provider->getCalendars('principals/users/user2');

		$this->assertCount(1, $calendars);
		$calendar = $calendars[0];
		$this->assertInstanceOf(FederatedCalendarImpl::class, $calendar);
		$this->assertFalse($calendar->isEnabled());
		$this->assertSame(Constants::PERMISSION_READ, $calendar->getPermissions());
		$this->assertFalse($calendar->isWritable());
		$this->assertSame('Federated', $calendar->getDisplayName());
	}
}
