<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\DAV;

use OCA\DAV\DAV\CustomPropertiesBackend;
use OCA\DAV\DAV\CustomPropertiesService;
use OCA\DAV\Db\Property;
use OCA\DAV\Db\PropertyMapper;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Test\TestCase;

class CustomPropertiesServiceTest extends TestCase {
	private PropertyMapper&MockObject $propertyMapper;
	private CustomPropertiesService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->propertyMapper = $this->createMock(PropertyMapper::class);
		$this->service = new CustomPropertiesService($this->propertyMapper);
	}

	private static function property(string $userId, string $path, string $name, string $value, int|string $valueType): Property {
		$property = new Property();
		$property->setUserid($userId);
		$property->setPropertypath($path);
		$property->setPropertyname($name);
		$property->setPropertyvalue($value);
		$property->setValuetype($valueType);
		return $property;
	}

	public function testGetProperties(): void {
		$longPath = 'calendars/user1/' . str_repeat('a', 250);

		$this->propertyMapper->expects($this->once())
			->method('findPropertiesByPathsAndUsers')
			->with([
				'user1' => ['calendars/user1/personal', sha1($longPath)],
				'user2' => ['calendars/user2/personal'],
			])
			->willReturn([
				// databases may return the value type as a string
				self::property('user1', 'calendars/user1/personal', '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp', str_replace(chr(0), '\x00', serialize(new ScheduleCalendarTransp('opaque'))), (string)CustomPropertiesBackend::PROPERTY_TYPE_OBJECT),
				self::property('user1', sha1($longPath), '{http://owncloud.org/ns}calendar-enabled', '0', CustomPropertiesBackend::PROPERTY_TYPE_STRING),
				self::property('user2', 'calendars/user2/personal', '{http://apple.com/ns/ical/}calendar-color', '#ff0000', CustomPropertiesBackend::PROPERTY_TYPE_STRING),
			]);

		$properties = $this->service->getProperties([
			'user1' => ['calendars/user1/personal', $longPath],
			'user2' => ['calendars/user2/personal'],
		]);

		$this->assertSame(['user1', 'user2'], array_keys($properties));
		$this->assertSame('opaque', $properties['user1']['calendars/user1/personal']['{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp']->getValue());
		$this->assertSame(['{http://owncloud.org/ns}calendar-enabled' => '0'], $properties['user1'][$longPath]);
		$this->assertSame(['{http://apple.com/ns/ical/}calendar-color' => '#ff0000'], $properties['user2']['calendars/user2/personal']);
	}

	public function testGetPropertiesWithoutPaths(): void {
		$this->propertyMapper->expects($this->never())
			->method('findPropertiesByPathsAndUsers');

		$this->assertSame([], $this->service->getProperties([]));
	}
}
