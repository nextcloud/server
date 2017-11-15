<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2017, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\DAV\Tests\unit\CalDAV\Schedule;

use OC\Mail\Mailer;
use OCA\DAV\CalDAV\Schedule\IMipPlugin;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Mail\IAttachment;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\ITip\Message;
use Test\TestCase;

class IMipPluginTest extends TestCase {

	public function testDelivery() {
		$mailMessage = $this->createMock(IMessage::class);
		$mailMessage->method('setFrom')->willReturn($mailMessage);
		$mailMessage->method('setReplyTo')->willReturn($mailMessage);
		$mailMessage->method('setTo')->willReturn($mailMessage);
		/** @var Mailer | \PHPUnit_Framework_MockObject_MockObject $mailer */
		$mailer = $this->getMockBuilder(IMailer::class)->disableOriginalConstructor()->getMock();
		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$emailAttachment = $this->createMock(IAttachment::class);
		$mailer->method('createEMailTemplate')->willReturn($emailTemplate);
		$mailer->method('createMessage')->willReturn($mailMessage);
		$mailer->method('createAttachment')->willReturn($emailAttachment);
		$mailer->expects($this->once())->method('send');
		/** @var ILogger | \PHPUnit_Framework_MockObject_MockObject $logger */
		$logger = $this->getMockBuilder(ILogger::class)->disableOriginalConstructor()->getMock();
		$timeFactory = $this->getMockBuilder(ITimeFactory::class)->disableOriginalConstructor()->getMock();
		$timeFactory->method('getTime')->willReturn(1);
		/** @var IConfig | \PHPUnit_Framework_MockObject_MockObject $config */
		$config = $this->createMock(IConfig::class);
		$l10n = $this->createMock(IL10N::class);
		/** @var IFactory | \PHPUnit_Framework_MockObject_MockObject $l10nFactory */
		$l10nFactory = $this->createMock(IFactory::class);
		$l10nFactory->method('get')->willReturn($l10n);
		/** @var IURLGenerator | \PHPUnit_Framework_MockObject_MockObject $urlGenerator */
		$urlGenerator = $this->createMock(IURLGenerator::class);
		/** @var Defaults | \PHPUnit_Framework_MockObject_MockObject $defaults */
		$defaults = $this->createMock(Defaults::class);
		$defaults->expects($this->once())
			->method('getName')
			->will($this->returnValue('Instance Name 123'));

		$plugin = new IMipPlugin($config, $mailer, $logger, $timeFactory, $l10nFactory, $urlGenerator, $defaults, 'user123');
		$message = new Message();
		$message->method = 'REQUEST';
		$message->message = new VCalendar();
		$message->message->add('VEVENT', [
			'UID' => $message->uid,
			'SEQUENCE' => $message->sequence,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2017-01-01 00:00:00') // 1483228800
		]);
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->recipient = 'mailto:frodo@hobb.it';

		$emailTemplate->expects($this->once())
			->method('setSubject')
			->with('Invitation: Fellowship meeting');
		$mailMessage->expects($this->once())
			->method('setTo')
			->with(['frodo@hobb.it' => null]);
		$mailMessage->expects($this->once())
			->method('setReplyTo')
			->with(['gandalf@wiz.ard' => null]);

		$plugin->schedule($message);
		$this->assertEquals('1.1', $message->getScheduleStatus());
	}

	public function testFailedDelivery() {
		$mailMessage = $this->createMock(IMessage::class);
		$mailMessage->method('setFrom')->willReturn($mailMessage);
		$mailMessage->method('setReplyTo')->willReturn($mailMessage);
		$mailMessage->method('setTo')->willReturn($mailMessage);
		/** @var Mailer | \PHPUnit_Framework_MockObject_MockObject $mailer */
		$mailer = $this->getMockBuilder(IMailer::class)->disableOriginalConstructor()->getMock();
		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$emailAttachment = $this->createMock(IAttachment::class);
		$mailer->method('createEMailTemplate')->willReturn($emailTemplate);
		$mailer->method('createMessage')->willReturn($mailMessage);
		$mailer->method('createAttachment')->willReturn($emailAttachment);
		$mailer->method('send')->willThrowException(new \Exception());
		/** @var ILogger | \PHPUnit_Framework_MockObject_MockObject $logger */
		$logger = $this->getMockBuilder(ILogger::class)->disableOriginalConstructor()->getMock();
		$timeFactory = $this->getMockBuilder(ITimeFactory::class)->disableOriginalConstructor()->getMock();
		$timeFactory->method('getTime')->willReturn(1);
		/** @var IConfig | \PHPUnit_Framework_MockObject_MockObject $config */
		$config = $this->createMock(IConfig::class);
		$l10n = $this->createMock(IL10N::class);
		/** @var IFactory | \PHPUnit_Framework_MockObject_MockObject $l10nFactory */
		$l10nFactory = $this->createMock(IFactory::class);
		$l10nFactory->method('get')->willReturn($l10n);
		/** @var IURLGenerator | \PHPUnit_Framework_MockObject_MockObject $urlGenerator */
		$urlGenerator = $this->createMock(IURLGenerator::class);
		/** @var Defaults | \PHPUnit_Framework_MockObject_MockObject $defaults */
		$defaults = $this->createMock(Defaults::class);

		$plugin = new IMipPlugin($config, $mailer, $logger, $timeFactory, $l10nFactory, $urlGenerator, $defaults, 'user123');
		$message = new Message();
		$message->method = 'REQUEST';
		$message->message = new VCalendar();
		$message->message->add('VEVENT', [
			'UID' => $message->uid,
			'SEQUENCE' => $message->sequence,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2017-01-01 00:00:00') // 1483228800
		]);
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->recipient = 'mailto:frodo@hobb.it';

		$emailTemplate->expects($this->once())
			->method('setSubject')
			->with('Invitation: Fellowship meeting');
		$mailMessage->expects($this->once())
			->method('setTo')
			->with(['frodo@hobb.it' => null]);
		$mailMessage->expects($this->once())
			->method('setReplyTo')
			->with(['gandalf@wiz.ard' => null]);

		$plugin->schedule($message);
		$this->assertEquals('5.0', $message->getScheduleStatus());
	}

