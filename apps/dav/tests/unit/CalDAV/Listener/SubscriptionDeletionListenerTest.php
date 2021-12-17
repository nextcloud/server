<?php
/**
 * @copyright 2021 Thomas Citharel <nextcloud@tcit.fr>
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

use OCA\DAV\BackgroundJob\RefreshWebcalJob;
use OCA\DAV\CalDAV\Reminder\Backend;
use OCA\DAV\Events\SubscriptionDeletedEvent;
use OCA\DAV\Listener\SubscriptionDeletionListener;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class SubscriptionDeletionListenerTest extends TestCase {

	/** @var Backend|MockObject */
	private $reminderBackend;

	/** @var IJobList|MockObject */
	private $jobList;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var SubscriptionDeletionListener */
	private $calendarPublicationListener;

	/** @var SubscriptionDeletedEvent|MockObject */
	private $event;

	protected function setUp(): void {
		parent::setUp();

		$this->reminderBackend = $this->createMock(Backend::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->event = $this->createMock(SubscriptionDeletedEvent::class);
		$this->calendarPublicationListener = new SubscriptionDeletionListener($this->jobList, $this->reminderBackend, $this->logger);
	}

	public function testInvalidEvent(): void {
		$this->jobList->expects($this->never())->method('remove');
		$this->logger->expects($this->never())->method('debug');
		$this->calendarPublicationListener->handle(new Event());
	}

	public function testEvent(): void {
		$this->event->expects($this->once())->method('getSubscriptionId')->with()->willReturn(5);
		$this->event->expects($this->once())->method('getSubscriptionData')->with()->willReturn(['principaluri' => 'principaluri', 'uri' => 'uri']);
		$this->jobList->expects($this->once())->method('remove')->with(RefreshWebcalJob::class, ['principaluri' => 'principaluri', 'uri' => 'uri']);
		$this->reminderBackend->expects($this->once())->method('cleanRemindersForCalendar')->with(5);
		$this->logger->expects($this->exactly(2))->method('debug');
		$this->calendarPublicationListener->handle($this->event);
	}
}
