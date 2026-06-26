<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Activity\Filter;

use OCA\DAV\CalDAV\Activity\Filter\Calendar;
use OCP\Activity\IFilter;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CalendarTest extends TestCase {
	protected IURLGenerator&MockObject $url;
	protected IFilter $filter;

	protected function setUp(): void {
		parent::setUp();
		$this->url = $this->createMock(IURLGenerator::class);
		$l = $this->createMock(IL10N::class);
		$l->expects($this->any())
			->method('t')
			->willReturnCallback(function ($string, $args) {
				return vsprintf($string, $args);
			});

		$this->filter = new Calendar(
			$l, $this->url
		);
	}

	public function testGetIcon(): void {
		$this->url->expects($this->once())
			->method('imagePath')
			->with('core', 'places/calendar.svg')
			->willReturn('path-to-icon');

		$this->url->expects($this->once())
			->method('getAbsoluteURL')
			->with('path-to-icon')
			->willReturn('absolute-path-to-icon');

		$this->assertEquals('absolute-path-to-icon', $this->filter->getIcon());
	}

	public static function dataFilterTypes(): array {
		return [
			[[], []],
			[['calendar', 'calendar_event'], ['calendar', 'calendar_event']],
			[['calendar', 'calendar_event', 'calendar_todo'], ['calendar', 'calendar_event']],
			[['calendar', 'calendar_event', 'files'], ['calendar', 'calendar_event']],
		];
	}

	/**
	 * @param string[] $types
	 * @param string[] $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataFilterTypes')]
	public function testFilterTypes(array $types, array $expected): void {
		$this->assertEquals($expected, $this->filter->filterTypes($types));
	}
}
