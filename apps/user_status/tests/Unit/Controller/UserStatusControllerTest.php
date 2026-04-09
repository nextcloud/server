<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Tests\Controller;

use OCA\DAV\CalDAV\Status\StatusService as CalendarStatusService;
use OCA\UserStatus\Controller\UserStatusController;
use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Exception\InvalidClearAtException;
use OCA\UserStatus\Exception\InvalidMessageIdException;
use OCA\UserStatus\Exception\InvalidStatusIconException;
use OCA\UserStatus\Exception\InvalidStatusTypeException;
use OCA\UserStatus\Exception\StatusMessageTooLongException;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use Throwable;

class UserStatusControllerTest extends TestCase {
	private LoggerInterface&MockObject $logger;
	private StatusService&MockObject $statusService;
	private CalendarStatusService&MockObject $calendarStatusService;
	private UserStatusController $controller;

	protected function setUp(): void {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$userId = 'john.doe';
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->statusService = $this->createMock(StatusService::class);
		$this->calendarStatusService = $this->createMock(CalendarStatusService::class);

		$this->controller = new UserStatusController(
			'user_status',
			$request,
			$userId,
			$this->logger,
			$this->statusService,
			$this->calendarStatusService,
		);
	}

	public function testGetStatus(): void {
		$userStatus = $this->getUserStatus();

		$this->statusService->expects($this->once())
			->method('findByUserId')
			->with('john.doe')
			->willReturn($userStatus);

		$response = $this->controller->getStatus();
		$this->assertEquals([
			'userId' => 'john.doe',
			'status' => 'invisible',
			'icon' => '🏝',
			'message' => 'On vacation',
			'clearAt' => 60000,
			'statusIsUserDefined' => true,
			'messageIsPredefined' => false,
			'messageId' => null,
		], $response->getData());
	}

