<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\DateTimeFormatter;
use OCP\Util;

class DateTimeFormatterTest extends TestCase {
	/** @var \OC\DateTimeFormatter */
	protected $formatter;
	protected static $oneMinute = 60;
	protected static $oneHour = 3600;
	protected static $oneDay;
	protected static $oneYear;

	protected static $defaultTimeZone;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$defaultTimeZone = date_default_timezone_get();
		date_default_timezone_set('UTC');

		self::$oneDay = self::$oneHour * 24;
		self::$oneYear = self::$oneDay * 365;
	}

	public static function tearDownAfterClass(): void {
		date_default_timezone_set(self::$defaultTimeZone);
		parent::tearDownAfterClass();
	}

	protected function setUp(): void {
		parent::setUp();
		$this->formatter = new DateTimeFormatter(new \DateTimeZone('UTC'), Util::getL10N('lib', 'en'));
	}

	protected static function getTimestampAgo($time, $seconds = 0, $minutes = 0, $hours = 0, $days = 0, $years = 0) {
		return $time - $seconds - $minutes * 60 - $hours * 3600 - $days * 24 * 3600 - $years * 365 * 24 * 3600;
	}

	public static function formatTimeSpanData(): array {
		$time = 1416916800; // Use a fixed timestamp so we don't switch days/years with the getTimestampAgo
		$deL10N = Util::getL10N('lib', 'de');
		return [
			['seconds ago',	$time, $time],
			['in a few seconds', $time + 5 , $time],
			['1 minute ago',	self::getTimestampAgo($time, 30, 1), $time],
			['15 minutes ago',	self::getTimestampAgo($time, 30, 15), $time],
			['in 15 minutes',	$time, self::getTimestampAgo($time, 30, 15)],
			['1 hour ago',		self::getTimestampAgo($time, 30, 15, 1), $time],
			['3 hours ago',	self::getTimestampAgo($time, 30, 15, 3), $time],
			['in 3 hours',		$time, self::getTimestampAgo($time, 30, 15, 3)],
			['4 days ago',		self::getTimestampAgo($time, 30, 15, 3, 4), $time],

			['seconds ago', new \DateTime('Wed, 02 Oct 2013 23:59:58 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')],
			['seconds ago', new \DateTime('Wed, 02 Oct 2013 23:59:00 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')],
			['1 minute ago', new \DateTime('Wed, 02 Oct 2013 23:58:30 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')],
			['3 minutes ago', new \DateTime('Wed, 02 Oct 2013 23:56:30 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')],
			['59 minutes ago', new \DateTime('Wed, 02 Oct 2013 23:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')],
			['1 hour ago', new \DateTime('Wed, 02 Oct 2013 22:59:59 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')],
			['3 hours ago', new \DateTime('Wed, 02 Oct 2013 20:39:59 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')],
			['yesterday', new \DateTime('Tue, 01 Oct 2013 20:39:59 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')],
			['2 days ago', new \DateTime('Mon, 30 Sep 2013 20:39:59 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')],

			[$deL10N->t('seconds ago'), new \DateTime('Wed, 02 Oct 2013 23:59:58 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000'), $deL10N],
			[$deL10N->n('%n minute ago', '%n minutes ago', 1), new \DateTime('Wed, 02 Oct 2013 23:58:30 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000'), $deL10N],
			[$deL10N->n('%n minute ago', '%n minutes ago', 3), new \DateTime('Wed, 02 Oct 2013 23:56:30 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000'), $deL10N],
			[$deL10N->n('%n hour ago', '%n hours ago', 1), new \DateTime('Wed, 02 Oct 2013 22:59:59 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000'), $deL10N],
			[$deL10N->n('%n hour ago', '%n hours ago', 3), new \DateTime('Wed, 02 Oct 2013 20:39:59 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000'), $deL10N],
			[$deL10N->n('%n day ago', '%n days ago', 2), new \DateTime('Mon, 30 Sep 2013 20:39:59 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000'), $deL10N],

		];
	}

	/**
	 * @dataProvider formatTimeSpanData
	 */
	public function testFormatTimeSpan($expected, $timestamp, $compare, $locale = null): void {
		$this->assertEquals((string)$expected, (string)$this->formatter->formatTimeSpan($timestamp, $compare, $locale));
	}

	public static function formatDateSpanData(): array {
		$time = 1416916800; // Use a fixed timestamp so we don't switch days/years with the getTimestampAgo
		$deL10N = Util::getL10N('lib', 'de');
		return [
			// Normal testing
			['today',			self::getTimestampAgo($time, 30, 15), $time],
			['yesterday',		self::getTimestampAgo($time, 0, 0, 0, 1), $time],
			['tomorrow',		$time, self::getTimestampAgo($time, 0, 0, 0, 1)],
			['4 days ago',		self::getTimestampAgo($time, 0, 0, 0, 4), $time],
			['in 4 days',		$time, self::getTimestampAgo($time, 0, 0, 0, 4)],
			['5 months ago',	self::getTimestampAgo($time, 0, 0, 0, 155), $time],
			['next month',		$time, self::getTimestampAgo($time, 0, 0, 0, 32)],
			['in 5 months',	$time, self::getTimestampAgo($time, 0, 0, 0, 155)],
			['2 years ago',	self::getTimestampAgo($time, 0, 0, 0, 0, 2), $time],
			['next year',		$time, self::getTimestampAgo($time, 0, 0, 0, 0, 1)],
			['in 2 years',		$time, self::getTimestampAgo($time, 0, 0, 0, 0, 2)],

			// Test with compare timestamp
			['today',			self::getTimestampAgo($time, 0, 0, 0, 0, 1), self::getTimestampAgo($time, 0, 0, 0, 0, 1)],
			['yesterday',		self::getTimestampAgo($time, 30, 15, 3, 1, 1), self::getTimestampAgo($time, 0, 0, 0, 0, 1)],
			['4 days ago',		self::getTimestampAgo($time, 30, 15, 3, 4, 1), self::getTimestampAgo($time, 0, 0, 0, 0, 1)],
			['5 months ago',	self::getTimestampAgo($time, 30, 15, 3, 155, 1), self::getTimestampAgo($time, 0, 0, 0, 0, 1)],
			['2 years ago',	self::getTimestampAgo($time, 30, 15, 3, 35, 3), self::getTimestampAgo($time, 0, 0, 0, 0, 1)],

			// Test translations
			[$deL10N->t('today'),			new \DateTime('Wed, 02 Oct 2013 12:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000'), $deL10N],
			[$deL10N->t('yesterday'),		new \DateTime('Tue, 01 Oct 2013 00:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 00:00:00 +0000'), $deL10N],
			[$deL10N->n('%n day ago', '%n days ago', 2), new \DateTime('Mon, 30 Sep 2013 00:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 00:00:00 +0000'), $deL10N],
			[$deL10N->n('%n month ago', '%n months ago', 9), new \DateTime('Tue, 31 Dec 2013 00:00:00 +0000'), new \DateTime('Thu, 02 Oct 2014 00:00:00 +0000'), $deL10N],
			[$deL10N->n('%n year ago', '%n years ago', 2), new \DateTime('Sun, 01 Jan 2012 00:00:00 +0000'), new \DateTime('Thu, 02 Oct 2014 00:00:00 +0000'), $deL10N],

			// Test time
			['today', new \DateTime('Wed, 02 Oct 2013 00:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')],
			['today', new \DateTime('Wed, 02 Oct 2013 12:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')],
			['today', new \DateTime('Wed, 02 Oct 2013 23:59:58 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')],

			// Test some special yesterdays
			['yesterday', new \DateTime('Tue, 01 Oct 2013 00:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 00:00:00 +0000')],
			['yesterday', new \DateTime('Tue, 01 Oct 2013 00:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')],
			['yesterday', new \DateTime('Tue, 01 Oct 2013 23:59:58 +0000'), new \DateTime('Wed, 02 Oct 2013 00:00:00 +0000')],
			['yesterday', new \DateTime('Tue, 01 Oct 2013 23:59:58 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')],
			['yesterday', new \DateTime('Mon, 30 Sep 2013 00:00:00 +0000'), new \DateTime('Tue, 01 Oct 2013 00:00:00 +0000')],
			['yesterday', new \DateTime('Mon, 31 Dec 2012 00:00:00 +0000'), new \DateTime('Tue, 01 Jan 2013 00:00:00 +0000')],

			// Test last month
			['2 days ago', new \DateTime('Mon, 30 Sep 2013 00:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 00:00:00 +0000')],
			['last month', new \DateTime('Mon, 30 Sep 2013 00:00:00 +0000'), new \DateTime('Tue, 31 Oct 2013 00:00:00 +0000')],
			['last month', new \DateTime('Sun, 01 Sep 2013 00:00:00 +0000'), new \DateTime('Tue, 01 Oct 2013 00:00:00 +0000')],
			['last month', new \DateTime('Sun, 01 Sep 2013 00:00:00 +0000'), new \DateTime('Thu, 31 Oct 2013 00:00:00 +0000')],

			// Test last year
			['9 months ago', new \DateTime('Tue, 31 Dec 2013 00:00:00 +0000'), new \DateTime('Thu, 02 Oct 2014 00:00:00 +0000')],
			['11 months ago', new \DateTime('Thu, 03 Oct 2013 00:00:00 +0000'), new \DateTime('Thu, 02 Oct 2014 00:00:00 +0000')],
			['last year', new \DateTime('Wed, 02 Oct 2013 00:00:00 +0000'), new \DateTime('Thu, 02 Oct 2014 00:00:00 +0000')],
			['last year', new \DateTime('Tue, 01 Jan 2013 00:00:00 +0000'), new \DateTime('Thu, 02 Oct 2014 00:00:00 +0000')],
			['2 years ago', new \DateTime('Sun, 01 Jan 2012 00:00:00 +0000'), new \DateTime('Thu, 02 Oct 2014 00:00:00 +0000')],
		];
	}

	/**
	 * @dataProvider formatDateSpanData
	 */
	public function testFormatDateSpan($expected, $timestamp, $compare = null, $locale = null): void {
		$this->assertEquals((string)$expected, (string)$this->formatter->formatDateSpan($timestamp, $compare, $locale));
	}

	public static function formatDateData(): array {
		return [
			[1102831200, 'December 12, 2004'],
		];
	}

	/**
	 * @dataProvider formatDateData
	 */
	public function testFormatDate($timestamp, $expected): void {
		$this->assertEquals($expected, (string)$this->formatter->formatDate($timestamp));
	}

	public static function formatDateTimeData(): array {
		return [
			[1350129205, null, "October 13, 2012, 11:53:25\xE2\x80\xAFAM UTC"],
			[1350129205, new \DateTimeZone('Europe/Berlin'), "October 13, 2012, 1:53:25\xE2\x80\xAFPM GMT+2"],
		];
	}

	/**
	 * @dataProvider formatDateTimeData
	 */
	public function testFormatDateTime($timestamp, $timeZone, $expected): void {
		$this->assertEquals($expected, (string)$this->formatter->formatDateTime($timestamp, 'long', 'long', $timeZone));
	}


	public function testFormatDateWithInvalidTZ(): void {
		$this->expectException(\Exception::class);

		$this->formatter->formatDate(1350129205, 'long', new \DateTimeZone('Mordor/Barad-dûr'));
	}
}
