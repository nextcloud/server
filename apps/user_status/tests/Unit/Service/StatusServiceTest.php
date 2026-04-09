<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Tests\Service;

use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Db\UserStatusMapper;
use OCA\UserStatus\Exception\InvalidClearAtException;
use OCA\UserStatus\Exception\InvalidMessageIdException;
use OCA\UserStatus\Exception\InvalidStatusIconException;
use OCA\UserStatus\Exception\InvalidStatusTypeException;
use OCA\UserStatus\Exception\StatusMessageTooLongException;
use OCA\UserStatus\Service\PredefinedStatusService;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\IEmojiHelper;
use OCP\IUserManager;
use OCP\UserStatus\IUserStatus;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class StatusServiceTest extends TestCase {
	private UserStatusMapper&MockObject $mapper;
	private ITimeFactory&MockObject $timeFactory;
	private PredefinedStatusService&MockObject $predefinedStatusService;
	private IEmojiHelper&MockObject $emojiHelper;
	private IConfig&MockObject $config;
	private IUserManager&MockObject $userManager;
	private LoggerInterface&MockObject $logger;

	private StatusService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(UserStatusMapper::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->predefinedStatusService = $this->createMock(PredefinedStatusService::class);
		$this->emojiHelper = $this->createMock(IEmojiHelper::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->config->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no']
			]);

		$this->service = new StatusService($this->mapper,
			$this->timeFactory,
			$this->predefinedStatusService,
			$this->emojiHelper,
			$this->config,
			$this->userManager,
			$this->logger,
		);
	}

	public function testFindAll(): void {
		$status1 = $this->createMock(UserStatus::class);
		$status2 = $this->createMock(UserStatus::class);

		$this->mapper->expects($this->once())
			->method('findAll')
			->with(20, 50)
			->willReturn([$status1, $status2]);

		$this->assertEquals([
			$status1,
			$status2,
		], $this->service->findAll(20, 50));
	}

	public function testFindAllRecentStatusChanges(): void {
		$status1 = $this->createMock(UserStatus::class);
		$status2 = $this->createMock(UserStatus::class);

		$this->mapper->expects($this->once())
			->method('findAllRecent')
			->with(20, 50)
			->willReturn([$status1, $status2]);

		$this->assertEquals([
			$status1,
			$status2,
		], $this->service->findAllRecentStatusChanges(20, 50));
	}

	public function testFindAllRecentStatusChangesNoEnumeration(): void {
		$status1 = $this->createMock(UserStatus::class);
		$status2 = $this->createMock(UserStatus::class);

		$this->mapper->method('findAllRecent')
			->with(20, 50)
			->willReturn([$status1, $status2]);

		// Rebuild $this->service with user enumeration turned off
		$this->config = $this->createMock(IConfig::class);

		$this->config->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no']
			]);

		$this->service = new StatusService($this->mapper,
			$this->timeFactory,
			$this->predefinedStatusService,
			$this->emojiHelper,
			$this->config,
			$this->userManager,
			$this->logger,
		);

		$this->assertEquals([], $this->service->findAllRecentStatusChanges(20, 50));

		// Rebuild $this->service with user enumeration limited to common groups
		$this->config = $this->createMock(IConfig::class);

		$this->config->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'yes']
			]);

		$this->service = new StatusService($this->mapper,
			$this->timeFactory,
			$this->predefinedStatusService,
			$this->emojiHelper,
			$this->config,
			$this->userManager,
			$this->logger,
		);

		$this->assertEquals([], $this->service->findAllRecentStatusChanges(20, 50));
	}

	public function testFindByUserIdDoesNotExist(): void {
		$this->mapper->expects($this->once())
			->method('findByUserId')
			->with('john.doe')
			->willThrowException(new DoesNotExistException(''));

		$this->expectException(DoesNotExistException::class);
		$this->service->findByUserId('john.doe');
	}

	public function testFindAllAddDefaultMessage(): void {
		$status = new UserStatus();
		$status->setMessageId('commuting');

		$this->predefinedStatusService->expects($this->once())
			->method('getDefaultStatusById')
			->with('commuting')
			->willReturn([
				'id' => 'commuting',
				'icon' => '🚌',
				'message' => 'Commuting',
				'clearAt' => [
					'type' => 'period',
					'time' => 1800,
				],
			]);
		$this->mapper->expects($this->once())
			->method('findByUserId')
			->with('john.doe')
			->willReturn($status);

		$this->assertEquals($status, $this->service->findByUserId('john.doe'));
		$this->assertEquals('🚌', $status->getCustomIcon());
		$this->assertEquals('Commuting', $status->getCustomMessage());
	}

	public function testFindAllClearStatus(): void {
		$status = new UserStatus();
		$status->setStatus('online');
		$status->setStatusTimestamp(1000);
		$status->setIsUserDefined(true);

		$this->timeFactory->method('getTime')
			->willReturn(2600);
		$this->mapper->expects($this->once())
			->method('findByUserId')
			->with('john.doe')
			->willReturn($status);

		$this->assertEquals($status, $this->service->findByUserId('john.doe'));
		$this->assertEquals('offline', $status->getStatus());
		$this->assertEquals(2600, $status->getStatusTimestamp());
		$this->assertFalse($status->getIsUserDefined());
	}

	public function testFindAllClearMessage(): void {
		$status = new UserStatus();
		$status->setClearAt(50);
		$status->setMessageId('commuting');
		$status->setStatusTimestamp(60);

		$this->timeFactory->method('getTime')
			->willReturn(60);
		$this->predefinedStatusService->expects($this->never())
			->method('getDefaultStatusById');
		$this->mapper->expects($this->once())
			->method('findByUserId')
			->with('john.doe')
			->willReturn($status);
		$this->assertEquals($status, $this->service->findByUserId('john.doe'));
		$this->assertNull($status->getClearAt());
		$this->assertNull($status->getMessageId());
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'setStatusDataProvider')]
	public function testSetStatus(
		string $userId,
		string $status,
		?int $statusTimestamp,
		bool $isUserDefined,
		bool $expectExisting,
		bool $expectSuccess,
		bool $expectTimeFactory,
		bool $expectException,
		?string $expectedExceptionClass,
		?string $expectedExceptionMessage,
	): void {
		$userStatus = new UserStatus();

		if ($expectExisting) {
			$userStatus->setId(42);
			$userStatus->setUserId($userId);

			$this->mapper->expects($this->once())
				->method('findByUserId')
				->with($userId)
				->willReturn($userStatus);
		} else {
			$this->mapper->expects($this->once())
				->method('findByUserId')
				->with($userId)
				->willThrowException(new DoesNotExistException(''));
		}

		if ($expectTimeFactory) {
			$this->timeFactory
				->method('getTime')
				->willReturn(40);
		}

		if ($expectException) {
			$this->expectException($expectedExceptionClass);
			$this->expectExceptionMessage($expectedExceptionMessage);

			$this->service->setStatus($userId, $status, $statusTimestamp, $isUserDefined);
		}

		if ($expectSuccess) {
			if ($expectExisting) {
				$this->mapper->expects($this->once())
					->method('update')
					->willReturnArgument(0);
			} else {
				$this->mapper->expects($this->once())
					->method('insert')
					->willReturnArgument(0);
			}

			$actual = $this->service->setStatus($userId, $status, $statusTimestamp, $isUserDefined);

			$this->assertEquals('john.doe', $actual->getUserId());
			$this->assertEquals($status, $actual->getStatus());
			$this->assertEquals($statusTimestamp ?? 40, $actual->getStatusTimestamp());
			$this->assertEquals($isUserDefined, $actual->getIsUserDefined());
		}
	}

	public static function setStatusDataProvider(): array {
		return [
			['john.doe', 'online', 50,   true,  true,  true, false, false, null, null],
			['john.doe', 'online', 50,   true,  false, true, false, false, null, null],
			['john.doe', 'online', 50,   false, true,  true, false, false, null, null],
			['john.doe', 'online', 50,   false, false, true, false, false, null, null],
			['john.doe', 'online', null, true,  true,  true, true,  false, null, null],
			['john.doe', 'online', null, true,  false, true, true,  false, null, null],
			['john.doe', 'online', null, false, true,  true, true,  false, null, null],
			['john.doe', 'online', null, false, false, true, true,  false, null, null],

			['john.doe', 'away', 50,   true,  true,  true, false, false, null, null],
			['john.doe', 'away', 50,   true,  false, true, false, false, null, null],
			['john.doe', 'away', 50,   false, true,  true, false, false, null, null],
			['john.doe', 'away', 50,   false, false, true, false, false, null, null],
			['john.doe', 'away', null, true,  true,  true, true,  false, null, null],
			['john.doe', 'away', null, true,  false, true, true,  false, null, null],
			['john.doe', 'away', null, false, true,  true, true,  false, null, null],
			['john.doe', 'away', null, false, false, true, true,  false, null, null],

			['john.doe', 'dnd', 50,   true,  true,  true, false, false, null, null],
			['john.doe', 'dnd', 50,   true,  false, true, false, false, null, null],
			['john.doe', 'dnd', 50,   false, true,  true, false, false, null, null],
			['john.doe', 'dnd', 50,   false, false, true, false, false, null, null],
			['john.doe', 'dnd', null, true,  true,  true, true,  false, null, null],
			['john.doe', 'dnd', null, true,  false, true, true,  false, null, null],
			['john.doe', 'dnd', null, false, true,  true, true,  false, null, null],
			['john.doe', 'dnd', null, false, false, true, true,  false, null, null],

			['john.doe', 'invisible', 50,   true,  true,  true, false, false, null, null],
			['john.doe', 'invisible', 50,   true,  false, true, false, false, null, null],
			['john.doe', 'invisible', 50,   false, true,  true, false, false, null, null],
			['john.doe', 'invisible', 50,   false, false, true, false, false, null, null],
			['john.doe', 'invisible', null, true,  true,  true, true,  false, null, null],
			['john.doe', 'invisible', null, true,  false, true, true,  false, null, null],
			['john.doe', 'invisible', null, false, true,  true, true,  false, null, null],
			['john.doe', 'invisible', null, false, false, true, true,  false, null, null],

			['john.doe', 'offline', 50,   true,  true,  true, false, false, null, null],
			['john.doe', 'offline', 50,   true,  false, true, false, false, null, null],
			['john.doe', 'offline', 50,   false, true,  true, false, false, null, null],
			['john.doe', 'offline', 50,   false, false, true, false, false, null, null],
			['john.doe', 'offline', null, true,  true,  true, true,  false, null, null],
			['john.doe', 'offline', null, true,  false, true, true,  false, null, null],
			['john.doe', 'offline', null, false, true,  true, true,  false, null, null],
			['john.doe', 'offline', null, false, false, true, true,  false, null, null],

			['john.doe', 'illegal-status', 50,   true,  true,  false, false, true, InvalidStatusTypeException::class, 'Status-type "illegal-status" is not supported'],
			['john.doe', 'illegal-status', 50,   true,  false, false, false, true, InvalidStatusTypeException::class, 'Status-type "illegal-status" is not supported'],
			['john.doe', 'illegal-status', 50,   false, true,  false, false, true, InvalidStatusTypeException::class, 'Status-type "illegal-status" is not supported'],
			['john.doe', 'illegal-status', 50,   false, false, false, false, true, InvalidStatusTypeException::class, 'Status-type "illegal-status" is not supported'],
			['john.doe', 'illegal-status', null, true,  true,  false, true,  true, InvalidStatusTypeException::class, 'Status-type "illegal-status" is not supported'],
			['john.doe', 'illegal-status', null, true,  false, false, true,  true, InvalidStatusTypeException::class, 'Status-type "illegal-status" is not supported'],
			['john.doe', 'illegal-status', null, false, true,  false, true,  true, InvalidStatusTypeException::class, 'Status-type "illegal-status" is not supported'],
			['john.doe', 'illegal-status', null, false, false, false, true,  true, InvalidStatusTypeException::class, 'Status-type "illegal-status" is not supported'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'setPredefinedMessageDataProvider')]
	public function testSetPredefinedMessage(
		string $userId,
		string $messageId,
		bool $isValidMessageId,
		?int $clearAt,
		bool $expectExisting,
		bool $expectSuccess,
		bool $expectException,
		?string $expectedExceptionClass,
		?string $expectedExceptionMessage,
	): void {
		$userStatus = new UserStatus();

		if ($expectExisting) {
			$userStatus->setId(42);
			$userStatus->setUserId($userId);
			$userStatus->setStatus('offline');
			$userStatus->setStatusTimestamp(0);
			$userStatus->setIsUserDefined(false);
			$userStatus->setCustomIcon('😀');
			$userStatus->setCustomMessage('Foo');

			$this->mapper->expects($this->once())
				->method('findByUserId')
				->with($userId)
				->willReturn($userStatus);
		} else {
			$this->mapper->expects($this->once())
				->method('findByUserId')
				->with($userId)
				->willThrowException(new DoesNotExistException(''));
		}

		$this->predefinedStatusService->expects($this->once())
			->method('isValidId')
			->with($messageId)
			->willReturn($isValidMessageId);

		$this->timeFactory
			->method('getTime')
			->willReturn(40);

		if ($expectException) {
			$this->expectException($expectedExceptionClass);
			$this->expectExceptionMessage($expectedExceptionMessage);

			$this->service->setPredefinedMessage($userId, $messageId, $clearAt);
		}

		if ($expectSuccess) {
			if ($expectExisting) {
				$this->mapper->expects($this->once())
					->method('update')
					->willReturnArgument(0);
			} else {
				$this->mapper->expects($this->once())
					->method('insert')
					->willReturnArgument(0);
			}

			$actual = $this->service->setPredefinedMessage($userId, $messageId, $clearAt);

			$this->assertEquals('john.doe', $actual->getUserId());
			$this->assertEquals('offline', $actual->getStatus());
			$this->assertEquals(0, $actual->getStatusTimestamp());
			$this->assertEquals(false, $actual->getIsUserDefined());
			$this->assertEquals($messageId, $actual->getMessageId());
			$this->assertNull($actual->getCustomIcon());
			$this->assertNull($actual->getCustomMessage());
			$this->assertEquals($clearAt, $actual->getClearAt());
		}
	}

	public static function setPredefinedMessageDataProvider(): array {
		return [
			['john.doe', 'sick-leave', true, null, true,  true,  false, null, null],
			['john.doe', 'sick-leave', true, null, false, true,  false, null, null],
			['john.doe', 'sick-leave', true, 20,   true,  false, true,  InvalidClearAtException::class, 'ClearAt is in the past'],
			['john.doe', 'sick-leave', true, 20,   false, false, true,  InvalidClearAtException::class, 'ClearAt is in the past'],
			['john.doe', 'sick-leave', true, 60,   true,  true,  false, null, null],
			['john.doe', 'sick-leave', true, 60,   false, true,  false, null, null],
			['john.doe', 'illegal-message-id', false, null, true, false, true, InvalidMessageIdException::class, 'Message-Id "illegal-message-id" is not supported'],
			['john.doe', 'illegal-message-id', false, null, false, false, true, InvalidMessageIdException::class, 'Message-Id "illegal-message-id" is not supported'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'setCustomMessageDataProvider')]
	public function testSetCustomMessage(
		string $userId,
		?string $statusIcon,
		bool $supportsEmoji,
		string $message,
		?int $clearAt,
		bool $expectExisting,
		bool $expectSuccess,
		bool $expectException,
		?string $expectedExceptionClass,
		?string $expectedExceptionMessage,
	): void {
		$userStatus = new UserStatus();

		if ($expectExisting) {
			$userStatus->setId(42);
			$userStatus->setUserId($userId);
			$userStatus->setStatus('offline');
			$userStatus->setStatusTimestamp(0);
			$userStatus->setIsUserDefined(false);
			$userStatus->setMessageId('messageId-42');

			$this->mapper->expects($this->once())
				->method('findByUserId')
				->with($userId)
				->willReturn($userStatus);
		} else {
			$this->mapper->expects($this->once())
				->method('findByUserId')
				->with($userId)
				->willThrowException(new DoesNotExistException(''));
		}

		$this->emojiHelper->method('isValidSingleEmoji')
			->with($statusIcon)
			->willReturn($supportsEmoji);

		$this->timeFactory
			->method('getTime')
			->willReturn(40);

		if ($expectException) {
			$this->expectException($expectedExceptionClass);
			$this->expectExceptionMessage($expectedExceptionMessage);

			$this->service->setCustomMessage($userId, $statusIcon, $message, $clearAt);
		}

		if ($expectSuccess) {
			if ($expectExisting) {
				$this->mapper->expects($this->once())
					->method('update')
					->willReturnArgument(0);
			} else {
				$this->mapper->expects($this->once())
					->method('insert')
					->willReturnArgument(0);
			}

			$actual = $this->service->setCustomMessage($userId, $statusIcon, $message, $clearAt);

			$this->assertEquals('john.doe', $actual->getUserId());
			$this->assertEquals('offline', $actual->getStatus());
			$this->assertEquals(0, $actual->getStatusTimestamp());
			$this->assertEquals(false, $actual->getIsUserDefined());
			$this->assertNull($actual->getMessageId());
			$this->assertEquals($statusIcon, $actual->getCustomIcon());
			$this->assertEquals($message, $actual->getCustomMessage());
			$this->assertEquals($clearAt, $actual->getClearAt());
		}
	}

	public static function setCustomMessageDataProvider(): array {
		return [
			['john.doe', '😁', true, 'Custom message', null, true,  true, false, null, null],
			['john.doe', '😁', true, 'Custom message', null, false, true, false, null, null],
			['john.doe', null, false, 'Custom message', null, true,  true, false, null, null],
			['john.doe', null, false, 'Custom message', null, false, true, false, null, null],
			['john.doe', '😁', false, 'Custom message', null, true,  false, true, InvalidStatusIconException::class, 'Status-Icon is longer than one character'],
			['john.doe', '😁', false, 'Custom message', null, false, false, true, InvalidStatusIconException::class, 'Status-Icon is longer than one character'],
			['john.doe', null, false, 'Custom message that is way too long and violates the maximum length and hence should be rejected', null, true,  false, true, StatusMessageTooLongException::class, 'Message is longer than supported length of 80 characters'],
			['john.doe', null, false, 'Custom message that is way too long and violates the maximum length and hence should be rejected', null, false, false, true, StatusMessageTooLongException::class, 'Message is longer than supported length of 80 characters'],
			['john.doe', '😁', true, 'Custom message', 80, true,  true, false, null, null],
			['john.doe', '😁', true, 'Custom message', 80, false, true, false, null, null],
			['john.doe', '😁', true, 'Custom message', 20, true,  false, true, InvalidClearAtException::class, 'ClearAt is in the past'],
			['john.doe', '😁', true, 'Custom message', 20, false, false, true, InvalidClearAtException::class, 'ClearAt is in the past'],
		];
	}

	public function testClearStatus(): void {
		$status = new UserStatus();
		$status->setId(1);
		$status->setUserId('john.doe');
		$status->setStatus('dnd');
		$status->setStatusTimestamp(1337);
		$status->setIsUserDefined(true);
		$status->setMessageId('messageId-42');
		$status->setCustomIcon('🙊');
		$status->setCustomMessage('My custom status message');
		$status->setClearAt(42);

		$this->mapper->expects($this->once())
			->method('findByUserId')
			->with('john.doe')
			->willReturn($status);

		$this->mapper->expects($this->once())
			->method('update')
			->with($status);

		$actual = $this->service->clearStatus('john.doe');
		$this->assertTrue($actual);
		$this->assertEquals('offline', $status->getStatus());
		$this->assertEquals(0, $status->getStatusTimestamp());
		$this->assertFalse($status->getIsUserDefined());
	}

	public function testClearStatusDoesNotExist(): void {
		$this->mapper->expects($this->once())
			->method('findByUserId')
			->with('john.doe')
			->willThrowException(new DoesNotExistException(''));

		$this->mapper->expects($this->never())
			->method('update');

		$actual = $this->service->clearStatus('john.doe');
		$this->assertFalse($actual);
	}

	public function testClearMessage(): void {
		$status = new UserStatus();
		$status->setId(1);
		$status->setUserId('john.doe');
		$status->setStatus('dnd');
		$status->setStatusTimestamp(1337);
		$status->setIsUserDefined(true);
		$status->setMessageId('messageId-42');
		$status->setCustomIcon('🙊');
		$status->setCustomMessage('My custom status message');
		$status->setClearAt(42);

		$this->mapper->expects($this->once())
			->method('findByUserId')
			->with('john.doe')
			->willReturn($status);

		$this->mapper->expects($this->once())
			->method('update')
			->with($status);

		$actual = $this->service->clearMessage('john.doe');
		$this->assertTrue($actual);
		$this->assertNull($status->getMessageId());
		$this->assertNull($status->getCustomMessage());
		$this->assertNull($status->getCustomIcon());
		$this->assertNull($status->getClearAt());
	}

	public function testClearMessageDoesNotExist(): void {
		$this->mapper->expects($this->once())
			->method('findByUserId')
			->with('john.doe')
			->willThrowException(new DoesNotExistException(''));

		$this->mapper->expects($this->never())
			->method('update');

		$actual = $this->service->clearMessage('john.doe');
		$this->assertFalse($actual);
	}

	public function testRemoveUserStatus(): void {
		$status = $this->createMock(UserStatus::class);
		$this->mapper->expects($this->once())
			->method('findByUserId')
			->with('john.doe')
			->willReturn($status);

		$this->mapper->expects($this->once())
			->method('delete')
			->with($status);

		$actual = $this->service->removeUserStatus('john.doe');
		$this->assertTrue($actual);
	}

	public function testRemoveUserStatusDoesNotExist(): void {
		$this->mapper->expects($this->once())
			->method('findByUserId')
			->with('john.doe')
			->willThrowException(new DoesNotExistException(''));

		$this->mapper->expects($this->never())
			->method('delete');

		$actual = $this->service->removeUserStatus('john.doe');
		$this->assertFalse($actual);
	}

	public function testCleanStatusAutomaticOnline(): void {
		$status = new UserStatus();
		$status->setStatus(IUserStatus::ONLINE);
		$status->setStatusTimestamp(1337);
		$status->setIsUserDefined(false);

		$this->mapper->expects(self::once())
			->method('update')
			->with($status);

		parent::invokePrivate($this->service, 'cleanStatus', [$status]);
	}

	public function testCleanStatusCustomOffline(): void {
		$status = new UserStatus();
		$status->setStatus(IUserStatus::OFFLINE);
		$status->setStatusTimestamp(1337);
		$status->setIsUserDefined(true);

		$this->mapper->expects(self::once())
			->method('update')
			->with($status);

		parent::invokePrivate($this->service, 'cleanStatus', [$status]);
	}

	public function testCleanStatusCleanedAlready(): void {
		$status = new UserStatus();
		$status->setStatus(IUserStatus::OFFLINE);
		$status->setStatusTimestamp(1337);
		$status->setIsUserDefined(false);

		// Don't update the status again and again when no value changed
		$this->mapper->expects(self::never())
			->method('update')
			->with($status);

		parent::invokePrivate($this->service, 'cleanStatus', [$status]);
	}

	public function testBackupWorkingHasBackupAlready(): void {
		$e = $this->createMock(Exception::class);
		$e->method('getReason')->willReturn(Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION);
		$this->mapper->expects($this->once())
			->method('createBackupStatus')
			->with('john')
			->willThrowException($e);

		$this->assertFalse($this->service->backupCurrentStatus('john'));
	}

	public function testBackupThrowsOther(): void {
		$e = new Exception('', Exception::REASON_CONNECTION_LOST);
		$this->mapper->expects($this->once())
			->method('createBackupStatus')
			->with('john')
			->willThrowException($e);

		$this->expectException(Exception::class);
		$this->service->backupCurrentStatus('john');
	}

	public function testBackup(): void {
		$this->mapper->expects($this->once())
			->method('createBackupStatus')
			->with('john')
			->willReturn(true);

		$this->assertTrue($this->service->backupCurrentStatus('john'));
	}

	public function testRevertMultipleUserStatus(): void {
		$john = new UserStatus();
		$john->setId(1);
		$john->setStatus(IUserStatus::AWAY);
		$john->setStatusTimestamp(1337);
		$john->setIsUserDefined(false);
		$john->setMessageId('call');
		$john->setUserId('john');
		$john->setIsBackup(false);

		$johnBackup = new UserStatus();
		$johnBackup->setId(2);
		$johnBackup->setStatus(IUserStatus::ONLINE);
		$johnBackup->setStatusTimestamp(1337);
		$johnBackup->setIsUserDefined(true);
		$johnBackup->setMessageId('hello');
		$johnBackup->setUserId('_john');
		$johnBackup->setIsBackup(true);

		$noBackup = new UserStatus();
		$noBackup->setId(3);
		$noBackup->setStatus(IUserStatus::AWAY);
		$noBackup->setStatusTimestamp(1337);
		$noBackup->setIsUserDefined(false);
		$noBackup->setMessageId('call');
		$noBackup->setUserId('nobackup');
		$noBackup->setIsBackup(false);

		$backupOnly = new UserStatus();
		$backupOnly->setId(4);
		$backupOnly->setStatus(IUserStatus::ONLINE);
		$backupOnly->setStatusTimestamp(1337);
		$backupOnly->setIsUserDefined(true);
		$backupOnly->setMessageId('hello');
		$backupOnly->setUserId('_backuponly');
		$backupOnly->setIsBackup(true);

		$noBackupDND = new UserStatus();
		$noBackupDND->setId(5);
		$noBackupDND->setStatus(IUserStatus::DND);
		$noBackupDND->setStatusTimestamp(1337);
		$noBackupDND->setIsUserDefined(false);
		$noBackupDND->setMessageId('call');
		$noBackupDND->setUserId('nobackupanddnd');
		$noBackupDND->setIsBackup(false);

		$this->mapper->expects($this->once())
			->method('findByUserIds')
			->with(['john', 'nobackup', 'backuponly', 'nobackupanddnd', '_john', '_nobackup', '_backuponly', '_nobackupanddnd'])
			->willReturn([
				$john,
				$johnBackup,
				$noBackup,
				$backupOnly,
				$noBackupDND,
			]);

		$this->mapper->expects($this->once())
			->method('deleteByIds')
			->with([1, 3, 5]);

		$this->mapper->expects($this->once())
			->method('restoreBackupStatuses')
			->with([2]);

		$this->service->revertMultipleUserStatus(['john', 'nobackup', 'backuponly', 'nobackupanddnd'], 'call');
	}

	public static function dataSetUserStatus(): array {
		return [
			[IUserStatus::MESSAGE_CALENDAR_BUSY, '', false],

			// Call > Meeting
			[IUserStatus::MESSAGE_CALENDAR_BUSY, IUserStatus::MESSAGE_CALL, false],
			[IUserStatus::MESSAGE_CALL, IUserStatus::MESSAGE_CALENDAR_BUSY, true],

			// Availability > Call&Meeting
			[IUserStatus::MESSAGE_CALENDAR_BUSY, IUserStatus::MESSAGE_AVAILABILITY, false],
			[IUserStatus::MESSAGE_CALL, IUserStatus::MESSAGE_AVAILABILITY, false],
			[IUserStatus::MESSAGE_AVAILABILITY, IUserStatus::MESSAGE_CALENDAR_BUSY, true],
			[IUserStatus::MESSAGE_AVAILABILITY, IUserStatus::MESSAGE_CALL, true],

			// Out-of-office > Availability&Call&Meeting
			[IUserStatus::MESSAGE_AVAILABILITY, IUserStatus::MESSAGE_OUT_OF_OFFICE, false],
			[IUserStatus::MESSAGE_CALENDAR_BUSY, IUserStatus::MESSAGE_OUT_OF_OFFICE, false],
			[IUserStatus::MESSAGE_CALL, IUserStatus::MESSAGE_OUT_OF_OFFICE, false],
			[IUserStatus::MESSAGE_OUT_OF_OFFICE, IUserStatus::MESSAGE_AVAILABILITY, true],
			[IUserStatus::MESSAGE_OUT_OF_OFFICE, IUserStatus::MESSAGE_CALENDAR_BUSY, true],
			[IUserStatus::MESSAGE_OUT_OF_OFFICE, IUserStatus::MESSAGE_CALL, true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataSetUserStatus')]
	public function testSetUserStatus(string $messageId, string $oldMessageId, bool $expectedUpdateShortcut): void {
		$previous = new UserStatus();
		$previous->setId(1);
		$previous->setStatus(IUserStatus::AWAY);
		$previous->setStatusTimestamp(1337);
		$previous->setIsUserDefined(false);
		$previous->setMessageId($oldMessageId);
		$previous->setUserId('john');
		$previous->setIsBackup(false);

		$this->mapper->expects($this->once())
			->method('findByUserId')
			->with('john')
			->willReturn($previous);

		/** @var MockObject&Exception $exception */
		$exception = $this->createMock(Exception::class);
		$exception->method('getReason')->willReturn(Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION);
		$this->mapper->expects($expectedUpdateShortcut ? $this->never() : $this->once())
			->method('createBackupStatus')
			->willThrowException($exception);

		$this->mapper->expects($this->any())
			->method('update')
			->willReturnArgument(0);

		$this->predefinedStatusService->expects($this->once())
			->method('isValidId')
			->with($messageId)
			->willReturn(true);

		$this->service->setUserStatus('john', IUserStatus::DND, $messageId, true);
	}
}
