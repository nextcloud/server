<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Activity;

use OC;
use OC\Activity\Manager;
use OCP\Activity\Exceptions\IncompleteActivityException;
use OCP\Activity\IConsumer;
use OCP\Activity\IEvent;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\RichObjectStrings\IRichTextFormatter;
use OCP\RichObjectStrings\IValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ManagerTest extends TestCase {
	private Manager $activityManager;

	protected IRequest&MockObject $request;
	protected IUserSession&MockObject $session;
	protected IConfig&MockObject $config;
	protected IValidator&MockObject $validator;
	protected IRichTextFormatter&MockObject $richTextFormatter;
	private ITimeFactory&MockObject $time;
	private IAppConfig $appConfig;
	private LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->session = $this->createMock(IUserSession::class);
		$this->config = $this->createMock(IConfig::class);
		$this->validator = $this->createMock(IValidator::class);
		$this->richTextFormatter = $this->createMock(IRichTextFormatter::class);
		$this->time = $this->createMock(ITimeFactory::class);
		$this->appConfig = OC::$server->get(IAppConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->activityManager = new Manager(
			$this->request,
			$this->session,
			$this->config,
			$this->validator,
			$this->richTextFormatter,
			$this->createMock(IL10N::class),
			$this->time,
			$this->appConfig,
			$this->logger,
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
	 *
	 * @param string $token
	 * @param array $users
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('getUserFromTokenThrowInvalidTokenData')]
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
	 *
	 * @param string $userLoggedIn
	 * @param string $token
	 * @param string $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('getUserFromTokenData')]
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
	 * @param string|null $author
	 * @param string $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataPublish')]
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

		$time = time();
		$this->time
			->method('getTime')
			->willReturn($time);

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
			->willReturnCallback(function (IEvent $event) use ($expected, $time): void {
				$this->assertEquals($time, $event->getTimestamp(), 'Timestamp not set correctly');
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

	public function testPublishWithSuperLongNestedParams(): void {
		$this->appConfig->setValueInt('activity', 'overly_long_activities', 0);
		$params = [
			'level_one' => [
				'title' => 'Lorem ipsum dolor sit amet',
				'intro' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
				'level_two' => [
					[
						'heading' => 'Sed ut perspiciatis unde omnis',
						'body' => 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.'
					],
					[
						'heading' => 'Neque porro quisquam est',
						'body' => 'Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam.'
					]
				]
			],
			'level_three' => [
				'section_a' => [
					'text' => 'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga.',
					'more_text' => 'Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus.'
				],
				'section_b' => [
					'paragraphs' => [
						'Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus.',
						'Ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
						'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.'
					]
				]
			],
			'level_four' => [
				'deep' => [
					'deeper' => [
						'block_one' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam.',
						'block_two' => 'Eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit.',
						'block_three' => 'Sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit.'
					]
				]
			],
			'level_five' => [
				'massive_text' => 'Sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur. At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus.'
			],
			'level_six' => [
				'repeat_blocks' => [
					[
						'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.'
					],
					[
						'text' => 'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollitia animi.'
					],
					[
						'text' => 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.'
					]
				]
			]
		];

		$event = $event2 = $this->activityManager->generateEvent();
		$event->setApp('test_app')
			->setType('test_type')
			->setAffectedUser('test_affected')
			->setAuthor('test_author')
			->setTimestamp(1337)
			->setSubject('test_subject', $params)
			->setMessage('test_message', ['test_message_params'])
			->setObject('test_object_type', 42, 'test_object_name')
			->setLink('test_link');

		$this->assertEquals(1, $this->appConfig->getValueInt('activity', 'overly_long_activities'));

		$event2->setApp('test_app')
			->setType('test_type')
			->setAffectedUser('test_affected')
			->setAuthor('test_author')
			->setTimestamp(1337)
			->setSubject('test_subject', $params)
			->setMessage('test_message', ['test_message_params'])
			->setObject('test_object_type', 42, 'test_object_name')
			->setLink('test_link');

		$consumer = $this->getMockBuilder('OCP\Activity\IConsumer')
			->disableOriginalConstructor()
			->getMock();
		$consumer->method('receive');
		$this->activityManager->registerConsumer(function () use ($consumer) {
			return $consumer;
		});

		$this->activityManager->publish($event);
		$this->activityManager->publish($event2);
		$this->assertEquals(2, $this->appConfig->getValueInt('activity', 'overly_long_activities'));
		$this->appConfig->setValueInt('activity', 'overly_long_activities', 0);

	}
}

class NoOpConsumer implements IConsumer {
	public function receive(IEvent $event) {
	}
}
