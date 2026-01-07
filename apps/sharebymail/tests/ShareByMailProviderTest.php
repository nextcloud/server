<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ShareByMail\Tests;

use DateTime;
use OC\Mail\Message;
use OC\Share20\Share;
use OCA\ShareByMail\Settings\SettingsManager;
use OCA\ShareByMail\ShareByMailProvider;
use OCP\Activity\IManager as IActivityManager;
use OCP\Constants;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Security\Events\GenerateSecurePasswordEvent;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use OCP\Security\PasswordContext;
use OCP\Server;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IAttributes;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\Util;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use Test\Traits\EmailValidatorTrait;

/**
 * Class ShareByMailProviderTest
 *
 * @package OCA\ShareByMail\Tests
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ShareByMailProviderTest extends TestCase {
	use EmailValidatorTrait;

	private IDBConnection $connection;

	private IL10N&MockObject $l;
	private IShare&MockObject $share;
	private IConfig&MockObject $config;
	private IMailer&MockObject $mailer;
	private IHasher&MockObject $hasher;
	private Defaults&MockObject $defaults;
	private IManager&MockObject $shareManager;
	private LoggerInterface&MockObject $logger;
	private IRootFolder&MockObject $rootFolder;
	private IUserManager&MockObject $userManager;
	private ISecureRandom&MockObject $secureRandom;
	private IURLGenerator&MockObject $urlGenerator;
	private SettingsManager&MockObject $settingsManager;
	private IActivityManager&MockObject $activityManager;
	private IEventDispatcher&MockObject $eventDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);

		$this->l = $this->createMock(IL10N::class);
		$this->l->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->rootFolder = $this->createMock('OCP\Files\IRootFolder');
		$this->userManager = $this->createMock(IUserManager::class);
		$this->secureRandom = $this->createMock('\OCP\Security\ISecureRandom');
		$this->mailer = $this->createMock('\OCP\Mail\IMailer');
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->share = $this->createMock(IShare::class);
		$this->activityManager = $this->createMock('OCP\Activity\IManager');
		$this->settingsManager = $this->createMock(SettingsManager::class);
		$this->defaults = $this->createMock(Defaults::class);
		$this->hasher = $this->createMock(IHasher::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->shareManager = $this->createMock(IManager::class);

		$this->userManager->expects($this->any())->method('userExists')->willReturn(true);
		$this->config->expects($this->any())->method('getAppValue')->with('core', 'enforce_strict_email_check')->willReturn('yes');
	}

	/**
	 * get instance of Mocked ShareByMailProvider
	 *
	 * @param array $mockedMethods internal methods which should be mocked
	 * @return \PHPUnit\Framework\MockObject\MockObject | ShareByMailProvider
	 */
	private function getInstance(array $mockedMethods = []) {
		if (!empty($mockedMethods)) {
			return $this->getMockBuilder(ShareByMailProvider::class)
				->setConstructorArgs([
					$this->config,
					$this->connection,
					$this->secureRandom,
					$this->userManager,
					$this->rootFolder,
					$this->l,
					$this->logger,
					$this->mailer,
					$this->urlGenerator,
					$this->activityManager,
					$this->settingsManager,
					$this->defaults,
					$this->hasher,
					$this->eventDispatcher,
					$this->shareManager,
					$this->getEmailValidatorWithStrictEmailCheck(),
				])
				->onlyMethods($mockedMethods)
				->getMock();
		}

		return new ShareByMailProvider(
			$this->config,
			$this->connection,
			$this->secureRandom,
			$this->userManager,
			$this->rootFolder,
			$this->l,
			$this->logger,
			$this->mailer,
			$this->urlGenerator,
			$this->activityManager,
			$this->settingsManager,
			$this->defaults,
			$this->hasher,
			$this->eventDispatcher,
			$this->shareManager,
			$this->getEmailValidatorWithStrictEmailCheck(),
		);
	}

	protected function tearDown(): void {
		$this->connection
			->getQueryBuilder()
			->delete('share')
			->executeStatement();

		parent::tearDown();
	}

	public function testCreate(): void {
		$expectedShare = $this->createMock(IShare::class);

		$share = $this->createMock(IShare::class);
		$share->expects($this->any())->method('getSharedWith')->willReturn('user1');

		$node = $this->createMock(File::class);
		$node->expects($this->any())->method('getName')->willReturn('filename');

		$instance = $this->getInstance(['getSharedWith', 'createMailShare', 'getRawShare', 'createShareObject', 'createShareActivity', 'sendEmail', 'sendPassword']);

		$instance->expects($this->once())->method('getSharedWith')->willReturn([]);
		$instance->expects($this->once())->method('createMailShare')->with($share)->willReturn(42);
		$instance->expects($this->once())->method('createShareActivity')->with($share);
		$instance->expects($this->once())->method('getRawShare')->with(42)->willReturn(['rawShare']);
		$instance->expects($this->once())->method('createShareObject')->with(['rawShare'])->willReturn($expectedShare);
		$share->expects($this->any())->method('getNode')->willReturn($node);

		// As share api link password is not enforced, the password will not be generated.
		$this->shareManager->expects($this->once())->method('shareApiLinkEnforcePassword')->willReturn(false);
		$this->settingsManager->expects($this->never())->method('sendPasswordByMail');

		// Mail notification is triggered by the share manager.
		$instance->expects($this->never())->method('sendEmail');
		$instance->expects($this->never())->method('sendPassword');

		$this->assertSame($expectedShare, $instance->create($share));
	}

	public function testCreateSendPasswordByMailWithoutEnforcedPasswordProtection(): void {
		$expectedShare = $this->createMock(IShare::class);

		$node = $this->createMock(File::class);
		$node->expects($this->any())->method('getName')->willReturn('filename');

		$share = $this->createMock(IShare::class);
		$share->expects($this->any())->method('getSharedWith')->willReturn('receiver@examplelölöl.com');
		$share->expects($this->any())->method('getSendPasswordByTalk')->willReturn(false);
		$share->expects($this->any())->method('getSharedBy')->willReturn('owner');
		$share->expects($this->any())->method('getNode')->willReturn($node);
		$share->expects($this->any())->method('getId')->willReturn(42);
		$share->expects($this->any())->method('getNote')->willReturn('');
		$share->expects($this->any())->method('getToken')->willReturn('token');

		$instance = $this->getInstance(['getSharedWith', 'createMailShare', 'getRawShare', 'createShareObject', 'createShareActivity', 'autoGeneratePassword', 'createPasswordSendActivity', 'sendEmail', 'sendPassword', 'sendPasswordToOwner']);
		$instance->expects($this->once())->method('getSharedWith')->willReturn([]);
		$instance->expects($this->once())->method('createMailShare')->with($share)->willReturn(42);
		$instance->expects($this->once())->method('createShareActivity')->with($share);
		$instance->expects($this->once())->method('getRawShare')->with(42)->willReturn(['rawShare']);
		$instance->expects($this->once())->method('createShareObject')->with(['rawShare'])->willReturn($expectedShare);
		$share->expects($this->any())->method('getNode')->willReturn($node);

		// The autogenerated password should not be mailed.
		$this->shareManager->expects($this->any())->method('shareApiLinkEnforcePassword')->willReturn(false);
		$this->settingsManager->expects($this->any())->method('sendPasswordByMail')->willReturn(true);
		$instance->expects($this->never())->method('autoGeneratePassword');

		// No password is set and no password sent via talk is requested
		$instance->expects($this->once())->method('sendEmail')->with($share, ['receiver@examplelölöl.com']);
		$instance->expects($this->never())->method('sendPassword');
		$instance->expects($this->never())->method('sendPasswordToOwner');

		// The manager sends the mail notification.
		// For the sake of testing simplicity, we will handle it ourselves.
		$this->assertSame($expectedShare, $instance->create($share));
		$instance->sendMailNotification($share);
	}

	public function testCreateSendPasswordByMailWithPasswordAndWithoutEnforcedPasswordProtectionWithPermanentPassword(): void {
		$expectedShare = $this->createMock(IShare::class);

		$node = $this->createMock(File::class);
		$node->expects($this->any())->method('getName')->willReturn('filename');

		$share = $this->createMock(IShare::class);
		$share->expects($this->any())->method('getSharedWith')->willReturn('receiver@example.com');
		$share->expects($this->any())->method('getSendPasswordByTalk')->willReturn(false);
		$share->expects($this->any())->method('getSharedBy')->willReturn('owner');
		$share->expects($this->any())->method('getNode')->willReturn($node);
		$share->expects($this->any())->method('getId')->willReturn(42);
		$share->expects($this->any())->method('getNote')->willReturn('');
		$share->expects($this->any())->method('getToken')->willReturn('token');

		$instance = $this->getInstance(['getSharedWith', 'createMailShare', 'getRawShare', 'createShareObject', 'createShareActivity', 'autoGeneratePassword', 'createPasswordSendActivity', 'sendEmail', 'sendPassword', 'sendPasswordToOwner']);

		$instance->expects($this->once())->method('getSharedWith')->willReturn([]);
		$instance->expects($this->once())->method('createMailShare')->with($share)->willReturn(42);
		$instance->expects($this->once())->method('createShareActivity')->with($share);
		$instance->expects($this->once())->method('getRawShare')->with(42)->willReturn(['rawShare', 'password' => 'password']);
		$instance->expects($this->once())->method('createShareObject')->with(['rawShare', 'password' => 'password'])->willReturn($expectedShare);
		$share->expects($this->any())->method('getNode')->willReturn($node);

		$share->expects($this->any())->method('getPassword')->willReturn('password');
		$this->hasher->expects($this->once())->method('hash')->with('password')->willReturn('passwordHashed');
		$share->expects($this->once())->method('setPassword')->with('passwordHashed');

		// The given password (but not the autogenerated password) should not be
		// mailed to the receiver of the share because permanent passwords are not enforced.
		$this->shareManager->expects($this->any())->method('shareApiLinkEnforcePassword')->willReturn(false);
		$this->config->expects($this->once())->method('getSystemValue')->with('sharing.enable_mail_link_password_expiration')->willReturn(false);
		$instance->expects($this->never())->method('autoGeneratePassword');

		// A password is set but no password sent via talk has been requested
		$instance->expects($this->once())->method('sendEmail')->with($share, ['receiver@example.com']);
		$instance->expects($this->once())->method('sendPassword')->with($share, 'password');
		$instance->expects($this->never())->method('sendPasswordToOwner');

		$this->assertSame($expectedShare, $instance->create($share));
		$instance->sendMailNotification($share);
	}

	public function testCreateSendPasswordByMailWithPasswordAndWithoutEnforcedPasswordProtectionWithoutPermanentPassword(): void {
		$expectedShare = $this->createMock(IShare::class);

		$node = $this->createMock(File::class);
		$node->expects($this->any())->method('getName')->willReturn('filename');

		$share = $this->createMock(IShare::class);
		$share->expects($this->any())->method('getSharedWith')->willReturn('receiver@example.com');
		$share->expects($this->any())->method('getSendPasswordByTalk')->willReturn(false);
		$share->expects($this->any())->method('getSharedBy')->willReturn('owner');
		$share->expects($this->any())->method('getNode')->willReturn($node);
		$share->expects($this->any())->method('getId')->willReturn(42);
		$share->expects($this->any())->method('getNote')->willReturn('');
		$share->expects($this->any())->method('getToken')->willReturn('token');

		$instance = $this->getInstance([
			'getSharedWith', 'createMailShare', 'getRawShare', 'createShareObject',
			'createShareActivity', 'autoGeneratePassword', 'createPasswordSendActivity',
			'sendEmail', 'sendPassword', 'sendPasswordToOwner',
		]);

		$instance->expects($this->once())->method('getSharedWith')->willReturn([]);
		$instance->expects($this->once())->method('createMailShare')->with($share)->willReturn(42);
		$instance->expects($this->once())->method('createShareActivity')->with($share);
		$instance->expects($this->once())->method('getRawShare')->with(42)->willReturn(['rawShare', 'password' => 'password']);
		$instance->expects($this->once())->method('createShareObject')->with(['rawShare', 'password' => 'password'])->willReturn($expectedShare);
		$share->expects($this->any())->method('getNode')->willReturn($node);

		$share->expects($this->any())->method('getPassword')->willReturn('password');
		$this->hasher->expects($this->once())->method('hash')->with('password')->willReturn('passwordHashed');
		$share->expects($this->once())->method('setPassword')->with('passwordHashed');

		// No password is generated, so no emails need to be sent
		// aside from the main email notification.
		$this->shareManager->expects($this->any())->method('shareApiLinkEnforcePassword')->willReturn(false);
		$instance->expects($this->never())->method('autoGeneratePassword');
		$this->config->expects($this->once())->method('getSystemValue')
			->with('sharing.enable_mail_link_password_expiration')
			->willReturn(true);

		// No password has been set and no password sent via talk has been requested,
		// but password has been enforced for the whole instance and will be generated.
		$instance->expects($this->once())->method('sendEmail')->with($share, ['receiver@example.com']);
		$instance->expects($this->never())->method('sendPassword');
		$instance->expects($this->never())->method('sendPasswordToOwner');

		$this->assertSame($expectedShare, $instance->create($share));
		$instance->sendMailNotification($share);
	}

	public function testCreateSendPasswordByMailWithEnforcedPasswordProtectionWithPermanentPassword(): void {
		$expectedShare = $this->createMock(IShare::class);

		$node = $this->createMock(File::class);
		$node->expects($this->any())->method('getName')->willReturn('filename');

		$share = $this->createMock(IShare::class);
		$share->expects($this->any())->method('getSharedWith')->willReturn('receiver@example.com');
		$share->expects($this->any())->method('getSendPasswordByTalk')->willReturn(false);
		$share->expects($this->any())->method('getSharedBy')->willReturn('owner');
		$share->expects($this->any())->method('getNode')->willReturn($node);
		$share->expects($this->any())->method('getId')->willReturn(42);
		$share->expects($this->any())->method('getNote')->willReturn('');
		$share->expects($this->any())->method('getToken')->willReturn('token');

		$this->urlGenerator->expects($this->once())->method('linkToRouteAbsolute')
			->with('files_sharing.sharecontroller.showShare', ['token' => 'token'])
			->willReturn('https://example.com/file.txt');

		$this->secureRandom->expects($this->once())
			->method('generate')
			->with(8, ISecureRandom::CHAR_HUMAN_READABLE)
			->willReturn('autogeneratedPassword');
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(new GenerateSecurePasswordEvent(PasswordContext::SHARING));

		$instance = $this->getInstance(['getSharedWith', 'createMailShare', 'getRawShare', 'createShareObject', 'createShareActivity', 'createPasswordSendActivity', 'sendPasswordToOwner']);

		$instance->expects($this->once())->method('getSharedWith')->willReturn([]);
		$instance->expects($this->once())->method('createMailShare')->with($share)->willReturn(42);
		$instance->expects($this->once())->method('createShareActivity')->with($share);
		$instance->expects($this->once())->method('getRawShare')->with(42)->willReturn(['rawShare', 'password' => 'autogeneratedPassword']);
		$instance->expects($this->once())->method('createShareObject')->with(['rawShare', 'password' => 'autogeneratedPassword'])->willReturn($expectedShare);

		// Initially not set, but will be set by the autoGeneratePassword method.
		$share->expects($this->exactly(3))->method('getPassword')->willReturnOnConsecutiveCalls(null, 'autogeneratedPassword', 'autogeneratedPassword');
		$this->hasher->expects($this->once())->method('hash')->with('autogeneratedPassword')->willReturn('autogeneratedPasswordHashed');
		$share->expects($this->once())->method('setPassword')->with('autogeneratedPasswordHashed');

		// The autogenerated password should be mailed to the receiver of the share because permanent passwords are enforced.
		$this->shareManager->expects($this->any())->method('shareApiLinkEnforcePassword')->willReturn(true);
		$this->config->expects($this->any())->method('getSystemValue')->with('sharing.enable_mail_link_password_expiration')->willReturn(false);
		$this->settingsManager->expects($this->any())->method('sendPasswordByMail')->willReturn(true);

		$message = $this->createMock(IMessage::class);
		$message->expects($this->exactly(2))->method('setTo')->with(['receiver@example.com']);
		$this->mailer->expects($this->exactly(2))->method('createMessage')->willReturn($message);
		$calls = [
			[
				'sharebymail.RecipientNotification',
				[
					'filename' => 'filename',
					'link' => 'https://example.com/file.txt',
					'initiator' => 'owner',
					'expiration' => null,
					'shareWith' => 'receiver@example.com',
					'note' => '',
				],
			],
			[
				'sharebymail.RecipientPasswordNotification',
				[
					'filename' => 'filename',
					'password' => 'autogeneratedPassword',
					'initiator' => 'owner',
					'initiatorEmail' => null,
					'shareWith' => 'receiver@example.com',
				],
			],
		];
		$this->mailer->expects($this->exactly(2))
			->method('createEMailTemplate')
			->willReturnCallback(function () use (&$calls) {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
				return $this->createMock(IEMailTemplate::class);
			});

		// Main email notification is sent as well as the password
		// to the recipient because shareApiLinkEnforcePassword is enabled.
		$this->mailer->expects($this->exactly(2))->method('send');
		$instance->expects($this->never())->method('sendPasswordToOwner');

		$this->assertSame($expectedShare, $instance->create($share));
		$instance->sendMailNotification($share);
	}

	public function testCreateSendPasswordByMailWithPasswordAndWithEnforcedPasswordProtectionWithPermanentPassword(): void {
		$expectedShare = $this->createMock(IShare::class);

		$node = $this->createMock(File::class);
		$node->expects($this->any())->method('getName')->willReturn('filename');

		$share = $this->createMock(IShare::class);
		$share->expects($this->any())->method('getSharedWith')->willReturn('receiver@example.com');
		$share->expects($this->any())->method('getSendPasswordByTalk')->willReturn(false);
		$share->expects($this->any())->method('getSharedBy')->willReturn('owner');
		$share->expects($this->any())->method('getNode')->willReturn($node);
		$share->expects($this->any())->method('getId')->willReturn(42);
		$share->expects($this->any())->method('getNote')->willReturn('');
		$share->expects($this->any())->method('getToken')->willReturn('token');

		$this->urlGenerator->expects($this->once())->method('linkToRouteAbsolute')
			->with('files_sharing.sharecontroller.showShare', ['token' => 'token'])
			->willReturn('https://example.com/file.txt');

		$instance = $this->getInstance(['getSharedWith', 'createMailShare', 'getRawShare', 'createShareObject', 'createShareActivity', 'autoGeneratePassword', 'createPasswordSendActivity', 'sendPasswordToOwner']);

		$instance->expects($this->once())->method('getSharedWith')->willReturn([]);
		$instance->expects($this->once())->method('createMailShare')->with($share)->willReturn(42);
		$instance->expects($this->once())->method('createShareActivity')->with($share);
		$instance->expects($this->once())->method('getRawShare')->with(42)->willReturn(['rawShare', 'password' => 'password']);
		$instance->expects($this->once())->method('createShareObject')->with(['rawShare', 'password' => 'password'])->willReturn($expectedShare);

		$share->expects($this->exactly(3))->method('getPassword')->willReturn('password');
		$this->hasher->expects($this->once())->method('hash')->with('password')->willReturn('passwordHashed');
		$share->expects($this->once())->method('setPassword')->with('passwordHashed');

		// The given password (but not the autogenerated password) should be
		// mailed to the receiver of the share.
		$this->shareManager->expects($this->any())->method('shareApiLinkEnforcePassword')->willReturn(true);
		$this->config->expects($this->any())->method('getSystemValue')->with('sharing.enable_mail_link_password_expiration')->willReturn(false);
		$this->settingsManager->expects($this->any())->method('sendPasswordByMail')->willReturn(true);
		$instance->expects($this->never())->method('autoGeneratePassword');

		$message = $this->createMock(IMessage::class);
		$message->expects($this->exactly(2))->method('setTo')->with(['receiver@example.com']);
		$this->mailer->expects($this->exactly(2))->method('createMessage')->willReturn($message);

		$calls = [
			[
				'sharebymail.RecipientNotification',
				[
					'filename' => 'filename',
					'link' => 'https://example.com/file.txt',
					'initiator' => 'owner',
					'expiration' => null,
					'shareWith' => 'receiver@example.com',
					'note' => '',
				],
			],
			[
				'sharebymail.RecipientPasswordNotification',
				[
					'filename' => 'filename',
					'password' => 'password',
					'initiator' => 'owner',
					'initiatorEmail' => null,
					'shareWith' => 'receiver@example.com',
				],
			],
		];
		$this->mailer->expects($this->exactly(2))
			->method('createEMailTemplate')
			->willReturnCallback(function () use (&$calls) {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
				return $this->createMock(IEMailTemplate::class);
			});

		// Main email notification is sent as well as the password
		// to the recipient because the password is set.
		$this->mailer->expects($this->exactly(2))->method('send');
		$instance->expects($this->never())->method('sendPasswordToOwner');

		$this->assertSame($expectedShare, $instance->create($share));
		$instance->sendMailNotification($share);
	}

	public function testCreateSendPasswordByTalkWithEnforcedPasswordProtectionWithPermanentPassword(): void {
		$expectedShare = $this->createMock(IShare::class);

		// The owner of the share.
		$owner = $this->createMock(IUser::class);
		$this->userManager->expects($this->any())->method('get')->with('owner')->willReturn($owner);
		$owner->expects($this->any())->method('getEMailAddress')->willReturn('owner@example.com');
		$owner->expects($this->any())->method('getDisplayName')->willReturn('owner');

		$node = $this->createMock(File::class);
		$node->expects($this->any())->method('getName')->willReturn('filename');

		$share = $this->createMock(IShare::class);
		$share->expects($this->any())->method('getSharedWith')->willReturn('receiver@example.com');
		$share->expects($this->any())->method('getSendPasswordByTalk')->willReturn(true);
		$share->expects($this->any())->method('getSharedBy')->willReturn('owner');
		$share->expects($this->any())->method('getNode')->willReturn($node);
		$share->expects($this->any())->method('getId')->willReturn(42);
		$share->expects($this->any())->method('getNote')->willReturn('');
		$share->expects($this->any())->method('getToken')->willReturn('token');

		$this->urlGenerator->expects($this->once())->method('linkToRouteAbsolute')
			->with('files_sharing.sharecontroller.showShare', ['token' => 'token'])
			->willReturn('https://example.com/file.txt');

		$instance = $this->getInstance(['getSharedWith', 'createMailShare', 'getRawShare', 'createShareObject', 'createShareActivity', 'autoGeneratePassword', 'createPasswordSendActivity']);

		$instance->expects($this->once())->method('getSharedWith')->willReturn([]);
		$instance->expects($this->once())->method('createMailShare')->with($share)->willReturn(42);
		$instance->expects($this->once())->method('createShareActivity')->with($share);
		$instance->expects($this->once())->method('getRawShare')->with(42)->willReturn(['rawShare', 'password' => 'autogeneratedPassword']);
		$instance->expects($this->once())->method('createShareObject')->with(['rawShare', 'password' => 'autogeneratedPassword'])->willReturn($expectedShare);

		$share->expects($this->exactly(4))->method('getPassword')->willReturnOnConsecutiveCalls(null, 'autogeneratedPassword', 'autogeneratedPassword', 'autogeneratedPassword');
		$this->hasher->expects($this->once())->method('hash')->with('autogeneratedPassword')->willReturn('autogeneratedPasswordHashed');
		$share->expects($this->once())->method('setPassword')->with('autogeneratedPasswordHashed');

		// The autogenerated password should be mailed to the owner of the share.
		$this->shareManager->expects($this->any())->method('shareApiLinkEnforcePassword')->willReturn(true);
		$this->config->expects($this->any())->method('getSystemValue')->with('sharing.enable_mail_link_password_expiration')->willReturn(false);
		$this->settingsManager->expects($this->any())->method('sendPasswordByMail')->willReturn(true);
		$instance->expects($this->once())->method('autoGeneratePassword')->with($share)->willReturn('autogeneratedPassword');

		$message = $this->createMock(IMessage::class);
		$setToCalls = [
			[['receiver@example.com']],
			[['owner@example.com' => 'owner']],
		];
		$message->expects($this->exactly(2))
			->method('setTo')
			->willReturnCallback(function () use (&$setToCalls, $message) {
				$expected = array_shift($setToCalls);
				$this->assertEquals($expected, func_get_args());
				return $message;
			});
		$this->mailer->expects($this->exactly(2))->method('createMessage')->willReturn($message);

		$calls = [
			[
				'sharebymail.RecipientNotification',
				[
					'filename' => 'filename',
					'link' => 'https://example.com/file.txt',
					'initiator' => 'owner',
					'expiration' => null,
					'shareWith' => 'receiver@example.com',
					'note' => '',
				],
			],
			[
				'sharebymail.OwnerPasswordNotification',
				[
					'filename' => 'filename',
					'password' => 'autogeneratedPassword',
					'initiator' => 'owner',
					'initiatorEmail' => 'owner@example.com',
					'shareWith' => 'receiver@example.com',
				],
			],
		];
		$this->mailer->expects($this->exactly(2))
			->method('createEMailTemplate')
			->willReturnCallback(function () use (&$calls) {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
				return $this->createMock(IEMailTemplate::class);
			});

		// Main email notification is sent as well as the password to owner
		// because the password is set and SendPasswordByTalk is enabled.
		$this->mailer->expects($this->exactly(2))->method('send');

		$this->assertSame($expectedShare, $instance->create($share));
		$instance->sendMailNotification($share);
	}

	// If attributes is set to multiple emails, use them as BCC
	public function sendNotificationToMultipleEmails() {
		$expectedShare = $this->createMock(IShare::class);

		$node = $this->createMock(File::class);
		$node->expects($this->any())->method('getName')->willReturn('filename');

		$share = $this->createMock(IShare::class);
		$share->expects($this->any())->method('getSharedWith')->willReturn('');
		$share->expects($this->any())->method('getSendPasswordByTalk')->willReturn(false);
		$share->expects($this->any())->method('getSharedBy')->willReturn('owner');
		$share->expects($this->any())->method('getNode')->willReturn($node);
		$share->expects($this->any())->method('getId')->willReturn(42);
		$share->expects($this->any())->method('getNote')->willReturn('');
		$share->expects($this->any())->method('getToken')->willReturn('token');

		$attributes = $this->createMock(IAttributes::class);
		$share->expects($this->any())->method('getAttributes')->willReturn($attributes);
		$attributes->expects($this->any())->method('getAttribute')->with('shareWith', 'emails')->willReturn([
			'receiver1@example.com',
			'receiver2@example.com',
			'receiver3@example.com',
		]);

		$instance = $this->getInstance(['getSharedWith', 'createMailShare', 'getRawShare', 'createShareObject', 'createShareActivity', 'autoGeneratePassword', 'createPasswordSendActivity', 'sendEmail', 'sendPassword', 'sendPasswordToOwner']);

		$instance->expects($this->once())->method('getSharedWith')->willReturn([]);
		$instance->expects($this->once())->method('createMailShare')->with($share)->willReturn(42);
		$instance->expects($this->once())->method('createShareActivity')->with($share);
		$instance->expects($this->once())->method('getRawShare')->with(42)->willReturn(['rawShare', 'password' => 'password']);
		$instance->expects($this->once())->method('createShareObject')->with(['rawShare', 'password' => 'password'])->willReturn($expectedShare);
		$share->expects($this->any())->method('getNode')->willReturn($node);

		$share->expects($this->any())->method('getPassword')->willReturn('password');
		$this->hasher->expects($this->once())->method('hash')->with('password')->willReturn('passwordHashed');
		$share->expects($this->once())->method('setPassword')->with('passwordHashed');

		// The given password (but not the autogenerated password) should not be
		// mailed to the receiver of the share because permanent passwords are not enforced.
		$this->shareManager->expects($this->any())->method('shareApiLinkEnforcePassword')->willReturn(false);
		$this->config->expects($this->once())->method('getSystemValue')->with('sharing.enable_mail_link_password_expiration')->willReturn(false);
		$instance->expects($this->never())->method('autoGeneratePassword');

		// A password is set but no password sent via talk has been requested
		$instance->expects($this->once())->method('sendEmail')
			->with($share, ['receiver1@example.com', 'receiver2@example.com', 'receiver3@example.com']);
		$instance->expects($this->once())->method('sendPassword')->with($share, 'password');
		$instance->expects($this->never())->method('sendPasswordToOwner');


		$message = $this->createMock(IMessage::class);
		$message->expects($this->never())->method('setTo');
		$message->expects($this->exactly(2))->method('setBcc')->with(['receiver1@example.com', 'receiver2@example.com', 'receiver3@example.com']);
		$this->mailer->expects($this->exactly(2))->method('createMessage')->willReturn($message);

		// Main email notification is sent as well as the password
		// to recipients because the password is set.
		$this->mailer->expects($this->exactly(2))->method('send');

		$this->assertSame($expectedShare, $instance->create($share));
		$instance->sendMailNotification($share);
	}

	public function testCreateFailed(): void {
		$this->expectException(\Exception::class);

		$this->share->expects($this->once())->method('getSharedWith')->willReturn('user1');
		$node = $this->createMock('OCP\Files\Node');
		$node->expects($this->any())->method('getName')->willReturn('fileName');
		$this->share->expects($this->any())->method('getNode')->willReturn($node);

		$instance = $this->getInstance(['getSharedWith', 'createMailShare', 'getRawShare', 'createShareObject']);

		$instance->expects($this->once())->method('getSharedWith')->willReturn(['found']);
		$instance->expects($this->never())->method('createMailShare');
		$instance->expects($this->never())->method('getRawShare');
		$instance->expects($this->never())->method('createShareObject');

		$this->assertSame('shareObject',
			$instance->create($this->share)
		);
	}

	public function testCreateMailShare(): void {
		$this->share->expects($this->any())->method('getToken')->willReturn('token');
		$this->share->expects($this->once())->method('setToken')->with('token');
		$this->share->expects($this->any())->method('getSharedBy')->willReturn('validby@valid.com');
		$this->share->expects($this->any())->method('getSharedWith')->willReturn('validwith@valid.com');
		$this->share->expects($this->any())->method('getNote')->willReturn('Check this!');
		$this->share->expects($this->any())->method('getMailSend')->willReturn(true);

		$node = $this->createMock('OCP\Files\Node');
		$node->expects($this->any())->method('getName')->willReturn('fileName');
		$this->share->expects($this->any())->method('getNode')->willReturn($node);

		$instance = $this->getInstance(['generateToken', 'addShareToDB', 'sendMailNotification']);

		$instance->expects($this->once())->method('generateToken')->willReturn('token');
		$instance->expects($this->once())->method('addShareToDB')->willReturn(42);

		// The manager handle the mail sending
		$instance->expects($this->never())->method('sendMailNotification');

		$this->assertSame(42,
			$this->invokePrivate($instance, 'createMailShare', [$this->share])
		);
	}

	public function testGenerateToken(): void {
		$instance = $this->getInstance();

		$this->secureRandom->expects($this->once())->method('generate')->willReturn('token');

		$this->assertSame('token',
			$this->invokePrivate($instance, 'generateToken')
		);
	}

	public function testAddShareToDB(): void {
		$itemSource = 11;
		$itemType = 'file';
		$shareWith = 'user@server.com';
		$sharedBy = 'user1';
		$uidOwner = 'user2';
		$permissions = 1;
		$token = 'token';
		$password = 'password';
		$sendPasswordByTalk = true;
		$hideDownload = true;
		$label = 'label';
		$expiration = new \DateTime();
		$passwordExpirationTime = new \DateTime();


		$instance = $this->getInstance();
		$id = $this->invokePrivate(
			$instance,
			'addShareToDB',
			[
				$itemSource,
				$itemType,
				$shareWith,
				$sharedBy,
				$uidOwner,
				$permissions,
				$token,
				$password,
				$passwordExpirationTime,
				$sendPasswordByTalk,
				$hideDownload,
				$label,
				$expiration
			]
		);

		$qb = $this->connection->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));

		$qResult = $qb->executeQuery();
		$result = $qResult->fetchAllAssociative();
		$qResult->closeCursor();

		$this->assertSame(1, count($result));

		$this->assertSame($itemSource, (int)$result[0]['item_source']);
		$this->assertSame($itemType, $result[0]['item_type']);
		$this->assertSame($shareWith, $result[0]['share_with']);
		$this->assertSame($sharedBy, $result[0]['uid_initiator']);
		$this->assertSame($uidOwner, $result[0]['uid_owner']);
		$this->assertSame($permissions, (int)$result[0]['permissions']);
		$this->assertSame($token, $result[0]['token']);
		$this->assertSame($password, $result[0]['password']);
		$this->assertSame($passwordExpirationTime->getTimestamp(), \DateTime::createFromFormat('Y-m-d H:i:s', $result[0]['password_expiration_time'])->getTimestamp());
		$this->assertSame($sendPasswordByTalk, (bool)$result[0]['password_by_talk']);
		$this->assertSame($hideDownload, (bool)$result[0]['hide_download']);
		$this->assertSame($label, $result[0]['label']);
		$this->assertSame($expiration->getTimestamp(), \DateTime::createFromFormat('Y-m-d H:i:s', $result[0]['expiration'])->getTimestamp());
	}

	public function testUpdate(): void {
		$itemSource = 11;
		$itemType = 'file';
		$shareWith = 'user@server.com';
		$sharedBy = 'user1';
		$uidOwner = 'user2';
		$permissions = 1;
		$token = 'token';
		$note = 'personal note';


		$instance = $this->getInstance();

		$id = $this->createDummyShare($itemType, $itemSource, $shareWith, $sharedBy, $uidOwner, $permissions, $token, $note);

		$this->share->expects($this->once())->method('getPermissions')->willReturn($permissions + 1);
		$this->share->expects($this->once())->method('getShareOwner')->willReturn($uidOwner);
		$this->share->expects($this->once())->method('getSharedBy')->willReturn($sharedBy);
		$this->share->expects($this->any())->method('getNote')->willReturn($note);
		$this->share->expects($this->atLeastOnce())->method('getId')->willReturn($id);
		$this->share->expects($this->atLeastOnce())->method('getNodeId')->willReturn($itemSource);
		$this->share->expects($this->once())->method('getSharedWith')->willReturn($shareWith);

		$this->assertSame($this->share,
			$instance->update($this->share)
		);

		$qb = $this->connection->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));

		$qResult = $qb->executeQuery();
		$result = $qResult->fetchAllAssociative();
		$qResult->closeCursor();

		$this->assertSame(1, count($result));

		$this->assertSame($itemSource, (int)$result[0]['item_source']);
		$this->assertSame($itemType, $result[0]['item_type']);
		$this->assertSame($shareWith, $result[0]['share_with']);
		$this->assertSame($sharedBy, $result[0]['uid_initiator']);
		$this->assertSame($uidOwner, $result[0]['uid_owner']);
		$this->assertSame($permissions + 1, (int)$result[0]['permissions']);
		$this->assertSame($token, $result[0]['token']);
		$this->assertSame($note, $result[0]['note']);
	}

	public static function dataUpdateSendPassword(): array {
		return [
			['password', 'hashed', 'hashed new', false, false, true],
			['', 'hashed', 'hashed new', false, false, false],
			[null, 'hashed', 'hashed new', false, false, false],
			['password', 'hashed', 'hashed', false, false, false],
			['password', 'hashed', 'hashed new', false, true, false],
			['password', 'hashed', 'hashed new', true, false, true],
			['password', 'hashed', 'hashed', true, false, true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataUpdateSendPassword')]
	public function testUpdateSendPassword(?string $plainTextPassword, string $originalPassword, string $newPassword, bool $originalSendPasswordByTalk, bool $newSendPasswordByTalk, bool $sendMail): void {
		$node = $this->createMock(File::class);
		$node->expects($this->any())->method('getName')->willReturn('filename');

		$this->settingsManager->method('sendPasswordByMail')->willReturn(true);

		$originalShare = $this->createMock(IShare::class);
		$originalShare->expects($this->any())->method('getSharedWith')->willReturn('receiver@example.com');
		$originalShare->expects($this->any())->method('getNode')->willReturn($node);
		$originalShare->expects($this->any())->method('getId')->willReturn(42);
		$originalShare->expects($this->any())->method('getPassword')->willReturn($originalPassword);
		$originalShare->expects($this->any())->method('getSendPasswordByTalk')->willReturn($originalSendPasswordByTalk);

		$share = $this->createMock(IShare::class);
		$share->expects($this->any())->method('getSharedWith')->willReturn('receiver@example.com');
		$share->expects($this->any())->method('getNode')->willReturn($node);
		$share->expects($this->any())->method('getId')->willReturn(42);
		$share->expects($this->any())->method('getPassword')->willReturn($newPassword);
		$share->expects($this->any())->method('getSendPasswordByTalk')->willReturn($newSendPasswordByTalk);

		if ($sendMail) {
			$this->mailer->expects($this->once())->method('createEMailTemplate')->with('sharebymail.RecipientPasswordNotification', [
				'filename' => 'filename',
				'password' => $plainTextPassword,
				'initiator' => null,
				'initiatorEmail' => null,
				'shareWith' => 'receiver@example.com',
			]);
			$this->mailer->expects($this->once())->method('send');
		} else {
			$this->mailer->expects($this->never())->method('send');
		}

		$instance = $this->getInstance(['getShareById', 'createPasswordSendActivity']);
		$instance->expects($this->once())->method('getShareById')->willReturn($originalShare);

		$this->assertSame($share,
			$instance->update($share, $plainTextPassword)
		);
	}

	public function testDelete(): void {
		$instance = $this->getInstance(['removeShareFromTable', 'createShareActivity']);
		$this->share->expects($this->once())->method('getId')->willReturn(42);
		$instance->expects($this->once())->method('removeShareFromTable')->with(42);
		$instance->expects($this->once())->method('createShareActivity')->with($this->share, 'unshare');
		$instance->delete($this->share);
	}

	public function testGetShareById(): void {
		$instance = $this->getInstance(['createShareObject']);

		$itemSource = 11;
		$itemType = 'file';
		$shareWith = 'user@server.com';
		$sharedBy = 'user1';
		$uidOwner = 'user2';
		$permissions = 1;
		$token = 'token';

		$this->createDummyShare($itemType, $itemSource, $shareWith, 'user1wrong', 'user2wrong', $permissions, $token);
		$id2 = $this->createDummyShare($itemType, $itemSource, $shareWith, $sharedBy, $uidOwner, $permissions, $token);

		$instance->expects($this->once())->method('createShareObject')
			->willReturnCallback(
				function ($data) use ($uidOwner, $sharedBy, $id2) {
					$this->assertSame($uidOwner, $data['uid_owner']);
					$this->assertSame($sharedBy, $data['uid_initiator']);
					$this->assertSame($id2, (int)$data['id']);
					return $this->share;
				}
			);

		$result = $instance->getShareById($id2);

		$this->assertInstanceOf('OCP\Share\IShare', $result);
	}


	public function testGetShareByIdFailed(): void {
		$this->expectException(ShareNotFound::class);

		$instance = $this->getInstance(['createShareObject']);

		$itemSource = 11;
		$itemType = 'file';
		$shareWith = 'user@server.com';
		$sharedBy = 'user1';
		$uidOwner = 'user2';
		$permissions = 1;
		$token = 'token';

		$id = $this->createDummyShare($itemType, $itemSource, $shareWith, $sharedBy, $uidOwner, $permissions, $token);

		$instance->getShareById($id + 1);
	}

	public function testGetShareByPath(): void {
		$itemSource = 11;
		$itemType = 'file';
		$shareWith = 'user@server.com';
		$sharedBy = 'user1';
		$uidOwner = 'user2';
		$permissions = 1;
		$token = 'token';

		$node = $this->createMock(Node::class);
		$node->expects($this->once())->method('getId')->willReturn($itemSource);


		$instance = $this->getInstance(['createShareObject']);

		$this->createDummyShare($itemType, 111, $shareWith, $sharedBy, $uidOwner, $permissions, $token);
		$id = $this->createDummyShare($itemType, $itemSource, $shareWith, $sharedBy, $uidOwner, $permissions, $token);

		$instance->expects($this->once())->method('createShareObject')
			->willReturnCallback(
				function ($data) use ($uidOwner, $sharedBy, $id) {
					$this->assertSame($uidOwner, $data['uid_owner']);
					$this->assertSame($sharedBy, $data['uid_initiator']);
					$this->assertSame($id, (int)$data['id']);
					return $this->share;
				}
			);

		$result = $instance->getSharesByPath($node);

		$this->assertTrue(is_array($result));
		$this->assertSame(1, count($result));
		$this->assertInstanceOf('OCP\Share\IShare', $result[0]);
	}

	public function testGetShareByToken(): void {
		$itemSource = 11;
		$itemType = 'file';
		$shareWith = 'user@server.com';
		$sharedBy = 'user1';
		$uidOwner = 'user2';
		$permissions = 1;
		$token = 'token';

		$instance = $this->getInstance(['createShareObject']);

		$idMail = $this->createDummyShare($itemType, $itemSource, $shareWith, $sharedBy, $uidOwner, $permissions, $token);
		$idPublic = $this->createDummyShare($itemType, $itemSource, $shareWith, $sharedBy, $uidOwner, $permissions, $token, '', IShare::TYPE_LINK);

		$this->assertTrue($idMail !== $idPublic);

		$instance->expects($this->once())->method('createShareObject')
			->willReturnCallback(
				function ($data) use ($idMail) {
					$this->assertSame($idMail, (int)$data['id']);
					return $this->share;
				}
			);

		$result = $instance->getShareByToken('token');

		$this->assertInstanceOf('OCP\Share\IShare', $result);
	}


	public function testGetShareByTokenFailed(): void {
		$this->expectException(ShareNotFound::class);


		$itemSource = 11;
		$itemType = 'file';
		$shareWith = 'user@server.com';
		$sharedBy = 'user1';
		$uidOwner = 'user2';
		$permissions = 1;
		$token = 'token';

		$instance = $this->getInstance(['createShareObject']);

		$idMail = $this->createDummyShare($itemType, $itemSource, $shareWith, $sharedBy, $uidOwner, $permissions, $token);
		$idPublic = $this->createDummyShare($itemType, $itemSource, $shareWith, $sharedBy, $uidOwner, $permissions, 'token2', '', IShare::TYPE_LINK);

		$this->assertTrue($idMail !== $idPublic);

		$this->assertInstanceOf('OCP\Share\IShare',
			$instance->getShareByToken('token2')
		);
	}

	public function testRemoveShareFromTable(): void {
		$itemSource = 11;
		$itemType = 'file';
		$shareWith = 'user@server.com';
		$sharedBy = 'user1';
		$uidOwner = 'user2';
		$permissions = 1;
		$token = 'token';

		$instance = $this->getInstance();

		$id = $this->createDummyShare($itemType, $itemSource, $shareWith, $sharedBy, $uidOwner, $permissions, $token);

		$query = $this->connection->getQueryBuilder();
		$query->select('*')->from('share')
			->where($query->expr()->eq('id', $query->createNamedParameter($id)));

		$result = $query->executeQuery();
		$before = $result->fetchAllAssociative();
		$result->closeCursor();

		$this->assertTrue(is_array($before));
		$this->assertSame(1, count($before));

		$this->invokePrivate($instance, 'removeShareFromTable', [$id]);

		$query = $this->connection->getQueryBuilder();
		$query->select('*')->from('share')
			->where($query->expr()->eq('id', $query->createNamedParameter($id)));

		$result = $query->executeQuery();
		$after = $result->fetchAllAssociative();
		$result->closeCursor();

		$this->assertTrue(is_array($after));
		$this->assertEmpty($after);
	}

	public function testUserDeleted(): void {
		$itemSource = 11;
		$itemType = 'file';
		$shareWith = 'user@server.com';
		$sharedBy = 'user1';
		$uidOwner = 'user2';
		$permissions = 1;
		$token = 'token';

		$this->createDummyShare($itemType, $itemSource, $shareWith, $sharedBy, $uidOwner, $permissions, $token);
		$id = $this->createDummyShare($itemType, $itemSource, $shareWith, $sharedBy, 'user2Wrong', $permissions, $token);

		$query = $this->connection->getQueryBuilder();
		$query->select('*')->from('share');

		$result = $query->executeQuery();
		$before = $result->fetchAllAssociative();
		$result->closeCursor();

		$this->assertTrue(is_array($before));
		$this->assertSame(2, count($before));


		$instance = $this->getInstance();

		$instance->userDeleted($uidOwner, IShare::TYPE_EMAIL);

		$query = $this->connection->getQueryBuilder();
		$query->select('*')->from('share');

		$result = $query->executeQuery();
		$after = $result->fetchAllAssociative();
		$result->closeCursor();

		$this->assertTrue(is_array($after));
		$this->assertSame(1, count($after));
		$this->assertSame($id, (int)$after[0]['id']);
	}

	public function testGetRawShare(): void {
		$itemSource = 11;
		$itemType = 'file';
		$shareWith = 'user@server.com';
		$sharedBy = 'user1';
		$uidOwner = 'user2';
		$permissions = 1;
		$token = 'token';

		$instance = $this->getInstance();

		$id = $this->createDummyShare($itemType, $itemSource, $shareWith, $sharedBy, $uidOwner, $permissions, $token);

		$result = $this->invokePrivate($instance, 'getRawShare', [$id]);

		$this->assertTrue(is_array($result));
		$this->assertSame($itemSource, (int)$result['item_source']);
		$this->assertSame($itemType, $result['item_type']);
		$this->assertSame($shareWith, $result['share_with']);
		$this->assertSame($sharedBy, $result['uid_initiator']);
		$this->assertSame($uidOwner, $result['uid_owner']);
		$this->assertSame($permissions, (int)$result['permissions']);
		$this->assertSame($token, $result['token']);
	}


	public function testGetRawShareFailed(): void {
		$this->expectException(ShareNotFound::class);

		$itemSource = 11;
		$itemType = 'file';
		$shareWith = 'user@server.com';
		$sharedBy = 'user1';
		$uidOwner = 'user2';
		$permissions = 1;
		$token = 'token';

		$instance = $this->getInstance();

		$id = $this->createDummyShare($itemType, $itemSource, $shareWith, $sharedBy, $uidOwner, $permissions, $token);

		$this->invokePrivate($instance, 'getRawShare', [$id + 1]);
	}

	private function createDummyShare($itemType, $itemSource, $shareWith, $sharedBy, $uidOwner, $permissions, $token, $note = '', $shareType = IShare::TYPE_EMAIL) {
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('share')
			->setValue('share_type', $qb->createNamedParameter($shareType))
			->setValue('item_type', $qb->createNamedParameter($itemType))
			->setValue('item_source', $qb->createNamedParameter($itemSource))
			->setValue('file_source', $qb->createNamedParameter($itemSource))
			->setValue('share_with', $qb->createNamedParameter($shareWith))
			->setValue('uid_owner', $qb->createNamedParameter($uidOwner))
			->setValue('uid_initiator', $qb->createNamedParameter($sharedBy))
			->setValue('permissions', $qb->createNamedParameter($permissions))
			->setValue('token', $qb->createNamedParameter($token))
			->setValue('note', $qb->createNamedParameter($note))
			->setValue('stime', $qb->createNamedParameter(time()));

		/*
		 * Added to fix https://github.com/owncloud/core/issues/22215
		 * Can be removed once we get rid of ajax/share.php
		 */
		$qb->setValue('file_target', $qb->createNamedParameter(''));

		$qb->executeStatement();
		$id = $qb->getLastInsertId();

		return (int)$id;
	}

	public function testGetSharesInFolder(): void {
		$userManager = Server::get(IUserManager::class);
		$rootFolder = Server::get(IRootFolder::class);

		$this->shareManager->expects($this->any())
			->method('newShare')
			->willReturn(new Share($rootFolder, $userManager));

		$provider = $this->getInstance(['sendMailNotification', 'createShareActivity']);

		$u1 = $userManager->createUser('testFed', md5((string)time()));
		$u2 = $userManager->createUser('testFed2', md5((string)time()));

		$folder1 = $rootFolder->getUserFolder($u1->getUID())->newFolder('foo');
		$file1 = $folder1->newFile('bar1');
		$file2 = $folder1->newFile('bar2');

		$share1 = $this->shareManager->newShare();
		$share1->setSharedWith('user@server.com')
			->setSharedBy($u1->getUID())
			->setShareOwner($u1->getUID())
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file1);
		$provider->create($share1);

		$share2 = $this->shareManager->newShare();
		$share2->setSharedWith('user@server.com')
			->setSharedBy($u2->getUID())
			->setShareOwner($u1->getUID())
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file2);
		$provider->create($share2);

		$result = $provider->getSharesInFolder($u1->getUID(), $folder1, false);
		$this->assertCount(1, $result);
		$this->assertCount(1, $result[$file1->getId()]);

		$result = $provider->getSharesInFolder($u1->getUID(), $folder1, true);
		$this->assertCount(2, $result);
		$this->assertCount(1, $result[$file1->getId()]);
		$this->assertCount(1, $result[$file2->getId()]);

		$u1->delete();
		$u2->delete();
	}

	public function testGetAccessList(): void {
		$userManager = Server::get(IUserManager::class);
		$rootFolder = Server::get(IRootFolder::class);

		$this->shareManager->expects($this->any())
			->method('newShare')
			->willReturn(new Share($rootFolder, $userManager));

		$provider = $this->getInstance(['sendMailNotification', 'createShareActivity']);

		$u1 = $userManager->createUser('testFed', md5((string)time()));
		$u2 = $userManager->createUser('testFed2', md5((string)time()));

		$folder = $rootFolder->getUserFolder($u1->getUID())->newFolder('foo');

		$accessList = $provider->getAccessList([$folder], true);
		$this->assertArrayHasKey('public', $accessList);
		$this->assertFalse($accessList['public']);
		$accessList = $provider->getAccessList([$folder], false);
		$this->assertArrayHasKey('public', $accessList);
		$this->assertFalse($accessList['public']);

		$share1 = $this->shareManager->newShare();
		$share1->setSharedWith('user@server.com')
			->setSharedBy($u1->getUID())
			->setShareOwner($u1->getUID())
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($folder);
		$share1 = $provider->create($share1);

		$share2 = $this->shareManager->newShare();
		$share2->setSharedWith('user2@server.com')
			->setSharedBy($u2->getUID())
			->setShareOwner($u1->getUID())
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($folder);
		$share2 = $provider->create($share2);

		$accessList = $provider->getAccessList([$folder], true);
		$this->assertArrayHasKey('public', $accessList);
		$this->assertTrue($accessList['public']);
		$accessList = $provider->getAccessList([$folder], false);
		$this->assertArrayHasKey('public', $accessList);
		$this->assertTrue($accessList['public']);

		$provider->delete($share2);

		$accessList = $provider->getAccessList([$folder], true);
		$this->assertArrayHasKey('public', $accessList);
		$this->assertTrue($accessList['public']);
		$accessList = $provider->getAccessList([$folder], false);
		$this->assertArrayHasKey('public', $accessList);
		$this->assertTrue($accessList['public']);

		$provider->delete($share1);

		$accessList = $provider->getAccessList([$folder], true);
		$this->assertArrayHasKey('public', $accessList);
		$this->assertFalse($accessList['public']);
		$accessList = $provider->getAccessList([$folder], false);
		$this->assertArrayHasKey('public', $accessList);
		$this->assertFalse($accessList['public']);

		$u1->delete();
		$u2->delete();
	}

	public function testSendMailNotificationWithSameUserAndUserEmail(): void {
		$provider = $this->getInstance();
		$user = $this->createMock(IUser::class);
		$this->settingsManager->expects($this->any())->method('replyToInitiator')->willReturn(true);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('OwnerUser')
			->willReturn($user);
		$user
			->expects($this->once())
			->method('getDisplayName')
			->willReturn('Mrs. Owner User');
		$message = $this->createMock(Message::class);
		$this->mailer
			->expects($this->once())
			->method('createMessage')
			->willReturn($message);
		$template = $this->createMock(IEMailTemplate::class);
		$this->mailer
			->expects($this->once())
			->method('createEMailTemplate')
			->willReturn($template);
		$template
			->expects($this->once())
			->method('addHeader');
		$template
			->expects($this->once())
			->method('addHeading')
			->with('Mrs. Owner User shared file.txt with you');
		$template
			->expects($this->once())
			->method('addBodyButton')
			->with(
				'Open file.txt',
				'https://example.com/file.txt'
			);
		$message
			->expects($this->once())
			->method('setTo')
			->with(['john@doe.com']);
		$this->defaults
			->expects($this->once())
			->method('getName')
			->willReturn('UnitTestCloud');
		$message
			->expects($this->once())
			->method('setFrom')
			->with([
				Util::getDefaultEmailAddress('UnitTestCloud') => 'Mrs. Owner User via UnitTestCloud'
			]);
		$user
			->expects($this->once())
			->method('getEMailAddress')
			->willReturn('owner@example.com');
		$message
			->expects($this->once())
			->method('setReplyTo')
			->with(['owner@example.com' => 'Mrs. Owner User']);
		$this->defaults
			->expects($this->exactly(2))
			->method('getSlogan')
			->willReturn('Testing like 1990');
		$template
			->expects($this->once())
			->method('addFooter')
			->with('UnitTestCloud - Testing like 1990');
		$template
			->expects($this->once())
			->method('setSubject')
			->with('Mrs. Owner User shared file.txt with you');
		$message
			->expects($this->once())
			->method('useTemplate')
			->with($template);

		$this->mailer
			->expects($this->once())
			->method('send')
			->with($message);

		$this->urlGenerator->expects($this->once())->method('linkToRouteAbsolute')
			->with('files_sharing.sharecontroller.showShare', ['token' => 'token'])
			->willReturn('https://example.com/file.txt');

		$node = $this->createMock(File::class);
		$node->expects($this->any())->method('getName')->willReturn('file.txt');

		$share = $this->createMock(IShare::class);
		$share->expects($this->any())->method('getSharedBy')->willReturn('OwnerUser');
		$share->expects($this->any())->method('getSharedWith')->willReturn('john@doe.com');
		$share->expects($this->any())->method('getNode')->willReturn($node);
		$share->expects($this->any())->method('getId')->willReturn(42);
		$share->expects($this->any())->method('getNote')->willReturn('');
		$share->expects($this->any())->method('getToken')->willReturn('token');

		self::invokePrivate(
			$provider,
			'sendMailNotification',
			[$share]
		);
	}

	public function testSendMailNotificationWithSameUserAndUserEmailAndNote(): void {
		$provider = $this->getInstance();
		$user = $this->createMock(IUser::class);
		$this->settingsManager->expects($this->any())->method('replyToInitiator')->willReturn(true);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('OwnerUser')
			->willReturn($user);
		$user
			->expects($this->once())
			->method('getDisplayName')
			->willReturn('Mrs. Owner User');
		$message = $this->createMock(Message::class);
		$this->mailer
			->expects($this->once())
			->method('createMessage')
			->willReturn($message);
		$template = $this->createMock(IEMailTemplate::class);
		$this->mailer
			->expects($this->once())
			->method('createEMailTemplate')
			->willReturn($template);
		$template
			->expects($this->once())
			->method('addHeader');
		$template
			->expects($this->once())
			->method('addHeading')
			->with('Mrs. Owner User shared file.txt with you');

		$this->urlGenerator->expects($this->once())->method('imagePath')
			->with('core', 'caldav/description.png')
			->willReturn('core/img/caldav/description.png');
		$this->urlGenerator->expects($this->once())->method('getAbsoluteURL')
			->with('core/img/caldav/description.png')
			->willReturn('https://example.com/core/img/caldav/description.png');
		$template
			->expects($this->once())
			->method('addBodyListItem')
			->with(
				'This is a note to the recipient',
				'Note:',
				'https://example.com/core/img/caldav/description.png',
				'This is a note to the recipient'
			);
		$template
			->expects($this->once())
			->method('addBodyButton')
			->with(
				'Open file.txt',
				'https://example.com/file.txt'
			);
		$message
			->expects($this->once())
			->method('setTo')
			->with(['john@doe.com']);
		$this->defaults
			->expects($this->once())
			->method('getName')
			->willReturn('UnitTestCloud');
		$message
			->expects($this->once())
			->method('setFrom')
			->with([
				Util::getDefaultEmailAddress('UnitTestCloud') => 'Mrs. Owner User via UnitTestCloud'
			]);
		$user
			->expects($this->once())
			->method('getEMailAddress')
			->willReturn('owner@example.com');
		$message
			->expects($this->once())
			->method('setReplyTo')
			->with(['owner@example.com' => 'Mrs. Owner User']);
		$this->defaults
			->expects($this->exactly(2))
			->method('getSlogan')
			->willReturn('Testing like 1990');
		$template
			->expects($this->once())
			->method('addFooter')
			->with('UnitTestCloud - Testing like 1990');
		$template
			->expects($this->once())
			->method('setSubject')
			->with('Mrs. Owner User shared file.txt with you');
		$message
			->expects($this->once())
			->method('useTemplate')
			->with($template);

		$this->mailer
			->expects($this->once())
			->method('send')
			->with($message);

		$this->urlGenerator->expects($this->once())->method('linkToRouteAbsolute')
			->with('files_sharing.sharecontroller.showShare', ['token' => 'token'])
			->willReturn('https://example.com/file.txt');

		$node = $this->createMock(File::class);
		$node->expects($this->any())->method('getName')->willReturn('file.txt');

		$share = $this->createMock(IShare::class);
		$share->expects($this->any())->method('getSharedBy')->willReturn('OwnerUser');
		$share->expects($this->any())->method('getSharedWith')->willReturn('john@doe.com');
		$share->expects($this->any())->method('getNode')->willReturn($node);
		$share->expects($this->any())->method('getId')->willReturn(42);
		$share->expects($this->any())->method('getNote')->willReturn('This is a note to the recipient');
		$share->expects($this->any())->method('getToken')->willReturn('token');

		self::invokePrivate(
			$provider,
			'sendMailNotification',
			[$share]
		);
	}

	public function testSendMailNotificationWithSameUserAndUserEmailAndExpiration(): void {
		$provider = $this->getInstance();
		$user = $this->createMock(IUser::class);
		$this->settingsManager->expects($this->any())->method('replyToInitiator')->willReturn(true);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('OwnerUser')
			->willReturn($user);
		$user
			->expects($this->once())
			->method('getDisplayName')
			->willReturn('Mrs. Owner User');
		$message = $this->createMock(Message::class);
		$this->mailer
			->expects($this->once())
			->method('createMessage')
			->willReturn($message);
		$template = $this->createMock(IEMailTemplate::class);
		$this->mailer
			->expects($this->once())
			->method('createEMailTemplate')
			->willReturn($template);
		$template
			->expects($this->once())
			->method('addHeader');
		$template
			->expects($this->once())
			->method('addHeading')
			->with('Mrs. Owner User shared file.txt with you');

		$expiration = new DateTime('2001-01-01');
		$this->l->expects($this->once())
			->method('l')
			->with('date', $expiration, ['width' => 'medium'])
			->willReturn('2001-01-01');
		$this->urlGenerator->expects($this->once())->method('imagePath')
			->with('core', 'caldav/time.png')
			->willReturn('core/img/caldav/time.png');
		$this->urlGenerator->expects($this->once())->method('getAbsoluteURL')
			->with('core/img/caldav/time.png')
			->willReturn('https://example.com/core/img/caldav/time.png');
		$template
			->expects($this->once())
			->method('addBodyListItem')
			->with(
				'This share is valid until 2001-01-01 at midnight',
				'Expiration:',
				'https://example.com/core/img/caldav/time.png',
			);

		$template
			->expects($this->once())
			->method('addBodyButton')
			->with(
				'Open file.txt',
				'https://example.com/file.txt'
			);
		$message
			->expects($this->once())
			->method('setTo')
			->with(['john@doe.com']);
		$this->defaults
			->expects($this->once())
			->method('getName')
			->willReturn('UnitTestCloud');
		$message
			->expects($this->once())
			->method('setFrom')
			->with([
				Util::getDefaultEmailAddress('UnitTestCloud') => 'Mrs. Owner User via UnitTestCloud'
			]);
		$user
			->expects($this->once())
			->method('getEMailAddress')
			->willReturn('owner@example.com');
		$message
			->expects($this->once())
			->method('setReplyTo')
			->with(['owner@example.com' => 'Mrs. Owner User']);
		$this->defaults
			->expects($this->exactly(2))
			->method('getSlogan')
			->willReturn('Testing like 1990');
		$template
			->expects($this->once())
			->method('addFooter')
			->with('UnitTestCloud - Testing like 1990');
		$template
			->expects($this->once())
			->method('setSubject')
			->with('Mrs. Owner User shared file.txt with you');
		$message
			->expects($this->once())
			->method('useTemplate')
			->with($template);

		$this->mailer
			->expects($this->once())
			->method('send')
			->with($message);

		$this->urlGenerator->expects($this->once())->method('linkToRouteAbsolute')
			->with('files_sharing.sharecontroller.showShare', ['token' => 'token'])
			->willReturn('https://example.com/file.txt');

		$node = $this->createMock(File::class);
		$node->expects($this->any())->method('getName')->willReturn('file.txt');

		$share = $this->createMock(IShare::class);
		$share->expects($this->any())->method('getSharedBy')->willReturn('OwnerUser');
		$share->expects($this->any())->method('getSharedWith')->willReturn('john@doe.com');
		$share->expects($this->any())->method('getNode')->willReturn($node);
		$share->expects($this->any())->method('getId')->willReturn(42);
		$share->expects($this->any())->method('getNote')->willReturn('');
		$share->expects($this->any())->method('getExpirationDate')->willReturn($expiration);
		$share->expects($this->any())->method('getToken')->willReturn('token');

		self::invokePrivate(
			$provider,
			'sendMailNotification',
			[$share]
		);
	}

	public function testSendMailNotificationWithDifferentUserAndNoUserEmail(): void {
		$provider = $this->getInstance();
		$initiatorUser = $this->createMock(IUser::class);
		$this->settingsManager->expects($this->any())->method('replyToInitiator')->willReturn(true);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('InitiatorUser')
			->willReturn($initiatorUser);
		$initiatorUser
			->expects($this->once())
			->method('getDisplayName')
			->willReturn('Mr. Initiator User');
		$message = $this->createMock(Message::class);
		$this->mailer
			->expects($this->once())
			->method('createMessage')
			->willReturn($message);
		$template = $this->createMock(IEMailTemplate::class);
		$this->mailer
			->expects($this->once())
			->method('createEMailTemplate')
			->willReturn($template);
		$template
			->expects($this->once())
			->method('addHeader');
		$template
			->expects($this->once())
			->method('addHeading')
			->with('Mr. Initiator User shared file.txt with you');
		$template
			->expects($this->once())
			->method('addBodyButton')
			->with(
				'Open file.txt',
				'https://example.com/file.txt'
			);
		$message
			->expects($this->once())
			->method('setTo')
			->with(['john@doe.com']);
		$this->defaults
			->expects($this->once())
			->method('getName')
			->willReturn('UnitTestCloud');
		$message
			->expects($this->once())
			->method('setFrom')
			->with([
				Util::getDefaultEmailAddress('UnitTestCloud') => 'Mr. Initiator User via UnitTestCloud'
			]);
		$message
			->expects($this->never())
			->method('setReplyTo');
		$template
			->expects($this->once())
			->method('addFooter')
			->with('');
		$template
			->expects($this->once())
			->method('setSubject')
			->with('Mr. Initiator User shared file.txt with you');
		$message
			->expects($this->once())
			->method('useTemplate')
			->with($template);

		$this->mailer
			->expects($this->once())
			->method('send')
			->with($message);

		$this->urlGenerator->expects($this->once())->method('linkToRouteAbsolute')
			->with('files_sharing.sharecontroller.showShare', ['token' => 'token'])
			->willReturn('https://example.com/file.txt');

		$node = $this->createMock(File::class);
		$node->expects($this->any())->method('getName')->willReturn('file.txt');

		$share = $this->createMock(IShare::class);
		$share->expects($this->any())->method('getSharedBy')->willReturn('InitiatorUser');
		$share->expects($this->any())->method('getSharedWith')->willReturn('john@doe.com');
		$share->expects($this->any())->method('getNode')->willReturn($node);
		$share->expects($this->any())->method('getId')->willReturn(42);
		$share->expects($this->any())->method('getNote')->willReturn('');
		$share->expects($this->any())->method('getToken')->willReturn('token');

		self::invokePrivate(
			$provider,
			'sendMailNotification',
			[$share]
		);
	}

	public function testSendMailNotificationWithSameUserAndUserEmailAndReplyToDesactivate(): void {
		$provider = $this->getInstance();
		$user = $this->createMock(IUser::class);
		$this->settingsManager->expects($this->any())->method('replyToInitiator')->willReturn(false);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('OwnerUser')
			->willReturn($user);
		$user
			->expects($this->once())
			->method('getDisplayName')
			->willReturn('Mrs. Owner User');
		$message = $this->createMock(Message::class);
		$this->mailer
			->expects($this->once())
			->method('createMessage')
			->willReturn($message);
		$template = $this->createMock(IEMailTemplate::class);
		$this->mailer
			->expects($this->once())
			->method('createEMailTemplate')
			->willReturn($template);
		$template
			->expects($this->once())
			->method('addHeader');
		$template
			->expects($this->once())
			->method('addHeading')
			->with('Mrs. Owner User shared file.txt with you');
		$template
			->expects($this->once())
			->method('addBodyButton')
			->with(
				'Open file.txt',
				'https://example.com/file.txt'
			);
		$message
			->expects($this->once())
			->method('setTo')
			->with(['john@doe.com']);
		$this->defaults
			->expects($this->once())
			->method('getName')
			->willReturn('UnitTestCloud');
		$message
			->expects($this->once())
			->method('setFrom')
			->with([
				Util::getDefaultEmailAddress('UnitTestCloud') => 'UnitTestCloud'
			]);
		// Since replyToInitiator is false, we never get the initiator email address
		$user
			->expects($this->never())
			->method('getEMailAddress');
		$message
			->expects($this->never())
			->method('setReplyTo');
		$template
			->expects($this->once())
			->method('addFooter')
			->with('');
		$template
			->expects($this->once())
			->method('setSubject')
			->with('Mrs. Owner User shared file.txt with you');
		$message
			->expects($this->once())
			->method('useTemplate')
			->with($template);

		$this->mailer
			->expects($this->once())
			->method('send')
			->with($message);

		$this->urlGenerator->expects($this->once())->method('linkToRouteAbsolute')
			->with('files_sharing.sharecontroller.showShare', ['token' => 'token'])
			->willReturn('https://example.com/file.txt');

		$node = $this->createMock(File::class);
		$node->expects($this->any())->method('getName')->willReturn('file.txt');

		$share = $this->createMock(IShare::class);
		$share->expects($this->any())->method('getSharedBy')->willReturn('OwnerUser');
		$share->expects($this->any())->method('getSharedWith')->willReturn('john@doe.com');
		$share->expects($this->any())->method('getNode')->willReturn($node);
		$share->expects($this->any())->method('getId')->willReturn(42);
		$share->expects($this->any())->method('getNote')->willReturn('');
		$share->expects($this->any())->method('getToken')->willReturn('token');

		self::invokePrivate(
			$provider,
			'sendMailNotification',
			[$share]
		);
	}

	public function testSendMailNotificationWithDifferentUserAndNoUserEmailAndReplyToDesactivate(): void {
		$provider = $this->getInstance();
		$initiatorUser = $this->createMock(IUser::class);
		$this->settingsManager->expects($this->any())->method('replyToInitiator')->willReturn(false);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('InitiatorUser')
			->willReturn($initiatorUser);
		$initiatorUser
			->expects($this->once())
			->method('getDisplayName')
			->willReturn('Mr. Initiator User');
		$message = $this->createMock(Message::class);
		$this->mailer
			->expects($this->once())
			->method('createMessage')
			->willReturn($message);
		$template = $this->createMock(IEMailTemplate::class);
		$this->mailer
			->expects($this->once())
			->method('createEMailTemplate')
			->willReturn($template);
		$template
			->expects($this->once())
			->method('addHeader');
		$template
			->expects($this->once())
			->method('addHeading')
			->with('Mr. Initiator User shared file.txt with you');
		$template
			->expects($this->once())
			->method('addBodyButton')
			->with(
				'Open file.txt',
				'https://example.com/file.txt'
			);
		$message
			->expects($this->once())
			->method('setTo')
			->with(['john@doe.com']);
		$this->defaults
			->expects($this->once())
			->method('getName')
			->willReturn('UnitTestCloud');
		$message
			->expects($this->once())
			->method('setFrom')
			->with([
				Util::getDefaultEmailAddress('UnitTestCloud') => 'UnitTestCloud'
			]);
		$message
			->expects($this->never())
			->method('setReplyTo');
		$template
			->expects($this->once())
			->method('addFooter')
			->with('');
		$template
			->expects($this->once())
			->method('setSubject')
			->with('Mr. Initiator User shared file.txt with you');
		$message
			->expects($this->once())
			->method('useTemplate')
			->with($template);

		$this->mailer
			->expects($this->once())
			->method('send')
			->with($message);

		$this->urlGenerator->expects($this->once())->method('linkToRouteAbsolute')
			->with('files_sharing.sharecontroller.showShare', ['token' => 'token'])
			->willReturn('https://example.com/file.txt');

		$node = $this->createMock(File::class);
		$node->expects($this->any())->method('getName')->willReturn('file.txt');

		$share = $this->createMock(IShare::class);
		$share->expects($this->any())->method('getSharedBy')->willReturn('InitiatorUser');
		$share->expects($this->any())->method('getSharedWith')->willReturn('john@doe.com');
		$share->expects($this->any())->method('getNode')->willReturn($node);
		$share->expects($this->any())->method('getId')->willReturn(42);
		$share->expects($this->any())->method('getNote')->willReturn('');
		$share->expects($this->any())->method('getToken')->willReturn('token');

		self::invokePrivate(
			$provider,
			'sendMailNotification',
			[$share]
		);
	}
}
