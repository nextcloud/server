<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Activity;

use OCP\Activity\Exceptions\IncompleteActivityException;
use OCP\Activity\IConsumer;
use OCP\Activity\IEvent;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\RichObjectStrings\IRichTextFormatter;
use OCP\RichObjectStrings\IValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ManagerTest extends TestCase {
	/** @var \OC\Activity\Manager */
	private $activityManager;

	protected IRequest&MockObject $request;
	protected IUserSession&MockObject $session;
	protected IConfig&MockObject $config;
	protected IValidator&MockObject $validator;
	protected IRichTextFormatter&MockObject $richTextFormatter;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->session = $this->createMock(IUserSession::class);
		$this->config = $this->createMock(IConfig::class);
		$this->validator = $this->createMock(IValidator::class);
		$this->richTextFormatter = $this->createMock(IRichTextFormatter::class);

		$this->activityManager = new \OC\Activity\Manager(
			$this->request,
			$this->session,
			$this->config,
			$this->validator,
			$this->richTextFormatter,
			$this->createMock(IL10N::class)
		);

		$this->assertSame([], self::invokePrivate($this->activityManager, 'getConsumers'));

		$this->activityManager->registerConsumer(function () {
			return new NoOpConsumer();
		});

		$this->assertNotEmpty(self::invokePrivate($this->activityManager, 'getConsumers'));
		$this->assertNotEmpty(self::invokePrivate($this->activityManager, 'getConsumers'));
	}

	public function testGetConsumers(): void {
		$consumers = self::invokePrivate($this->activityManager, 'getConsumers');

		$this->assertNotEmpty($consumers);
	}


	public function testGetConsumersInvalidConsumer(): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->activityManager->registerConsumer(function () {
			return new \stdClass();
		});

		self::invokePrivate($this->activityManager, 'getConsumers');
	}

	public static function getUserFromTokenThrowInvalidTokenData(): array {
		return [
			[null, []],
			['', []],
			['12345678901234567890123456789', []],
			['1234567890123456789012345678901', []],
			['123456789012345678901234567890', []],
			['123456789012345678901234567890', ['user1', 'user2']],
		];
	}

	/**
	 * @dataProvider getUserFromTokenThrowInvalidTokenData
	 *
	 * @param string $token
	 * @param array $users
	 */
	public function testGetUserFromTokenThrowInvalidToken($token, $users): void {
		$this->expectException(\UnexpectedValueException::class);

		$this->mockRSSToken($token, $token, $users);
		self::invokePrivate($this->activityManager, 'getUserFromToken');
	}

	public static function getUserFromTokenData(): array {
		return [
			[null, '123456789012345678901234567890', 'user1'],
			['user2', null, 'user2'],
			['user2', '123456789012345678901234567890', 'user2'],
		];
	}

	/**
	 * @dataProvider getUserFromTokenData
	 *
	 * @param string $userLoggedIn
	 * @param string $token
	 * @param string $expected
	 */
	public function testGetUserFromToken($userLoggedIn, $token, $expected): void {
		if ($userLoggedIn !== null) {
			$this->mockUserSession($userLoggedIn);
		}
		$this->mockRSSToken($token, '123456789012345678901234567890', ['user1']);

		$this->assertEquals($expected, $this->activityManager->getCurrentUserId());
	}

	protected function mockRSSToken($requestToken, $userToken, $users) {
		if ($requestToken !== null) {
			$this->request->expects($this->any())
				->method('getParam')
				->with('token', '')
				->willReturn($requestToken);
		}

		$this->config->expects($this->any())
			->method('getUsersForUserValue')
			->with('activity', 'rsstoken', $userToken)
			->willReturn($users);
	}

	protected function mockUserSession($user) {
		$mockUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$mockUser->expects($this->any())
			->method('getUID')
			->willReturn($user);

		$this->session->expects($this->any())
			->method('isLoggedIn')
			->willReturn(true);
		$this->session->expects($this->any())
			->method('getUser')
			->willReturn($mockUser);
	}


	public function testPublishExceptionNoApp(): void {
		$this->expectException(IncompleteActivityException::class);

		$event = $this->activityManager->generateEvent();
		$this->activityManager->publish($event);
	}


	public function testPublishExceptionNoType(): void {
		$this->expectException(IncompleteActivityException::class);

		$event = $this->activityManager->generateEvent();
		$event->setApp('test');
		$this->activityManager->publish($event);
	}


	public function testPublishExceptionNoAffectedUser(): void {
		$this->expectException(IncompleteActivityException::class);

		$event = $this->activityManager->generateEvent();
		$event->setApp('test')
			->setType('test_type');
		$this->activityManager->publish($event);
	}


	public function testPublishExceptionNoSubject(): void {
		$this->expectException(IncompleteActivityException::class);

		$event = $this->activityManager->generateEvent();
		$event->setApp('test')
			->setType('test_type')
			->setAffectedUser('test_affected');
		$this->activityManager->publish($event);
	}

	public static function dataPublish(): array {
		return [
			[null, ''],
			['test_author', 'test_author'],
		];
	}

	/**
	 * @dataProvider dataPublish
	 * @param string|null $author
	 * @param string $expected
	 */
	public function testPublish($author, $expected): void {
		if ($author !== null) {
			$authorObject = $this->getMockBuilder(IUser::class)
				->disableOriginalConstructor()
				->getMock();
			$authorObject->expects($this->once())
				->method('getUID')
				->willReturn($author);
			$this->session->expects($this->atLeastOnce())
				->method('getUser')
				->willReturn($authorObject);
		}

		$event = $this->activityManager->generateEvent();
		$event->setApp('test')
			->setType('test_type')
			->setSubject('test_subject', [])
			->setAffectedUser('test_affected')
			->setObject('file', 123);

		$consumer = $this->getMockBuilder('OCP\Activity\IConsumer')
			->disableOriginalConstructor()
			->getMock();
		$consumer->expects($this->once())
			->method('receive')
			->with($event)
			->willReturnCallback(function (IEvent $event) use ($expected): void {
				$this->assertLessThanOrEqual(time() + 2, $event->getTimestamp(), 'Timestamp not set correctly');
				$this->assertGreaterThanOrEqual(time() - 2, $event->getTimestamp(), 'Timestamp not set correctly');
				$this->assertSame($expected, $event->getAuthor(), 'Author name not set correctly');
			});
		$this->activityManager->registerConsumer(function () use ($consumer) {
			return $consumer;
		});

		$this->activityManager->publish($event);
	}

	public function testPublishAllManually(): void {
		$event = $this->activityManager->generateEvent();
		$event->setApp('test_app')
			->setType('test_type')
			->setAffectedUser('test_affected')
			->setAuthor('test_author')
			->setTimestamp(1337)
			->setSubject('test_subject', ['test_subject_param'])
			->setMessage('test_message', ['test_message_param'])
			->setObject('test_object_type', 42, 'test_object_name')
			->setLink('test_link')
		;

		$consumer = $this->getMockBuilder('OCP\Activity\IConsumer')
			->disableOriginalConstructor()
			->getMock();
		$consumer->expects($this->once())
			->method('receive')
			->willReturnCallback(function (IEvent $event): void {
				$this->assertSame('test_app', $event->getApp(), 'App not set correctly');
				$this->assertSame('test_type', $event->getType(), 'Type not set correctly');
				$this->assertSame('test_affected', $event->getAffectedUser(), 'Affected user not set correctly');
				$this->assertSame('test_author', $event->getAuthor(), 'Author not set correctly');
				$this->assertSame(1337, $event->getTimestamp(), 'Timestamp not set correctly');
				$this->assertSame('test_subject', $event->getSubject(), 'Subject not set correctly');
				$this->assertSame(['test_subject_param'], $event->getSubjectParameters(), 'Subject parameter not set correctly');
				$this->assertSame('test_message', $event->getMessage(), 'Message not set correctly');
				$this->assertSame(['test_message_param'], $event->getMessageParameters(), 'Message parameter not set correctly');
				$this->assertSame('test_object_type', $event->getObjectType(), 'Object type not set correctly');
				$this->assertSame(42, $event->getObjectId(), 'Object ID not set correctly');
				$this->assertSame('test_object_name', $event->getObjectName(), 'Object name not set correctly');
				$this->assertSame('test_link', $event->getLink(), 'Link not set correctly');
			});
		$this->activityManager->registerConsumer(function () use ($consumer) {
			return $consumer;
		});

		$this->activityManager->publish($event);
	}
}

class NoOpConsumer implements IConsumer {
	public function receive(IEvent $event) {
	}
}
