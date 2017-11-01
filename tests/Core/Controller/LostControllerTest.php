<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace Tests\Core\Controller;

use OC\Core\Controller\LostController;
use OC\Mail\Message;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Defaults;
use OCP\Encryption\IManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class LostControllerTest
 *
 * @package OC\Core\Controller
 */
class LostControllerTest extends \Test\TestCase {

	/** @var LostController */
	private $lostController;
	/** @var IUser */
	private $existingUser;
	/** @var IURLGenerator | PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var IL10N */
	private $l10n;
	/** @var IUserManager | PHPUnit_Framework_MockObject_MockObject */
	private $userManager;
	/** @var Defaults */
	private $defaults;
	/** @var IConfig | PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var IMailer | PHPUnit_Framework_MockObject_MockObject */
	private $mailer;
	/** @var ISecureRandom | PHPUnit_Framework_MockObject_MockObject */
	private $secureRandom;
	/** @var IManager|PHPUnit_Framework_MockObject_MockObject */
	private $encryptionManager;
	/** @var ITimeFactory | PHPUnit_Framework_MockObject_MockObject */
	private $timeFactory;
	/** @var IRequest */
	private $request;
	/** @var ICrypto|\PHPUnit_Framework_MockObject_MockObject */
	private $crypto;

	protected function setUp() {
		parent::setUp();

		$this->existingUser = $this->createMock(IUser::class);
		$this->existingUser->expects($this->any())
			->method('getEMailAddress')
			->willReturn('test@example.com');
		$this->existingUser->expects($this->any())
			->method('getUID')
			->willReturn('ExistingUser');
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
			->will($this->returnCallback(function($text, $parameters = array()) {
				return vsprintf($text, $parameters);
			}));
		$this->defaults = $this->getMockBuilder('\OCP\Defaults')
			->disableOriginalConstructor()->getMock();
		$this->userManager = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()->getMock();
		$this->urlGenerator = $this->getMockBuilder(IURLGenerator::class)
			->disableOriginalConstructor()->getMock();
		$this->mailer = $this->getMockBuilder('\OCP\Mail\IMailer')
			->disableOriginalConstructor()->getMock();
		$this->secureRandom = $this->getMockBuilder('\OCP\Security\ISecureRandom')
			->disableOriginalConstructor()->getMock();
		$this->timeFactory = $this->getMockBuilder('\OCP\AppFramework\Utility\ITimeFactory')
			->disableOriginalConstructor()->getMock();
		$this->request = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()->getMock();
		$this->encryptionManager = $this->getMockBuilder(IManager::class)
			->disableOriginalConstructor()->getMock();
		$this->encryptionManager->expects($this->any())
			->method('isEnabled')
			->willReturn(true);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->lostController = new LostController(
			'Core',
			$this->request,
			$this->urlGenerator,
			$this->userManager,
			$this->defaults,
			$this->l10n,
			$this->config,
			$this->secureRandom,
			'lostpassword-noreply@localhost',
			$this->encryptionManager,
			$this->mailer,
			$this->timeFactory,
			$this->crypto
		);
	}

	public function testResetFormWithNotExistingUser() {
		$this->userManager->method('get')
			->with('NotExistingUser')
			->willReturn(null);

		$expectedResponse = new TemplateResponse(
			'core',
			'error',
			[
				'errors' => [
					['error' => 'Couldn\'t reset password because the token is invalid'],
				]
			],
			'guest'
		);
		$this->assertEquals($expectedResponse, $this->lostController->resetform('MySecretToken', 'NotExistingUser'));
	}

	public function testResetFormInvalidTokenMatch() {
		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->willReturn('encryptedToken');
		$this->existingUser->method('getLastLogin')
			->will($this->returnValue(12344));
		$this->userManager->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);
		$this->crypto->method('decrypt')
			->with(
				$this->equalTo('encryptedToken'),
				$this->equalTo('test@example.comSECRET')
			)->willReturn('12345:TheOnlyAndOnlyOneTokenToResetThePassword');

