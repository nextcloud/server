<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\DAV\Controller;

use OCA\DAV\CalDAV\InvitationResponse\InvitationResponseServer;
use OCA\DAV\Controller\InvitationResponseController;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\ITip\Message;
use Test\TestCase;

class InvitationResponseControllerTest extends TestCase {
	private IDBConnection&MockObject $dbConnection;
	private IRequest&MockObject $request;
	private ITimeFactory&MockObject $timeFactory;
	private InvitationResponseServer&MockObject $responseServer;
	private InvitationResponseController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->dbConnection = $this->createMock(IDBConnection::class);
		$this->request = $this->createMock(IRequest::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->responseServer = $this->createMock(InvitationResponseServer::class);

		$this->controller = new InvitationResponseController(
			'appName',
			$this->request,
			$this->dbConnection,
			$this->timeFactory,
			$this->responseServer
		);
	}

	public static function attendeeProvider(): array {
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

	private function buildQueryExpects(string $token, ?array $return, int $time): void {
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
