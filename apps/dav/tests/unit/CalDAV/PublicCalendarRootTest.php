<?php

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\Calendar;
use OCP\IL10N;
use OCP\IConfig;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\PublicCalendarRoot;
use Test\TestCase;
use Sabre\Uri;

/**
 * Class PublicCalendarRootTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\CalDAV
 */
class PublicCalendarRootTest extends TestCase {

	const UNIT_TEST_USER = 'principals/users/caldav-unit-test';

	/** @var CalDavBackend */
	private $backend;

	/** @var PublicCalendarRoot */
	private $publicCalendarRoot;

	/** @var IL10N */
	private $l10n;

	/** var IConfig */
	protected $config;

	private $principal;

	public function setUp() {
		parent::setUp();

		$db = \OC::$server->getDatabaseConnection();
		$this->principal = $this->getMockBuilder('OCA\DAV\Connector\Sabre\Principal')
			->disableOriginalConstructor()
			->getMock();
		$this->config = \OC::$server->getConfig();

		$this->backend = new CalDavBackend($db, $this->principal, $this->config);

		$this->publicCalendarRoot = new PublicCalendarRoot($this->backend);

		$this->l10n = $this->getMockBuilder('\OCP\IL10N')
			->disableOriginalConstructor()->getMock();
	}

	public function testGetName() {
		$name = $this->publicCalendarRoot->getName();
		$this->assertEquals('public-calendars', $name);
	}

	public function testGetChild() {

		$calendar = $this->createPublicCalendar();

		$publicCalendarURI = md5($this->config->getSystemValue('secret', '') . $calendar->getResourceId());

		$calendarResult = $this->publicCalendarRoot->getChild($publicCalendarURI);
		$this->assertEquals($calendar, $calendarResult);
	}

	public function testGetChildren() {

		$publicCalendars = $this->backend->getPublicCalendars();

		$calendarResults = $this->publicCalendarRoot->getChildren();

		$this->assertEquals(1, count($calendarResults));
		$this->assertEquals(new Calendar($this->backend, $publicCalendars[0], $this->l10n), $calendarResults[0]);

	}

	/**
	 * @return Calendar
	 */
	protected function createPublicCalendar() {
		$this->backend->createCalendar(self::UNIT_TEST_USER, 'Example', []);

		$calendarInfo = $this->backend->getCalendarsForUser(self::UNIT_TEST_USER)[0];

		$calendarInfo['uri'] = md5($this->config->getSystemValue('secret', '') . $calendarInfo['id']);
		list(, $name) = Uri\split($calendarInfo['principaluri']);
		$calendarInfo['{DAV:}displayname'] = $calendarInfo['{DAV:}displayname'] . ' (' . $name . ')';
		$calendarInfo['{http://owncloud.org/ns}owner-principal'] = $calendarInfo['principaluri'];
		$calendarInfo['{http://owncloud.org/ns}read-only'] = false;
		$calendarInfo['{http://owncloud.org/ns}public'] = true;

		$calendar = new Calendar($this->backend, $calendarInfo, $this->l10n);
		$calendar->setPublishStatus(true);

		return $calendar;
	}


}
