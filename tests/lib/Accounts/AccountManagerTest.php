<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Accounts;

use OC\Accounts\Account;
use OC\Accounts\AccountManager;
use OC\PhoneNumberUtil;
use OCA\Settings\BackgroundJobs\VerifyUserData;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\UserUpdatedEvent;
use OCP\BackgroundJob\IJobList;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IPhoneNumberUtil;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Security\ICrypto;
use OCP\Security\VerificationToken\IVerificationToken;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class AccountManagerTest
 *
 * @group DB
 * @package Test\Accounts
 */
class AccountManagerTest extends TestCase {

	/** accounts table name */
	private string $table = 'accounts';
	private AccountManager $accountManager;
	private IDBConnection $connection;
	private IPhoneNumberUtil $phoneNumberUtil;

	protected IVerificationToken&MockObject $verificationToken;
	protected IMailer&MockObject $mailer;
	protected ICrypto&MockObject $crypto;
	protected IURLGenerator&MockObject $urlGenerator;
	protected Defaults&MockObject $defaults;
	protected IFactory&MockObject $l10nFactory;
	protected IConfig&MockObject $config;
	protected IEventDispatcher&MockObject $eventDispatcher;
	protected IJobList&MockObject $jobList;
	private LoggerInterface&MockObject $logger;
	private IClientService&MockObject $clientService;

