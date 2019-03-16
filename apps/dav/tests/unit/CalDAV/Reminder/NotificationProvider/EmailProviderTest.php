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

use OCA\DAV\CalDAV\Reminder\NotificationProvider\EmailProvider;
use OCA\DAV\CalDAV\Reminder\AbstractNotificationProvider;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\L10N\IFactory as L10NFactory;
use OCP\IUser;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IAttachment;
use OCP\Mail\IMessage;
use Test\TestCase;
use OCA\DAV\Tests\unit\CalDAV\Reminder\AbstractNotificationProviderTest;

class EmailProviderTest extends AbstractNotificationProviderTest {

    const USER_EMAIL = 'frodo@hobb.it';

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

    /** @var IMailer|\PHPUnit\Framework\MockObject\MockObject */
	private $mailer;

    public function setUp() {
        parent::setUp();

        $this->mailer = $this->createMock(IMailer::class);

        $this->provider = new EmailProvider(
            $this->config,
            $this->mailer,
            $this->logger,
            $this->l10nFactory,
            $this->urlGenerator
        );
    }

    public function testSendWithNoUserEmail(): void
    {
        $this->user->expects($this->once())
            ->method('getEMailAddress')
            ->with()
            ->willReturn(null);

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->provider->send($this->vcalendar, $this->calendarDisplayName, $this->user);
    }

    public function testSendWithFailedRecipients(): void
    {
        $this->user->expects($this->exactly(2))
            ->method('getEMailAddress')
            ->with()
            ->willReturn(self::USER_EMAIL);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->willReturn([self::USER_EMAIL])
        ;

        $this->logger
            ->expects($this->once())
            ->method('error');

        $l10n = $this->createMock(IL10N::class);
        $this->l10nFactory
            ->method('get')
            ->willReturn($l10n);

		$this->provider->send($this->vcalendar, $this->calendarDisplayName, $this->user);
    }

    public function testSendWithMailerFailure(): void
    {
        $this->user->expects($this->exactly(2))
            ->method('getEMailAddress')
            ->with()
            ->willReturn(self::USER_EMAIL);

        $ex = new \Exception();

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->will($this->throwException($ex))
        ;

        $this->logger
            ->expects($this->once())
            ->method('logException')
            ->with($ex, ['app' => 'dav']);

        $l10n = $this->createMock(IL10N::class);
        $this->l10nFactory
            ->method('get')
            ->willReturn($l10n);

		$this->provider->send($this->vcalendar, $this->calendarDisplayName, $this->user);
    }

    public function testSend(): void
    {
        $this->user->expects($this->exactly(2))
            ->method('getEMailAddress')
            ->with()
            ->willReturn(self::USER_EMAIL);

        $this->user->expects($this->once())
            ->method('getDisplayName')
            ->with()
            ->willReturn('Frodo');

        $this->urlGenerator
            ->expects($this->exactly(2))
            ->method('getAbsoluteURL');

        $this->urlGenerator
            ->expects($this->exactly(2))
            ->method('imagePath');

        $mailMessage = $this->createMock(IMessage::class);
        $mailMessage->expects($this->once())
            ->method('setFrom')
            ->with([\OCP\Util::getDefaultEmailAddress('invitations-noreply') => 'Nextcloud'])
            ->willReturn($mailMessage);

        $mailMessage->expects($this->once())
			->method('setTo')
            ->with([self::USER_EMAIL => 'Frodo'])
            ->willReturn($mailMessage);

        $mailMessage
            ->expects($this->never())
            ->method('setReplyTo')
            ->willReturn($mailMessage);

		$emailTemplate = $this->createMock(IEMailTemplate::class);
        $this->mailer
            ->expects($this->once())
            ->method('createEMailTemplate')
            ->willReturn($emailTemplate);

        $emailTemplate->expects($this->once())
			->method('setSubject')
            ->with('Notification: Fellowship meeting');

        $emailTemplate->expects($this->once())
            ->method('addHeader');

        $emailTemplate->expects($this->once())
            ->method('addHeading');

        $emailTemplate->expects($this->exactly(2))
            ->method('addBodyListItem');

        $emailTemplate->expects($this->once())
            ->method('addFooter');

        $mailMessage->expects($this->once())
            ->method('useTemplate')
            ->with($emailTemplate);

        $this->mailer
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($mailMessage);

        $emailAttachment = $this->createMock(IAttachment::class);
        $this->mailer
            ->expects($this->once())
            ->method('createAttachment')
            ->willReturn($emailAttachment);

        $this->mailer
            ->expects($this->once())
            ->method('send');

		$l10n = $this->createMock(IL10N::class);
        $this->l10nFactory
            ->method('get')
            ->willReturn($l10n);

		$this->provider->send($this->vcalendar, $this->calendarDisplayName, $this->user);
    }
}
