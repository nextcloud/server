<?php
/**
 * @copyright 2022 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
namespace OCA\DAV\Tests\unit\CalDAV\Listeners;

use OCA\DAV\CalDAV\Activity\Backend;
use OCA\DAV\Events\CalendarShareUpdatedEvent;
use OCA\DAV\Listener\CalendarShareUpdateListener;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CalendarShareUpdateListenerTest extends TestCase {

	/** @var Backend|MockObject */
	private $activityBackend;

	/** @var LoggerInterface|MockObject */
	private $logger;

	private CalendarShareUpdateListener $calendarPublicationListener;

	/** @var CalendarShareUpdatedEvent|MockObject */
	private $event;

	protected function setUp(): void {
		parent::setUp();

		$this->activityBackend = $this->createMock(Backend::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->event = $this->createMock(CalendarShareUpdatedEvent::class);
		$this->calendarPublicationListener = new CalendarShareUpdateListener($this->activityBackend, $this->logger);
	}

	public function testInvalidEvent(): void {
		$this->activityBackend->expects($this->never())->method('onCalendarUpdateShares');
		$this->logger->expects($this->never())->method('debug');
		$this->calendarPublicationListener->handle(new Event());
	}

	public function testEvent(): void {
		$this->event->expects($this->once())->method('getCalendarData')->with()->willReturn([]);
		$this->event->expects($this->once())->method('getOldShares')->with()->willReturn([]);
		$this->event->expects($this->once())->method('getAdded')->with()->willReturn([]);
		$this->event->expects($this->once())->method('getRemoved')->with()->willReturn([]);
		$this->activityBackend->expects($this->once())->method('onCalendarUpdateShares')->with([], [], [], []);
		$this->logger->expects($this->once())->method('debug');
		$this->calendarPublicationListener->handle($this->event);
	}
}
