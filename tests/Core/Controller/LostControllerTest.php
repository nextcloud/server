<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Controller;

use OC\Authentication\TwoFactorAuth\Manager;
use OC\Core\Controller\LostController;
use OC\Core\Events\BeforePasswordResetEvent;
use OC\Core\Events\PasswordResetEvent;
use OC\Mail\Message;
use OC\Security\RateLimiting\Limiter;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Defaults;
use OCP\Encryption\IEncryptionModule;
use OCP\Encryption\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Security\VerificationToken\InvalidTokenException;
use OCP\Security\VerificationToken\IVerificationToken;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class LostControllerTest
 *
 * @package OC\Core\Controller
 */
class LostControllerTest extends TestCase {
	private LostController $lostController;
	/** @var IUser */
	private $existingUser;
	/** @var IURLGenerator | MockObject */
	private $urlGenerator;
	/** @var IL10N */
	private $l10n;
	/** @var IUserManager | MockObject */
	private $userManager;
	/** @var Defaults */
	private $defaults;
	/** @var IConfig | MockObject */
	private $config;
	/** @var IMailer | MockObject */
	private $mailer;
	/** @var IManager|MockObject */
	private $encryptionManager;
	/** @var IRequest|MockObject */
	private $request;
	/** @var LoggerInterface|MockObject */
	private $logger;
	/** @var Manager|MockObject */
	private $twofactorManager;
	/** @var IInitialState|MockObject */
	private $initialState;
	/** @var IVerificationToken|MockObject */
	private $verificationToken;
	/** @var IEventDispatcher|MockObject */
	private $eventDispatcher;
	/** @var Limiter|MockObject */
	private $limiter;

