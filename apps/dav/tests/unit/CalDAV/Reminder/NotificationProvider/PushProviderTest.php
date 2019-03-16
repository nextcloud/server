<?php
/**
 * @copyright Copyright (c) 2019, Thomas Citharel
 *
 * @author Thomas Citharel <tcit@tcit.fr>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\CalDAV\Reminder\NotificationProvider;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\PushProvider;
use OCA\DAV\CalDAV\Reminder\AbstractNotificationProvider;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\L10N\IFactory as L10NFactory;
use OCP\IUser;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\AppFramework\Utility\ITimeFactory;
use Test\TestCase;
use OCA\DAV\Tests\unit\CalDAV\Reminder\AbstractNotificationProviderTest;

class PushProviderTest extends AbstractNotificationProviderTest {

    /** @var ILogger|\PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var L10NFactory|\PHPUnit\Framework\MockObject\MockObject */
    protected $l10nFactory;

    /** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
    protected $l10n;

    /** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
    protected $urlGenerator;

    /** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
    protected $config;

    /** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $timeFactory;

    public function setUp() {
        parent::setUp();

        $this->manager = $this->createMock(IManager::class);
        $this->timeFactory = $this->createMock(ITimeFactory::class);

        $this->provider = new PushProvider(
            $this->config,
            $this->manager,
            $this->logger,
            $this->l10nFactory,
            $this->urlGenerator,
            $this->timeFactory
        );
    }

    public function testSend(): void
    {
        $notification = $this->createMock(INotification::class);
        $notification
            ->expects($this->once())
            ->method('setApp')
            ->with(Application::APP_ID)
            ->willReturn($notification);

        $notification
            ->expects($this->once())
            ->method('setUser')
            ->willReturn($notification)
        ;

        $notification
            ->expects($this->once())
            ->method('setDateTime')
            ->willReturn($notification)
        ;

        $notification
            ->expects($this->once())
            ->method('setObject')
            ->willReturn($notification)
        ;

        $notification
            ->expects($this->once())
            ->method('setSubject')
            ->willReturn($notification)
        ;

        $notification
            ->expects($this->once())
            ->method('setMessage')
            ->willReturn($notification)
        ;

        $this->manager
            ->expects($this->once())
            ->method('createNotification')
            ->willReturn($notification);

        $this->manager
            ->expects($this->once())
            ->method('notify')
            ->with($notification);

        $l10n = $this->createMock(IL10N::class);
        $this->l10nFactory
            ->method('get')
            ->willReturn($l10n);

        $this->timeFactory->expects($this->once())
			->method('getDateTime')
			->with()
			->willReturn(new \DateTime());

		$this->provider->send($this->vcalendar, $this->calendarDisplayName, $this->user);
    }
}