	protected function setUp(): void {
		parent::setUp();
		$this->connection = Server::get(IDBConnection::class);
		$this->phoneNumberUtil = new PhoneNumberUtil();

		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->config = $this->createMock(IConfig::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->verificationToken = $this->createMock(IVerificationToken::class);
		$this->mailer = $this->createMock(IMailer::class);
		$this->defaults = $this->createMock(Defaults::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->clientService = $this->createMock(IClientService::class);

		$this->accountManager = new AccountManager(
			$this->connection,
			$this->config,
			$this->eventDispatcher,
			$this->jobList,
			$this->logger,
			$this->verificationToken,
			$this->mailer,
			$this->defaults,
			$this->l10nFactory,
			$this->urlGenerator,
			$this->crypto,
			$this->phoneNumberUtil,
			$this->clientService,
		);
	}

	protected function tearDown(): void {
		parent::tearDown();
		$query = $this->connection->getQueryBuilder();
		$query->delete($this->table)->executeStatement();
	}

	protected function makeUser(string $uid, string $name, ?string $email = null): IUser {
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUid')
			->willReturn($uid);
		$user->expects($this->any())
			->method('getDisplayName')
			->willReturn($name);
		if ($email !== null) {
			$user->expects($this->any())
				->method('getEMailAddress')
				->willReturn($email);
		}

		return $user;
	}

	protected function populateOrUpdate(): void {
		$users = [
			[
				'user' => $this->makeUser('j.doe', 'Jane Doe', 'jane.doe@acme.com'),
				'data' => [
					[
						'name' => IAccountManager::PROPERTY_DISPLAYNAME,
						'value' => 'Jane Doe',
						'scope' => IAccountManager::SCOPE_PUBLISHED
					],
					[
						'name' => IAccountManager::PROPERTY_EMAIL,
						'value' => 'jane.doe@acme.com',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_TWITTER,
						'value' => '@sometwitter',
						'scope' => IAccountManager::SCOPE_PUBLISHED
					],
					[
						'name' => IAccountManager::PROPERTY_FEDIVERSE,
						'value' => '@someMastodon@mastodon.social',
						'scope' => IAccountManager::SCOPE_PUBLISHED
					],
					[
						'name' => IAccountManager::PROPERTY_PHONE,
						'value' => '+491601231212',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
					[
						'name' => IAccountManager::PROPERTY_ADDRESS,
						'value' => 'some street',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_WEBSITE,
						'value' => 'https://acme.com',
						'scope' => IAccountManager::SCOPE_PRIVATE
					],
					[
						'name' => IAccountManager::PROPERTY_ORGANISATION,
						'value' => 'Some organisation',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_ROLE,
						'value' => 'Human',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_HEADLINE,
						'value' => 'Hi',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_BIOGRAPHY,
						'value' => 'Biography',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
				],
			],
			[
				'user' => $this->makeUser('a.allison', 'Alice Allison', 'a.allison@example.org'),
				'data' => [
					[
						'name' => IAccountManager::PROPERTY_DISPLAYNAME,
						'value' => 'Alice Allison',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_EMAIL,
						'value' => 'a.allison@example.org',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_TWITTER,
						'value' => '@a_alice',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
					[
						'name' => IAccountManager::PROPERTY_FEDIVERSE,
						'value' => '@a_alice@cool.social',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
					[
						'name' => IAccountManager::PROPERTY_PHONE,
						'value' => '+491602312121',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_ADDRESS,
						'value' => 'Dundee Road 45',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_WEBSITE,
						'value' => 'https://example.org',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_ORGANISATION,
						'value' => 'Another organisation',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
					[
						'name' => IAccountManager::PROPERTY_ROLE,
						'value' => 'Alien',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
					[
						'name' => IAccountManager::PROPERTY_HEADLINE,
						'value' => 'Hello',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
					[
						'name' => IAccountManager::PROPERTY_BIOGRAPHY,
						'value' => 'Different biography',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
				],
			],
			[
				'user' => $this->makeUser('b32c5a5b-1084-4380-8856-e5223b16de9f', 'Armel Oliseh', 'oliseh@example.com'),
				'data' => [
					[
						'name' => IAccountManager::PROPERTY_DISPLAYNAME,
						'value' => 'Armel Oliseh',
						'scope' => IAccountManager::SCOPE_PUBLISHED
					],
					[
						'name' => IAccountManager::PROPERTY_EMAIL,
						'value' => 'oliseh@example.com',
						'scope' => IAccountManager::SCOPE_PUBLISHED
					],
					[
						'name' => IAccountManager::PROPERTY_TWITTER,
						'value' => '',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_FEDIVERSE,
						'value' => '',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_PHONE,
						'value' => '+491603121212',
						'scope' => IAccountManager::SCOPE_PUBLISHED
					],
					[
						'name' => IAccountManager::PROPERTY_ADDRESS,
						'value' => 'Sunflower Blvd. 77',
						'scope' => IAccountManager::SCOPE_PUBLISHED
					],
					[
						'name' => IAccountManager::PROPERTY_WEBSITE,
						'value' => 'https://example.com',
						'scope' => IAccountManager::SCOPE_PUBLISHED
					],
					[
						'name' => IAccountManager::PROPERTY_ORGANISATION,
						'value' => 'Yet another organisation',
						'scope' => IAccountManager::SCOPE_PUBLISHED
					],
					[
						'name' => IAccountManager::PROPERTY_ROLE,
						'value' => 'Being',
						'scope' => IAccountManager::SCOPE_PUBLISHED
					],
					[
						'name' => IAccountManager::PROPERTY_HEADLINE,
						'value' => 'This is a headline',
						'scope' => IAccountManager::SCOPE_PUBLISHED
					],
					[
						'name' => IAccountManager::PROPERTY_BIOGRAPHY,
						'value' => 'Some long biography',
						'scope' => IAccountManager::SCOPE_PUBLISHED
					],
				],
			],
			[
				'user' => $this->makeUser('31b5316a-9b57-4b17-970a-315a4cbe73eb', 'K. Cheng', 'cheng@emca.com'),
				'data' => [
					[
						'name' => IAccountManager::PROPERTY_DISPLAYNAME,
						'value' => 'K. Cheng',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
					[
						'name' => IAccountManager::PROPERTY_EMAIL,
						'value' => 'cheng@emca.com',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
					[
						'name' => IAccountManager::PROPERTY_TWITTER,
						'value' => '', '
						scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_FEDIVERSE,
						'value' => '', '
						scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_PHONE,
						'value' => '+71601212123',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_ADDRESS,
						'value' => 'Pinapple Street 22',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_WEBSITE,
						'value' => 'https://emca.com',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
					[
						'name' => IAccountManager::PROPERTY_ORGANISATION,
						'value' => 'Organisation A',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_ROLE,
						'value' => 'Animal',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_HEADLINE,
						'value' => 'My headline',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_BIOGRAPHY,
						'value' => 'Short biography',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::COLLECTION_EMAIL,
						'value' => 'k.cheng@emca.com',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::COLLECTION_EMAIL,
						'value' => 'kai.cheng@emca.com',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
				],
			],
			[
				'user' => $this->makeUser('goodpal@elpmaxe.org', 'Goodpal, Kim', 'goodpal@elpmaxe.org'),
				'data' => [
					[
						'name' => IAccountManager::PROPERTY_DISPLAYNAME,
						'value' => 'Goodpal, Kim',
						'scope' => IAccountManager::SCOPE_PUBLISHED
					],
					[
						'name' => IAccountManager::PROPERTY_EMAIL,
						'value' => 'goodpal@elpmaxe.org',
						'scope' => IAccountManager::SCOPE_PUBLISHED
					],
					[
						'name' => IAccountManager::PROPERTY_TWITTER,
						'value' => '',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_FEDIVERSE,
						'value' => '',
						'scope' => IAccountManager::SCOPE_LOCAL
					],
					[
						'name' => IAccountManager::PROPERTY_PHONE,
						'value' => '+71602121231',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
					[
						'name' => IAccountManager::PROPERTY_ADDRESS,
						'value' => 'Octopus Ave 17',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
					[
						'name' => IAccountManager::PROPERTY_WEBSITE,
						'value' => 'https://elpmaxe.org',
						'scope' => IAccountManager::SCOPE_PUBLISHED
					],
					[
						'name' => IAccountManager::PROPERTY_ORGANISATION,
						'value' => 'Organisation B',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
					[
						'name' => IAccountManager::PROPERTY_ROLE,
						'value' => 'Organism',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
					[
						'name' => IAccountManager::PROPERTY_HEADLINE,
						'value' => 'Best headline',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
					[
						'name' => IAccountManager::PROPERTY_BIOGRAPHY,
						'value' => 'Autobiography',
						'scope' => IAccountManager::SCOPE_FEDERATED
					],
				],
			],
		];
		$this->config->expects($this->exactly(count($users)))->method('getSystemValue')->with('account_manager.default_property_scope', [])->willReturn([]);
		foreach ($users as $userInfo) {
			$this->invokePrivate($this->accountManager, 'updateUser', [$userInfo['user'], $userInfo['data'], null, false]);
		}
	}

	/**
	 * get a instance of the accountManager
	 *
	 * @return MockObject | AccountManager
	 */
	public function getInstance(?array $mockedMethods = null) {
		return $this->getMockBuilder(AccountManager::class)
			->setConstructorArgs([
				$this->connection,
				$this->config,
				$this->eventDispatcher,
				$this->jobList,
				$this->logger,
				$this->verificationToken,
				$this->mailer,
				$this->defaults,
				$this->l10nFactory,
				$this->urlGenerator,
				$this->crypto,
				$this->phoneNumberUtil,
				$this->clientService,
			])
			->onlyMethods($mockedMethods)
			->getMock();
	}

	/**
	 * @dataProvider dataTrueFalse
	 *
	 */
	public function testUpdateUser(array $newData, array $oldData, bool $insertNew, bool $updateExisting): void {
		$accountManager = $this->getInstance(['getUser', 'insertNewUser', 'updateExistingUser']);
		/** @var IUser $user */
		$user = $this->createMock(IUser::class);

		if ($updateExisting) {
			$accountManager->expects($this->once())->method('updateExistingUser')
				->with($user, $newData);
			$accountManager->expects($this->never())->method('insertNewUser');
		}
		if ($insertNew) {
			$accountManager->expects($this->once())->method('insertNewUser')
				->with($user, $newData);
			$accountManager->expects($this->never())->method('updateExistingUser');
		}

		if (!$insertNew && !$updateExisting) {
			$accountManager->expects($this->never())->method('updateExistingUser');
			$accountManager->expects($this->never())->method('insertNewUser');
			$this->eventDispatcher->expects($this->never())->method('dispatchTyped');
		} else {
			$this->eventDispatcher->expects($this->once())->method('dispatchTyped')
				->willReturnCallback(
					function ($event) use ($user, $newData): void {
						$this->assertInstanceOf(UserUpdatedEvent::class, $event);
						$this->assertSame($user, $event->getUser());
						$this->assertSame($newData, $event->getData());
					}
				);
		}

		$this->invokePrivate($accountManager, 'updateUser', [$user, $newData, $oldData]);
	}

	public static function dataTrueFalse(): array {
		return [
			#$newData | $oldData | $insertNew | $updateExisting
			[['myProperty' => ['value' => 'newData']], ['myProperty' => ['value' => 'oldData']], false, true],
			[['myProperty' => ['value' => 'oldData']], ['myProperty' => ['value' => 'oldData']], false, false]
		];
	}

	public function testAddMissingDefaults(): void {
		$user = $this->createMock(IUser::class);

		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('settings', 'profile_enabled_by_default', '1')
			->willReturn('1');

		$input = [
			[
				'name' => IAccountManager::PROPERTY_DISPLAYNAME,
				'value' => 'bob',
				'verified' => IAccountManager::NOT_VERIFIED,
			],
			[
				'name' => IAccountManager::PROPERTY_EMAIL,
				'value' => 'bob@bob.bob',
			],
		];

		$expected = [
			[
				'name' => IAccountManager::PROPERTY_DISPLAYNAME,
				'value' => 'bob',
				'scope' => IAccountManager::SCOPE_FEDERATED,
				'verified' => IAccountManager::NOT_VERIFIED,
			],

			[
				'name' => IAccountManager::PROPERTY_EMAIL,
				'value' => 'bob@bob.bob',
				'scope' => IAccountManager::SCOPE_FEDERATED,
				'verified' => IAccountManager::NOT_VERIFIED,
			],

			[
				'name' => IAccountManager::PROPERTY_ADDRESS,
				'value' => '',
				'scope' => IAccountManager::SCOPE_LOCAL,
				'verified' => IAccountManager::NOT_VERIFIED,
			],

			[
				'name' => IAccountManager::PROPERTY_WEBSITE,
				'value' => '',
				'scope' => IAccountManager::SCOPE_LOCAL,
				'verified' => IAccountManager::NOT_VERIFIED,
			],

			[
				'name' => IAccountManager::PROPERTY_AVATAR,
				'scope' => IAccountManager::SCOPE_FEDERATED
			],

			[
				'name' => IAccountManager::PROPERTY_PHONE,
				'value' => '',
				'scope' => IAccountManager::SCOPE_LOCAL,
				'verified' => IAccountManager::NOT_VERIFIED,
			],

			[
				'name' => IAccountManager::PROPERTY_TWITTER,
				'value' => '',
				'scope' => IAccountManager::SCOPE_LOCAL,
				'verified' => IAccountManager::NOT_VERIFIED,
			],

			[
				'name' => IAccountManager::PROPERTY_FEDIVERSE,
				'value' => '',
				'scope' => IAccountManager::SCOPE_LOCAL,
				'verified' => IAccountManager::NOT_VERIFIED,
			],

			[
				'name' => IAccountManager::PROPERTY_ORGANISATION,
				'value' => '',
				'scope' => IAccountManager::SCOPE_LOCAL,
			],

			[
				'name' => IAccountManager::PROPERTY_ROLE,
				'value' => '',
				'scope' => IAccountManager::SCOPE_LOCAL,
			],

			[
				'name' => IAccountManager::PROPERTY_HEADLINE,
				'value' => '',
				'scope' => IAccountManager::SCOPE_LOCAL,
			],

			[
				'name' => IAccountManager::PROPERTY_BIOGRAPHY,
				'value' => '',
				'scope' => IAccountManager::SCOPE_LOCAL,
			],

			[
				'name' => IAccountManager::PROPERTY_BIRTHDATE,
				'value' => '',
				'scope' => IAccountManager::SCOPE_LOCAL,
			],

			[
				'name' => IAccountManager::PROPERTY_PROFILE_ENABLED,
				'value' => '1',
			],

			[
				'name' => IAccountManager::PROPERTY_PRONOUNS,
				'value' => '',
				'scope' => IAccountManager::SCOPE_FEDERATED,
			],
		];
		$this->config->expects($this->once())->method('getSystemValue')->with('account_manager.default_property_scope', [])->willReturn([]);

		$defaultUserRecord = $this->invokePrivate($this->accountManager, 'buildDefaultUserRecord', [$user]);
		$result = $this->invokePrivate($this->accountManager, 'addMissingDefaultValues', [$input, $defaultUserRecord]);

		$this->assertSame($expected, $result);
	}

	public function testGetAccount(): void {
		$accountManager = $this->getInstance(['getUser']);
		/** @var IUser $user */
		$user = $this->createMock(IUser::class);

		$data = [
			[
				'value' => '@twitterhandle',
				'scope' => IAccountManager::SCOPE_LOCAL,
				'verified' => IAccountManager::NOT_VERIFIED,
				'name' => IAccountManager::PROPERTY_TWITTER,
			],
			[
				'value' => '@mastohandle@mastodon.social',
				'scope' => IAccountManager::SCOPE_LOCAL,
				'verified' => IAccountManager::NOT_VERIFIED,
				'name' => IAccountManager::PROPERTY_FEDIVERSE,
			],
			[
				'value' => 'test@example.com',
				'scope' => IAccountManager::SCOPE_PUBLISHED,
				'verified' => IAccountManager::VERIFICATION_IN_PROGRESS,
				'name' => IAccountManager::PROPERTY_EMAIL,
			],
			[
				'value' => 'https://example.com',
				'scope' => IAccountManager::SCOPE_FEDERATED,
				'verified' => IAccountManager::VERIFIED,
				'name' => IAccountManager::PROPERTY_WEBSITE,
			],
		];
		$expected = new Account($user);
		$expected->setProperty(IAccountManager::PROPERTY_TWITTER, '@twitterhandle', IAccountManager::SCOPE_LOCAL, IAccountManager::NOT_VERIFIED);
		$expected->setProperty(IAccountManager::PROPERTY_FEDIVERSE, '@mastohandle@mastodon.social', IAccountManager::SCOPE_LOCAL, IAccountManager::NOT_VERIFIED);
		$expected->setProperty(IAccountManager::PROPERTY_EMAIL, 'test@example.com', IAccountManager::SCOPE_PUBLISHED, IAccountManager::VERIFICATION_IN_PROGRESS);
		$expected->setProperty(IAccountManager::PROPERTY_WEBSITE, 'https://example.com', IAccountManager::SCOPE_FEDERATED, IAccountManager::VERIFIED);

		$accountManager->expects($this->once())
			->method('getUser')
			->willReturn($data);
		$this->assertEquals($expected, $accountManager->getAccount($user));
	}

	public static function dataParsePhoneNumber(): array {
		return [
			['0711 / 25 24 28-90', 'DE', '+4971125242890'],
			['0711 / 25 24 28-90', '', null],
			['+49 711 / 25 24 28-90', '', '+4971125242890'],
		];
	}

	/**
	 * @dataProvider dataParsePhoneNumber
	 */
	public function testSanitizePhoneNumberOnUpdateAccount(string $phoneInput, string $defaultRegion, ?string $phoneNumber): void {
		$this->config->method('getSystemValueString')
			->willReturn($defaultRegion);

		$user = $this->createMock(IUser::class);
		$account = new Account($user);
		$account->setProperty(IAccountManager::PROPERTY_PHONE, $phoneInput, IAccountManager::SCOPE_LOCAL, IAccountManager::NOT_VERIFIED);
		$manager = $this->getInstance(['getUser', 'updateUser']);
		$manager->method('getUser')
			->with($user, false)
			->willReturn([]);
		$manager->expects($phoneNumber === null ? self::never() : self::once())
			->method('updateUser');

		if ($phoneNumber === null) {
			$this->expectException(\InvalidArgumentException::class);
		}

		$manager->updateAccount($account);

		if ($phoneNumber !== null) {
			self::assertEquals($phoneNumber, $account->getProperty(IAccountManager::PROPERTY_PHONE)->getValue());
		}
	}

	public static function dataSanitizeOnUpdate(): array {
		return [
			[IAccountManager::PROPERTY_WEBSITE, 'https://nextcloud.com', 'https://nextcloud.com'],
			[IAccountManager::PROPERTY_WEBSITE, 'http://nextcloud.com', 'http://nextcloud.com'],
			[IAccountManager::PROPERTY_WEBSITE, 'ftp://nextcloud.com', null],
			[IAccountManager::PROPERTY_WEBSITE, '//nextcloud.com/', null],
			[IAccountManager::PROPERTY_WEBSITE, 'https:///?query', null],

			[IAccountManager::PROPERTY_TWITTER, '@nextcloud', 'nextcloud'],
			[IAccountManager::PROPERTY_TWITTER, '_nextcloud', '_nextcloud'],
			[IAccountManager::PROPERTY_TWITTER, 'FooB4r', 'FooB4r'],
			[IAccountManager::PROPERTY_TWITTER, 'X', null],
			[IAccountManager::PROPERTY_TWITTER, 'next.cloud', null],
			[IAccountManager::PROPERTY_TWITTER, 'ab/cd.zip', null],
			[IAccountManager::PROPERTY_TWITTER, 'tooLongForTwitterAndX', null],

			[IAccountManager::PROPERTY_FEDIVERSE, 'nextcloud@mastodon.social', 'nextcloud@mastodon.social'],
			[IAccountManager::PROPERTY_FEDIVERSE, '@nextcloud@mastodon.xyz', 'nextcloud@mastodon.xyz'],
			[IAccountManager::PROPERTY_FEDIVERSE, 'l33t.h4x0r@sub.localhost.local', 'l33t.h4x0r@sub.localhost.local'],
			[IAccountManager::PROPERTY_FEDIVERSE, 'invalid/name@mastodon.social', null],
			[IAccountManager::PROPERTY_FEDIVERSE, 'name@evil.host/malware.exe', null],
			[IAccountManager::PROPERTY_FEDIVERSE, '@is-it-a-host-or-name', null],
			[IAccountManager::PROPERTY_FEDIVERSE, 'only-a-name', null],
		];
	}

	/**
	 * @dataProvider dataSanitizeOnUpdate
	 */
	public function testSanitizingOnUpdateAccount(string $property, string $input, ?string $output): void {

		if ($property === IAccountManager::PROPERTY_FEDIVERSE) {
			// We do not test the server response here we do this in the `testSanitizingFediverseServer`
			$this->config
				->method('getSystemValueBool')
				->with('has_internet_connection', true)
				->willReturn(false);
		}

		$user = $this->createMock(IUser::class);

		$account = new Account($user);
		$account->setProperty($property, $input, IAccountManager::SCOPE_LOCAL, IAccountManager::NOT_VERIFIED);

		$manager = $this->getInstance(['getUser', 'updateUser']);
		$manager->method('getUser')
			->with($user, false)
			->willReturn([]);
		$manager->expects($output === null ? self::never() : self::once())
			->method('updateUser');

		if ($output === null) {
			$this->expectException(\InvalidArgumentException::class);
			$this->expectExceptionMessage($property);
		}

		$manager->updateAccount($account);

		if ($output !== null) {
			self::assertEquals($output, $account->getProperty($property)->getValue());
		}
	}

	public static function dataSanitizeFediverseServer(): array {
		return [
			'no internet' => [
				'@foo@example.com',
				'foo@example.com',
				false,
				null,
			],
			'no internet - no at' => [
				'foo@example.com',
				'foo@example.com',
				false,
				null,
			],
			'valid response' => [
				'@foo@example.com',
				'foo@example.com',
				true,
				json_encode(['username' => 'foo']),
			],
			'valid response - no at' => [
				'foo@example.com',
				'foo@example.com',
				true,
				json_encode(['username' => 'foo']),
			],
			// failures
			'invalid response' => [
				'@foo@example.com',
				null,
				true,
				json_encode(['not found']),
			],
			'no response' => [
				'@foo@example.com',
				null,
				true,
				null,
			],
			'wrong user' => [
				'@foo@example.com',
				null,
				true,
				json_encode(['username' => 'foo@other.example.com']),
			],
		];
	}

	/**
	 * @dataProvider dataSanitizeFediverseServer
	 */
	public function testSanitizingFediverseServer(string $input, ?string $output, bool $hasInternet, ?string $serverResponse): void {
		$this->config->expects(self::once())
			->method('getSystemValueBool')
			->with('has_internet_connection', true)
			->willReturn($hasInternet);

		if ($hasInternet) {
			$client = $this->createMock(IClient::class);
			if ($serverResponse !== null) {
				$response = $this->createMock(IResponse::class);
				$response->method('getBody')
					->willReturn($serverResponse);
				$client->expects(self::once())
					->method('get')
					->with('https://example.com/api/v1/accounts/lookup?acct=foo@example.com')
					->willReturn($response);
			} else {
				$client->expects(self::once())
					->method('get')
					->with('https://example.com/api/v1/accounts/lookup?acct=foo@example.com')
					->willThrowException(new \Exception('404'));
			}

			$this->clientService
				->expects(self::once())
				->method('newClient')
				->willReturn($client);
		} else {
			$this->clientService
				->expects(self::never())
				->method('newClient');
		}

		$user = $this->createMock(IUser::class);
		$account = new Account($user);
		$account->setProperty(IAccountManager::PROPERTY_FEDIVERSE, $input, IAccountManager::SCOPE_LOCAL, IAccountManager::NOT_VERIFIED);

		$manager = $this->getInstance(['getUser', 'updateUser']);
		$manager->method('getUser')
			->with($user, false)
			->willReturn([]);
		$manager->expects($output === null ? self::never() : self::once())
			->method('updateUser');

		if ($output === null) {
			$this->expectException(\InvalidArgumentException::class);
			$this->expectExceptionMessage(IAccountManager::PROPERTY_FEDIVERSE);
		}

		$manager->updateAccount($account);

		if ($output !== null) {
			self::assertEquals($output, $account->getProperty(IAccountManager::PROPERTY_FEDIVERSE)->getValue());
		}
	}

	/**
	 * @dataProvider searchDataProvider
	 */
	public function testSearchUsers(string $property, array $values, array $expected): void {
		$this->populateOrUpdate();

		$matchedUsers = $this->accountManager->searchUsers($property, $values);
		foreach ($expected as $expectedEntry) {
			$this->assertContains($expectedEntry, $matchedUsers);
		}
		if (empty($expected)) {
			$this->assertEmpty($matchedUsers);
		}
	}

	public static function searchDataProvider(): array {
		return [
			[ #0 Search for an existing name
				IAccountManager::PROPERTY_DISPLAYNAME,
				['Jane Doe'],
				['Jane Doe' => 'j.doe']
			],
			[ #1 Search for part of a name (no result)
				IAccountManager::PROPERTY_DISPLAYNAME,
				['Jane'],
				[]
			],
			[ #2 Search for part of a name (no result, test wildcard)
				IAccountManager::PROPERTY_DISPLAYNAME,
				['Jane%'],
				[]
			],
			[ #3 Search for phone
				IAccountManager::PROPERTY_PHONE,
				['+491603121212'],
				['+491603121212' => 'b32c5a5b-1084-4380-8856-e5223b16de9f'],
			],
			[ #4 Search for twitter handles
				IAccountManager::PROPERTY_TWITTER,
				['@sometwitter', '@a_alice', '@unseen'],
				['@sometwitter' => 'j.doe', '@a_alice' => 'a.allison'],
			],
			[ #5 Search for email
				IAccountManager::PROPERTY_EMAIL,
				['cheng@emca.com'],
				['cheng@emca.com' => '31b5316a-9b57-4b17-970a-315a4cbe73eb'],
			],
			[ #6 Search for email by additional email
				IAccountManager::PROPERTY_EMAIL,
				['kai.cheng@emca.com'],
				['kai.cheng@emca.com' => '31b5316a-9b57-4b17-970a-315a4cbe73eb'],
			],
			[ #7 Search for additional email
				IAccountManager::COLLECTION_EMAIL,
				['kai.cheng@emca.com', 'cheng@emca.com'],
				['kai.cheng@emca.com' => '31b5316a-9b57-4b17-970a-315a4cbe73eb'],
			],
			[ #8 Search for email by additional email (two valid search values, but the same user)
				IAccountManager::PROPERTY_EMAIL,
				['kai.cheng@emca.com', 'cheng@emca.com'],
				[
					'kai.cheng@emca.com' => '31b5316a-9b57-4b17-970a-315a4cbe73eb',
				],
			],
		];
	}

	public static function dataCheckEmailVerification(): array {
		return [
			[['steve', 'Steve Smith', 'steve@steve.steve'], null],
			[['emma', 'Emma Morales', 'emma@emma.com'], 'emma@morales.com'],
			[['sarah@web.org', 'Sarah Foster', 'sarah@web.org'], null],
			[['cole@web.org', 'Cole Harrison', 'cole@web.org'], 'cole@example.com'],
			[['8d29e358-cf69-4849-bbf9-28076c0b908b', 'Alice McPherson', 'alice@example.com'], 'alice@mcpherson.com'],
			[['11da2744-3f4d-4c17-8c13-4c057a379237', 'James Loranger', 'james@example.com'], ''],
		];
	}

	/**
	 * @dataProvider dataCheckEmailVerification
	 */
	public function testCheckEmailVerification(array $userData, ?string $newEmail): void {
		$user = $this->makeUser(...$userData);
		// Once because of getAccount, once because of getUser
		$this->config->expects($this->exactly(2))->method('getSystemValue')->with('account_manager.default_property_scope', [])->willReturn([]);
		$account = $this->accountManager->getAccount($user);
		$emailUpdated = false;

		if (!empty($newEmail)) {
			$account->getProperty(IAccountManager::PROPERTY_EMAIL)->setValue($newEmail);
			$emailUpdated = true;
		}

		if ($emailUpdated) {
			$this->jobList->expects($this->once())
				->method('add')
				->with(VerifyUserData::class);
		} else {
			$this->jobList->expects($this->never())
				->method('add')
				->with(VerifyUserData::class);
		}

		/** @var array $oldData */
		$oldData = $this->invokePrivate($this->accountManager, 'getUser', [$user, false]);
		$this->invokePrivate($this->accountManager, 'checkEmailVerification', [$account, $oldData]);
	}

	public static function dataSetDefaultPropertyScopes(): array {
		return [
			[
				[],
				[
					IAccountManager::PROPERTY_DISPLAYNAME => IAccountManager::SCOPE_FEDERATED,
					IAccountManager::PROPERTY_ADDRESS => IAccountManager::SCOPE_LOCAL,
					IAccountManager::PROPERTY_EMAIL => IAccountManager::SCOPE_FEDERATED,
					IAccountManager::PROPERTY_ROLE => IAccountManager::SCOPE_LOCAL,
				]
			],
			[
				[
					IAccountManager::PROPERTY_DISPLAYNAME => IAccountManager::SCOPE_FEDERATED,
					IAccountManager::PROPERTY_EMAIL => IAccountManager::SCOPE_LOCAL,
					IAccountManager::PROPERTY_ROLE => IAccountManager::SCOPE_PRIVATE,
				], [
					IAccountManager::PROPERTY_DISPLAYNAME => IAccountManager::SCOPE_FEDERATED,
					IAccountManager::PROPERTY_EMAIL => IAccountManager::SCOPE_LOCAL,
					IAccountManager::PROPERTY_ROLE => IAccountManager::SCOPE_PRIVATE,
				]
			],
			[
				[
					IAccountManager::PROPERTY_ADDRESS => 'invalid scope',
					'invalid property' => IAccountManager::SCOPE_LOCAL,
					IAccountManager::PROPERTY_ROLE => IAccountManager::SCOPE_PRIVATE,
				],
				[
					IAccountManager::PROPERTY_ADDRESS => IAccountManager::SCOPE_LOCAL,
					IAccountManager::PROPERTY_EMAIL => IAccountManager::SCOPE_FEDERATED,
					IAccountManager::PROPERTY_ROLE => IAccountManager::SCOPE_PRIVATE,
				]
			],
		];
	}

	/**
	 * @dataProvider dataSetDefaultPropertyScopes
	 */
	public function testSetDefaultPropertyScopes(array $propertyScopes, array $expectedResultScopes): void {
		$user = $this->makeUser('steve', 'Steve Smith', 'steve@steve.steve');
		$this->config->expects($this->once())->method('getSystemValue')->with('account_manager.default_property_scope', [])->willReturn($propertyScopes);

		$result = $this->invokePrivate($this->accountManager, 'buildDefaultUserRecord', [$user]);
		$resultProperties = array_column($result, 'name');

		$this->assertEmpty(array_diff($resultProperties, IAccountManager::ALLOWED_PROPERTIES), 'Building default user record returned non-allowed properties');
		foreach ($expectedResultScopes as $expectedResultScopeKey => $expectedResultScopeValue) {
			$resultScope = $result[array_search($expectedResultScopeKey, $resultProperties)]['scope'];
			$this->assertEquals($expectedResultScopeValue, $resultScope, "The result scope doesn't follow the value set into the config or defaults correctly.");
		}
	}
}