	/**
	 * @dataProvider dataNoMessageSendForPastEvents
	 */
	public function testNoMessageSendForPastEvents($veventParams, $expectsMail) {
		$mailMessage = $this->createMock(IMessage::class);
		$mailMessage->method('setFrom')->willReturn($mailMessage);
		$mailMessage->method('setReplyTo')->willReturn($mailMessage);
		$mailMessage->method('setTo')->willReturn($mailMessage);
		/** @var Mailer | \PHPUnit_Framework_MockObject_MockObject $mailer */
		$mailer = $this->getMockBuilder(IMailer::class)->disableOriginalConstructor()->getMock();
		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$emailAttachment = $this->createMock(IAttachment::class);
		$mailer->method('createEMailTemplate')->willReturn($emailTemplate);
		$mailer->method('createMessage')->willReturn($mailMessage);
		$mailer->method('createAttachment')->willReturn($emailAttachment);
		if ($expectsMail) {
			$mailer->expects($this->once())->method('send');
		} else {
			$mailer->expects($this->never())->method('send');
		}
		/** @var ILogger | \PHPUnit_Framework_MockObject_MockObject $logger */
		$logger = $this->getMockBuilder(ILogger::class)->disableOriginalConstructor()->getMock();
		$timeFactory = $this->getMockBuilder(ITimeFactory::class)->disableOriginalConstructor()->getMock();
		$timeFactory->method('getTime')->willReturn(1496912528);
		/** @var IConfig | \PHPUnit_Framework_MockObject_MockObject $config */
		$config = $this->createMock(IConfig::class);
		$l10n = $this->createMock(IL10N::class);
		/** @var IFactory | \PHPUnit_Framework_MockObject_MockObject $l10nFactory */
		$l10nFactory = $this->createMock(IFactory::class);
		$l10nFactory->method('get')->willReturn($l10n);
		/** @var IURLGenerator | \PHPUnit_Framework_MockObject_MockObject $urlGenerator */
		$urlGenerator = $this->createMock(IURLGenerator::class);
		/** @var Defaults | \PHPUnit_Framework_MockObject_MockObject $defaults */
		$defaults = $this->createMock(Defaults::class);

		$plugin = new IMipPlugin($config, $mailer, $logger, $timeFactory, $l10nFactory, $urlGenerator, $defaults, 'user123');
		$message = new Message();
		$message->method = 'REQUEST';
		$message->message = new VCalendar();
		$message->message->add('VEVENT', array_merge([
			'UID' => 'uid1337',
			'SEQUENCE' => 42,
			'SUMMARY' => 'Fellowship meeting',
		], $veventParams));
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->recipient = 'mailto:frodo@hobb.it';

		$plugin->schedule($message);

		if ($expectsMail) {
			$this->assertEquals('1.1', $message->getScheduleStatus());
 		} else {
			$this->assertEquals(false, $message->getScheduleStatus());
		}
	}

	public function dataNoMessageSendForPastEvents() {
		return [
			[['DTSTART' => new \DateTime('2017-01-01 00:00:00')], false],
			[['DTSTART' => new \DateTime('2017-01-01 00:00:00'), 'DTEND' => new \DateTime('2017-01-01 00:00:00')], false],
			[['DTSTART' => new \DateTime('2017-01-01 00:00:00'), 'DTEND' => new \DateTime('2017-12-31 00:00:00')], true],
			[['DTSTART' => new \DateTime('2017-01-01 00:00:00'), 'DURATION' => 'P1D'], false],
			[['DTSTART' => new \DateTime('2017-01-01 00:00:00'), 'DURATION' => 'P52W'], true],
			[['DTSTART' => new \DateTime('2017-01-01 00:00:00'), 'DTEND' => new \DateTime('2017-01-01 00:00:00'), 'RRULE' => 'FREQ=WEEKLY'], true],
			[['DTSTART' => new \DateTime('2017-01-01 00:00:00'), 'DTEND' => new \DateTime('2017-01-01 00:00:00'), 'RRULE' => 'FREQ=WEEKLY;COUNT=3'], false],
			[['DTSTART' => new \DateTime('2017-01-01 00:00:00'), 'DTEND' => new \DateTime('2017-01-01 00:00:00'), 'RRULE' => 'FREQ=WEEKLY;UNTIL=20170301T000000Z'], false],
			[['DTSTART' => new \DateTime('2017-01-01 00:00:00'), 'DTEND' => new \DateTime('2017-01-01 00:00:00'), 'RRULE' => 'FREQ=WEEKLY;COUNT=33'], true],
			[['DTSTART' => new \DateTime('2017-01-01 00:00:00'), 'DTEND' => new \DateTime('2017-01-01 00:00:00'), 'RRULE' => 'FREQ=WEEKLY;UNTIL=20171001T000000Z'], true],
		];
	}
}