	protected function setUp(): void {
		parent::setUp();

		$this->existingUser = $this->createMock(IUser::class);
		$this->existingUser->expects($this->any())
			->method('getEMailAddress')
			->willReturn('test@example.com');
		$this->existingUser->expects($this->any())
			->method('getUID')
			->willReturn('ExistingUser');
		$this->existingUser->expects($this->any())
			->method('getDisplayName')
			->willReturn('Existing User');
		$this->existingUser->expects($this->any())
			->method('isEnabled')
			->willReturn(true);

		$this->config = $this->createMock(IConfig::class);
		$this->config->expects($this->any())
			->method('getSystemValue')
			->willReturnMap([
				['secret', null, 'SECRET'],
				['secret', '', 'SECRET'],
				['lost_password_link', '', ''],
			]);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});
		$this->defaults = $this->createMock(Defaults::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->mailer = $this->createMock(IMailer::class);
		$this->request = $this->createMock(IRequest::class);
		$this->encryptionManager = $this->createMock(IManager::class);
		$this->encryptionManager->expects($this->any())
			->method('isEnabled')
			->willReturn(true);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->twofactorManager = $this->createMock(Manager::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->verificationToken = $this->createMock(IVerificationToken::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->limiter = $this->createMock(Limiter::class);
		$this->lostController = new LostController(
			'Core',
			$this->request,
			$this->urlGenerator,
			$this->userManager,
			$this->defaults,
			$this->l10n,
			$this->config,
			'lostpassword-noreply@localhost',
			$this->encryptionManager,
			$this->mailer,
			$this->logger,
			$this->twofactorManager,
			$this->initialState,
			$this->verificationToken,
			$this->eventDispatcher,
			$this->limiter
		);
	}

	public function testResetFormTokenError(): void {
		$this->userManager->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);
		$this->verificationToken->expects($this->once())
			->method('check')
			->with('12345:MySecretToken', $this->existingUser, 'lostpassword', 'test@example.com')
			->willThrowException(new InvalidTokenException(InvalidTokenException::TOKEN_DECRYPTION_ERROR));

		$response = $this->lostController->resetform('12345:MySecretToken', 'ValidTokenUser');
		$expectedResponse = new TemplateResponse('core',
			'error',
			[
				'errors' => [
					['error' => 'Could not reset password because the token is invalid'],
				]
			],
			'guest');
		$expectedResponse->throttle();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testResetFormValidToken(): void {
		$this->userManager->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);
		$this->verificationToken->expects($this->once())
			->method('check')
			->with('MySecretToken', $this->existingUser, 'lostpassword', 'test@example.com');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.lost.setPassword', ['userId' => 'ValidTokenUser', 'token' => 'MySecretToken'])
			->willReturn('https://example.tld/index.php/lostpassword/set/sometoken/someuser');

		$calls = [
			['resetPasswordUser', 'ValidTokenUser'],
			['resetPasswordTarget', 'https://example.tld/index.php/lostpassword/set/sometoken/someuser'],
		];
		$this->initialState
			->expects($this->exactly(2))
			->method('provideInitialState')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		$response = $this->lostController->resetform('MySecretToken', 'ValidTokenUser');
		$expectedResponse = new TemplateResponse('core',
			'login',
			[],
			'guest');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testEmailUnsuccessful(): void {
		$existingUser = 'ExistingUser';
		$nonExistingUser = 'NonExistingUser';
		$this->userManager
			->expects($this->any())
			->method('userExists')
			->willReturnMap([
				[true, $existingUser],
				[false, $nonExistingUser]
			]);

		$this->logger->expects($this->exactly(0))
			->method('error');
		$this->logger->expects($this->exactly(2))
			->method('warning');

		$this->userManager
			->method('getByEmail')
			->willReturn([]);

		// With a non existing user
		$response = $this->lostController->email($nonExistingUser);
		$expectedResponse = new JSONResponse([
			'status' => 'success',
		]);
		$expectedResponse->throttle();
		$this->assertEquals($expectedResponse, $response);

		// With no mail address
		$this->config
			->expects($this->any())
			->method('getUserValue')
			->with($existingUser, 'settings', 'email')
			->willReturn(null);
		$response = $this->lostController->email($existingUser);
		$expectedResponse = new JSONResponse([
			'status' => 'success',
		]);
		$expectedResponse->throttle();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testEmailSuccessful(): void {
		$this->userManager
			->expects($this->any())
			->method('get')
			->with('ExistingUser')
			->willReturn($this->existingUser);
		$this->verificationToken->expects($this->once())
			->method('create')
			->willReturn('ThisIsMaybeANotSoSecretToken!');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.lost.resetform', ['userId' => 'ExistingUser', 'token' => 'ThisIsMaybeANotSoSecretToken!'])
			->willReturn('https://example.tld/index.php/lostpassword/');
		$message = $this->getMockBuilder('\OC\Mail\Message')
			->disableOriginalConstructor()->getMock();
		$message
			->expects($this->once())
			->method('setTo')
			->with(['test@example.com' => 'Existing User']);
		$message
			->expects($this->once())
			->method('setFrom')
			->with(['lostpassword-noreply@localhost' => null]);

		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$emailTemplate->expects($this->any())
			->method('renderHtml')
			->willReturn('HTML body');
		$emailTemplate->expects($this->any())
			->method('renderText')
			->willReturn('text body');

		$message
			->expects($this->once())
			->method('useTemplate')
			->with($emailTemplate);

		$this->mailer
			->expects($this->once())
			->method('createEMailTemplate')
			->willReturn($emailTemplate);
		$this->mailer
			->expects($this->once())
			->method('createMessage')
			->willReturn($message);
		$this->mailer
			->expects($this->once())
			->method('send')
			->with($message);

		$response = $this->lostController->email('ExistingUser');
		$expectedResponse = new JSONResponse(['status' => 'success']);
		$expectedResponse->throttle();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testEmailWithMailSuccessful(): void {
		$this->userManager
			->expects($this->any())
			->method('get')
			->with('test@example.com')
			->willReturn(null);
		$this->userManager
			->expects($this->any())
			->method('getByEmail')
			->with('test@example.com')
			->willReturn([$this->existingUser]);
		$this->verificationToken->expects($this->once())
			->method('create')
			->willReturn('ThisIsMaybeANotSoSecretToken!');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.lost.resetform', ['userId' => 'ExistingUser', 'token' => 'ThisIsMaybeANotSoSecretToken!'])
			->willReturn('https://example.tld/index.php/lostpassword/');
		$message = $this->getMockBuilder('\OC\Mail\Message')
			->disableOriginalConstructor()->getMock();
		$message
			->expects($this->once())
			->method('setTo')
			->with(['test@example.com' => 'Existing User']);
		$message
			->expects($this->once())
			->method('setFrom')
			->with(['lostpassword-noreply@localhost' => null]);

		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$emailTemplate->expects($this->any())
			->method('renderHtml')
			->willReturn('HTML body');
		$emailTemplate->expects($this->any())
			->method('renderText')
			->willReturn('text body');

		$message
			->expects($this->once())
			->method('useTemplate')
			->with($emailTemplate);

		$this->mailer
			->expects($this->once())
			->method('createEMailTemplate')
			->willReturn($emailTemplate);
		$this->mailer
			->expects($this->once())
			->method('createMessage')
			->willReturn($message);
		$this->mailer
			->expects($this->once())
			->method('send')
			->with($message);

		$response = $this->lostController->email('test@example.com');
		$expectedResponse = new JSONResponse(['status' => 'success']);
		$expectedResponse->throttle();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testEmailCantSendException(): void {
		$this->userManager
			->expects($this->any())
			->method('get')
			->with('ExistingUser')
			->willReturn($this->existingUser);
		$this->verificationToken->expects($this->once())
			->method('create')
			->willReturn('ThisIsMaybeANotSoSecretToken!');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.lost.resetform', ['userId' => 'ExistingUser', 'token' => 'ThisIsMaybeANotSoSecretToken!'])
			->willReturn('https://example.tld/index.php/lostpassword/');
		$message = $this->createMock(Message::class);
		$message
			->expects($this->once())
			->method('setTo')
			->with(['test@example.com' => 'Existing User']);
		$message
			->expects($this->once())
			->method('setFrom')
			->with(['lostpassword-noreply@localhost' => null]);

		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$emailTemplate->expects($this->any())
			->method('renderHtml')
			->willReturn('HTML body');
		$emailTemplate->expects($this->any())
			->method('renderText')
			->willReturn('text body');

		$message
			->expects($this->once())
			->method('useTemplate')
			->with($emailTemplate);

		$this->mailer
			->expects($this->once())
			->method('createEMailTemplate')
			->willReturn($emailTemplate);
		$this->mailer
			->expects($this->once())
			->method('createMessage')
			->willReturn($message);
		$this->mailer
			->expects($this->once())
			->method('send')
			->with($message)
			->willThrowException(new \Exception());

		$this->logger->expects($this->exactly(1))
			->method('error');

		$response = $this->lostController->email('ExistingUser');
		$expectedResponse = new JSONResponse(['status' => 'success']);
		$expectedResponse->throttle();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testSetPasswordUnsuccessful(): void {
		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->willReturn('encryptedData');
		$this->existingUser->method('getLastLogin')
			->willReturn(12344);
		$this->existingUser->expects($this->once())
			->method('setPassword')
			->with('NewPassword')
			->willReturn(false);
		$this->userManager->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);
		$beforePasswordResetEvent = new BeforePasswordResetEvent($this->existingUser, 'NewPassword');
		$this->eventDispatcher
			->expects($this->once())
			->method('dispatchTyped')
			->with($beforePasswordResetEvent);
		$this->config->expects($this->never())
			->method('deleteUserValue');

		$response = $this->lostController->setPassword('TheOnlyAndOnlyOneTokenToResetThePassword', 'ValidTokenUser', 'NewPassword', true);
		$expectedResponse = ['status' => 'error', 'msg' => ''];
		$this->assertSame($expectedResponse, $response->getData());
	}

	public function testSetPasswordSuccessful(): void {
		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->willReturn('encryptedData');
		$this->existingUser->method('getLastLogin')
			->willReturn(12344);
		$this->existingUser->expects($this->once())
			->method('setPassword')
			->with('NewPassword')
			->willReturn(true);
		$this->userManager->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);

		$calls = [
			[new BeforePasswordResetEvent($this->existingUser, 'NewPassword')],
			[new PasswordResetEvent($this->existingUser, 'NewPassword')],
		];
		$this->eventDispatcher
			->expects($this->exactly(2))
			->method('dispatchTyped')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		$this->config->expects($this->once())
			->method('deleteUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword');

		$response = $this->lostController->setPassword('TheOnlyAndOnlyOneTokenToResetThePassword', 'ValidTokenUser', 'NewPassword', true);
		$expectedResponse = ['user' => 'ValidTokenUser', 'status' => 'success'];
		$this->assertSame($expectedResponse, $response->getData());
	}

	public function testSetPasswordExpiredToken(): void {
		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->willReturn('encryptedData');
		$this->userManager->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);
		$this->verificationToken->expects($this->atLeastOnce())
			->method('check')
			->willThrowException(new InvalidTokenException(InvalidTokenException::TOKEN_EXPIRED));

		$response = $this->lostController->setPassword('TheOnlyAndOnlyOneTokenToResetThePassword', 'ValidTokenUser', 'NewPassword', true);
		$expectedResponse = [
			'status' => 'error',
			'msg' => 'Could not reset password because the token is expired',
		];
		$this->assertSame($expectedResponse, $response->getData());
	}

	public function testSetPasswordInvalidDataInDb(): void {
		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->willReturn('invalidEncryptedData');
		$this->userManager
			->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);
		$this->verificationToken->expects($this->atLeastOnce())
			->method('check')
			->willThrowException(new InvalidTokenException(InvalidTokenException::TOKEN_INVALID_FORMAT));

		$response = $this->lostController->setPassword('TheOnlyAndOnlyOneTokenToResetThePassword', 'ValidTokenUser', 'NewPassword', true);
		$expectedResponse = [
			'status' => 'error',
			'msg' => 'Could not reset password because the token is invalid',
		];
		$this->assertSame($expectedResponse, $response->getData());
	}

	public function testIsSetPasswordWithoutTokenFailing(): void {
		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->willReturn('aValidtoken');
		$this->userManager->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);
		$this->verificationToken->expects($this->atLeastOnce())
			->method('check')
			->willThrowException(new InvalidTokenException(InvalidTokenException::TOKEN_MISMATCH));

		$response = $this->lostController->setPassword('', 'ValidTokenUser', 'NewPassword', true);
		$expectedResponse = [
			'status' => 'error',
			'msg' => 'Could not reset password because the token is invalid'
		];
		$this->assertSame($expectedResponse, $response->getData());
	}

	public function testSetPasswordForDisabledUser(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('isEnabled')
			->willReturn(false);
		$user->expects($this->never())
			->method('setPassword');
		$user->expects($this->any())
			->method('getEMailAddress')
			->willReturn('random@example.org');

		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->willReturn('encryptedData');
		$this->userManager->method('get')
			->with('DisabledUser')
			->willReturn($user);

		$this->verificationToken->expects($this->atLeastOnce())
			->method('check')
			->willThrowException(new InvalidTokenException(InvalidTokenException::USER_UNKNOWN));

		$response = $this->lostController->setPassword('TheOnlyAndOnlyOneTokenToResetThePassword', 'DisabledUser', 'NewPassword', true);
		$expectedResponse = [
			'status' => 'error',
			'msg' => 'Could not reset password because the token is invalid'
		];
		$this->assertSame($expectedResponse, $response->getData());
	}

	public function testSendEmailNoEmail(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('isEnabled')
			->willReturn(true);
		$this->userManager->method('userExists')
			->with('ExistingUser')
			->willReturn(true);
		$this->userManager->method('get')
			->with('ExistingUser')
			->willReturn($user);

		$this->logger->expects($this->exactly(0))
			->method('error');
		$this->logger->expects($this->once())
			->method('warning');

		$response = $this->lostController->email('ExistingUser');
		$expectedResponse = new JSONResponse(['status' => 'success']);
		$expectedResponse->throttle();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testSetPasswordEncryptionDontProceedPerUserKey(): void {
		/** @var IEncryptionModule|MockObject $encryptionModule */
		$encryptionModule = $this->createMock(IEncryptionModule::class);
		$encryptionModule->expects($this->once())->method('needDetailedAccessList')->willReturn(true);
		$this->encryptionManager->expects($this->once())->method('getEncryptionModules')
			->willReturn([0 => ['callback' => function () use ($encryptionModule) {
				return $encryptionModule;
			}]]);
		$response = $this->lostController->setPassword('myToken', 'user', 'newpass', false);
		$expectedResponse = ['status' => 'error', 'msg' => '', 'encryption' => true];
		$this->assertSame($expectedResponse, $response->getData());
	}

	public function testSetPasswordDontProceedMasterKey(): void {
		$encryptionModule = $this->createMock(IEncryptionModule::class);
		$encryptionModule->expects($this->once())->method('needDetailedAccessList')->willReturn(false);
		$this->encryptionManager->expects($this->once())->method('getEncryptionModules')
			->willReturn([0 => ['callback' => function () use ($encryptionModule) {
				return $encryptionModule;
			}]]);
		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->willReturn('encryptedData');
		$this->existingUser->method('getLastLogin')
			->willReturn(12344);
		$this->existingUser->expects($this->once())
			->method('setPassword')
			->with('NewPassword')
			->willReturn(true);
		$this->userManager->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);
		$this->config->expects($this->once())
			->method('deleteUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword');

		$response = $this->lostController->setPassword('TheOnlyAndOnlyOneTokenToResetThePassword', 'ValidTokenUser', 'NewPassword', false);
		$expectedResponse = ['user' => 'ValidTokenUser', 'status' => 'success'];
		$this->assertSame($expectedResponse, $response->getData());
	}

	public function testTwoUsersWithSameEmail(): void {
		$user1 = $this->createMock(IUser::class);
		$user1->expects($this->any())
			->method('getEMailAddress')
			->willReturn('test@example.com');
		$user1->expects($this->any())
			->method('getUID')
			->willReturn('User1');
		$user1->expects($this->any())
			->method('isEnabled')
			->willReturn(true);

		$user2 = $this->createMock(IUser::class);
		$user2->expects($this->any())
			->method('getEMailAddress')
			->willReturn('test@example.com');
		$user2->expects($this->any())
			->method('getUID')
			->willReturn('User2');
		$user2->expects($this->any())
			->method('isEnabled')
			->willReturn(true);

		$this->userManager
			->method('get')
			->willReturn(null);

		$this->userManager
			->method('getByEmail')
			->willReturn([$user1, $user2]);

		$this->logger->expects($this->exactly(0))
			->method('error');
		$this->logger->expects($this->once())
			->method('warning');

		// request password reset for test@example.com
		$response = $this->lostController->email('test@example.com');

		$expectedResponse = new JSONResponse([
			'status' => 'success'
		]);
		$expectedResponse->throttle();

		$this->assertEquals($expectedResponse, $response);
	}


	/**
	 * @return array
	 */
	public static function dataTwoUsersWithSameEmailOneDisabled(): array {
		return [
			['userEnabled1' => true, 'userEnabled2' => false],
			['userEnabled1' => false, 'userEnabled2' => true]
		];
	}

	/**
	 * @dataProvider dataTwoUsersWithSameEmailOneDisabled
	 * @param bool $userEnabled1
	 * @param bool $userEnabled2
	 */
	public function testTwoUsersWithSameEmailOneDisabled(bool $userEnabled1, bool $userEnabled2): void {
		$user1 = $this->createMock(IUser::class);
		$user1->method('getEMailAddress')
			->willReturn('test@example.com');
		$user1->method('getUID')
			->willReturn('User1');
		$user1->method('isEnabled')
			->willReturn($userEnabled1);

		$user2 = $this->createMock(IUser::class);
		$user2->method('getEMailAddress')
			->willReturn('test@example.com');
		$user2->method('getUID')
			->willReturn('User2');
		$user2->method('isEnabled')
			->willReturn($userEnabled2);

		$this->userManager
			->method('get')
			->willReturn(null);

		$this->userManager
			->method('getByEmail')
			->willReturn([$user1, $user2]);

		$result = self::invokePrivate($this->lostController, 'findUserByIdOrMail', ['test@example.com']);
		$this->assertInstanceOf(IUser::class, $result);
	}

	public function testTrimEmailInput(): void {
		$this->userManager
			->expects($this->once())
			->method('getByEmail')
			->with('test@example.com')
			->willReturn([$this->existingUser]);

		$this->mailer
			->expects($this->once())
			->method('send');

		$response = $this->lostController->email('  test@example.com  ');
		$expectedResponse = new JSONResponse(['status' => 'success']);
		$expectedResponse->throttle();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testUsernameInput(): void {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn($this->existingUser);

		$this->mailer
			->expects($this->once())
			->method('send');

		$response = $this->lostController->email('  ExistingUser  ');
		$expectedResponse = new JSONResponse(['status' => 'success']);
		$expectedResponse->throttle();
		$this->assertEquals($expectedResponse, $response);
	}
}