	public function testGetStatusDoesNotExist(): void {
		$this->calendarStatusService->expects(self::once())
			->method('processCalendarStatus')
			->with('john.doe');
		$this->statusService->expects($this->once())
			->method('findByUserId')
			->with('john.doe')
			->willThrowException(new DoesNotExistException(''));

		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('No status for the current user');

		$this->controller->getStatus();
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'setStatusDataProvider')]
	public function testSetStatus(
		string $statusType,
		?string $statusIcon,
		?string $message,
		?int $clearAt,
		bool $expectSuccess,
		bool $expectException,
		?Throwable $exception,
		bool $expectLogger,
		?string $expectedLogMessage,
	): void {
		$userStatus = $this->getUserStatus();

		if ($expectException) {
			$this->statusService->expects($this->once())
				->method('setStatus')
				->with('john.doe', $statusType, null, true)
				->willThrowException($exception);
		} else {
			$this->statusService->expects($this->once())
				->method('setStatus')
				->with('john.doe', $statusType, null, true)
				->willReturn($userStatus);
		}

		if ($expectLogger) {
			$this->logger->expects($this->once())
				->method('debug')
				->with($expectedLogMessage);
		}
		if ($expectException) {
			$this->expectException(OCSBadRequestException::class);
			$this->expectExceptionMessage('Original exception message');
		}

		$response = $this->controller->setStatus($statusType);

		if ($expectSuccess) {
			$this->assertEquals([
				'userId' => 'john.doe',
				'status' => 'invisible',
				'icon' => '🏝',
				'message' => 'On vacation',
				'clearAt' => 60000,
				'statusIsUserDefined' => true,
				'messageIsPredefined' => false,
				'messageId' => null,
			], $response->getData());
		}
	}

	public static function setStatusDataProvider(): array {
		return [
			['busy', '👨🏽‍💻', 'Busy developing the status feature', 500, true, false, null, false, null],
			['busy', '👨🏽‍💻', 'Busy developing the status feature', 500, false, true, new InvalidStatusTypeException('Original exception message'), true,
				'New user-status for "john.doe" was rejected due to an invalid status type "busy"'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'setPredefinedMessageDataProvider')]
	public function testSetPredefinedMessage(
		string $messageId,
		?int $clearAt,
		bool $expectSuccess,
		bool $expectException,
		?Throwable $exception,
		bool $expectLogger,
		?string $expectedLogMessage,
	): void {
		$userStatus = $this->getUserStatus();

		if ($expectException) {
			$this->statusService->expects($this->once())
				->method('setPredefinedMessage')
				->with('john.doe', $messageId, $clearAt)
				->willThrowException($exception);
		} else {
			$this->statusService->expects($this->once())
				->method('setPredefinedMessage')
				->with('john.doe', $messageId, $clearAt)
				->willReturn($userStatus);
		}

		if ($expectLogger) {
			$this->logger->expects($this->once())
				->method('debug')
				->with($expectedLogMessage);
		}
		if ($expectException) {
			$this->expectException(OCSBadRequestException::class);
			$this->expectExceptionMessage('Original exception message');
		}

		$response = $this->controller->setPredefinedMessage($messageId, $clearAt);

		if ($expectSuccess) {
			$this->assertEquals([
				'userId' => 'john.doe',
				'status' => 'invisible',
				'icon' => '🏝',
				'message' => 'On vacation',
				'clearAt' => 60000,
				'statusIsUserDefined' => true,
				'messageIsPredefined' => false,
				'messageId' => null,
			], $response->getData());
		}
	}

	public static function setPredefinedMessageDataProvider(): array {
		return [
			['messageId-42', 500, true, false, null, false, null],
			['messageId-42', 500, false, true, new InvalidClearAtException('Original exception message'), true,
				'New user-status for "john.doe" was rejected due to an invalid clearAt value "500"'],
			['messageId-42', 500, false, true, new InvalidMessageIdException('Original exception message'), true,
				'New user-status for "john.doe" was rejected due to an invalid message-id "messageId-42"'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'setCustomMessageDataProvider')]
	public function testSetCustomMessage(
		?string $statusIcon,
		string $message,
		?int $clearAt,
		bool $expectSuccess,
		bool $expectException,
		?Throwable $exception,
		bool $expectLogger,
		?string $expectedLogMessage,
		bool $expectSuccessAsReset = false,
	): void {
		$userStatus = $this->getUserStatus();

		if ($expectException) {
			$this->statusService->expects($this->once())
				->method('setCustomMessage')
				->with('john.doe', $statusIcon, $message, $clearAt)
				->willThrowException($exception);
		} else {
			if ($expectSuccessAsReset) {
				$this->statusService->expects($this->never())
					->method('setCustomMessage');
				$this->statusService->expects($this->once())
					->method('clearMessage')
					->with('john.doe');
				$this->statusService->expects($this->once())
					->method('findByUserId')
					->with('john.doe')
					->willReturn($userStatus);
			} else {
				$this->statusService->expects($this->once())
					->method('setCustomMessage')
					->with('john.doe', $statusIcon, $message, $clearAt)
					->willReturn($userStatus);

				$this->statusService->expects($this->never())
					->method('clearMessage');
			}
		}

		if ($expectLogger) {
			$this->logger->expects($this->once())
				->method('debug')
				->with($expectedLogMessage);
		}
		if ($expectException) {
			$this->expectException(OCSBadRequestException::class);
			$this->expectExceptionMessage('Original exception message');
		}

		$response = $this->controller->setCustomMessage($statusIcon, $message, $clearAt);

		if ($expectSuccess) {
			$this->assertEquals([
				'userId' => 'john.doe',
				'status' => 'invisible',
				'icon' => '🏝',
				'message' => 'On vacation',
				'clearAt' => 60000,
				'statusIsUserDefined' => true,
				'messageIsPredefined' => false,
				'messageId' => null,
			], $response->getData());
		}
	}

	public static function setCustomMessageDataProvider(): array {
		return [
			['👨🏽‍💻', 'Busy developing the status feature', 500, true, false, null, false, null],
			['👨🏽‍💻', '', 500, true, false, null, false, null, false],
			['👨🏽‍💻', '', 0, true, false, null, false, null, false],
			['👨🏽‍💻', 'Busy developing the status feature', 500, false, true, new InvalidClearAtException('Original exception message'), true,
				'New user-status for "john.doe" was rejected due to an invalid clearAt value "500"'],
			['👨🏽‍💻', 'Busy developing the status feature', 500, false, true, new InvalidStatusIconException('Original exception message'), true,
				'New user-status for "john.doe" was rejected due to an invalid icon value "👨🏽‍💻"'],
			['👨🏽‍💻', 'Busy developing the status feature', 500, false, true, new StatusMessageTooLongException('Original exception message'), true,
				'New user-status for "john.doe" was rejected due to a too long status message.'],
		];
	}

	public function testClearMessage(): void {
		$this->statusService->expects($this->once())
			->method('clearMessage')
			->with('john.doe');

		$response = $this->controller->clearMessage();
		$this->assertEquals([], $response->getData());
	}

	private function getUserStatus(): UserStatus {
		$userStatus = new UserStatus();
		$userStatus->setId(1337);
		$userStatus->setUserId('john.doe');
		$userStatus->setStatus('invisible');
		$userStatus->setStatusTimestamp(5000);
		$userStatus->setIsUserDefined(true);
		$userStatus->setCustomIcon('🏝');
		$userStatus->setCustomMessage('On vacation');
		$userStatus->setClearAt(60000);

		return $userStatus;
	}
}
