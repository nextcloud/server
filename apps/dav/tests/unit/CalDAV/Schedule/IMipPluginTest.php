<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
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
use OCP\ILogger;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\ITip\Message;
use Test\TestCase;

class IMipPluginTest extends TestCase {

	public function testDelivery() {
		$mailMessage = new \OC\Mail\Message(new \Swift_Message());
		/** @var Mailer | \PHPUnit_Framework_MockObject_MockObject $mailer */
		$mailer = $this->getMockBuilder('OC\Mail\Mailer')->disableOriginalConstructor()->getMock();
		$mailer->method('createMessage')->willReturn($mailMessage);
		$mailer->expects($this->once())->method('send');
		/** @var ILogger | \PHPUnit_Framework_MockObject_MockObject $logger */
		$logger = $this->getMockBuilder('OC\Log')->disableOriginalConstructor()->getMock();

		$plugin = new IMipPlugin($mailer, $logger);
		$message = new Message();
		$message->method = 'REQUEST';
		$message->message = new VCalendar();
		$message->message->add('VEVENT', [
			'UID' => $message->uid,
			'SEQUENCE' => $message->sequence,
			'SUMMARY' => 'Fellowship meeting',
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
		$mailMessage = new \OC\Mail\Message(new \Swift_Message());
		/** @var Mailer | \PHPUnit_Framework_MockObject_MockObject $mailer */
		$mailer = $this->getMockBuilder('OC\Mail\Mailer')->disableOriginalConstructor()->getMock();
		$mailer->method('createMessage')->willReturn($mailMessage);
		$mailer->method('send')->willThrowException(new \Exception());
		/** @var ILogger | \PHPUnit_Framework_MockObject_MockObject $logger */
		$logger = $this->getMockBuilder('OC\Log')->disableOriginalConstructor()->getMock();

		$plugin = new IMipPlugin($mailer, $logger);
		$message = new Message();
		$message->method = 'REQUEST';
		$message->message = new VCalendar();
		$message->message->add('VEVENT', [
			'UID' => $message->uid,
			'SEQUENCE' => $message->sequence,
			'SUMMARY' => 'Fellowship meeting',
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

}
