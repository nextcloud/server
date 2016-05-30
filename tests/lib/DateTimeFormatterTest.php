<?php
/**
 * Copyright (c) 2014 Joas Schilling nickvergessen@owncloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

class DateTimeFormatterTest extends TestCase {
	/** @var \OC\DateTimeFormatter */
	protected $formatter;
	static protected $oneMinute = 60;
	static protected $oneHour = 3600;
	static protected $oneDay;
	static protected $oneYear;

	static protected $defaultTimeZone;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$defaultTimeZone = date_default_timezone_get();
		date_default_timezone_set('UTC');

		self::$oneDay = self::$oneHour * 24;
		self::$oneYear = self::$oneDay * 365;
	}

	public static function tearDownAfterClass() {
		date_default_timezone_set(self::$defaultTimeZone);
		parent::tearDownAfterClass();
	}

	protected function setUp() {
		parent::setUp();
		$this->formatter = new \OC\DateTimeFormatter(new \DateTimeZone('UTC'), new \OC_L10N('lib', 'en'));
	}

	protected function getTimestampAgo($time, $seconds = 0, $minutes = 0, $hours = 0, $days = 0, $years = 0) {
		return $time - $seconds - $minutes * 60 - $hours * 3600 - $days * 24*3600 - $years * 365*24*3600;
	}

	public function formatTimeSpanData() {
		$time = 1416916800; // Use a fixed timestamp so we don't switch days/years with the getTimestampAgo
		$deL10N = new \OC_L10N('lib', 'de');
		return array(
			array('seconds ago',	$time, $time),
			array('1 minute ago',	$this->getTimestampAgo($time, 30, 1), $time),
			array('15 minutes ago',	$this->getTimestampAgo($time, 30, 15), $time),
			array('1 hour ago',		$this->getTimestampAgo($time, 30, 15, 1), $time),
			array('3 hours ago',	$this->getTimestampAgo($time, 30, 15, 3), $time),
			array('4 days ago',		$this->getTimestampAgo($time, 30, 15, 3, 4), $time),

			array('seconds ago', new \DateTime('Wed, 02 Oct 2013 23:59:58 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')),
			array('seconds ago', new \DateTime('Wed, 02 Oct 2013 23:59:00 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')),
			array('1 minute ago', new \DateTime('Wed, 02 Oct 2013 23:58:30 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')),
			array('3 minutes ago', new \DateTime('Wed, 02 Oct 2013 23:56:30 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')),
			array('59 minutes ago', new \DateTime('Wed, 02 Oct 2013 23:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')),
			array('1 hour ago', new \DateTime('Wed, 02 Oct 2013 22:59:59 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')),
			array('3 hours ago', new \DateTime('Wed, 02 Oct 2013 20:39:59 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')),
			array('yesterday', new \DateTime('Tue, 01 Oct 2013 20:39:59 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')),
			array('2 days ago', new \DateTime('Mon, 30 Sep 2013 20:39:59 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')),

			array($deL10N->t('seconds ago'), new \DateTime('Wed, 02 Oct 2013 23:59:58 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000'), $deL10N),
			array($deL10N->n('%n minute ago', '%n minutes ago', 1), new \DateTime('Wed, 02 Oct 2013 23:58:30 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000'), $deL10N),
			array($deL10N->n('%n minute ago', '%n minutes ago', 3), new \DateTime('Wed, 02 Oct 2013 23:56:30 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000'), $deL10N),
			array($deL10N->n('%n hour ago', '%n hours ago', 1), new \DateTime('Wed, 02 Oct 2013 22:59:59 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000'), $deL10N),
			array($deL10N->n('%n hour ago', '%n hours ago', 3), new \DateTime('Wed, 02 Oct 2013 20:39:59 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000'), $deL10N),
			array($deL10N->n('%n day ago', '%n days ago', 2), new \DateTime('Mon, 30 Sep 2013 20:39:59 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000'), $deL10N),

		);
	}

	/**
	 * @dataProvider formatTimeSpanData
	 */
	public function testFormatTimeSpan($expected, $timestamp, $compare, $locale = null) {
		$this->assertEquals((string) $expected, (string) $this->formatter->formatTimeSpan($timestamp, $compare, $locale));
	}

	public function formatDateSpanData() {
		$time = 1416916800; // Use a fixed timestamp so we don't switch days/years with the getTimestampAgo
		$deL10N = new \OC_L10N('lib', 'de');
		return array(
			// Normal testing
			array('today',			$this->getTimestampAgo($time, 30, 15), $time),
			array('yesterday',		$this->getTimestampAgo($time, 0, 0, 0, 1), $time),
			array('4 days ago',		$this->getTimestampAgo($time, 0, 0, 0, 4), $time),
			array('5 months ago',	$this->getTimestampAgo($time, 0, 0, 0, 155), $time),
			array('2 years ago',	$this->getTimestampAgo($time, 0, 0, 0, 0, 2), $time),

			// Test with compare timestamp
			array('today',			$this->getTimestampAgo($time,  0,  0, 0, 0, 1), $this->getTimestampAgo($time, 0, 0, 0, 0, 1)),
			array('yesterday',		$this->getTimestampAgo($time, 30, 15, 3, 1, 1), $this->getTimestampAgo($time, 0, 0, 0, 0, 1)),
			array('4 days ago',		$this->getTimestampAgo($time, 30, 15, 3, 4, 1), $this->getTimestampAgo($time, 0, 0, 0, 0, 1)),
			array('5 months ago',	$this->getTimestampAgo($time, 30, 15, 3, 155, 1), $this->getTimestampAgo($time, 0, 0, 0, 0, 1)),
			array('2 years ago',	$this->getTimestampAgo($time, 30, 15, 3, 35, 3), $this->getTimestampAgo($time, 0, 0, 0, 0, 1)),

			// Test translations
			array($deL10N->t('today'),			new \DateTime('Wed, 02 Oct 2013 12:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000'), $deL10N),
			array($deL10N->t('yesterday'),		new \DateTime('Tue, 01 Oct 2013 00:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 00:00:00 +0000'), $deL10N),
			array($deL10N->n('%n day ago', '%n days ago', 2), new \DateTime('Mon, 30 Sep 2013 00:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 00:00:00 +0000'), $deL10N),
			array($deL10N->n('%n month ago', '%n months ago', 9), new \DateTime('Tue, 31 Dec 2013 00:00:00 +0000'), new \DateTime('Thu, 02 Oct 2014 00:00:00 +0000'), $deL10N),
			array($deL10N->n('%n year ago', '%n years ago', 2), new \DateTime('Sun, 01 Jan 2012 00:00:00 +0000'), new \DateTime('Thu, 02 Oct 2014 00:00:00 +0000'), $deL10N),

			// Test time
			array('today', new \DateTime('Wed, 02 Oct 2013 00:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')),
			array('today', new \DateTime('Wed, 02 Oct 2013 12:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')),
			array('today', new \DateTime('Wed, 02 Oct 2013 23:59:58 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')),

			// Test some special yesterdays
			array('yesterday', new \DateTime('Tue, 01 Oct 2013 00:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 00:00:00 +0000')),
			array('yesterday', new \DateTime('Tue, 01 Oct 2013 00:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')),
			array('yesterday', new \DateTime('Tue, 01 Oct 2013 23:59:58 +0000'), new \DateTime('Wed, 02 Oct 2013 00:00:00 +0000')),
			array('yesterday', new \DateTime('Tue, 01 Oct 2013 23:59:58 +0000'), new \DateTime('Wed, 02 Oct 2013 23:59:59 +0000')),
			array('yesterday', new \DateTime('Mon, 30 Sep 2013 00:00:00 +0000'), new \DateTime('Tue, 01 Oct 2013 00:00:00 +0000')),
			array('yesterday', new \DateTime('Mon, 31 Dec 2012 00:00:00 +0000'), new \DateTime('Tue, 01 Jan 2013 00:00:00 +0000')),

			// Test last month
			array('2 days ago', new \DateTime('Mon, 30 Sep 2013 00:00:00 +0000'), new \DateTime('Wed, 02 Oct 2013 00:00:00 +0000')),
			array('last month', new \DateTime('Mon, 30 Sep 2013 00:00:00 +0000'), new \DateTime('Tue, 31 Oct 2013 00:00:00 +0000')),
			array('last month', new \DateTime('Sun, 01 Sep 2013 00:00:00 +0000'), new \DateTime('Tue, 01 Oct 2013 00:00:00 +0000')),
			array('last month', new \DateTime('Sun, 01 Sep 2013 00:00:00 +0000'), new \DateTime('Thu, 31 Oct 2013 00:00:00 +0000')),

			// Test last year
			array('9 months ago', new \DateTime('Tue, 31 Dec 2013 00:00:00 +0000'), new \DateTime('Thu, 02 Oct 2014 00:00:00 +0000')),
			array('11 months ago', new \DateTime('Thu, 03 Oct 2013 00:00:00 +0000'), new \DateTime('Thu, 02 Oct 2014 00:00:00 +0000')),
			array('last year', new \DateTime('Wed, 02 Oct 2013 00:00:00 +0000'), new \DateTime('Thu, 02 Oct 2014 00:00:00 +0000')),
			array('last year', new \DateTime('Tue, 01 Jan 2013 00:00:00 +0000'), new \DateTime('Thu, 02 Oct 2014 00:00:00 +0000')),
			array('2 years ago', new \DateTime('Sun, 01 Jan 2012 00:00:00 +0000'), new \DateTime('Thu, 02 Oct 2014 00:00:00 +0000')),
		);
	}

	/**
	 * @dataProvider formatDateSpanData
	 */
	public function testFormatDateSpan($expected, $timestamp, $compare = null, $locale = null) {
		$this->assertEquals((string) $expected, (string) $this->formatter->formatDateSpan($timestamp, $compare, $locale));
	}

	public function formatDateData() {
		return array(
			array(1102831200, 'December 12, 2004'),
		);
	}

	/**
	 * @dataProvider formatDateData
	 */
	public function testFormatDate($timestamp, $expected) {
		$this->assertEquals($expected, (string) $this->formatter->formatDate($timestamp));
	}

	public function formatDateTimeData() {
		return array(
			array(1350129205, null, 'October 13, 2012 at 11:53:25 AM GMT+0'),
			array(1350129205, new \DateTimeZone('Europe/Berlin'), 'October 13, 2012 at 1:53:25 PM GMT+2'),
		);
	}

	/**
	 * @dataProvider formatDateTimeData
	 */
	public function testFormatDateTime($timestamp, $timeZone, $expected) {
		$this->assertEquals($expected, (string) $this->formatter->formatDateTime($timestamp, 'long', 'long', $timeZone));
	}

	/**
	 * @expectedException \Exception
	 */
	function testFormatDateWithInvalidTZ() {
		$this->formatter->formatDate(1350129205, 'long', new \DateTimeZone('Mordor/Barad-dûr'));
	}
}
