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

use OCA\DAV\BackgroundJob\RefreshWebcalJob;
use OCA\DAV\CalDAV\Reminder\Backend;
use OCA\DAV\CalDAV\WebcalCaching\RefreshWebcalService;
use OCA\DAV\Events\SubscriptionCreatedEvent;
use OCA\DAV\Events\SubscriptionDeletedEvent;
use OCA\DAV\Listener\SubscriptionListener;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class SubscriptionListenerTest extends TestCase {

	/** @var RefreshWebcalService|MockObject */
	private $refreshWebcalService;

	/** @var Backend|MockObject */
	private $reminderBackend;

	/** @var IJobList|MockObject */
	private $jobList;

	/** @var LoggerInterface|MockObject */
	private $logger;

	private SubscriptionListener $calendarPublicationListener;

	/** @var SubscriptionCreatedEvent|MockObject */
	private $subscriptionCreatedEvent;

	/** @var SubscriptionDeletedEvent|MockObject */
	private $subscriptionDeletedEvent;

	protected function setUp(): void {
		parent::setUp();

		$this->refreshWebcalService = $this->createMock(RefreshWebcalService::class);
		$this->reminderBackend = $this->createMock(Backend::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->subscriptionCreatedEvent = $this->createMock(SubscriptionCreatedEvent::class);
		$this->subscriptionDeletedEvent = $this->createMock(SubscriptionDeletedEvent::class);
		$this->calendarPublicationListener = new SubscriptionListener($this->jobList, $this->refreshWebcalService, $this->reminderBackend, $this->logger);
	}

	public function testInvalidEvent(): void {
		$this->refreshWebcalService->expects($this->never())->method('refreshSubscription');
		$this->jobList->expects($this->never())->method('add');
		$this->logger->expects($this->never())->method('debug');
		$this->calendarPublicationListener->handle(new Event());
	}

	public function testCreateSubscriptionEvent(): void {
		$this->subscriptionCreatedEvent->expects($this->once())->method('getSubscriptionId')->with()->willReturn(5);
		$this->subscriptionCreatedEvent->expects($this->once())->method('getSubscriptionData')->with()->willReturn(['principaluri' => 'principaluri', 'uri' => 'uri']);
		$this->refreshWebcalService->expects($this->once())->method('refreshSubscription')->with('principaluri', 'uri');
		$this->jobList->expects($this->once())->method('add')->with(RefreshWebcalJob::class, ['principaluri' => 'principaluri', 'uri' => 'uri']);
		$this->logger->expects($this->exactly(2))->method('debug');
		$this->calendarPublicationListener->handle($this->subscriptionCreatedEvent);
	}

	public function testDeleteSubscriptionEvent(): void {
		$this->subscriptionDeletedEvent->expects($this->once())->method('getSubscriptionId')->with()->willReturn(5);
		$this->subscriptionDeletedEvent->expects($this->once())->method('getSubscriptionData')->with()->willReturn(['principaluri' => 'principaluri', 'uri' => 'uri']);
		$this->jobList->expects($this->once())->method('remove')->with(RefreshWebcalJob::class, ['principaluri' => 'principaluri', 'uri' => 'uri']);
		$this->reminderBackend->expects($this->once())->method('cleanRemindersForCalendar')->with(5);
		$this->logger->expects($this->exactly(2))->method('debug');
		$this->calendarPublicationListener->handle($this->subscriptionDeletedEvent);
	}
}
