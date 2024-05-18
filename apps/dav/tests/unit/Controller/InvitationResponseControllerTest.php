<?php

declare(strict_types=1);

/**
 * @copyright 2018, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OCA\DAV\Tests\Unit\DAV\Controller;

use OCA\DAV\CalDAV\InvitationResponse\InvitationResponseServer;
use OCA\DAV\Controller\InvitationResponseController;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IRequest;
use Sabre\VObject\ITip\Message;
use Test\TestCase;

class InvitationResponseControllerTest extends TestCase {
	/** @var InvitationResponseController */
	private $controller;

	/** @var IDBConnection|\PHPUnit\Framework\MockObject\MockObject */
	private $dbConnection;

	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;

	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $timeFactory;

	/** @var InvitationResponseServer|\PHPUnit\Framework\MockObject\MockObject */
	private $responseServer;

	protected function setUp(): void {
		parent::setUp();

		$this->dbConnection = $this->createMock(IDBConnection::class);
		$this->request = $this->createMock(IRequest::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->responseServer = $this->getMockBuilder(InvitationResponseServer::class)
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new InvitationResponseController(
			'appName',
			$this->request,
			$this->dbConnection,
			$this->timeFactory,
			$this->responseServer
		);
	}

	public function attendeeProvider(): array {
		return [
			'local attendee' => [false],
			'external attendee' => [true]
		];
	}

	/**
	 * @dataProvider attendeeProvider
	 */
	public function testAccept(bool $isExternalAttendee): void {
		$this->buildQueryExpects('TOKEN123', [
			'id' => 0,
			'uid' => 'this-is-the-events-uid',
			'recurrenceid' => null,
			'attendee' => 'mailto:attendee@foo.bar',
			'organizer' => 'mailto:organizer@foo.bar',
			'sequence' => null,
			'token' => 'TOKEN123',
			'expiration' => 420000,
		], 1337);

		$expected = <<<EOF
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Nextcloud/Nextcloud CalDAV Server//EN
METHOD:REPLY
BEGIN:VEVENT
ATTENDEE;PARTSTAT=ACCEPTED:mailto:attendee@foo.bar
ORGANIZER:mailto:organizer@foo.bar
UID:this-is-the-events-uid
SEQUENCE:0
REQUEST-STATUS:2.0;Success
DTSTAMP:19700101T002217Z
END:VEVENT
END:VCALENDAR

EOF;
		$expected = preg_replace('~\R~u', "\r\n", $expected);

		$called = false;
		$this->responseServer->expects($this->once())
			->method('handleITipMessage')
			->willReturnCallback(function (Message $iTipMessage) use (&$called, $isExternalAttendee, $expected): void {
				$called = true;
				$this->assertEquals('this-is-the-events-uid', $iTipMessage->uid);
				$this->assertEquals('VEVENT', $iTipMessage->component);
				$this->assertEquals('REPLY', $iTipMessage->method);
				$this->assertEquals(null, $iTipMessage->sequence);
				$this->assertEquals('mailto:attendee@foo.bar', $iTipMessage->sender);
				if ($isExternalAttendee) {
					$this->assertEquals('mailto:organizer@foo.bar', $iTipMessage->recipient);
				} else {
					$this->assertEquals('mailto:attendee@foo.bar', $iTipMessage->recipient);
				}

				$iTipMessage->scheduleStatus = '1.2;Message delivered locally';

				$this->assertEquals($expected, $iTipMessage->message->serialize());
			});
		$this->responseServer->expects($this->once())
			->method('isExternalAttendee')
			->willReturn($isExternalAttendee);

		$response = $this->controller->accept('TOKEN123');
		$this->assertInstanceOf(TemplateResponse::class, $response);
		$this->assertEquals('schedule-response-success', $response->getTemplateName());
		$this->assertEquals([], $response->getParams());
		$this->assertTrue($called);
	}

	/**
	 * @dataProvider attendeeProvider
	 */
	public function testAcceptSequence(bool $isExternalAttendee): void {
		$this->buildQueryExpects('TOKEN123', [
			'id' => 0,
			'uid' => 'this-is-the-events-uid',
			'recurrenceid' => null,
			'attendee' => 'mailto:attendee@foo.bar',
			'organizer' => 'mailto:organizer@foo.bar',
			'sequence' => 1337,
			'token' => 'TOKEN123',
			'expiration' => 420000,
		], 1337);

		$expected = <<<EOF
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Nextcloud/Nextcloud CalDAV Server//EN
METHOD:REPLY
BEGIN:VEVENT
ATTENDEE;PARTSTAT=ACCEPTED:mailto:attendee@foo.bar
ORGANIZER:mailto:organizer@foo.bar
UID:this-is-the-events-uid
SEQUENCE:1337
REQUEST-STATUS:2.0;Success
DTSTAMP:19700101T002217Z
END:VEVENT
END:VCALENDAR

EOF;
		$expected = preg_replace('~\R~u', "\r\n", $expected);

		$called = false;
		$this->responseServer->expects($this->once())
			->method('handleITipMessage')
			->willReturnCallback(function (Message $iTipMessage) use (&$called, $isExternalAttendee, $expected): void {
				$called = true;
				$this->assertEquals('this-is-the-events-uid', $iTipMessage->uid);
				$this->assertEquals('VEVENT', $iTipMessage->component);
				$this->assertEquals('REPLY', $iTipMessage->method);
				$this->assertEquals(1337, $iTipMessage->sequence);
				$this->assertEquals('mailto:attendee@foo.bar', $iTipMessage->sender);
				if ($isExternalAttendee) {
					$this->assertEquals('mailto:organizer@foo.bar', $iTipMessage->recipient);
				} else {
					$this->assertEquals('mailto:attendee@foo.bar', $iTipMessage->recipient);
				}

				$iTipMessage->scheduleStatus = '1.2;Message delivered locally';

				$this->assertEquals($expected, $iTipMessage->message->serialize());
			});
		$this->responseServer->expects($this->once())
			->method('isExternalAttendee')
			->willReturn($isExternalAttendee);

		$response = $this->controller->accept('TOKEN123');
		$this->assertInstanceOf(TemplateResponse::class, $response);
		$this->assertEquals('schedule-response-success', $response->getTemplateName());
		$this->assertEquals([], $response->getParams());
		$this->assertTrue($called);
	}

	/**
	 * @dataProvider attendeeProvider
	 */
	public function testAcceptRecurrenceId(bool $isExternalAttendee): void {
		$this->buildQueryExpects('TOKEN123', [
			'id' => 0,
			'uid' => 'this-is-the-events-uid',
			'recurrenceid' => "RECURRENCE-ID;TZID=Europe/Berlin:20180726T150000\n",
			'attendee' => 'mailto:attendee@foo.bar',
			'organizer' => 'mailto:organizer@foo.bar',
			'sequence' => null,
			'token' => 'TOKEN123',
			'expiration' => 420000,
		], 1337);

		$expected = <<<EOF
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Nextcloud/Nextcloud CalDAV Server//EN
METHOD:REPLY
BEGIN:VEVENT
ATTENDEE;PARTSTAT=ACCEPTED:mailto:attendee@foo.bar
ORGANIZER:mailto:organizer@foo.bar
UID:this-is-the-events-uid
SEQUENCE:0
REQUEST-STATUS:2.0;Success
RECURRENCE-ID;TZID=Europe/Berlin:20180726T150000
DTSTAMP:19700101T002217Z
END:VEVENT
END:VCALENDAR

EOF;
		$expected = preg_replace('~\R~u', "\r\n", $expected);

		$called = false;
		$this->responseServer->expects($this->once())
			->method('handleITipMessage')
			->willReturnCallback(function (Message $iTipMessage) use (&$called, $isExternalAttendee, $expected): void {
				$called = true;
				$this->assertEquals('this-is-the-events-uid', $iTipMessage->uid);
				$this->assertEquals('VEVENT', $iTipMessage->component);
				$this->assertEquals('REPLY', $iTipMessage->method);
				$this->assertEquals(0, $iTipMessage->sequence);
				$this->assertEquals('mailto:attendee@foo.bar', $iTipMessage->sender);
				if ($isExternalAttendee) {
					$this->assertEquals('mailto:organizer@foo.bar', $iTipMessage->recipient);
				} else {
					$this->assertEquals('mailto:attendee@foo.bar', $iTipMessage->recipient);
				}

				$iTipMessage->scheduleStatus = '1.2;Message delivered locally';

				$this->assertEquals($expected, $iTipMessage->message->serialize());
			});
		$this->responseServer->expects($this->once())
			->method('isExternalAttendee')
			->willReturn($isExternalAttendee);

		$response = $this->controller->accept('TOKEN123');
		$this->assertInstanceOf(TemplateResponse::class, $response);
		$this->assertEquals('schedule-response-success', $response->getTemplateName());
		$this->assertEquals([], $response->getParams());
		$this->assertTrue($called);
	}

	public function testAcceptTokenNotFound(): void {
		$this->buildQueryExpects('TOKEN123', null, 1337);

		$response = $this->controller->accept('TOKEN123');
		$this->assertInstanceOf(TemplateResponse::class, $response);
		$this->assertEquals('schedule-response-error', $response->getTemplateName());
		$this->assertEquals([], $response->getParams());
	}

	public function testAcceptExpiredToken(): void {
		$this->buildQueryExpects('TOKEN123', [
			'id' => 0,
			'uid' => 'this-is-the-events-uid',
			'recurrenceid' => null,
			'attendee' => 'mailto:attendee@foo.bar',
			'organizer' => 'mailto:organizer@foo.bar',
			'sequence' => null,
			'token' => 'TOKEN123',
			'expiration' => 42,
		], 1337);

		$response = $this->controller->accept('TOKEN123');
		$this->assertInstanceOf(TemplateResponse::class, $response);
		$this->assertEquals('schedule-response-error', $response->getTemplateName());
		$this->assertEquals([], $response->getParams());
	}

	/**
	 * @dataProvider attendeeProvider
	 */
	public function testDecline(bool $isExternalAttendee): void {
		$this->buildQueryExpects('TOKEN123', [
			'id' => 0,
			'uid' => 'this-is-the-events-uid',
			'recurrenceid' => null,
			'attendee' => 'mailto:attendee@foo.bar',
			'organizer' => 'mailto:organizer@foo.bar',
			'sequence' => null,
			'token' => 'TOKEN123',
			'expiration' => 420000,
		], 1337);

		$expected = <<<EOF
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Nextcloud/Nextcloud CalDAV Server//EN
METHOD:REPLY
BEGIN:VEVENT
ATTENDEE;PARTSTAT=DECLINED:mailto:attendee@foo.bar
ORGANIZER:mailto:organizer@foo.bar
UID:this-is-the-events-uid
SEQUENCE:0
REQUEST-STATUS:2.0;Success
DTSTAMP:19700101T002217Z
END:VEVENT
END:VCALENDAR

EOF;
		$expected = preg_replace('~\R~u', "\r\n", $expected);

		$called = false;
		$this->responseServer->expects($this->once())
			->method('handleITipMessage')
			->willReturnCallback(function (Message $iTipMessage) use (&$called, $isExternalAttendee, $expected): void {
				$called = true;
				$this->assertEquals('this-is-the-events-uid', $iTipMessage->uid);
				$this->assertEquals('VEVENT', $iTipMessage->component);
				$this->assertEquals('REPLY', $iTipMessage->method);
				$this->assertEquals(null, $iTipMessage->sequence);
				$this->assertEquals('mailto:attendee@foo.bar', $iTipMessage->sender);
				if ($isExternalAttendee) {
					$this->assertEquals('mailto:organizer@foo.bar', $iTipMessage->recipient);
				} else {
					$this->assertEquals('mailto:attendee@foo.bar', $iTipMessage->recipient);
				}

				$iTipMessage->scheduleStatus = '1.2;Message delivered locally';

				$this->assertEquals($expected, $iTipMessage->message->serialize());
			});
		$this->responseServer->expects($this->once())
			->method('isExternalAttendee')
			->willReturn($isExternalAttendee);

		$response = $this->controller->decline('TOKEN123');
		$this->assertInstanceOf(TemplateResponse::class, $response);
		$this->assertEquals('schedule-response-success', $response->getTemplateName());
		$this->assertEquals([], $response->getParams());
		$this->assertTrue($called);
	}

	public function testOptions(): void {
		$response = $this->controller->options('TOKEN123');
		$this->assertInstanceOf(TemplateResponse::class, $response);
		$this->assertEquals('schedule-response-options', $response->getTemplateName());
		$this->assertEquals(['token' => 'TOKEN123'], $response->getParams());
	}

	/**
	 * @dataProvider attendeeProvider
	 */
	public function testProcessMoreOptionsResult(bool $isExternalAttendee): void {
		$this->request->expects($this->once())
			->method('getParam')
			->with('partStat')
			->willReturn('TENTATIVE');

		$this->buildQueryExpects('TOKEN123', [
			'id' => 0,
			'uid' => 'this-is-the-events-uid',
			'recurrenceid' => null,
			'attendee' => 'mailto:attendee@foo.bar',
			'organizer' => 'mailto:organizer@foo.bar',
			'sequence' => null,
			'token' => 'TOKEN123',
			'expiration' => 420000,
		], 1337);

		$expected = <<<EOF
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Nextcloud/Nextcloud CalDAV Server//EN
METHOD:REPLY
BEGIN:VEVENT
ATTENDEE;PARTSTAT=TENTATIVE:mailto:attendee@foo.bar
ORGANIZER:mailto:organizer@foo.bar
UID:this-is-the-events-uid
SEQUENCE:0
REQUEST-STATUS:2.0;Success
DTSTAMP:19700101T002217Z
END:VEVENT
END:VCALENDAR

EOF;
		$expected = preg_replace('~\R~u', "\r\n", $expected);

		$called = false;
		$this->responseServer->expects($this->once())
			->method('handleITipMessage')
			->willReturnCallback(function (Message $iTipMessage) use (&$called, $isExternalAttendee, $expected): void {
				$called = true;
				$this->assertEquals('this-is-the-events-uid', $iTipMessage->uid);
				$this->assertEquals('VEVENT', $iTipMessage->component);
				$this->assertEquals('REPLY', $iTipMessage->method);
				$this->assertEquals(null, $iTipMessage->sequence);
				$this->assertEquals('mailto:attendee@foo.bar', $iTipMessage->sender);
				if ($isExternalAttendee) {
					$this->assertEquals('mailto:organizer@foo.bar', $iTipMessage->recipient);
				} else {
					$this->assertEquals('mailto:attendee@foo.bar', $iTipMessage->recipient);
				}

				$iTipMessage->scheduleStatus = '1.2;Message delivered locally';

				$this->assertEquals($expected, $iTipMessage->message->serialize());
			});
		$this->responseServer->expects($this->once())
			->method('isExternalAttendee')
			->willReturn($isExternalAttendee);


		$response = $this->controller->processMoreOptionsResult('TOKEN123');
		$this->assertInstanceOf(TemplateResponse::class, $response);
		$this->assertEquals('schedule-response-success', $response->getTemplateName());
		$this->assertEquals([], $response->getParams());
		$this->assertTrue($called);
	}

	private function buildQueryExpects($token, $return, $time): void {
		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$stmt = $this->createMock(IResult::class);
		$expr = $this->createMock(IExpressionBuilder::class);

		$this->dbConnection->expects($this->once())
			->method('getQueryBuilder')
			->with()
			->willReturn($queryBuilder);
		$queryBuilder->method('expr')
			->willReturn($expr);
		$queryBuilder->method('createNamedParameter')
			->willReturnMap([
				[$token, \PDO::PARAM_STR, null, 'namedParameterToken']
			]);

		$stmt->expects($this->once())
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->willReturn($return);
		$stmt->expects($this->once())
			->method('closeCursor');

		$function = 'functionToken';
		$expr->expects($this->once())
			->method('eq')
			->with('token', 'namedParameterToken')
			->willReturn((string)$function);

		$this->dbConnection->expects($this->once())
			->method('getQueryBuilder')
			->with()
			->willReturn($queryBuilder);

		$queryBuilder->expects($this->once())
			->method('select')
			->with('*')
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->once())
			->method('from')
			->with('calendar_invitations')
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->once())
			->method('where')
			->with($function)
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->once())
			->method('executeQuery')
			->with()
			->willReturn($stmt);

		$this->timeFactory->method('getTime')
			->willReturn($time);
	}
}
