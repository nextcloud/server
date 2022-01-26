<?php
/**
 * @copyright Copyright (c) 2016 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vinicius Cubas Brand <vinicius@eita.org.br>
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

use OC;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\PublicCalendar;
use OCA\DAV\CalDAV\PublicCalendarRoot;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\NotFound;
use Test\TestCase;

/**
 * Class PublicCalendarRootTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\CalDAV
 */
class PublicCalendarRootTest extends TestCase {
	public const UNIT_TEST_USER = '';
	/** @var CalDavBackend */
	private $backend;
	/** @var PublicCalendarRoot */
	private $publicCalendarRoot;
	/** @var IL10N */
	private $l10n;
	/** @var Principal|MockObject */
	private $principal;
	/** @var IUserManager|MockObject */
	protected $userManager;
	/** @var IGroupManager|MockObject */
	protected $groupManager;
	/** @var IConfig */
	protected $config;
	/** @var LoggerInterface */
	protected $logger;

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	protected function setUp(): void {
		parent::setUp();

		$db = OC::$server->get(IDBConnection::class);
		$this->principal = $this->createMock(Principal::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$random = OC::$server->get(ISecureRandom::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$config = $this->createMock(IConfig::class);

		$this->principal->expects($this->any())->method('getGroupMembership')
			->withAnyParameters()
			->willReturn([]);

		$this->principal->expects($this->any())->method('getCircleMembership')
			->withAnyParameters()
			->willReturn([]);

		$this->backend = new CalDavBackend(
			$db,
			$this->principal,
			$this->userManager,
			$this->groupManager,
			$random,
			$this->logger,
			$dispatcher,
			$config
		);
		$this->l10n = $this->createMock(IL10N::class);
		$this->config = $this->createMock(IConfig::class);

		$this->publicCalendarRoot = new PublicCalendarRoot($this->backend,
			$this->l10n, $this->config, $this->logger);
	}

	protected function tearDown(): void {
		parent::tearDown();

		if (is_null($this->backend)) {
			return;
		}
		$this->principal->expects($this->any())->method('getGroupMembership')
			->withAnyParameters()
			->willReturn([]);

		$this->principal->expects($this->any())->method('getCircleMembership')
			->withAnyParameters()
			->willReturn([]);

		$books = $this->backend->getCalendarsForUser(self::UNIT_TEST_USER);
		foreach ($books as $book) {
			$this->backend->deleteCalendar($book['id'], true);
		}
	}

	public function testGetName() {
		$name = $this->publicCalendarRoot->getName();
		$this->assertEquals('public-calendars', $name);
	}

	/**
	 * @throws Exception
	 * @throws NotFound
	 */
	public function testGetChild() {
		$calendar = $this->createPublicCalendar();

		$publicCalendars = $this->backend->getPublicCalendars();
		$this->assertCount(1, $publicCalendars);
		$this->assertEquals(true, $publicCalendars[0]['{http://owncloud.org/ns}public']);

		$publicCalendarURI = $publicCalendars[0]['uri'];

		$calendarResult = $this->publicCalendarRoot->getChild($publicCalendarURI);
		$this->assertEquals($calendar, $calendarResult);
	}

	/**
	 * @throws Exception
	 */
	public function testGetChildren() {
		$this->createPublicCalendar();
		$calendarResults = $this->publicCalendarRoot->getChildren();
		$this->assertSame([], $calendarResults);
	}

	/**
	 * @return Calendar
	 * @throws Exception
	 */
	protected function createPublicCalendar() {
		$this->backend->createCalendar(self::UNIT_TEST_USER, 'Example', []);

		$calendarInfo = $this->backend->getCalendarsForUser(self::UNIT_TEST_USER)[0];
		$calendar = new PublicCalendar($this->backend, $calendarInfo, $this->l10n, $this->config, $this->logger);
		$publicUri = $calendar->setPublishStatus(true);

		$calendarInfo = $this->backend->getPublicCalendar($publicUri);
		return new PublicCalendar($this->backend, $calendarInfo, $this->l10n, $this->config, $this->logger);
	}
}
