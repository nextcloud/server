<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2017, Georg Ehrke
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
use OCP\ILogger;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\ITip\Message;
use Test\TestCase;

class IMipPluginTest extends TestCase {

	public function testDelivery() {
		$mailMessage = new \OC\Mail\Message(new \Swift_Message(), false);
		/** @var Mailer | \PHPUnit_Framework_MockObject_MockObject $mailer */
		$mailer = $this->getMockBuilder('OC\Mail\Mailer')->disableOriginalConstructor()->getMock();
		$mailer->method('createMessage')->willReturn($mailMessage);
		$mailer->expects($this->once())->method('send');
		/** @var ILogger | \PHPUnit_Framework_MockObject_MockObject $logger */
		$logger = $this->getMockBuilder('OC\Log')->disableOriginalConstructor()->getMock();
		$timeFactory = $this->getMockBuilder(ITimeFactory::class)->disableOriginalConstructor()->getMock();
		$timeFactory->method('getTime')->willReturn(1);

		$plugin = new IMipPlugin($mailer, $logger, $timeFactory);
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

		$plugin->schedule($message);
		$this->assertEquals('1.1', $message->getScheduleStatus());
		$this->assertEquals('Fellowship meeting', $mailMessage->getSubject());
		$this->assertEquals(['frodo@hobb.it' => null], $mailMessage->getTo());
		$this->assertEquals(['gandalf@wiz.ard' => null], $mailMessage->getReplyTo());
		$this->assertEquals('text/calendar; charset=UTF-8; method=REQUEST', $mailMessage->getSwiftMessage()->getContentType());
	}

	public function testFailedDelivery() {
		$mailMessage = new \OC\Mail\Message(new \Swift_Message(), false);
		/** @var Mailer | \PHPUnit_Framework_MockObject_MockObject $mailer */
		$mailer = $this->getMockBuilder('OC\Mail\Mailer')->disableOriginalConstructor()->getMock();
		$mailer->method('createMessage')->willReturn($mailMessage);
		$mailer->method('send')->willThrowException(new \Exception());
		/** @var ILogger | \PHPUnit_Framework_MockObject_MockObject $logger */
		$logger = $this->getMockBuilder('OC\Log')->disableOriginalConstructor()->getMock();
		$timeFactory = $this->getMockBuilder(ITimeFactory::class)->disableOriginalConstructor()->getMock();
		$timeFactory->method('getTime')->willReturn(1);

		$plugin = new IMipPlugin($mailer, $logger, $timeFactory);
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

		$plugin->schedule($message);
		$this->assertEquals('5.0', $message->getScheduleStatus());
		$this->assertEquals('Fellowship meeting', $mailMessage->getSubject());
		$this->assertEquals(['frodo@hobb.it' => null], $mailMessage->getTo());
		$this->assertEquals(['gandalf@wiz.ard' => null], $mailMessage->getReplyTo());
		$this->assertEquals('text/calendar; charset=UTF-8; method=REQUEST', $mailMessage->getSwiftMessage()->getContentType());
	}

	/**
	 * @dataProvider dataNoMessageSendForPastEvents
	 */
	public function testNoMessageSendForPastEvents($veventParams, $expectsMail) {
		$mailMessage = new \OC\Mail\Message(new \Swift_Message(), false);
		/** @var Mailer | \PHPUnit_Framework_MockObject_MockObject $mailer */
		$mailer = $this->getMockBuilder('OC\Mail\Mailer')->disableOriginalConstructor()->getMock();
		$mailer->method('createMessage')->willReturn($mailMessage);
		if ($expectsMail) {
			$mailer->expects($this->once())->method('send');
		} else {
			$mailer->expects($this->never())->method('send');
		}
		/** @var ILogger | \PHPUnit_Framework_MockObject_MockObject $logger */
		$logger = $this->getMockBuilder('OC\Log')->disableOriginalConstructor()->getMock();
		$timeFactory = $this->getMockBuilder(ITimeFactory::class)->disableOriginalConstructor()->getMock();
		$timeFactory->method('getTime')->willReturn(1496912528);

		$plugin = new IMipPlugin($mailer, $logger, $timeFactory);
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
