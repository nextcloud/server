<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\DefaultCalendarValidator;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Test\TestCase;

class DefaultCalendarValidatorTest extends TestCase {
	private DefaultCalendarValidator $validator;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new DefaultCalendarValidator();
	}

	public function testValidateScheduleDefaultCalendar(): void {
		$node = $this->createMock(Calendar::class);
		$node->expects(self::once())
			->method('isSubscription')
			->willReturn(false);
		$node->expects(self::once())
			->method('canWrite')
			->willReturn(true);
		$node->expects(self::once())
			->method('isShared')
			->willReturn(false);
		$node->expects(self::once())
			->method('isDeleted')
			->willReturn(false);
		$node->expects(self::once())
			->method('getProperties')
			->willReturn([
				'{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VEVENT']),
			]);

		$this->validator->validateScheduleDefaultCalendar($node);
	}

	public function testValidateScheduleDefaultCalendarWithEmptyProperties(): void {
		$node = $this->createMock(Calendar::class);
		$node->expects(self::once())
			->method('isSubscription')
			->willReturn(false);
		$node->expects(self::once())
			->method('canWrite')
			->willReturn(true);
		$node->expects(self::once())
			->method('isShared')
			->willReturn(false);
		$node->expects(self::once())
			->method('isDeleted')
			->willReturn(false);
		$node->expects(self::once())
			->method('getProperties')
			->willReturn([]);

		$this->validator->validateScheduleDefaultCalendar($node);
	}

	public function testValidateScheduleDefaultCalendarWithSubscription(): void {
		$node = $this->createMock(Calendar::class);
		$node->expects(self::once())
			->method('isSubscription')
			->willReturn(true);
		$node->expects(self::never())
			->method('canWrite');
		$node->expects(self::never())
			->method('isShared');
		$node->expects(self::never())
			->method('isDeleted');
		$node->expects(self::never())
			->method('getProperties');

		$this->expectException(\Sabre\DAV\Exception::class);
		$this->validator->validateScheduleDefaultCalendar($node);
	}

	public function testValidateScheduleDefaultCalendarWithoutWrite(): void {
		$node = $this->createMock(Calendar::class);
		$node->expects(self::once())
			->method('isSubscription')
			->willReturn(false);
		$node->expects(self::once())
			->method('canWrite')
			->willReturn(false);
		$node->expects(self::never())
			->method('isShared');
		$node->expects(self::never())
			->method('isDeleted');
		$node->expects(self::never())
			->method('getProperties');

		$this->expectException(\Sabre\DAV\Exception::class);
		$this->validator->validateScheduleDefaultCalendar($node);
	}

	public function testValidateScheduleDefaultCalendarWithShared(): void {
		$node = $this->createMock(Calendar::class);
		$node->expects(self::once())
			->method('isSubscription')
			->willReturn(false);
		$node->expects(self::once())
			->method('canWrite')
			->willReturn(true);
		$node->expects(self::once())
			->method('isShared')
			->willReturn(true);
		$node->expects(self::never())
			->method('isDeleted');
		$node->expects(self::never())
			->method('getProperties');

		$this->expectException(\Sabre\DAV\Exception::class);
		$this->validator->validateScheduleDefaultCalendar($node);
	}

	public function testValidateScheduleDefaultCalendarWithDeleted(): void {
		$node = $this->createMock(Calendar::class);
		$node->expects(self::once())
			->method('isSubscription')
			->willReturn(false);
		$node->expects(self::once())
			->method('canWrite')
			->willReturn(true);
		$node->expects(self::once())
			->method('isShared')
			->willReturn(false);
		$node->expects(self::once())
			->method('isDeleted')
			->willReturn(true);
		$node->expects(self::never())
			->method('getProperties');

		$this->expectException(\Sabre\DAV\Exception::class);
		$this->validator->validateScheduleDefaultCalendar($node);
	}

	public function testValidateScheduleDefaultCalendarWithoutVeventSupport(): void {
		$node = $this->createMock(Calendar::class);
		$node->expects(self::once())
			->method('isSubscription')
			->willReturn(false);
		$node->expects(self::once())
			->method('canWrite')
			->willReturn(true);
		$node->expects(self::once())
			->method('isShared')
			->willReturn(false);
		$node->expects(self::once())
			->method('isDeleted')
			->willReturn(false);
		$node->expects(self::once())
			->method('getProperties')
			->willReturn([
				'{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VTODO']),
			]);

		$this->expectException(\Sabre\DAV\Exception::class);
		$this->validator->validateScheduleDefaultCalendar($node);
	}
}