		$response = $this->lostController->resetform('12345:MySecretToken', 'ValidTokenUser');
		$expectedResponse = new TemplateResponse('core',
			'error',
			[
				'errors' => [
					['error' => 'Couldn\'t reset password because the token is invalid'],
				]
			],
			'guest');
		$this->assertEquals($expectedResponse, $response);
	}


	public function testResetFormExpiredToken() {
		$this->userManager->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);
		$this->config
			->expects($this->once())
			->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->will($this->returnValue('encryptedToken'));
		$this->crypto->method('decrypt')
			->with(
				$this->equalTo('encryptedToken'),
				$this->equalTo('test@example.comSECRET')
			)->willReturn('12345:TheOnlyAndOnlyOneTokenToResetThePassword');
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->willReturn(999999);

		$response = $this->lostController->resetform('TheOnlyAndOnlyOneTokenToResetThePassword', 'ValidTokenUser');
		$expectedResponse = new TemplateResponse('core',
			'error',
			[
				'errors' => [
					['error' => 'Couldn\'t reset password because the token is expired'],
				]
			],
			'guest');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testResetFormValidToken() {
		$this->existingUser->method('getLastLogin')
			->willReturn(12344);
		$this->userManager->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->willReturn(12348);

		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->willReturn('encryptedToken');

		$this->crypto->method('decrypt')
			->with(
				$this->equalTo('encryptedToken'),
				$this->equalTo('test@example.comSECRET')
			)->willReturn('12345:TheOnlyAndOnlyOneTokenToResetThePassword');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.lost.setPassword', array('userId' => 'ValidTokenUser', 'token' => 'TheOnlyAndOnlyOneTokenToResetThePassword'))
			->will($this->returnValue('https://example.tld/index.php/lostpassword/'));

		$response = $this->lostController->resetform('TheOnlyAndOnlyOneTokenToResetThePassword', 'ValidTokenUser');
		$expectedResponse = new TemplateResponse('core',
			'lostpassword/resetpassword',
			array(
				'link' => 'https://example.tld/index.php/lostpassword/',
			),
			'guest');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testEmailUnsuccessful() {
		$existingUser = 'ExistingUser';
		$nonExistingUser = 'NonExistingUser';
		$this->userManager
			->expects($this->any())
			->method('userExists')
			->will($this->returnValueMap(array(
				array(true, $existingUser),
				array(false, $nonExistingUser)
			)));

		$this->userManager
			->method('getByEmail')
			->willReturn([]);

		// With a non existing user
		$response = $this->lostController->email($nonExistingUser);
		$expectedResponse = new JSONResponse([
			'status' => 'error',
			'msg' => 'Couldn\'t send reset email. Please make sure your username is correct.'
		]);
		$expectedResponse->throttle();
		$this->assertEquals($expectedResponse, $response);

		// With no mail address
		$this->config
			->expects($this->any())
			->method('getUserValue')
			->with($existingUser, 'settings', 'email')
			->will($this->returnValue(null));
		$response = $this->lostController->email($existingUser);
		$expectedResponse = new JSONResponse([
			'status' => 'error',
			'msg' => 'Couldn\'t send reset email. Please make sure your username is correct.'
		]);
		$expectedResponse->throttle();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testEmailSuccessful() {
		$this->secureRandom
			->expects($this->once())
			->method('generate')
			->with('21')
			->will($this->returnValue('ThisIsMaybeANotSoSecretToken!'));
		$this->userManager
				->expects($this->any())
				->method('get')
				->with('ExistingUser')
				->willReturn($this->existingUser);
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->will($this->returnValue(12348));
		$this->config
			->expects($this->once())
			->method('setUserValue')
			->with('ExistingUser', 'core', 'lostpassword', 'encryptedToken');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.lost.resetform', array('userId' => 'ExistingUser', 'token' => 'ThisIsMaybeANotSoSecretToken!'))
			->will($this->returnValue('https://example.tld/index.php/lostpassword/'));
		$message = $this->getMockBuilder('\OC\Mail\Message')
			->disableOriginalConstructor()->getMock();
		$message
			->expects($this->at(0))
			->method('setTo')
			->with(['test@example.com' => 'ExistingUser']);
		$message
			->expects($this->at(1))
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
			->expects($this->at(2))
			->method('useTemplate')
			->with($emailTemplate);

		$this->mailer
			->expects($this->at(0))
			->method('createEMailTemplate')
			->willReturn($emailTemplate);
		$this->mailer
			->expects($this->at(1))
			->method('createMessage')
			->will($this->returnValue($message));
		$this->mailer
			->expects($this->at(2))
			->method('send')
			->with($message);

		$this->crypto->method('encrypt')
			->with(
				$this->equalTo('12348:ThisIsMaybeANotSoSecretToken!'),
				$this->equalTo('test@example.comSECRET')
			)->willReturn('encryptedToken');

		$response = $this->lostController->email('ExistingUser');
		$expectedResponse = new JSONResponse(['status' => 'success']);
		$expectedResponse->throttle();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testEmailWithMailSuccessful() {
		$this->secureRandom
			->expects($this->once())
			->method('generate')
			->with('21')
			->will($this->returnValue('ThisIsMaybeANotSoSecretToken!'));
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
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->will($this->returnValue(12348));
		$this->config
			->expects($this->once())
			->method('setUserValue')
			->with('ExistingUser', 'core', 'lostpassword', 'encryptedToken');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.lost.resetform', array('userId' => 'ExistingUser', 'token' => 'ThisIsMaybeANotSoSecretToken!'))
			->will($this->returnValue('https://example.tld/index.php/lostpassword/'));
		$message = $this->getMockBuilder('\OC\Mail\Message')
			->disableOriginalConstructor()->getMock();
		$message
			->expects($this->at(0))
			->method('setTo')
			->with(['test@example.com' => 'ExistingUser']);
		$message
			->expects($this->at(1))
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
			->expects($this->at(2))
			->method('useTemplate')
			->with($emailTemplate);

		$this->mailer
			->expects($this->at(0))
			->method('createEMailTemplate')
			->willReturn($emailTemplate);
		$this->mailer
			->expects($this->at(1))
			->method('createMessage')
			->will($this->returnValue($message));
		$this->mailer
			->expects($this->at(2))
			->method('send')
			->with($message);

		$this->crypto->method('encrypt')
			->with(
				$this->equalTo('12348:ThisIsMaybeANotSoSecretToken!'),
				$this->equalTo('test@example.comSECRET')
			)->willReturn('encryptedToken');

		$response = $this->lostController->email('test@example.com');
		$expectedResponse = new JSONResponse(['status' => 'success']);
		$expectedResponse->throttle();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testEmailCantSendException() {
		$this->secureRandom
			->expects($this->once())
			->method('generate')
			->with('21')
			->will($this->returnValue('ThisIsMaybeANotSoSecretToken!'));
		$this->userManager
				->expects($this->any())
				->method('get')
				->with('ExistingUser')
				->willReturn($this->existingUser);
		$this->config
			->expects($this->once())
			->method('setUserValue')
			->with('ExistingUser', 'core', 'lostpassword', 'encryptedToken');
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->will($this->returnValue(12348));
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('core.lost.resetform', array('userId' => 'ExistingUser', 'token' => 'ThisIsMaybeANotSoSecretToken!'))
			->will($this->returnValue('https://example.tld/index.php/lostpassword/'));
		$message = $this->createMock(Message::class);
		$message
			->expects($this->at(0))
			->method('setTo')
			->with(['test@example.com' => 'ExistingUser']);
		$message
			->expects($this->at(1))
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
			->expects($this->at(2))
			->method('useTemplate')
			->with($emailTemplate);

		$this->mailer
			->expects($this->at(0))
			->method('createEMailTemplate')
			->willReturn($emailTemplate);
		$this->mailer
			->expects($this->at(1))
			->method('createMessage')
			->will($this->returnValue($message));
		$this->mailer
			->expects($this->at(2))
			->method('send')
			->with($message)
			->will($this->throwException(new \Exception()));

		$this->crypto->method('encrypt')
			->with(
				$this->equalTo('12348:ThisIsMaybeANotSoSecretToken!'),
				$this->equalTo('test@example.comSECRET')
			)->willReturn('encryptedToken');

		$response = $this->lostController->email('ExistingUser');
		$expectedResponse = new JSONResponse(['status' => 'error', 'msg' => 'Couldn\'t send reset email. Please contact your administrator.']);
		$expectedResponse->throttle();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testSetPasswordUnsuccessful() {
		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->willReturn('encryptedData');
		$this->existingUser->method('getLastLogin')
			->will($this->returnValue(12344));
		$this->existingUser->expects($this->once())
			->method('setPassword')
			->with('NewPassword')
			->willReturn(false);
		$this->userManager->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);
		$this->config->expects($this->never())
			->method('deleteUserValue');
		$this->timeFactory->method('getTime')
			->will($this->returnValue(12348));

		$this->crypto->method('decrypt')
			->with(
				$this->equalTo('encryptedData'),
				$this->equalTo('test@example.comSECRET')
			)->willReturn('12345:TheOnlyAndOnlyOneTokenToResetThePassword');

		$response = $this->lostController->setPassword('TheOnlyAndOnlyOneTokenToResetThePassword', 'ValidTokenUser', 'NewPassword', true);
		$expectedResponse = array('status' => 'error', 'msg' => '');
		$this->assertSame($expectedResponse, $response);
	}

	public function testSetPasswordSuccessful() {
		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->willReturn('encryptedData');
		$this->existingUser->method('getLastLogin')
			->will($this->returnValue(12344));
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
		$this->timeFactory->method('getTime')
			->will($this->returnValue(12348));

		$this->crypto->method('decrypt')
			->with(
				$this->equalTo('encryptedData'),
				$this->equalTo('test@example.comSECRET')
			)->willReturn('12345:TheOnlyAndOnlyOneTokenToResetThePassword');

		$response = $this->lostController->setPassword('TheOnlyAndOnlyOneTokenToResetThePassword', 'ValidTokenUser', 'NewPassword', true);
		$expectedResponse = array('status' => 'success');
		$this->assertSame($expectedResponse, $response);
	}

	public function testSetPasswordExpiredToken() {
		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->willReturn('encryptedData');
		$this->userManager->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);
		$this->timeFactory->method('getTime')
			->willReturn(55546);

		$this->crypto->method('decrypt')
			->with(
				$this->equalTo('encryptedData'),
				$this->equalTo('test@example.comSECRET')
			)->willReturn('12345:TheOnlyAndOnlyOneTokenToResetThePassword');

		$response = $this->lostController->setPassword('TheOnlyAndOnlyOneTokenToResetThePassword', 'ValidTokenUser', 'NewPassword', true);
		$expectedResponse = [
			'status' => 'error',
			'msg' => 'Couldn\'t reset password because the token is expired',
		];
		$this->assertSame($expectedResponse, $response);
	}

	public function testSetPasswordInvalidDataInDb() {
		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->willReturn('invalidEncryptedData');
		$this->userManager
			->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);

		$this->crypto->method('decrypt')
			->with(
				$this->equalTo('invalidEncryptedData'),
				$this->equalTo('test@example.comSECRET')
			)->willReturn('TheOnlyAndOnlyOneTokenToResetThePassword');

		$response = $this->lostController->setPassword('TheOnlyAndOnlyOneTokenToResetThePassword', 'ValidTokenUser', 'NewPassword', true);
		$expectedResponse = [
			'status' => 'error',
			'msg' => 'Couldn\'t reset password because the token is invalid',
		];
		$this->assertSame($expectedResponse, $response);
	}

	public function testSetPasswordExpiredTokenDueToLogin() {
		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->willReturn('encryptedData');
		$this->existingUser->method('getLastLogin')
			->will($this->returnValue(12346));
		$this->userManager
			->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);
		$this->timeFactory
			->method('getTime')
			->will($this->returnValue(12345));

		$this->crypto->method('decrypt')
			->with(
				$this->equalTo('encryptedData'),
				$this->equalTo('test@example.comSECRET')
			)->willReturn('12345:TheOnlyAndOnlyOneTokenToResetThePassword');

		$response = $this->lostController->setPassword('TheOnlyAndOnlyOneTokenToResetThePassword', 'ValidTokenUser', 'NewPassword', true);
		$expectedResponse = [
			'status' => 'error',
			'msg' => 'Couldn\'t reset password because the token is expired',
		];
		$this->assertSame($expectedResponse, $response);
	}

	public function testIsSetPasswordWithoutTokenFailing() {
		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->will($this->returnValue(null));
		$this->userManager->method('get')
			->with('ValidTokenUser')
			->willReturn($this->existingUser);

		$this->crypto->method('decrypt')
			->with(
				$this->equalTo(''),
				$this->equalTo('test@example.comSECRET')
			)->willThrowException(new \Exception());

		$response = $this->lostController->setPassword('', 'ValidTokenUser', 'NewPassword', true);
		$expectedResponse = [
			'status' => 'error',
			'msg' => 'Couldn\'t reset password because the token is invalid'
			];
		$this->assertSame($expectedResponse, $response);
	}

	public function testSetPasswordForDisabledUser() {
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('isEnabled')
			->willReturn(false);
		$user->expects($this->never())
			->method('setPassword');

		$this->config->method('getUserValue')
			->with('ValidTokenUser', 'core', 'lostpassword', null)
			->willReturn('encryptedData');
		$this->userManager->method('get')
			->with('DisabledUser')
			->willReturn($this->existingUser);

		$response = $this->lostController->setPassword('TheOnlyAndOnlyOneTokenToResetThePassword', 'DisabledUser', 'NewPassword', true);
		$expectedResponse = [
			'status' => 'error',
			'msg' => 'Couldn\'t reset password because the token is invalid'
			];
		$this->assertSame($expectedResponse, $response);
	}

	public function testSendEmailNoEmail() {
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

		$response = $this->lostController->email('ExistingUser');
		$expectedResponse = new JSONResponse(['status' => 'error', 'msg' => 'Could not send reset email because there is no email address for this username. Please contact your administrator.']);
		$expectedResponse->throttle();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testSetPasswordEncryptionDontProceed() {
		$response = $this->lostController->setPassword('myToken', 'user', 'newpass', false);
		$expectedResponse = ['status' => 'error', 'msg' => '', 'encryption' => true];
		$this->assertSame($expectedResponse, $response);
	}

}
